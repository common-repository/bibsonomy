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

# $Id: bibsonomy_def.php,v 1.4 2008-05-30 14:51:33 cschenk Exp $

# Common
define('BIBSONOMY_BASEURL', 'http://www.bibsonomy.org/');
define('BIBSONOMY_APIURL', BIBSONOMY_BASEURL.'api');
define('BIBSONOMY_CACHE_DIR', 'cache');
define('BIBSONOMY_CACHE_TTL', 86400);
define('DEBUG', false);
#define('DEBUG', true);

# cURL
define('HTTP_METHOD_GET', 'GET');
define('HTTP_METHOD_POST', 'POST');
define('HTTP_METHOD_PUT', 'PUT');
define('HTTP_METHOD_DELETE', 'DELETE');

# Groups
define('GROUP_PUBLIC', 'public');
define('GROUP_PRIVATE', 'private');
define('GROUP_FRIENDS', 'friends');

# Status flags
define('STATUS_UNDEF', -1);
define('STATUS_OK', 1);
define('STATUS_POST_ALREADY_EXISTS', 2);
define('STATUS_UNKNOWN', 255);

# Posts
define('DEFAULT_POSTS_START', 0);
define('DEFAULT_POSTS_END', 20);
define('DEFAULT_MAX_POSTS', DEFAULT_POSTS_END - DEFAULT_POSTS_START);

?>