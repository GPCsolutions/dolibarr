#!/usr/bin/php
<?php
/*
 * Tous les fournisseurs sur tous les produits pour Dolibarr
 * Copyright (C) 2012 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
$path=dirname(__FILE__).'/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer ce script en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

echo "ATTENTION : Ce script va affecter TOUS les fournisseurs à TOUS les produits.\nIl va aussi réinitialiser tous les prix d'achat à 0 !\nASSUREZ-VOUS D'AVOIR UNE SAUVEGARDE DE LA BASE AVANT DE COMMENCER\nÊtes-vous prêt ?\n Saisissez 'Oui' pour continuer : ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(trim($line) != 'Oui'){
    echo "ABORTING!\n";
    exit;
}

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.product.class.php");

// Default values
$price = 0;
$quantity = 1;
$price_base_type = "HT";

$result = $user->fetch('','gpc');
if (! $result > 0) { dol_print_error('',$user->error); exit; }
$user->getrights();

$db->begin();

// Récupération de la liste des fournisseurs
$fournisseur = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur = 1";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$fournisseur[$i] = $row[0];
		$i++;
	}
}
else
{
	echo "ERREUR: Aucun fournisseur trouvé\n";
}

$sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."product";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$ref_fourn = $row[1];
		$product = new ProductFournisseur($db);
		$result = $product->fetch($row[0]);
		if ($result > 0)
		{
			$error=0;
			
			foreach ($fournisseur as $id_fourn)
			{
				$ret = $product->add_fournisseur($user, $id_fourn, $ref_fourn);
				if ($ret < 0)
				{
					$error++;
					echo "ERREUR : Ajout du fournisseur ".$idfourn." impossible sur le produit ".$product->id."\n";
				}
				
				// Il est nécessaire de fixer un prix d'achat !
				$supplier = new Fournisseur($db);
				$result = $supplier->fetch($id_fourn);
				$ret = $product->update_buyprice($quantity, $price, $user, $price_base_type, $supplier);
				if ($ret < 0)
				{
					$error++;
					echo "ERREUR : Ajout du prix d'achat impossible pour le fournisseur ".$idfourn." sur le produit ".$product->id."\n";
				}
				if (! $error)
				{
					$db->commit();
				}
				else
				{
					$db->rollback();
				}
			}
		}
		$i++;
	}
}
else
{
	echo "ERREUR: Aucun produit trouvé\n";
}

?>
