<?php
/*
Plugin Name: BibSonomy
Plugin URI: http://www.christianschenk.org/projects/wordpress-bibsonomy-plugin/
Description: Use BibSonomy on your site.
Version: 1.10
Author: Christian Schenk
Author URI: http://www.christianschenk.org/
*/

#
# WordPress BibSonomy plugin
# Copyright (C) 2007-2011 Christian Schenk
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


# The installation path in the WP directory
define('BIBSONOMY_FILEPATH', '/wp-content/plugins/bibsonomy/bibsonomy.php');
# Identifier for various actions of this script (e.g. CSS)
define('BIBSONOMY_ACTION', 'bibsonomy_action');
# Include the config
require_once('config.php');


/*
 * Parses the actions
 */
if (!empty($_REQUEST[BIBSONOMY_ACTION])) {
	switch ($_REQUEST[BIBSONOMY_ACTION]) {
		case 'css':
			header('Content-type: text/css');
?>
.tagcloud { text-align: justify; font-size: 70%; }
.tagcloud li { display: inline; padding: 0em .1em 0em .1em; }
.tagcloud a,.taglist a { color: #069; }
.tagcloud a:hover, .taglist a:hover { color: #f00; text-decoration: none; }
.tagcloud .tag1 a { color: #3399cc; }
.tagcloud .tag2 a { font-size: 140%; }
.tagcloud .tag3 a { font-size: 150%; font-weight: bold; }
<?php
			# echos the user defined CSS
			bibsonomy_get_custom_css();
		default:
			die();
			break;
	}
}


/**
 * BibSonomy plugin init.
 */
function bibsonomy_init() {
	if (function_exists('load_plugin_textdomain')) {
		load_plugin_textdomain('bibsonomy', 'wp-content/plugins/bibsonomy/messages');
	}
}
if (function_exists('add_action')) add_action('init', 'bibsonomy_init');


/*
 * Adds a menu 'BibSonomy' to the 'Settings' page
 */
function bibsonomy_add_options_page() {
	if(function_exists('add_options_page'))
		add_options_page('BibSonomy', 'BibSonomy', 'activate_plugins', basename(__FILE__), 'bibsonomy_show_options_page');
}
if (function_exists('add_action')) add_action('admin_menu', 'bibsonomy_add_options_page');


# Identifier for the username
define('OPTION_BIBSONOMY_USERNAME', 'bibsonomy_username');
# Identifier for the api key
define('OPTION_BIBSONOMY_APIKEY', 'bibsonomy_apikey');
# Identifier for the default tags
define('OPTION_BIBSONOMY_DEFAULT_TAGS', 'bibsonomy_default_tags');

# We need these helper functions
require_once('util/cwpu/util.php');


/*
 * The logic and layout of the options page
 */
function bibsonomy_show_options_page() {
	global $chWpHelper;

	$username    = get_option(OPTION_BIBSONOMY_USERNAME);
	$apikey      = get_option(OPTION_BIBSONOMY_APIKEY);
	$defaultTags = get_option(OPTION_BIBSONOMY_DEFAULT_TAGS);

	if(isset($_POST['updateoptions'])) {
		$username    = $_POST['username'];
		$apikey      = $_POST['apikey'];
		$defaultTags = $_POST['defaultTags'];

		update_option(OPTION_BIBSONOMY_USERNAME, $username);
		update_option(OPTION_BIBSONOMY_APIKEY, $apikey);
		update_option(OPTION_BIBSONOMY_DEFAULT_TAGS, $defaultTags);

		echo '<div class="updated"><p><strong>'.__('Options saved.', 'bibsonomy').'</strong></p></div>';
	} else if (isset($_POST['postsites'])) {
		require_once('api/bibsonomy_api.php');
		$bibsonomy = BibSonomyFactory::produce($username, $apikey);
		$numPosts = 0; # counter for the total number of new or changed posts
		$posts = $chWpHelper->getPostsAndPages();
		foreach ($posts as $post) {
			if (isset($_POST['id-'.$post->ID]) == false) continue;
			if (($_POST['id-'.$post->ID] == 1) == false) continue;

			# set URL and title
			$url = $post->guid;
			$title = $post->post_title;
			# set description
			$desc = $chWpHelper->getPostExcerpt($post->ID);
			# set tags
			$tags = BibSonomyHelper::array2Tags(getAllTags4Post($post->ID, $defaultTags));

			$bibsonomy->createPost($url, $title, $desc, $tags);
			switch ($bibsonomy->getStatus()->getCode()) {
				case STATUS_OK:
					$numPosts++;
					break;
				case STATUS_POST_ALREADY_EXISTS:
					$hash = $bibsonomy->getStatus()->getDescription();
					if (empty($hash)) break;
					# send an update
					$bibsonomy->changePost($url, $title, $desc, $tags, $hash);
					if ($bibsonomy->getStatus()->getCode() == STATUS_OK) $numPosts++;
					break;
			}
		}

		echo '<div class="updated"><p><strong>'. sprintf(__('Posted %s posts to BibSonomy.', 'bibsonomy'), $numPosts) .'</strong></p></div>';
	}
?>
<div class="wrap">
<h2>BibSonomy</h2>
<form method="post" action="">
	<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Username', 'bibsonomy'); ?>:</th>
			<td><input name="username" value="<?php echo $username; ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('API key', 'bibsonomy'); ?>:</th>
			<td><input name="apikey" size="34" value="<?php echo $apikey; ?>" /><br/>
				<?php _e('You can generate one <a href="http://www.bibsonomy.org/settings?seltab=2">here</a>.', 'bibsonomy'); ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Default tags', 'bibsonomy'); ?>:</th>
			<td><input name="defaultTags" value="<?php echo $defaultTags; ?>" /></td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" class="button-primary" name="updateoptions" value="<?php _e('Update Options', 'bibsonomy'); ?>" />
	</p>
</form>

<?php // copied from wp-admin/edit-comments.php ?>
<script type="text/javascript">
<!--
function checkAll(form) {
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox") {
			if(form.elements[i].checked == true)
				form.elements[i].checked = false;
			else
				form.elements[i].checked = true;
		}
	}
}
//-->
</script>

<h3><?php _e('Tag your posts and pages', 'bibsonomy'); ?></h3>

<?php
# TODO: maybe we should have a table with the ten most recent posts.
#       -> or paginated results...
if (empty($_POST['show_posts'])) { ?>
	<p><?php _e("The posts aren't include here by default because it might take a while to load.", 'bibsonomy'); ?></p>
	<form method="post" action="">
		<p class="submit">
			<input type="submit" name="show_posts" value="<?php _e('Show posts', 'bibsonomy'); ?>" class="button-secondary" />
		</p>
	</form>
<?php
} else { ?>
	<p><?php _e("Simply select the posts and pages you'd like to post to BibSonomy. If there's already a post for one of your selected URLs, it'll be updated.", 'bibsonomy'); ?></p>
<?php
	$posts = $chWpHelper->getPostsAndPages();
	if(count($posts) > 0) {
		?>
		<form name="post2bibsonomy" id="post2bibsonomy" action="" method="post">
		<table class="widefat">
		<thead>
			<tr>
				<th scope="col" style="text-align: center"><input type="checkbox" onclick="checkAll(document.getElementById('post2bibsonomy'));" /></th>
				<th scope="col"><?php _e('Name', 'bibsonomy'); ?></th>
				<th scope="col"><?php _e('Description', 'bibsonomy'); ?></th>
				<th scope="col" style="text-align: center"><?php _e('Tags', 'bibsonomy'); ?></th>
			</tr>
		</thead>
		<?php
		$alternate = 0;
		foreach($posts as $post) {
			# skip posts/pages with no guid
			if (empty($post->guid)) continue;
			?>
			<tr valign="top"<?php echo (($alternate % 2 == 0)?' class="alternate"':''); ?>>
				<td style="text-align: center">
					<input name="id-<?php echo $post->ID; ?>" id="id-<?php echo $post->ID; ?>" type="checkbox" value="1" <?php if (isset($_POST['id-'.$post->ID])) echo 'checked'; ?>/>
				</td>
				<td>
					<a href="<?php echo get_permalink($post->ID); ?>"><label for="id-<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></label></a>
				</td>
				<td>
					<?php
						$excerpt = explode("\n", wordwrap($chWpHelper->getPostExcerpt($post->ID), 50));
						echo $excerpt[0].' [...]';
					?>
				</td>
				<td style="text-align: center">
					<?php echo count(getAllTags4Post($post->ID)); ?>
				</td>
			</tr>
			<?php
			# don't forget to increment the counter
			$alternate++;
		}
		?>
		</table>
		<p class="submit">
			<input type="submit" name="postsites" value="<?php _e('Post selection', 'bibsonomy'); ?>" class="button-secondary" />
		</p>
		</form>
		<?php
	} else {
		echo '<em>'.__('No valid posts or pages found.', 'bibsonomy').'</em>';
	}
}
?>
</div>
<?php
}

/*
 * Deletes all options on deactivation
 */
function bibsonomy_deactivate() {
	delete_option(OPTION_BIBSONOMY_USERNAME);  
	delete_option(OPTION_BIBSONOMY_APIKEY);  
	delete_option(OPTION_BIBSONOMY_DEFAULT_TAGS);  
}
if (function_exists('add_action')) add_action('deactivate_bibsonomy/bibsonomy.php', 'bibsonomy_deactivate');


/*
 * Retrieves the tag cloud/list
 */
function bibsonomy_tags_shortcode($atts, $content = null) {
	extract(shortcode_atts(array(
		'minusercount' => 5,
		'style' => 'cloud'
	), $atts));

	# If the CSS isn't present we don't output anything
	if (isAnnotatedWithBibSonomy() === false) return '';

	$username = get_option(OPTION_BIBSONOMY_USERNAME);
	$apikey   = get_option(OPTION_BIBSONOMY_APIKEY);

	require_once('api/bibsonomy_api.php');
	$bibsonomy = BibSonomyFactory::produce($username, $apikey);

	$rVal = '';
	switch ($style) {
		#
		# Markup for a tag cloud
		#
		case 'cloud':
			$tags = $bibsonomy->getUserTags(NULL, $minusercount);
			$tagLimits = getLimits4TagCloud($tags);

			$rVal = '<ul class="tagcloud">'."\n";
			foreach ($tags as $tag) {
				$class = 'class="tag1"';
				if ($tag->getUserCount() > $tagLimits[0]) $class = 'class="tag2"';
				if ($tag->getUserCount() > $tagLimits[1]) $class = 'class="tag3"';
				$rVal .= '<li'.((empty($class) == false) ? ' '.$class : '').'>'.
				         '<a title="'.$tag->getUserCount().' post'.(($tag->getUserCount() == 1) ? '' : 's').'" href="'.BIBSONOMY_BASEURL.'user/'.$username.'/'.$tag->getName().'">'.$tag->getName().'</a>'.
				         '</li>'."\n";
			}
			$rVal .= '</ul>'."\n";
			break;
		#
		# Generates a chart: y axis: usercount, x axis: tag names
		#
		case 'occurrence':
			$tags = $bibsonomy->getPublicTags($minusercount);

			# sort tags, put them into an array and keep the top 100
			BibSonomySorter::byUserCount($tags, false);
			foreach ($tags as $tag) $data[$tag->getName()] = $tag->getUserCount();
			$data = array_slice($data, 0, 100);

			# get values for the graph from nested shortcode
			# XXX: ugly hack since WP inserts "\n<br>" in front of the first and after the last attribute
			$hackAtts = str_replace("\n", '', strip_tags(do_shortcode($content)));
			extract(unserialize($hackAtts));

			require_once('util/googchart/GoogChart.class.php');
			$chart = new GoogChart();
			$chart->setChartAttrs(array(
				'type' => 'line',
				'title' => 'Tag occurrence',
				'xlabels' => $xlabels,
				'data' => $data,
				'size' => array($width, $height),
				'labelsXY' => true,
				'color' => (($color === false) ? array() : array($color))
			));
			$rVal = $chart;
		default:
			break;
	}

	return $rVal;
}
if (function_exists('add_shortcode')) add_shortcode('bibsonomy-tags', 'bibsonomy_tags_shortcode');


/*
 * Nested macro for "bibsonomy-tags" that holds options for the chart.
 */
function bibsonomy_tags_chart_shortcode($atts) {
	$shAtts = shortcode_atts(array(
		'color' => false,
		'xlabels' => 5,
		'width' => 550,
		'height' => 200
	), $atts);
	return serialize($shAtts);
}
if (function_exists('add_shortcode')) add_shortcode('bibsonomy-chart', 'bibsonomy_tags_chart_shortcode');


/*
 * Retrieves a list of posts
 */
function bibsonomy_posts_shortcode($atts) {
	extract(shortcode_atts(array(
		'resourcetype' => 'bookmark',
		'tags' => NULL,
		'style' => 'list',
		'sort' => NULL,
		'start' => '0',
		'end' => '20'
	), $atts));

	# prechecks
	if (substr($style, 0, 4) == 'publ' and $resourcetype != 'bibtex') return '<!-- '.__('This style is only available for publications', 'bibsonomy').' -->';

	$username = get_option(OPTION_BIBSONOMY_USERNAME);
	$apikey   = get_option(OPTION_BIBSONOMY_APIKEY);

	require_once('api/bibsonomy_api.php');
	require_once('api/bibsonomy_helper.php');
	$bibsonomy = BibSonomyFactory::produce($username, $apikey);
	$posts = $bibsonomy->getPublicPosts($resourcetype, explode(' ', $tags), NULL, $start, $end);

	# currently we're only sorting by years in tags, so this simple if does it
	if ($sort != NULL) {
		$posts = BibSonomySorter::byYearTag($posts, $sort);
	}

	# TODO: refactor duplicate code (sort feature)
	$rVal = '';
	switch ($style) {
		#
		# Simple list
		#
		case 'list':
			require_once('api/bibsonomy_export.php');
			if ($sort == NULL) {
				$rVal = BibSonomyExport::getSimple($posts, $resourcetype, $username);
			} else {
				foreach ($posts as $key => $value) {
					$rVal .= '<h2>'.$key.'</h2>';
					$rVal .= BibSonomyExport::getSimple($value, $resourcetype, $username);
				}
			}
			break;
		#
		# BibSonomy's own format (aka HTML export)
		#
		case 'publ-bibsonomy':
			require_once('api/bibsonomy_export.php');
			if ($sort == NULL) {
				$rVal = BibSonomyExport::getBibSonomy($posts, $username);
			} else {
				foreach ($posts as $key => $value) {
					$rVal .= '<h2>'.$key.'</h2>';
					$rVal .= BibSonomyExport::getBibSonomy($value, $username);
				}
			}
			break;
		#
		# Harvard
		#
		case 'publ-harvard':
			require_once('api/bibsonomy_export.php');
			if ($sort == NULL) {
				$rVal = BibSonomyExport::getHarvard($posts, $username);
			} else {
				foreach ($posts as $key => $value) {
					$rVal .= '<h2>'.$key.'</h2>';
					$rVal .= BibSonomyExport::getHarvard($value, $username);
				}
			}
			break;
		#
		# Journal of Universal Computer Science
		#
		case 'publ-jucs':
			require_once('api/bibsonomy_export.php');
			if ($sort == NULL) {
				$rVal = BibSonomyExport::getJUCS($posts, $username);
			} else {
				foreach ($posts as $key => $value) {
					$rVal .= '<h2>'.$key.'</h2>';
					$rVal .= BibSonomyExport::getJUCS($value, $username);
				}
			}
			break;
		default:
			break;
	}

	return $rVal;
}
if (function_exists('add_shortcode')) add_shortcode('bibsonomy-posts', 'bibsonomy_posts_shortcode');


/*
 * Adds a link to the css stylesheet
 */
function filter_bibsonomy_tags_css() {
	if (BIBSONOMY_INCLUDE_CSS === false) if (isAnnotatedWithBibSonomy() === false) return;
	$wp = get_bloginfo('wpurl');
	$url = $wp.BIBSONOMY_FILEPATH;
	echo '<link rel="stylesheet" type="text/css" href="'.$url.'?'.BIBSONOMY_ACTION.'=css" />';
}
if (function_exists('add_action')) add_action('wp_head', 'filter_bibsonomy_tags_css');


/*
 * Returns true if the current page was annotated with "bibsonomy", i.e. such a
 * custom field exists.
 *
 * Methods that rely on CSS/JavaScript being included in the header should
 * check that this is true.
 */
function isAnnotatedWithBibSonomy() {
	global $post;
	$meta = get_post_meta($post->ID, 'bibsonomy', true);
	if (empty($meta)) return false;
	return true;
}


/*
 * Calculates sensible limits for the tagcloud, i.e. small, medium and large
 * appearance. It returns an upper and lower bound that marks the three
 * different styles for the tags in the tagcloud:
 *  small  :  < lower bound                        ( 0 -   5 %)
 *  medium :  > lower bound  and  < upper bound    ( 5 -  90 %)
 *  large  :  > upper bound                        (90 - 100 %)
 */
function getLimits4TagCloud(array $tags) {
	$min = 1000000; $max = -1000000;
	foreach ($tags as $tag) {
		if ($tag->getUserCount() < $min) $min = $tag->getUserCount();
		if ($tag->getUserCount() > $max) $max = $tag->getUserCount();
	}
	$range = $max - $min;
	$low   = $min + $range * (5/100);
	$high  = $max - $range * (1/10);
	return array($low, $high);
}

?>