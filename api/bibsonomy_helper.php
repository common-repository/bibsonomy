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

# $Id: bibsonomy_helper.php,v 1.7 2008-06-24 14:23:44 cschenk Exp $

require_once('bibsonomy_def.php');
require_once('bibsonomy_model.php');


/*
 * Holds the status and a description after an interaction with BibSonomy.
 */
class BibSonomyStatus {
	private $code, $description;
	public function __construct($code = STATUS_UNDEF, $description = '') {
		$this->code = $code;
		$this->description = $description;
	}
	public function getCode() { return $this->code; }
	public function getDescription() { return $this->description; }
}


/*
 * Contains helper methods (doh!).
 */
class BibSonomyHelper {

	/*	
	 * Checks whether the given resourceType is valid
	 */
	public static function isValidResourceType($resourceType) {
		$type = strtolower($resourceType);
		if ($type == 'bookmark' or $type == 'bibtex') return true;
		return false;
	}

	/*
	 * Almos the same as isValidResourceType but throws an Exception
	 */
	public static function assertValidResourceType($resourceType) {
		if (BibSonomyHelper::isValidResourceType($resourceType) == false) {
			throw new Exception('Unknown resourcetype "'.$resourceType.'"');
		}
	}

	/*
	 * Converts a string to an array of Tag objects.
	 */
	public static function string2Tags($string) {
		return BibSonomyHelper::array2Tags(explode(' ', $string));
	}

	/*
	 * Converts an array of (hopefully) strings to an array of Tag objects.
	 */
	public static function array2Tags(array $array) {
		$tags = array();
		foreach ($array as $value) {
			if (empty($value)) continue;
			$tag = BibSonomyHelper::sanitizeTag($value);
			$tags[] = new Tag($tag);
		}
		return $tags;
	}

	/*
	 * Some tag improvement.
	 */
	public static function sanitizeTag($tag) {
		return str_replace(' ', '_', $tag);
	}

	/*
	 * Removes special characters from a BibTex encoded string.
	 */
	public static function removeBibTexSpecialChars($string) {
		$string = str_replace('{', '', $string);
		$string = str_replace('}', '', $string);
		return $string;
	}

	/*
	 * TODO: NOT USED but may be useful.
	 */
	private function getFilledArray(array $defaults, array $args) {
		if (count($args) > count($defaults)) throw new Exception("There're more arguments than default values.");
		for ($i = 0; $i < count($defaults); $i++) {
			if (count($args) <= $i) break;
			$defaults[$i] = $args[$i];
		}
		return $defaults;
	}

	/**
	 * Checks that the given file exists and is readable.
	 */
	public static function assertFileReadability($filename, $exception = true) {
		try {
			if (file_exists($filename) == false) throw new Exception("File doesn't exist (".$filename.')');
			if (is_readable($filename) == false) throw new Exception("File isn't readable (".$filename.')');
		} catch (Exception $ex) {
			if ($exception === true) throw $ex;
			return false;
		}
	}

	/**
	 * Checks that the given file exists, is readable and writable.
	 * XXX: Not used, but may be useful.
	 */
	public static function assertFileReadWritability($filename) {
		BibSonomyHelper::assertFileReadability($filename);
		if (is_writable($filename) == false) throw new Exception("File isn't writable (".$filename.')');
	}

	/*
	 * Returns tags with four digit years from the array of tags.
	 */
	public static function getYearTags(array $tags, $prefix = '') {
		$yearTags = array();
		foreach ($tags as $tag) {
			# we're searching for a four digit year
			if (strlen($tag->getName()) != strlen($prefix) + 4) continue;
			# prefix must match
			if (substr($tag->getName(), 0, strlen($prefix)) != $prefix) continue;
			# match year
			$possibleYear = substr($tag->getName(), strlen($prefix), strlen($tag->getName()));
			if (preg_match('/[0-9]{4}/', $possibleYear, $matches)) $yearTags[] = $matches[0];
		}
		return $yearTags;
	}
}


/*
 * Contains methods to sort posts.
 */
class BibSonomySorter {

	/*
	 * Searches for tags that might be a year (four digits) and sorts the posts
	 * by these years. Posts without a year attached are skipped and posts with
	 * more than one year will be added to every year, i.e. will appear more
	 * than once in the returned array.
	 *
	 * @param array $posts some posts
	 * @param string $yearTagPrefix prefix for the "year-tag" (e.g. "read:" if you've got "read:2005")
	 * @param bool $asc 
	 * @return array sorted array of posts (key: year, value: array of posts)
	 */
	public static function byYearTag(array $posts, $yearTagPrefix = '', $asc = false) {
		$yearBuckets = array();
		foreach ($posts as $post) {
			$years = BibSonomyHelper::getYearTags($post->getTags(), $yearTagPrefix);
			# skip posts without a year
			if (count($years) == false) continue;
			foreach ($years as $year) {
				$yearBucket = $yearBuckets[$year];
				if (is_array($yearBucket) == false) $yearBucket = array();
				$yearBucket[] = $post;
				$yearBuckets[$year] = $yearBucket;
			}
		}
		if ($asc) ksort($yearBuckets);
		else krsort($yearBuckets);
		return $yearBuckets;
	}

	/**
	 * Sorts an array of tags by their userCount.
	 */
	public static function byUserCount(array &$tags, $asc = true) {
		if ($asc === true) {
			usort($tags, array('BibSonomySorter', 'byUserCountCmpAsc'));
		} else {
			usort($tags, array('BibSonomySorter', 'byUserCountCmpDesc'));
		}
	}

	/**
	 * Sorts tags in ascending order by their userCount.
	 */
	private static function byUserCountCmpAsc($tag1, $tag2) {
		if ($tag1->getUserCount() == $tag2->getUserCount()) return 0;
		return (($tag1->getUserCount() > $tag2->getUserCount()) ? 1 : -1);
	}

	/**
	 * Sorts tags in descending order by their userCount.
	 */
	private static function byUserCountCmpDesc($tag1, $tag2) {
		if ($tag1->getUserCount() == $tag2->getUserCount()) return 0;
		return (($tag1->getUserCount() > $tag2->getUserCount()) ? -1 : 1);
	}

}

?>