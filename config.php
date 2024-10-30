<?php
/*
 * We keep the configuration in a separate file to ease updating: if you plan
 * to update the plugin simply put this file aside, copy the new files over the
 * old ones and put this config back into place and your settings won't be lost.
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


# Include the CSS everywhere - no need for a custom field
define('BIBSONOMY_INCLUDE_CSS', false);


/*
 * May echo some custom CSS. This comes in handy if you'd like to change the
 * style of the posts that you're including.
 */
function bibsonomy_get_custom_css() {
	// echo '';
}
?>