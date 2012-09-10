#!/usr/bin/php
<?php
/* Copyright (C) 2007-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012 Cédric Salvador <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 *
 * Third parties import script for Dolibarr
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
 *      \brief     Import de tiers depuis un CSV
 *      \version   1.0.5
 *      \author    Cédric Salvador
 *      \author    Raphaël Doursenaud
 */
//TODO factorize code

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
// After this $db, $mysoc, $langs and $conf->entity are defined. Opened handler to database will be closed at end of file.

require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
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
	$categorie = 0; // Created categories counter
	while (($data = fgetcsv($handle)) !== FALSE) {
		$soc = new Societe($db);

		$line ++;
		if ($line == 1) {
			continue; // Ignores first line
			// TODO: Test that first line is what we expect
		}

		$soc->particulier = $data[0];
		if ($soc->particulier == 1) { // Si particulier
			$soc->name = empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION) ? trim($data[2] . ' ' . $data[1]) : trim($data[1] . ' ' . $data[2]);
			$soc->nom_particulier = $data[1];
			$soc->prenom = $data[2];
			$soc->civilite_id = $data[3];
		} else {
			$soc->name = $data[1];
		}
		$soc->nom = $soc->name; // TODO obsolete
		$soc->status = $data[4];
		$soc->client = $data[5];

		/*
		 * Code client
		 */
		if (isset($data[5])) {
			$soc->code_client = -1; //Automatically generates a code
		}

		$soc->fournisseur = $data[7];

		/*
		 * Code fournisseur
		 */
		if (isset($data[7])) {
			$soc->code_fournisseur = -1; //Automatically generates a code
		}

		$soc->address = $data[9];
		$soc->adresse = $soc->address; // TODO obsolete
		$soc->zip = $data[10];
		$soc->cp = $soc->zip; // TODO obsolete
		$soc->town = $data[11];
		$soc->ville = $soc->town; // TODO obsolete

		/*
		 * Code pays
		 */
		if ( ! empty($data[12])) {
			$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'c_pays where code="' . $data[12] . '"';
			$resql = $db->query($sql);
			unset($sql);
			if ($resql && ($resql->num_rows != 0)) {
				$res = $db->fetch_array($resql);
				$countryid = $res['rowid'];
				$soc->country_id = $countryid;
				$soc->pays_id = $soc->country_id; // TODO obsolete
				$db->free($resql);
				unset($res);
			} else {
				$error ++;
				printLine($line);
				print "The country code " . $data[12] . " is invalid\n";
			}
			unset($resql);
		}

		/*
		 * Code département
		 */
		if ( ! empty($data[13])) {
			$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'c_departements where code_departement="' . $data[13] . '"';
			$resql = $db->query($sql);
			unset($sql);
			if ($resql && ($resql->num_rows != 0)) {
				$res = $db->fetch_array($resql);
				$depid = $res['rowid'];
				$soc->state_id = $depid;
				$soc->departement_id = $soc->state_id; // TODO obsolete
				$db->free($resql);
				unset($res);
			} else {
				$error ++;
				printLine($line);
				print "The state code " . $data[13] . " is invalid\n";
			}
			unset($resql);
		}

		$soc->tel = $data[14];
		$soc->fax = $data[15];
		$soc->email = trim($data[16]);
		$soc->url = trim($data[17]);
		$soc->idprof1 = $data[18]; // Siren
		$soc->siren = $soc->idprof1; // TODO: deprecated
		$soc->idprof2 = $data[19]; //Siret
		$soc->siret = $soc->idprof2; // TODO: deprecated
		$soc->idprof3 = $data[20]; // NAF
		$soc->ape = $soc->idprof3; // TODO: deprecated
		$soc->idprof4 = $data[21]; // RCS
		$soc->tva_assuj = $data[22];
		$soc->tva_intra = $data[23];

		/*
		 * Type d'entreprise
		 */
		if ( ! empty($data[24]) && ( ! $soc->particulier)) {
			$sql = 'select id from ' . MAIN_DB_PREFIX . 'c_typent where code="' . $data[24] . '"';
			$resql = $db->query($sql);
			unset($sql);
			if ($resql && ($resql->num_rows != 0)) {
				$res = $db->fetch_array($resql);
				$entid = $res['rowid'];
				$soc->typent_id = $entid;
				$db->free($resql);
				unset($res);
			} else {
				$error ++;
				printLine($line);
				print "The company type code " . $data[24] . " is invalid\n";
			}
			unset($resql);
		} elseif ($soc->particulier) {
			$soc->typent_id = 8; // TODO predict another method if the field "special" change of rowid
		}

		/*
		 * Effectif
		 */
		//TESTME
		if ( ! empty($data[25])) {
			$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'c_effectif where code="' . $data[25] . '"';
			$resql = $db->query($sql);
			unset($sql);
			if ($resql && ($resql->num_rows != 0)) {
				$res = $db->fetch_array($resql);
				$effid = $res['rowid'];
				$soc->effectif_id = $effid;
				$db->free($resql);
				unset($res);
			} else {
				$error ++;
				printLine($line);
				print "The effectif code " . $data[25] . " is invalid\n";
			}
			unset($resql);
		}

		//TESTME
		$soc->forme_juridique_code = $data[26];

		$soc->capital = $data[27];
		$soc->gencod = $data[28];

		if ( ! empty($data[29])) {
			//TODO verify that the language code is available
			$soc->default_lang = $data[29];
		}

		/*
		  TODO: Taxes espagnoles
		  $soc->localtax1_assuj       = $data["localtax1assuj_value"];
		  $soc->localtax2_assuj       = $data["localtax2assuj_value"];
		  $soc->prefix_comm           = $data["prefix_comm"]; // Obsolete
		 */

		/*
		 * Commercial
		 */
		if ( ! empty($data[30])) {
			$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'user where login="' . $data[30] . '"';
			$resql = $db->query($sql);
			unset($sql);
			if ($resql && ($resql->num_rows != 0)) {
				$res = $db->fetch_array($resql);
				$comid = $res['rowid'];
				$soc->commercial_id = $comid;
				$db->free($resql);
				unset($res);
			} else {
				$error ++;
				printLine($line);
				print "The user login " . $data[30] . " is unknown\n";
			}
			unset($resql);
		}

		$soc->note = $data[31];

		/*
		 * Création
		 */
		if ($error == 0) {
			$result = $soc->create();
			if ($result >= 0) {
				/*
				 * Catégories clients
				 */
				// TODO: Gérer les catégories imbriquées
				if ( ! empty($data[6])) {
					$labels_client = $data[6];
					$labels_categories_client = array(); // Make sure that it's initialized
					$labels_categories_client = explode(',', $labels_client);

					foreach ($labels_categories_client as $labelcli) {
						$labelcli = trim($labelcli);
						$catcli = new Categorie($db);
						$catcli->label = $labelcli;
						$catcli->type = 2;
						if ( ! $catcli->already_exists()) {
							$catcli->import_key = $import_key;
							$result = $catcli->create();
							if ($result >= 0) {
								$categorie ++;
								// TOFIX_UPSTREAM
								// Import key is not populated by the class !
								// Let's do this manually
								$sql = "UPDATE " . MAIN_DB_PREFIX . "categorie SET import_key='" . $import_key . "' WHERE rowid=" . $catcli->id;
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
								print "Unable to create customer category\n";
							}
						} else {
							$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'categorie where label="' . $labelcli . '"';
							$resql = $db->query($sql);
							unset($sql);
							if ($resql && ($resql->num_rows != 0)) {
								// FIXME Vérifier unicité !!!
								$res = $db->fetch_array($resql);
								$catcliid = $res['rowid'];
								$catcli->fetch($catcliid);
								$db->free($resql);
								unset($res);
							} else {
								$error ++;
								printLine($line);
								print "Should never be there customer\n";
							}
							unset($resql);
						}
						$catcli->add_type($soc, 'societe');
					}
				}

				/*
				 * Catégories fournisseurs
				 */
				// TODO: Gérer les catégories imbriquées
				if ( ! empty($data[8])) {
					$labels_fourn = $data[8];
					$labels_categories_fourn = array();
					$labels_categories_fourn = explode(',', $labels_fourn);
					foreach ($labels_categories_fourn as $labelfourn) {
						$labelfourn = trim($labelfourn);
						$catfourn = new Categorie($db);
						$catfourn->label = $labelfourn;
						$catfourn->type = 1;
						if ( ! $catfourn->already_exists()) {
							$catfourn->import_key = $import_key;
							$result = $catfourn->create();
							if ($result >= 0) {
								$categorie ++;
								// TOFIX_UPSTREAM
								// Import key is not populated by the class !
								// Let's do this manually
								$sql = "UPDATE " . MAIN_DB_PREFIX . "categorie SET import_key='" . $import_key . "' WHERE rowid=" . $catfourn->id;
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
								print "Unable to create supplier category\n";
							}
						} else {
							$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'categorie where label="' . $labelfourn . '"';
							$resql = $db->query($sql);
							unset($sql);
							if ($resql && ($resql->num_rows != 0)) {
								// FIXME Vérifier unicité !!!
								$res = $db->fetch_array($resql);
								$catfournid = $res['rowid'];
								$catfourn->fetch($catfournid);
								$db->free($resql);
								unset($res);
							} else {
								$error ++;
								printLine($line);
								print "Should never be there supplier\n";
							}
							unset($resql);
						}
						$catfourn->add_type($soc, 'fournisseur');
					}
				}

				// Also create a contact if personnal people
				if ($soc->particulier) {
					$contact = new Contact($db);

					$contact->civilite_id = $soc->civilite_id;
					$contact->name = $soc->nom_particulier;
					$contact->firstname = $soc->prenom;
					$contact->address = $soc->address;
					$contact->zip = $soc->zip;
					$contact->cp = $soc->cp;
					$contact->town = $soc->town;
					$contact->ville = $soc->ville;
					$contact->fk_departement = $soc->departement_id;
					$contact->fk_pays = $soc->pays_id;
					$contact->socid = $soc->id;				   // fk_soc
					$contact->status = 1;
					$contact->email = $soc->email;
					$contact->phone_pro = $soc->tel;
					$contact->fax = $soc->fax;
					$contact->priv = 0;
					$contact->default_lang = $soc->default_lang;

					$result = $contact->create($user);
					if ($result >= 0) {
						// TOFIX_UPSTREAM
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
						print "Unable to create contact for personal people\n";
					}
				}

				// TOFIX_UPSTREAM
				// Notes are not populated by the class !
				// Let's do this manually
				if (isset($soc->note)) {
					$sql = "UPDATE " . MAIN_DB_PREFIX . "societe SET note='" . $db->escape($soc->note) . "' WHERE rowid=" . $soc->id;
					$resql = $db->query($sql);
					unset($sql);
					if ( ! $resql) {
						$error ++;
						printLine($line);
						print "Unable to append note\n";
					}
					unset($resql);
				}

				// TOFIX_UPSTREAM
				// Import key is not populated by the class !
				// Let's do this manually
				$sql = "UPDATE " . MAIN_DB_PREFIX . "societe SET import_key='" . $import_key . "' WHERE rowid=" . $soc->id;
				$resql = $db->query($sql);
				unset($sql);
				if ( ! $resql) {
					$error ++;
					printLine($line);
					print "Unable to set company import key\n";
				}
				unset($resql);
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
	print ($categorie) . " categories created\n";
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

$db->close(); // Close database opened handler

return $error;
