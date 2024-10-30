<?php

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

if (class_exists('ChristiansWordPressHelper') == false) {
class ChristiansWordPressHelper {

	# a "link" to the real $wpdb
	private $wpdb;

	public function __construct($wpdb) {
		$this->wpdb = $wpdb;
	}

	#
	# Returns all published posts and pages with their ID, guid and title
	#
	public function getPostsAndPages() {
		$sql = 'SELECT ID, guid, post_title
		        FROM '.$this->wpdb->posts.'
		        WHERE post_status = "publish"
		        ORDER BY ID DESC';
		return $this->wpdb->get_results($sql);
	}

	#
	# Returns all categories for the post with the given id.
	#
	public function getCategoriesForPost($id) {
		return $this->getCategoriesAndTagsForPost($id);
	}

	#
	# Returns all tags for the post with the given id.
	#
	public function getTagsForPost($id) {
		return $this->getCategoriesAndTagsForPost($id, 'post_tag');
	}

	#
	# Returns all categories or tags for a post or page.
	#
	private function getCategoriesAndTagsForPost($id, $taxonomy = 'category', $name = false) {
		$select = 'slug';
		if ($name) $select = 'name';
	
		$sql = 'SELECT '.$select.'
		        FROM '.$this->wpdb->terms.' AS terms,
		             '.$this->wpdb->term_taxonomy.' AS tax,
		             '.$this->wpdb->term_relationships.' AS rel
		        WHERE rel.object_id = '.$id.'
		              AND rel.term_taxonomy_id = tax.term_taxonomy_id
		              AND tax.term_id = terms.term_id
		              AND tax.taxonomy = "'.$taxonomy.'"';
		$cats = $this->wpdb->get_results($sql);

		# put the strings into an array
		$rVal = array();
		foreach ($cats as $cat) {
			$rVal[] = $cat->$select;
		}
		return $rVal;
	}

	#
	# Gets the excerpt for a post from the posts or post_meta table.
	#
	public function getPostExcerpt($id) {
		$sql = 'SELECT post_excerpt
		        FROM '.$this->wpdb->posts.'
		        WHERE ID = '.$id.' AND post_status = "publish"';
		$excerpt = $this->wpdb->get_var($sql);
		# if there's no excerpt, we'll check the postmeta table
		if (empty($excerpt)) { $excerpt = get_post_meta($id, 'excerpt', true); }
		if (empty($excerpt)) { $excerpt = get_post_meta($id, 'description', true); }
	
		return $excerpt;
	}

	/**
	 * Copied from 'wp-includes/post-template.php'.
	 * This version is like 'get_the_content' with all filters applied.
	 */
	public function getTheContent() {
		return 'test';
		#$content = get_the_content();
		#$content = apply_filters('the_content', $content);
		#$content = str_replace(']]>', ']]&gt;', $content);
		#return $content;
	}

}
} # end if class_exists

?>
