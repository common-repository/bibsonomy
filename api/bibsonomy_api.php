<?php

#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#

# $Id: bibsonomy_api.php,v 1.7 2008-06-24 14:23:44 cschenk Exp $

require_once('bibsonomy_def.php');
require_once('bibsonomy_cache.php');
require_once('bibsonomy_model.php');
require_once('bibsonomy_helper.php');
require_once('bibsonomy_xml_helper.php');

/*
 * Returns instances of the BibSonomy class, either with or without the caching
 * mechanism enabled.
 */
class BibSonomyFactory {

	public static function produce($username, $password) {
		$bibsonomy = new BibSonomy($username, $password);

		$cache = new BibSonomyCache($bibsonomy);
		if ($cache->isSetUp()) return $cache;

		return $bibsonomy;
	}
}

/*
 * This class communicates with the webservice.
 *
 * Hint: Use the BibSonomyFactory class to produce instances of this class
 * instead of instantiating it yourself.
 */
class BibSonomy {

	private $username, $password;
	private $status;

	public function __construct($username, $password) {
		$this->username = $username;
		$this->password = $password;
		$this->status = new BibSonomyStatus();
	}

	public function createPost($url, $title, $description, array $tags) {
		$post = $this->createXMLPost($url, $title, $description, $tags);
		$this->doCurl(BIBSONOMY_APIURL.'/users/'.$this->username.'/posts/', HTTP_METHOD_POST, $post->getXML());
	}

	public function changePost($url, $title, $description, array $tags, $intraHash) {
		$post = $this->createXMLPost($url, $title, $description, $tags, $intraHash, true);
		$post->setPostingdate();
		$this->doCurl(BIBSONOMY_APIURL.'/users/'.$this->username.'/posts/'.$intraHash, HTTP_METHOD_PUT, $post->getXML());
	}

	# this function should be called by createPost and changePost
	private function checkPost($url, $title, $description, array $tags) {
		# TODO: simply skip invalid urls
		if (empty($url) || substr(strtolower($url), 0, 3) != 'htt') throw new Exception('Invalid URL');
	}

	public function deletePost($intraHash) {
		$this->doCurl(BIBSONOMY_APIURL.'/users/cschenk/posts/'.$intraHash, HTTP_METHOD_DELETE);
	}

	# Hint: The order of the XML elements matters and so does the order of the
	#       method calls in this method.
	private function createXMLPost($url, $title, $description, array $tags, $intraHash = '', $update = false) {
		$post = new XmlPost($this->username, $update);
		$post->setDescription($description);
		$post->setTags($tags);
		$post->setGroup(GROUP_PUBLIC);
		$bookmark = new Bookmark();
		$bookmark->setURL($url);
		$bookmark->setTitle($title);
		$bookmark->setIntraHash($intraHash);
		$bookmark->setInterHash($intraHash);
		$post->setBookmark($bookmark);

		if (DEBUG) echo $post->getXML()."\n";
		return $post;
	}

	private function doCurl($url, $method, $request = NULL) {
		# init curl
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_USERPWD, $this->username.':'.$this->password);

		$fh = fopen('php://memory', 'rw');

		if ($method == HTTP_METHOD_GET) {
			# nothing to do here
		} else if ($method == HTTP_METHOD_POST) {
			$headers[] = 'Content-Type: text/xml';
			$headers[] = 'Content-Length: ' . strlen($request);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		} else if ($method == HTTP_METHOD_PUT) {
			fwrite($fh, $request);
			rewind($fh);
			curl_setopt($curl, CURLOPT_PUT, 1);
			curl_setopt($curl, CURLOPT_INFILE, $fh);
			curl_setopt($curl, CURLOPT_INFILESIZE, strlen($request));
			#curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect: '));
		} else if ($method == HTTP_METHOD_DELETE) {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		} else {
			die('Invalid HTTP method');
		}

		# execute curl
		$result = curl_exec($curl);
		curl_close($curl);
		fclose($fh);

		if (DEBUG) echo 'Result: '.$result;
		$this->setCurrentStatus($result);
		if (HTTP_METHOD_GET) return $result;
	}

	/*
	 * Sets the current status object for the most recent action.
	 */
	private function setCurrentStatus($result) {
		$xml = new SimpleXMLElement($result);

		$stat = getAttribute($xml, 'stat');
		switch ($stat) {
			case 'ok':
				$this->setStatus(STATUS_OK);
				break;
			case 'fail':
				if (DEBUG) echo "Request failed.\nReason: ".$xml->error;
				if (preg_match('/already exists/', $xml->error)) {
					# get hash
					preg_match('/intrahash:[^)]*/', $xml->error, $match);
					$splitMatch = preg_split('/ /', $match[0]);
					$hash = $splitMatch[1];
					$this->setStatus(STATUS_POST_ALREADY_EXISTS, $hash);
				} else {
					$this->setStatus(STATUS_UNKNOWN);
				}
				break;
		}
	}

	/*	
	 * Returns a status object for the last action
	 */
	public function getStatus() {
		return $this->status;
	}

	private function setStatus($code, $description = '') {
		$this->status = new BibSonomyStatus($code, $description);
	}

	/**
	 * Returns a list of public tags for the authorized user.
	 */
	public function getPublicTags($minCount = 10) {
		return $this->getTags('viewable', 'public', $minCount);
	}

	/**
	 * Returns a list of tags for the given user.
	 */
	public function getUserTags($username = NULL, $minCount = 10) {
		return $this->getTags('user', $username, $minCount);
	}

	/*
	 * Returns a list of tags.
	 */
	private function getTags($type = 'user', $name = NULL, $minCount = 10) {
		if ($name == NULL) $name = $this->username;

		$url = BIBSONOMY_APIURL.'/tags?'.$type.'='.$name.'&start=0&end=10000&format=xml'; # XXX determine sensible value for end or implement an "iterator-fetcher"
		$result = $this->doCurl($url, HTTP_METHOD_GET);
		$xml = new SimpleXMLElement($result);
		$tags = array();
		foreach ($xml->tags->tag as $xmlTag) {
			$tag = new Tag(getAttribute($xmlTag, 'name'));
			$tag->setUserCount(getAttribute($xmlTag, 'usercount'));
			$tag->setGlobalCount(getAttribute($xmlTag, 'globalcount'));
			if ($tag->getUserCount() < $minCount) continue;
			$tags[] = $tag;
		}
		return $tags;
	}


	/*
	 * Returns public posts only.
	 *
	 * XXX: It's not guranteed that it returns the corret amount of posts given by $start and $end.
	 */
	public function getPublicPosts($resourceType, array $tags = NULL, $username = NULL, $start = DEFAULT_POSTS_START, $end = DEFAULT_POSTS_END) {
		$numberOfPosts = $end - $start;
		$rVal = array();
		$posts = $this->getPosts($resourceType, $tags, $username, $start, $end + $this->getOptimisticPrefetch($numberOfPosts));
		foreach ($posts as $post) {
			if ($post->getGroup() != GROUP_PUBLIC) continue;
			$rVal[] = $post;
		}
		return array_slice($rVal, 0, $numberOfPosts);
	}

	/*
	 * We're fetching this many posts more than the given $end in the hope that
	 * the result contains enough posts that match our search.
	 *
	 * TODO: implement iterator that fetches the right number of posts.
	 */
	private function getOptimisticPrefetch($numberOfPosts) {
		if ($numberOfPosts < DEFAULT_MAX_POSTS / 2) return DEFAULT_MAX_POSTS;
		return $numberOfPosts * 4;
	}

	/*
	 * Returns posts with the given resource type, tags and user.
	 */
	private function getPosts($resourceType, array $tags = NULL, $username = NULL, $start = DEFAULT_POSTS_START, $end = DEFAULT_POSTS_END) {
		BibSonomyHelper::assertValidResourceType($resourceType);
		if ($username == NULL) $username = $this->username;
		if ($tags != NULL) {
			$tagUrl = 'tags=';
			foreach ($tags as $tag) $tagUrl .= $tag.'+';
			$tagUrl = substr($tagUrl, 0, strlen($tagUrl) - 1);
		}

		$url = BIBSONOMY_APIURL.'/posts/?resourcetype='.$resourceType.'&user='.$username.'&start='.$start.'&end='.($start + $end).'&format=xml';
		if (isset($tagUrl)) $url .= '&'.$tagUrl;
		#echo $url;

		$result = $this->doCurl($url, HTTP_METHOD_GET);
		if ($this->getStatus()->getCode() != STATUS_OK) {
			if (DEBUG) echo "Couldn't get ".$url;
			throw new Exception("Couldn't get '".$resourceType."'");
		}

		$xml = new SimpleXMLElement($result);
		$posts = array();
		foreach ($xml->posts->post as $xmlPost) {
			$post = new PostImpl($username);
			$post->setPostingdate(getAttribute($xmlPost, 'postingdate'));
			$post->setDescription(getAttribute($xmlPost, 'description'));
			$post->setGroup(getAttribute($xmlPost->group, 'name'));

			$tags = array();
			foreach ($xmlPost->tag as $tag) {
				$tags[] = new Tag(getAttribute($tag, 'name'));
			}
			$post->setTags($tags);

			if ($resourceType == 'bookmark') {
				$bookmark = new Bookmark();
				$bookmark->setURL(getAttribute($xmlPost->bookmark, 'url'));
				$bookmark->setTitle(getAttribute($xmlPost->bookmark, 'title'));
				$bookmark->setHref(getAttribute($xmlPost->bookmark, 'href'));
				$bookmark->setIntraHash(getAttribute($xmlPost->bookmark, 'intrahash'));
				$bookmark->setInterHash(getAttribute($xmlPost->bookmark, 'interhash'));
				$post->setBookmark($bookmark);
			} else if ($resourceType == 'bibtex') {
				$bibtex = new BibTex();
				$bibtex->setURL(getAttribute($xmlPost->bibtex, 'url'));
				$bibtex->setTitle(getAttribute($xmlPost->bibtex, 'title'));
				$bibtex->setHref(getAttribute($xmlPost->bibtex, 'href'));
				$bibtex->setIntraHash(getAttribute($xmlPost->bibtex, 'intrahash'));
				$bibtex->setInterHash(getAttribute($xmlPost->bibtex, 'interhash'));
				$bibtex->setAuthor(getAttribute($xmlPost->bibtex, 'author'));
				$bibtex->setYear(getAttribute($xmlPost->bibtex, 'year'));
				$bibtex->setPublisher(getAttribute($xmlPost->bibtex, 'publisher'));
				$bibtex->setAddress(getAttribute($xmlPost->bibtex, 'address'));
				$bibtex->setPrivnote(getAttribute($xmlPost->bibtex, 'privnote'));
				$bibtex->setPages(getAttribute($xmlPost->bibtex, 'pages'));
				$bibtex->setEntrytype(getAttribute($xmlPost->bibtex, 'entrytype'));
				$bibtex->setBooktitle(getAttribute($xmlPost->bibtex, 'booktitle'));
				$bibtex->setBibTexKey(getAttribute($xmlPost->bibtex, 'bibtexKey'));
				$bibtex->setMisc(getAttribute($xmlPost->bibtex, 'misc'));
				$post->setBibTex($bibtex);
			}

			$posts[] = $post;
		}

		return $posts;
	}
}

?>