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

# $Id: bibsonomy_xml_helper.php,v 1.4 2008-05-14 15:55:22 cschenk Exp $

#
# Seems to be the only way to get a specific attribute of a SimpleXMLElement
#
function getAttribute($simpleXMLElement, $attributeName) {
	foreach($simpleXMLElement->attributes() as $key => $value) {
		if($key == $attributeName) {
			return (string)$value;
		}
	}
}

?>