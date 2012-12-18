#!/usr/bin/php
<?php
/* Copyright (C) 2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 *
 * Purchase prices import script for Dolibarr
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


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Global variables
$version = '0.1.0';
$error = 0;

// Include Dolibarr environment
require_once($path . "../../htdocs/master.inc.php");
// After this $db, $mysoc, $langs and $conf->entity are defined. Opened database handler will be closed at end of file.

require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.product.class.php");

@set_time_limit(0);  // No timeout for this script

function printLine($line)
{
	print "Line " . $line . ": ";
}

// TODO: infoline
print "***** " . $script_file . " (" . $version . ") *****\n";
if (! isset($argv[1])) { // Check parameters
	print "Usage: " . $script_file . " file.csv [username] [entity]\n";
	exit;
}
print 'Processing ' . $argv[1] . "\n";

// Parse arguments
if (! isset($argv[2])) {
	$username = 'admin';
} else {
	$username = $argv[2];
}
if (! isset($argv[3])) {
	$conf->entity = 1;
} else {
	$conf->entity = $argv[3];
}

// Load user and its permissions
$result = $user->fetch('', $username); // Load user for login 'admin'. Comment line to run as anonymous user.
if (! $result > 0) {
	dol_print_error('', $user->error . " " . $username);
	exit;
}
$user->getrights();
unset($result);

$fname = $argv[1];

// Start of transaction
$db->begin();

if (($handle = fopen($fname, 'r')) !== FALSE) {
	$line = 0; // Line counter
	while (($data = fgetcsv($handle)) !== FALSE) {
		$line++;
		if ($line == 1) {
	 		continue; // Ignores first line
			// TODO: Test that first line is what we expect
		}
		// TODO: search product
		// $product_ref = trim($data[0]);
		// TODO: search supplier
		// $supplier_list = explode(',', trim($data[1]));
		foreach ($supplier_list as $supplier_name) {
			// TODO: test if there's already a purchase price
			// TODO: if not, set the listed price
			// $purchase_price = trim($data[2]);
		}
	}
	fclose($handle);
} else {
	$error ++;
	print "Unable to access file <" . $fname . ">\n";
}

if ($error == 0) {
	$db->commit();
	print ($line - 1) . " prices imported\n";
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
