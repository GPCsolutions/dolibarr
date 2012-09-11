#!/usr/bin/php
<?php
/* Copyright (C) 2007-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012 Cédric Salvador <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 *
 * Contacts import script for Dolibarr
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
 *      \file      scripts/thirdparties/import-contacts-csv.php
 *      \brief     Contacts import from a CSV file
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

require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");

@set_time_limit(0);  // No timeout for this script

function printLine($line)
{
	print "Line " . $line . ": ";
}

// TODO: infoline
print "***** " . $script_file . " (" . $version . ") *****\n";
if ( ! isset($argv[1])) { // Check parameters
	print "Usage: " . $script_file . " file.csv [username]...\n";
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

$import_key = dol_print_date(dol_now(), '%Y%m%d%H%M%S');

$fname = $argv[1];

// Start of transaction
$db->begin();

if (($handle = fopen($fname, 'r')) !== FALSE) {
	// TODO: merge id and nomSociete into an associative object because they are interdependent
	$nomSociete = NULL; // Last company name used to avoid useless SQL queries
	$socid = 0; // Refering company id
	$line = 0; // CSV lines counter
	$ignored = 0; // ignored records counter
	while (($data = fgetcsv($handle)) !== FALSE) {
		$contact = new Contact($db);

		$line ++;
		if ($line == 1) {
			continue; // Ignores first line
			// TODO: Test that first line is what we expect
		}

		$contact->lastname = trim($data[0]);
		$contact->name = $contact->lastname; // TODO: deprecated
		$contact->firstname = trim($data[1]);

		/*
		 * Duplicates check
		 */
		$sql = 'select fk_soc from ' . MAIN_DB_PREFIX . 'socpeople where ';
		$sql .='name="' . $contact->lastname . '" and firstname="' . $contact->firstname . '"';
		$resql = $db->query($sql);
		// TODO: test return before continuing
		unset($sql);
		while ($res = $db->fetch_array($resql)) {
			$fk_soc = $res['fk_soc'];

			if ($fk_soc) {
				$soc = new Societe($db);
				$soc->fetch($fk_soc);
				if ($soc->name == trim($data[2])) {
					$ignored ++;
					printLine($line);
					print "this record already exists\n";
					$db->free($resql);
					unset($res);
					unset($resql);
					continue 2;
				}
			}
		}
		$db->free($resql);
		unset($res);
		unset($resql);

		/*
		 * Linked company
		 */
		if ( ! empty($data[2])) {
			if ($nomSociete != trim($data[2])) { // Company not already seen
				$nomSociete = trim($data[2]);
				$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'societe where nom="' . $nomSociete . '"';
				$resql = $db->query($sql);
				unset($sql);
				if ($resql && ($resql->num_rows > 0)) {
					// FIXME: Check unicity
					$res = $db->fetch_array($resql);
					$socid = $res['rowid'];
					$db->free($resql);
					unset($res);
				} else {
					$error ++;
					printLine($line);
					print "The name " . $data[2] . " is invalid\n";
				}
				unset($resql);
			}
			$contact->socid = $socid;
		} else {
			$error ++;
			printLine($line);
			print "Company name is mandatory\n";
		}

		$contact->civilite_id = $data[3];
		$contact->poste = $data[4];
		$contact->address = $data[5];
		$contact->zip = $data[6];
		$contact->town = $data[7];

		/*
		 * Coutry
		 */
		if ( ! empty($data[8])) {
			$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'c_pays where code="' . $data[8] . '"';
			$resql = $db->query($sql);
			unset($sql);
			if ($resql && ($resql->num_rows != 0)) {
				$res = $db->fetch_array($resql);
				$countryid = $res['rowid'];
				$contact->country_id = $countryid;
				$contact->fk_pays = $contact->country_id;
				$db->free($resql);
				unset($res);
				unset($countryid);
			} else {
				$error ++;
				printLine($line);
				print "The country code " . $data[8] . " is invalid\n";
			}
			unset($resql);
		}

		/*
		 * State
		 */
		if ( ! empty($data[9])) {
			$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'c_departements where code_departement="' . $data[9] . '"';
			$resql = $db->query($sql);
			unset($sql);
			if ($resql && ($resql->num_rows != 0)) {
				$res = $db->fetch_array($resql);
				$depid = $res['rowid'];
				$contact->state_id = $depid;
				$contact->fk_departement = $contact->state_id; // TODO: deprecated
				$db->free($resql);
				unset($res);
				unset($depid);
			} else {
				$error ++;
				printLine($line);
				print "The state code " . $data[9] . " is invalid\n";
			}
			unset($resql);
		}

		$contact->phone_pro = $data[10];
		$contact->phone_perso = $data[11];
		$contact->phone_mobile = $data[12];
		$contact->fax = $data[13];
		$contact->email = $data[14];
		$contact->jabberid = $data[15];
		$contact->priv = $data[16];
		$contact->note = $data[17];

		/*
		 * Date of birth
		 */
		if ( ! empty($data[18])) {
			$date = explode('/', $data[18]);
			$day = $date[2];
			$month = $date[1];
			$year = $date[0];
			$contact->birthday = dol_mktime(0, 0, 0, $month, $day, $year);
		}
		$contact->birthday_alert = $data[19];

		if ( ! empty($data[20])) {
			//TODO: check that the language code is available
			$contact->default_lang = $data[20];
		}

		/*
		 * Creation
		 */
		if ($error == 0) {
			$result = $contact->create($user);
			if ($result >= 0) {
				// FIXME: upstream
				// Import key is not populated by the class !
				// Let's do this manually
				$sql = "UPDATE " . MAIN_DB_PREFIX . "socpeople SET import_key='" . $import_key . "' WHERE rowid=" . $contact->id;
				$resql = $db->query($sql);
				unset($sql);
				if ( ! $resql) {
					$error ++;
					printLine($line);
					print "Unable to set import key\n";
				}
				unset($resql);
			} else {
				$error ++;
				printLine($line);
				print "Unable to create contact. A field might be malformed.\n";
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
	print ($line - 1 - $ignored) . " records imported\n";
	print $ignored . " records ignored\n";
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
