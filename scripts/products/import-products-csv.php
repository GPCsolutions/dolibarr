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

require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/extrafields.class.php");
require_once(DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php");

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

define('STD_COLS_NB', '21'); // Number of columns for standard fields
$data_cols_nb = 21; // Total number of columns
$extra_fields = False;

// Start of transaction
$db->begin();

if (($handle = fopen($fname, 'r')) !== FALSE) {
	$line = 0; // Line counter
	$categories = 0; // Created categories counter
	while (($data = fgetcsv($handle)) !== FALSE) {
		$prod = new Product($db);

		$line ++;
		if ($line == 1) {
			$data_cols_nb = count($data);
			// Is there any extra field?
			if ($data_cols_nb > STD_COLS_NB) {
				// Extra fields found
				$extra_fields = True;
				// Let's get the existing options
				$extra = new ExtraFields($db);
				$extra_options = array_keys($extra->fetch_name_optionals_label('product'));
				unset($extra);
				// Let's get the options present in the file
				$i = STD_COLS_NB;
				$extra_data = array();
				while ($i < $data_cols_nb) {
					// Is the option allowed?
					if (in_array($data[$i], $extra_options, true)) {
						$extra_data[$i] = $data[$i];
					} else {
						$error ++;
						print "Unknown extra field\n";
						continue(2); // Exit to throw error
					}
					$i ++;
				}
			}
			continue; // Ignores first line
			// TODO: Test that first line is what we expect
		}

		//TODO: Check for duplicates
		// Check required fields
		if ( ! strlen($data[0])) {
			$required = "reference";
		} elseif ( ! strlen($data[1])) {
			$required = "label";
		} elseif ( ! strlen($data[14])) {
			$required = "sellable";
		} elseif ( ! strlen($data[15])) {
			$required = "buyable";
		} elseif ( ! strlen($data[16])) {
			$required = "type";
		}
		if ($required) {
			$error ++;
			print "The " . $required . " field is required\n";
			continue; // Exit to throw error
		}

		if ($error == 0) {
			$prod->ref = $data[0];
			$prod->label = $data[1];
			$prod->libelle = $prod->label; // TODO: deprecated
			$prod->description = $data[2];
			$prod->accountancy_code_sell = $data[3];
			$prod->accountancy_code_buy = $data[4];
			$prod->note = $data[5];
			$prod->type = $data[16];
			if ($prod->type == 1) {
				// FIXME: units handling
				$prod->length = $data[6];
				$prod->surface = $data[7];
				$prod->volume = $data[8];
				$prod->weight = $data[9];
				$prod->finished = $data[17];
			} elseif ($prod->type == 2) {
				// FIXME: units handling
				$prod->duration_value = $data[10];
			}
			$prod->customcode = $data[11];
			$prod->price = $data[12];
			$prod->tva_tx = $data[13];
			$prod->status = $data[14];
			$prod->status_buy = $data[15];

			/*
			 * Extrafields
			 */
			if ($extra_fields) {
				$i = STD_COLS_NB;
				while ($i < $data_cols_nb) {
					// We add options_ before the key name because the code expects this form!
					$array_options = array('options_' . $extra_data[$i] => $data[$i]);
					$prod->array_options = $array_options;
					$prod->insertExtraFields();
					$i ++;
				}
			}

			/*
			 * Creation
			 */
			$result = $prod->create($user);
			if ($result >= 0) {
				/*
				 * Stock
				 */
				if ( ! empty($data[18])) {
					$stock_qty = $data[18];
					// TODO: check there is a warehouse and create one if there's none
					// Uses first warehouse from entity
					$warehouse = new Entrepot($db);
					$warehouse_list = $warehouse->list_array();
					$warehouse_ids = array_keys($warehouse_list);					
					$result = $prod->correct_stock($user, $warehouse_id[0], $stock_qty, 0, $import_key);
					if ($result <= 0) {
						$error ++;
						printLine($line);
						print "Unable to add product to stock, make sure you have a warehouse!\n";
					}
				}
				/*
				 * Supplier
				 */
				if ( ! empty($data[19])) {
					$suppliers_list = explode(',', $data[19]);
					foreach ($suppliers_list as $supplier) {
						// TODO: Create a new supplier if it doesn't exist
						$supplier = trim($supplier);
						$sql = "SELECT s.rowid";
						$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
						$sql .= " WHERE s.entity IN (" . $conf->entity . ")";
						$sql .= " AND s.nom LIKE '%" . $db->escape($supplier) . "%'";
						$resql = $db->query($sql);
						unset($sql);
						if ($resql && ($resql->num_rows != 0)) {
							// FIXME: Check unicity !!!
							$res = $db->fetch_array($resql);
							$id_fourn = $res['rowid'];
							$prod->add_fournisseur($user, $id_fourn, $data[0], 1);
						} else {
							$error ++;
							printLine($line);
							print "Unable to find supplier " . $supplier . "\n";
						}
						unset($resql);
					}
				}

				/*
				 * Product category
				 */
				// TODO: support nested categories
				// TODO: support cagories references
				if ( ! empty($data[20])) {
					$labels_product = $data[20];
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
							$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'categorie WHERE label="' . $labelprod . '" AND entity IN (' . $conf->entity . ')';
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
