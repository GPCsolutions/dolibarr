#!/usr/bin/php
<?php
/* Copyright (C) 2007-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012 Cédric Salvador <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 *
 * Products import script for Dolibarr
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file      scripts/categories/import-categories-csv.php
 *      \brief     Third parties import from a CSV file
 *      \version   1.1.0
 *      \author    Cédric Salvador
 *      \author    Raphaël Doursenaud
 */
//TODO: factorize code

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Global variables
$version = '1.1.0';
$error = 0;

// Include Dolibarr environment
require_once($path . "../../htdocs/master.inc.php");
// After this $db, $mysoc, $langs and $conf->entity are defined. Opened database handler will be closed at end of file.

require_once(DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php");

@set_time_limit(0);  // No timeout for this script

function printLine($line)
{
	print "Line " . $line . ": ";
}

// TODO: infoline
print "***** " . $script_file . " (" . $version . ") *****\n";
if ( ! isset($argv[1])) { // Check parameters
	print "Usage: " . $script_file . " file.csv [username] [entity]\n";
	exit;
}
print 'Processing ' . $argv[1] . "\n";

// Parse arguments
if ( ! isset($argv[2])) {
	$username = 'admin';
} else {
	$username = $argv[2];
}
if ( ! isset($argv[3])) {
	$conf->entity = 1;
} else {
	$conf->entity = $argv[3];
}

// Load user and its permissions
$result = $user->fetch('', $username); // Load user for login 'admin'. Comment line to run as anonymous user.
if ( ! $result > 0) {
	dol_print_error('', $user->error . " " . $username);
	exit;
}
$user->getrights();
unset($result);

$import_key = dol_print_date(dol_now(), '%Y%m%d%H%M%S');

$fname = $argv[1];

// Start of transaction
$db->begin();

if (($handle = fopen($fname, 'r')) !== FALSE) {
	$line = 0; // Line counter
	while (($data = fgetcsv($handle)) !== FALSE) {
		$cat = new Categorie($db);

		$line ++;
		if ($line == 1) {
			continue; // Ignores first line
			// TODO: Test that first line is what we expect
		}

		//TODO: Check for duplicates
		// Check required fields
		if ( ! strlen($data[0])) {
			$required = "type";
		} elseif ( ! strlen($data[1])) {
			$required = "reference";

		}
		if ($required) {
			$error ++;
			print "The " . $required . " field is required\n";
			continue; // Exit to throw error
		}

		if ($error == 0) {
			$cat->type = $data[0];
			$cat->label = trim($data[1]);
			$cat->description = trim($data[2]);
			$cat->import_key = $import_key;
			
			/*
			 * Creation
			 */
			$result = $cat->create($user);
			if ($result >= 0) {
				// FIXME: upstream
				// Import key is not populated by the class !
				// Let's do this manually
				$sql = "UPDATE " . MAIN_DB_PREFIX . "categorie SET import_key='" . $import_key . "' WHERE rowid=" . $cat->id;
				$resql = $db->query($sql);
				unset($sql);
				if ( ! $resql) {
					$error ++;
					printLine($line);
					print "Unable to set category import key\n";
				}
				unset($resql);
			} else {
				$error ++;
				printLine($line);
				print "Unable to create category\n";
			}
		} else {
			break;
			$error ++;
			print "Unknow error occured\n";
		}
	}
	fclose($handle);
} else {
	$error ++;
	print "Unable to access file <" . $fname . ">\n";
}

if ($error == 0) {
	$db->commit();
	print ($line - 1) . " records imported\n";
	print "Import key is " . $import_key . "\n";
	print "Import complete\n";
} else {
	print "Error " . $error . "\n";
	print "Import aborted";
	if (isset($line)) {
		print " at line " . $line;
	}
	print "\n";
	$db->rollback();
}

$db->close(); // Close database handler

return $error;
