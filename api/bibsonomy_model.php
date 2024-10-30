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

# $Id: bibsonomy_model.php,v 1.5 2008-05-14 15:55:22 cschenk Exp $

require_once('bibsonomy_def.php');
require_once('bibsonomy_helper.php');

/*
 * Post
 *
 * XXX: setBookmark and setBibTex are separate functions because there're no
 *      generics in PHP which would allow a method like getPost
 */
interface Post {
	public function setPostingdate($postingdate);
	public function setDescription($description);
	public function setGroup($groupType);
	public function setTags(array $tags);
	public function setBookmark($bookmark);
	public function setBibTex($bibtex);
}

/*
 * Implementations of Post should extend this class.
 */
abstract class PostBase implements Post {

    protected $username;

	public function __construct($username) {
        $this->username = $username;
    }
}

/*
 * Simple post that stores the given data.
 */
class PostImpl extends PostBase {
	private $postingdate, $description, $groupType, $tags, $bookmark, $bibtex;
	public function getPostingdate() { return $this->postingdate; }
	public function setPostingdate($postingdate) { $this->postingdate = $postingdate; }
	public function getDescription() { return $this->description; }
	public function setDescription($description) { $this->description = $description; }
	public function getGroup() { return $this->groupType; }
	public function setGroup($groupType) { $this->groupType = $groupType; }
	public function getTags() { return $this->tags; }
	public function setTags(array $tags) { $this->tags = $tags; }
	public function getBookmark() { return $this->bookmark; }
	public function setBookmark($bookmark) { $this->bookmark = $bookmark; }
	public function getBibTex() { return $this->bibtex; }
	public function setBibTex($bibtex) { $this->bibtex = $bibtex; }
}

/*
 * This post can be used to post a post.
 */
class XmlPost extends PostBase {

	# Holds the XML for this post
	private $xmlPost;
	# true if a resource (bookmark, bibtex, etc.) has been set, otherwise false
	private $resourceSet;
	# true if this post is used for an update, otherwise false
	private $update;

	public function __construct($username, $update = false) {
		parent::__construct($username);
		$this->resourceSet = false;
		$this->update = $update;

		$this->xmlPost = new SimpleXMLElement('<bibsonomy/>');
		$post = $this->xmlPost->addChild('post');
		$this->setUser($username);
	}

	public function setPostingdate($postingdate) {
		#if (empty($postingdate)) return;
		$post = $this->xmlPost->post;
		#$post->addAttribute('postingdate', $postingdate);
		$post->addAttribute('postingdate', date(DATE_ATOM));
	}

	public function setDescription($description) {
		$post = $this->xmlPost->post;
		$post->addAttribute('description', $description);
	}

	# TODO: use $this->username instead of the parameter
	private function setUser($username) {
		$user = $this->xmlPost->post->addChild('user');
		$user->addAttribute('name', $username);
		if ($this->update == true) $user->addAttribute('href', BIBSONOMY_APIURL.'/users/'.$username);
	}

	public function setGroup($groupType) {
		if ($groupType != GROUP_PUBLIC && $groupType != GROUP_PRIVATE && $groupType != GROUP_FRIENDS)
			throw new Exception('Wrong group type');
		$group = $this->xmlPost->post->addChild('group');
		$group->addAttribute('name', $groupType);
		if ($this->update == true) $group->addAttribute('href', BIBSONOMY_APIURL.'/groups/'.$groupType);
	}

	public function setTags(array $tags) {
		foreach($tags as $tag) {
			$xmlTag = $this->xmlPost->post->addChild('tag');
			$xmlTag->addAttribute('name', $tag->getName());
		}
	}

	public function setBookmark($bookmark) {
		if ($this->resourceSet == true) throw new Exception('Resource already set');
		$this->resourceSet = true;

		$xmlBookmark = $this->xmlPost->post->addChild('bookmark');
		$xmlBookmark->addAttribute('url', $bookmark->getURL());
		$xmlBookmark->addAttribute('title', $bookmark->getTitle());
		
		# FIXME variable jugling...
		$intraHash = $bookmark->getIntraHash();
		$interHash = $bookmark->getInterHash();
		if (!empty($intraHash)) $xmlBookmark->addAttribute('intrahash', $bookmark->getIntraHash());
		if (!empty($interHash)) {
			$xmlBookmark->addAttribute('interhash', $bookmark->getInterHash());
			$xmlBookmark->addAttribute('href', BIBSONOMY_APIURL.'/users/'.$this->username.'/posts/'.$bookmark->getInterHash());
		}
	}

	public function setBibTex($bibtex) {
		if ($this->resourceSet == true) throw new Exception('Resource already set');
		$this->resourceSet = true;
		# TODO
		throw new Exception('Not implemented yet');
	}

	public function getXML() {
		## # check postingdate
		## $postingdate = $this->xmlPost->xpath("//post/@postingdate");
		## if (empty($postingdate)) $this->setPostingdate(date(DATE_ATOM));
		# check group
		$group = $this->xmlPost->post->group;
		if (empty($group)) $this->setGroup(GROUP_PUBLIC);
		# check tags
		$tag = $this->xmlPost->post->tag[0];
		if (empty($tag)) $this->setTags(array(new Tag('imported')));
		# check resource
		$bookmark = $this->xmlPost->post->bookmark;
		$bibtex = $this->xmlPost->post->bibtex;
		if (empty($bookmark) && empty($bibtex)) throw new Exception('No resource set');

		return $this->xmlPost->asXML();
	}
}

/*
 * Tag
 */
class Tag {
	private $name, $userCount, $globalCount;
	public function __construct($name) { $this->name = $name; }
	public function getName() { return $this->name; }
	public function getUserCount() { return $this->userCount; }
	public function getGlobalCount() { return $this->globalCount; }
	public function getBibSonomyURL($username = NULL) { return BIBSONOMY_BASEURL.(($username == NULL) ? 'tag/' : 'user/'.$username.'/').$this->getName(); }
	public function setUserCount($userCount) { $this->userCount = $userCount; }
	public function setGlobalCount($globalCount) { $this->globalCount = $globalCount; }
}

/*
 * Resource - a taggable thing in BibSonomy
 */
abstract class Resource {
	private $url, $title, $href, $intraHash, $interHash, $bibsonomyURL;
	public function getURL() { return $this->url; }
	public function getTitle() { return $this->title; }
	public function getHref() { return $this->href; }
	public function getIntraHash() { return $this->intraHash; }
	public function getInterHash() { return $this->interHash; }
	public function setURL($url) { $this->url = $url; }
	public function setTitle($title) { $this->title = $title; }
	public function setHref($href) { $this->href = $href; }
	public function setIntraHash($intraHash) { $this->intraHash = $intraHash; $this->setBibSonomyURL($intraHash); }
	public function setInterHash($interHash) { $this->interHash = $interHash; }
	private function setBibSonomyURL($intraHash) { $this->bibsonomyURL = BIBSONOMY_BASEURL.$this->getIntraHashURLPrefix().$intraHash; }
	public function getBibSonomyURL($username = NULL) { return $this->bibsonomyURL.(($username != NULL) ? '/'.$username : ''); }
	protected abstract function getIntraHashURLPrefix();
}

/*
 * Bookmark
 */
class Bookmark extends Resource {
	protected function getIntraHashURLPrefix() { return 'url/'; }
}

/*
 * BibTex
 */
class BibTex extends Resource {
	private $author, $year, $publisher, $address, $privnote, $pages, $entrytype, $booktitle, $bibtexKey, $misc;
	public function setTitle($title) { parent::setTitle(BibSonomyHelper::removeBibTexSpecialChars($title)); }
	public function getAuthor() { return $this->author; }
	public function getYear() { return $this->year; }
	public function getPublisher() { return $this->publisher; }
	public function getAddress() { return $this->address; }
	public function getPrivnote() { return $this->privnote; }
	public function getPages() { return $this->pages; }
	public function getEntrytype() { return $this->entrytype; }
	public function getBooktitle() { return $this->booktitle; }
	public function getBibTexKey() { return $this->bibtexKey; }
	public function getMisc() { return $this->misc; }
	public function setAuthor($author) { $this->author = $author; }
	public function setYear($year) { $this->year = $year; }
	public function setPublisher($publisher) { $this->publisher = $publisher; }
	public function setAddress($address) { $this->address = $address; }
	public function setPrivnote($privnote) { $this->privnote = $privnote; }
	public function setPages($pages) { $this->pages = $pages; }
	public function setEntrytype($entrytype) { $this->entrytype = $entrytype; }
	public function setBooktitle($booktitle) { $this->booktitle = $booktitle; }
	public function setBibTexKey($bibtexKey) { $this->bibtexKey = $bibtexKey; }
	public function setMisc($misc) { $this->misc = $misc; }
	protected function getIntraHashURLPrefix() { return 'bibtex/2'; }
}

?>