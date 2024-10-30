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

#
# Contains some helper functions for basic PHP operations. I haven't found
# equivalent functions in the PHP API yet.
#
if (class_exists('ChristiansPhpHelper') == false) {
class ChristiansPhpHelper {

	#
	# Returns an array as a combination of the given two arrays.
	#
	public function combineArrays($array1, $array2) {
		$both = array();
		foreach ($array1 as $value) { $both[] = $value; }
		foreach ($array2 as $value) { $both[] = $value; }
		return $both;
	}

	#
	# Converts a string to an array by splitting the string at a delimiter.
	# Hint: the delim is part of a regular expression.
	# TODO: can explode do this too?
	#
	public function string2array($str, $delim) {
		return preg_split('/'.$delim.'/', $str);
	}

}
} # end if class_exists

?>
