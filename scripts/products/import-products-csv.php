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
 *      \file      scripts/thirdparties/import-thirdparties-csv.php
 *      \brief     Third parties import from a CSV file
 *      \version   1.0.5
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
$version = '1.0.5';
$error = 0;

// Include Dolibarr environment
require_once($path . "../../htdocs/master.inc.php");
// After this $db, $mysoc, $langs and $conf->entity are defined. Opened database handler will be closed at end of file.

require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/translate.class.php");

@set_time_limit(0);	 // No timeout for this script

function printLine($line)
{
	print "Line " . $line . ": ";
}

// TODO: infoline
print "***** " . $script_file . " (" . $version . ") *****\n";
if ( ! isset($argv[1])) { // Check parameters
	print "Usage: " . $script_file . " file.csv [username]\n";
	exit;
}
print 'Processing ' . $argv[1] . "\n";

// Load user and its permissions
if ( ! isset($argv[2])) {
	$username = 'admin';
} else {
	$username = $argv[2];
}
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
	$categories = 0; // Created categories counter
	while (($data = fgetcsv($handle)) !== FALSE) {
		$prod = new Product($db);

		$line ++;
		if ($line == 1) {
			continue; // Ignores first line
			// TODO: Test that first line is what we expect
		}

		//TODO: Check for duplicates
		$prod->ref = $data[0];
		$prod->label = $data[1];
		$prod->libelle = $prod->label; // TODO: deprecated
		$prod->description = $data[2];
		$prod->accountancy_code_sell = $data[3];
		$prod->accountancy_code_buy = $data[4];
		$prod->note = $data[5];
		$prod->lenght = $data[6];
		$prod->surface = $data[7];
		$prod->volume = $data[8];
		$prod->weight = $data[9];
		$prod->duration = $data[10];
		$prod->customcode = $data[11];
		$prod->price = $data[12];
		$prod->price_ttc = $data[13];
		$prod->tva_tx = $data[14];
		$prod->status = $data[15];
		$prod->status_buy = $data[16];
		$prod->type = $data[17];
		$prod->finished = $data[18];

		// TODO: add extrafields support

		/*
		 * Creation
		 */
		if ($error == 0) {
			$result = $prod->create($user);
			if ($result >= 0) {
				/*
				 * Product category
				 */
				// TODO: support nested categories
				if ( ! empty($data[19])) {
					$labels_product = $data[19];
					$labels_categories_product = array(); // Make sure that it's initialized
					$labels_categories_product = explode(',', $labels_product);

					foreach ($labels_categories_product as $labelprod) {
						$labelprod = trim($labelprod);
						$catprod = new Categorie($db);
						$catprod->label = $labelprod;
						$catprod->type = 0; // Product
						if ( ! $catprod->already_exists()) {
							$catprod->import_key = $import_key;
							$result = $catprod->create();
							if ($result >= 0) {
								$categories ++;
								// FIXME: upstream
								// Import key is not populated by the class !
								// Let's do this manually
								$sql = "UPDATE " . MAIN_DB_PREFIX . "categorie SET import_key='" . $import_key . "' WHERE rowid=" . $catprod->id;
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
								print "Unable to create product category\n";
							}
						} else {
							$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'categorie where label="' . $labelprod . '"';
							$resql = $db->query($sql);
							unset($sql);
							if ($resql && ($resql->num_rows != 0)) {
								// FIXME: Check unicity !!!
								$res = $db->fetch_array($resql);
								$catprodid = $res['rowid'];
								$catprod->fetch($catprodid);
								$db->free($resql);
								unset($res);
							} else {
								$error ++;
								printLine($line);
								print "Should never be there\n";
							}
							unset($resql);
						}
						$catprod->add_type($prod, 'product');
					}
				}

				// FIXME: upstream
				// Import key is not populated by the class !
				// Let's do this manually
				$sql = "UPDATE " . MAIN_DB_PREFIX . "product SET import_key='" . $import_key . "' WHERE rowid=" . $prod->id;
				$resql = $db->query($sql);
				unset($sql);
				if ( ! $resql) {
					$error ++;
					printLine($line);
					print "Unable to set product import key\n";
				}
				unset($resql);
			} else {
				$error ++;
				print "Unable to import product. A field might be malformed.\n";
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
	print ($categories) . " categories created\n";
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
