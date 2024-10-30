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

# $Id: bibsonomy_cache.php,v 1.2 2008-06-24 14:23:44 cschenk Exp $

require_once('bibsonomy_api.php');
require_once('bibsonomy_def.php');
require_once('bibsonomy_helper.php');

/*
 * This class forwards all methods to the BibSonomy class if it can't find the
 * result for the given method and arguments in its cache.
 *
 * Usage pattern:
 * <pre>
 *   $bibsonomy = new BibSonomy($username, $password);
 *   $cache = new BibSonomyCache($bibsonomy);
 *   if ($cache->isSetUp()) return $cache;
 *   else return $bibsonomy;
 * </pre>
 */
class BibSonomyCache {

	/* Instance of BibSonomy class */
	private $bibsonomy;
	/* This folder holds the cached results */
	private $cacheDir;
	/* Time until an object in the cache expires in seconds */
	private $cacheTTL;
	/* These methods may be cached */
	private $cachableMethods;

	public function __construct($bibsonomy, $cacheDir = BIBSONOMY_CACHE_DIR, $cacheTTL = BIBSONOMY_CACHE_TTL) {
		if (is_a($bibsonomy, 'BibSonomy') == false) throw new Exception('Expecting class BibSonomy');
		$this->bibsonomy = $bibsonomy;
		$this->cacheDir = $cacheDir;
		$this->cacheTTL = $cacheTTL;
		$this->cachableMethods = array('getPublicTags', 'getUserTags', 'getPublicPosts');
	}

	/**
	 * Check this method before using this class. If it returns false the
	 * directory for the cache isn't setup properly.
	 */
	public function isSetUp() {
		if (file_exists($this->getCacheDir()) && is_writable($this->getCacheDir())) return true;
		return false;
	}

	/**
	 * Circumvents all method calls to this class in a proxy-like manner and
	 * facilitates the cache.
	 */
	public function __call($method, array $args) {
		if (is_callable(array($this->bibsonomy, $method)) == false) throw new Exception("Can't call method '$method'");

		$result = $this->get($method, $args);
		if ($result == NULL) {
			$m = new ReflectionMethod('BibSonomy', $method);
			$result = $m->invokeArgs($this->bibsonomy, $args);
			$this->put($method, $args, $result);
		}
		return $result;
	}

	/**
	 * Retrieves data from the cache.
	 */
	private function get($method, array $args) {
		if (in_array($method, $this->cachableMethods) == false) return NULL;
		if ($this->isSetUp() == false) return NULL; # sanity check

		$hash = $this->getHash($method, $args);
		$cachedFileData = $this->getCacheFileData($hash);
		$cachedFileMeta = $this->getCacheFileMeta($hash);
		if (BibSonomyHelper::assertFileReadability($cachedFileData, false) === false) return NULL;
		if (BibSonomyHelper::assertFileReadability($cachedFileMeta, false) === false) return NULL;

		# If the data expired, we'll delete the corresponding files from the cache
		if ((int) date('U') > (int) file_get_contents($cachedFileMeta) + $this->cacheTTL) {
			unlink($cachedFileData);
			unlink($cachedFileMeta);
			return NULL;
		}

		return unserialize(file_get_contents($cachedFileData));
	}

	/**
	 * Adds data to the cache.
	 */
	private function put($method, array $args, $result) {
		if (in_array($method, $this->cachableMethods) == false) return;
		if ($this->isSetUp() == false) return; # sanity check

		$hash = $this->getHash($method, $args);
		$cachedFileData = $this->getCacheFileData($hash);
		$cachedFileMeta = $this->getCacheFileMeta($hash);

		file_put_contents($cachedFileData, serialize($result));
		file_put_contents($cachedFileMeta, date('U'));
	}

	/**
	 * Combines the method name and some arguments and builds a (hopefully)
	 * unique hash for them.
	 */
	private function getHash($method, array $args) {
		$str = $method;
		foreach ($args as $arg) $str .= $arg;
		return md5($str);
	}

	private function getCacheDir() {
		return dirname(__FILE__).'/'.$this->cacheDir;
	}

	private function getCacheFileData($hash) {
		return $this->getCacheDir().'/'.$hash.'.data';
	}

	private function getCacheFileMeta($hash) {
		return $this->getCacheDir().'/'.$hash.'.meta';
	}

}

?>