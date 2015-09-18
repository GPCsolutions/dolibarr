<?php
/* Copyright (c) 2015 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * \file    htdocs/csp-parser.php
 * \ingroup core
 * \brief   Parse content security policy violation reports and issues them to logger
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined("NOLOGIN"))        define("NOLOGIN",'1');

require 'main.inc.php';

$data = file_get_contents('php://input');

$data = json_decode($data, True);

if ($data) {
	dol_syslog(
		"Content Security Policy Violation Report:\n" .
		"\tdocument-uri: " . $data['csp-report']['document-uri'] . "\n" .
		"\treferrer: " . $data['csp-report']['referrer'] . "\n" .
		"\tviolated-directive: " . $data['csp-report']['violated-directive'] . "\n" .
		"\toriginal-policy: " . $data['csp-report']['original-policy'] . "\n" .
		"\tblocked-uri: " . $data['csp-report']['blocked-uri'] . "\n",
		LOG_WARNING
	);
}

