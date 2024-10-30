<?php
/*
Plugin Name: Christian's WordPress Utils
Plugin URI: http://www.christianschenk.org/projects/christians-wordpress-utils/
Description: This plugin collects useful code for WordPress
Version: 1.1
Author: Christian Schenk
Author URI: http://www.christianschenk.org/
*/

#
# Christian's WordPress Utils
# Copyright (C) 2007-2008 Christian Schenk
# 
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
#

# WP helper class
require_once('christians-wp-helper.php');
$chWpHelper = new ChristiansWordPressHelper($wpdb);
# PHP helper class
require_once('christians-php-helper.php');
$chPhpHelper = new ChristiansPhpHelper();

#
# Prints the name of this blog and appends "s Blog" if extraFullName is set to true.
#
if (function_exists('getExtraBlogName') == false) {
function getExtraBlogName($display = true, $extraFullName = true) {
	$name = get_bloginfo('name');
	if ($extraFullName) $name .= "s Blog";
	if ($display) echo $name;
	else return $name;
}
} # end if function_exists

#
# Prints a title depending on WordPress' conditional tags
#
if (function_exists('printTitle') == false) {
function printTitle($display = true, $postId = NULL) {
	// removes the leading whitespace
	$trimmed_title = trim(wp_title('', false));
	$title = "";

	if (is_home()) {
	  	$title = getExtraBlogName(false);
	} elseif (is_single() or is_page()) {
		$title = $trimmed_title;
	} elseif (is_category()) {
		$title = 'Category '.single_cat_title("", false)." on ".getExtraBlogName(false);
	} elseif (is_search()) {
		$title = 'Search '.getExtraBlogName(false);
	} else {
		$title = get_bloginfo("name")." ".$trimmed_title;
	}

	# a special title overwrites the default:
	if ($postId != NULL) {
		$optTitle = get_post_meta($postId, "title", true);
		if (! empty($optTitle)) $title = $optTitle;
	}

	if ($display) echo $title;
	else return $title;
}
} # end if function_exists

#
# Returns the language for this blog.
#
if (function_exists('printLanguage') == false) {
function printLanguage($id) {
	$default = 'en';
	$metaLang = get_post_meta($id, 'language', true);
	if (!empty($metaLang)) echo $metaLang;
	else echo $default;
}
} # end if function_exists

#
# Prints keywords that can be used inside a meta tag consisting of the
# categories and tags for the specified post or page. You can add an array with
# keywords that should be ignored.
#
if (function_exists('printMetaKeywords4Post') == false) {
function printMetaKeywords4Post($id, array $ignoreTags = NULL) {
	$keywords = array();

	if (is_single() || is_page()) {
		foreach (getAllTags4Post($id) as $tag) {
			$keywords[] = $tag;
		}
	} else {
		$title = strtolower(trim(wp_title('', false)));
		$keywords = array("christian schenk", "blog");
		if (! empty($title)) $keywords[] = $title;
	}

	// print the keywords
	foreach ($keywords as $keyword) {
		if (empty($keyword)) continue;
		if ($ignoreTags != NULL) {
			$ignore = false;
			foreach ($ignoreTags as $ignoreTag) {
				if ($keyword != $ignoreTag) continue;
				$ignore = true;
				break;
			}
			if ($ignore) continue;
		}

		$com = ', ';
		// don't put a comma at the end
		if ($keywords[count($keywords) - 1] == $keyword) { $com = ''; }
		echo $keyword . $com;
	}
}
} # end if function_exists

#
# Returns all tags for the given post. The tags are a combination of some
# default tags, the WP categories and WP tags.
#
if (function_exists('getAllTags4Post') == false) {
function getAllTags4Post($id, $defaultTags = " ") {
	global $chWpHelper, $chPhpHelper;
	$tags = $chPhpHelper->string2array($defaultTags, ' ');
	$tags = $chPhpHelper->combineArrays($tags, $chWpHelper->getCategoriesForPost($id));
	$tags = $chPhpHelper->combineArrays($tags, $chWpHelper->getTagsForPost($id));
	return $tags;
}
} # end if function_exists

#
# Prints the tagline of this blog. If there's an excerpt for the current post
# or page we'll use it as a description.
#
if (function_exists('printDescription4Post') == false) {
function printDescription4Post($id, $display = true) {
	$description = get_bloginfo('description');

	$excerpt = "";
	if (is_single() and !is_page()) {
		$excerpt = get_the_excerpt();
	} else if (is_page()) {
		$excerpt = get_post_meta($id, "excerpt", true);
	} else if (is_category()) {
		$excerpt = printTitle(false);
	}
	$excerpt = trim($excerpt);
	if (! empty($excerpt)) $description = $excerpt;

	if ($display) echo $description;
	else return $description;
}
} # end if function_exists

?>
