<?php

/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \file       htdocs/detailedStock/core/triggers/interface_25_modDetailledStock_Command.class.php
 *  \ingroup    detailedStock
 *  \brief      
 */
require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT . "/detailedstock/class/productstockdet.class.php");

/**
 *  Class of triggers for demo module
 */
class InterfaceCommand
{

  var $db;

  /**
   *   Constructor
   *
   *   @param		DoliDB		$db      Database handler
   */
  function InterfaceCommand($db)
  {
    $this->db = $db;

    $this->name = preg_replace('/^Interface/i', '', get_class($this));
    $this->family = "detailedStock";
    $this->description = "when it's done";
    $this->version = 'development';            // 'development', 'experimental', 'dolibarr' or version
    $this->picto = 'technic';
  }

  /**
   *   Return name of trigger file
   *
   *   @return     string      Name of trigger file
   */
  function getName()
  {
    return $this->name;
  }

  /**
   *   Return description of trigger file
   *
   *   @return     string      Description of trigger file
   */
  function getDesc()
  {
    return $this->description;
  }

  /**
   *   Return version of trigger file
   *
   *   @return     string      Version of trigger file
   */
  function getVersion()
  {
    global $langs;
    $langs->load("admin");

    if ($this->version == 'development') return $langs->trans("Development");
    elseif ($this->version == 'experimental') return $langs->trans("Experimental");
    elseif ($this->version == 'dolibarr') return DOL_VERSION;
    elseif ($this->version) return $this->version;
    else return $langs->trans("Unknown");
  }

  /**
   *      Function called when a Dolibarrr business event is done.
   *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
   *
   *      @param	string		$action		Event action code
   *      @param  Object		$object     Object
   *      @param  User		$user       Object user
   *      @param  Translate	$langs      Object langs
   *      @param  conf		$conf       Object conf
   *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
   */
  function run_trigger($action, $object, $user, $langs, $conf)
  {
    // Put here code you want to execute when a Dolibarr business events occurs.
    // Data and type of action are stored into $object and $action
    // Users
    if ($action == 'DISPATCH_PRODUCT') {
      $langs->load('detailedStock@detailedstock');
      $form = new Form($this->db);
      llxHeader();
      dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
      print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" method="post"><table class="noborder" width="100%">';
      print '<input type="hidden" name="action" value="create"/>';
      for ($i=0; $i<$object->qtyinfo;$i++) {
        $newDet = new Productstockdet($this->db);
        $newDet->tms_i = dol_now();
        $newDet->price = price2num($object->priceinfo,'MT');
        //$newDet->price = price2num($line->total_ht / $line->qty, 'MT');
        $newDet->fk_command_line = $object->id;
        $newDet->fk_user_author_i = $user->id;
        //$newDet->fk_product = $line->fk_product;
        $newDet->fk_product = $object->produitinfo;
        $newDet->fk_supplier = $object->fourn_id;
        $newDet->fk_entrepot = $object->entrepotinfo;
        print '<tr class="liste_titre"><td>' . $langs->trans("SerialType") . '</td>';
        print '<td>' . $langs->trans("SerialNumber") . '</td>';
        print '<td>' . $langs->trans("Supplier") . '</td>';
        print '<td>' . $langs->trans("BuyingPrice") . '</td>';
        print '<td>' . $langs->trans("Warehouse") . '</td>';
        print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

        print '<tr>';
        print '<td>' . $newDet->selectSerialType($newDet->fk_serial_type, 'serialType') . '</td>';
        print '<td><input type="text" name="serialNumber" value="' . $newDet->serial . '"/></td>';
        print '<td>' . $form->select_company($newDet->fk_supplier, 'supplier', 's.fournisseur=1') . '</td>';
        print '<td><input type="text" name="buyingPrice" value="' . $newDet->price . '"/></td>';
        print '<td>' . $newDet->selectWarehouses($newDet->fk_entrepot, 'warehouse', 'ps.reel > 0', 0, 0,
                $newDet->fk_product) . '</td>';
        print '</tr>';
      }
      print '<tr><td><input type="submit" name ="valid" value="' . $langs->trans("Valid") . '"/></td><td><input type="submit" name="cancel" value="' . $langs->trans("Cancel") . '"/></td></tr>';
      print '</table></form>';
      //return 1;
      llxFooter();
      exit(); //DEBUG
    }


    //return 0;
  }

}

?>
