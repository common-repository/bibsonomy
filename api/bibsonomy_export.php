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

# $Id: bibsonomy_export.php,v 1.1 2008-05-14 15:55:22 cschenk Exp $

require_once('bibsonomy_def.php');
require_once('bibsonomy_model.php');

/*
 * Exports data from BibSonomy into various formats.
 *
 * Although the export formats come close to the original version (e.g. our
 * Harvard vs. the original Harvard) we don't claim to implement a perfect
 * match here.
 */
class BibSonomyExport {

	/*
	 * Returns a very simple list that's suitable for bookmarks and publications.
	 */
	public static function getSimple(array $posts, $resourcetype, $username) {
		$rVal .= '<ul class="simple">'."\n";
		foreach ($posts as $post) {
			switch ($resourcetype) {
				case 'bookmark':
					$rVal .= '<li><a href="'.$post->getBookmark()->getURL().'">'.$post->getBookmark()->getTitle().'</a></li>';
					break;
				case 'bibtex':
					$rVal .= '<li><a href="'.$post->getBibTex()->getBibSonomyURL($username).'">'.$post->getBibTex()->getTitle().'</a></li>';
					break;
			}
			$rVal .= "\n";
		}
		$rVal .= "</ul>\n";
		return $rVal;
	}

	/*
	 * Returns an array of posts in BibSonomy's own format (aka HTML export).
	 * -> Rainer Zufall and Peter Silie. Title of our publication. Publisher's name, volume, pages, 1970. [BibSonomy: <tag-1> ... <tag-n>] URL
	 */
	public static function getBibSonomy(array $posts, $username = NULL) {
		$rVal .= '<div class="publ-bibsonomy">';
		foreach ($posts as $post) {
			$rVal .= '<span class="author">'.$post->getBibTex()->getAuthor().'</span>. ';
			$rVal .= '<a href="'.$post->getBibTex()->getBibSonomyURL($username).'" class="title">'.$post->getBibTex()->getTitle().'</a>. ';
			if (strlen($post->getBibTex()->getPublisher())) $rVal .= '<span class="publisher">'.$post->getBibTex()->getPublisher().'</span>';
			if (strlen($post->getBibTex()->getYear())) {
				if (strlen($post->getBibTex()->getPublisher())) $rVal .= ', ';
				$rVal .= '<span class="year">'.$post->getBibTex()->getYear().'</span>.';
			}
			$rVal .= ' [<a href="'.BIBSONOMY_BASEURL.'">BibSonomy</a>:';
			foreach ($post->getTags() as $tag) $rVal .= ' <a href="'.$tag->getBibSonomyURL($username).'">'.$tag->getName().'</a>';
			$rVal .= ']';
			if (strlen($post->getBibTex()->getURL())) $rVal .= ' <a href="'.$post->getBibTex()->getURL().'" class="tag">URL</a>';
			$rVal .= "<br/>\n";
		}
		$rVal .= '</div>';
		return $rVal;
	}

	/*
	 * Harvard
	 * -> Brin, S. & Page, L. (1998), 'The Anatomy of a Large-Scale Hypertextual Web Search Engine', Computer Networks and ISDN Systems 30(1-7), 107--117.
	 */
	public static function getHarvard(array $posts, $username = NULL) {
		$rVal .= '<div class="publ-harvard">';
		foreach ($posts as $post) {
			$author = BibSonomyExport::getAuthorLastnameAndAbbreviatedFirstname($post->getBibTex()->getAuthor(), ' & ');
			$rVal .= '<span class="author">'.$author.'</span>';
			if (strlen($post->getBibTex()->getYear())) $rVal .= ' <span class="year">('.$post->getBibTex()->getYear().')</span>';
			$rVal .= ',';
			$rVal .= ' <it><a href="'.$post->getBibTex()->getBibSonomyURL($username).'" class="title">'.$post->getBibTex()->getTitle().'</a></it>';
			if (strlen($post->getBibTex()->getPublisher())) $rVal .= ', <span class="publisher">'.$post->getBibTex()->getPublisher().'</span>';
			if (strlen($post->getBibTex()->getPages())) $rVal .= ', <span class="pages">'.$post->getBibTex()->getPages().'</span>';
			$rVal .= ".<br/>\n";
		}
		$rVal .= '</div>';
		return $rVal;
	}

	/*
	 * Journal of Universal Computer Science (JUCS)
	 * -> [Brin and Page 1998] Brin, S. and Page, L.: "The Anatomy of a Large-Scale Hypertextual Web Search Engine"; Computer Networks and ISDN Systems 30 (1-7)  (1998), 107--117.
	 */
	public static function getJUCS(array $posts, $username = NULL) {
		$rVal .= '<div class="publ-jucs">';
		foreach ($posts as $post) {
			$authorsKey = '';
			$authors = BibSonomyExport::getAuthorLastnameAndAbbreviatedFirstname($post->getBibTex()->getAuthor(), ' and ');
			$authorSplit = explode(' ', $authors);
			foreach ($authorSplit as $author) if ($author[strlen($author) - 1] == ',') $authorsKey .= substr($author, 0, strlen($author) - 1) . ' and ';
			$authorsKey = substr($authorsKey, 0, strlen($authorsKey) - strlen(' and '));
			$year = ((strlen($post->getBibTex()->getYear()) ? ' <span class="year">'.$post->getBibTex()->getYear().'</span>' : ''));
			$rVal .= '<span class="author">['.$authorsKey.$year.']</span>';
			$rVal .= ' '.$authors.':';
			$rVal .= ' "<a href="'.$post->getBibTex()->getBibSonomyURL($username).'" class="title">'.$post->getBibTex()->getTitle().'</a>"';
			if (strlen($post->getBibTex()->getPublisher())) $rVal .= '; <it><span class="publisher">'.$post->getBibTex()->getPublisher().'</span></it>';
			if (strlen($post->getBibTex()->getPages())) $rVal .= ', <span class="pages">'.$post->getBibTex()->getPages().'</span>';
			if (strlen($post->getBibTex()->getYear())) $rVal .= ' <span class="year">('.$post->getBibTex()->getYear().')</span>';
			$rVal .= ".<br/>\n";
		}
		$rVal .= '</div>';
		return $rVal;
	}

	/*
	 * Transforms a string of the form 'Sergey Brin and Lawrence Page' to 'Brin, S. <$concat> Page, L.'.
	 */
	private static function getAuthorLastnameAndAbbreviatedFirstname($authorString, $concat = ' and ') {
		$authors = '';
		foreach (explode(' and ', $authorString) as $author) {
			$nameSplit = explode(' ', $author);
			$authors .= $nameSplit[count($nameSplit) - 1].', ';
			for ($i = 0; $i < count($nameSplit) - 1; $i++) $authors .= $nameSplit[$i][0].'.';
			$authors .= $concat;
		}
		$authors = substr($authors, 0, strlen($authors) - strlen($concat));
		return $authors;
	}
}

?>