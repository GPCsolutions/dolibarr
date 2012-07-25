<?php
/*
 * Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT . "/detailedstock/class/productstockdet.class.php");
require_once(DOL_DOCUMENT_ROOT . "/detailedstock/class/serialtype.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/detailedstock/class/commandefournisseurdispatch.class.php");
global $langs, $user;
$langs->load("products");
$langs->load("orders");
$langs->load("bills");
$langs->load("stocks");
$langs->load("detailedStock@detailedstock");

$id = GETPOST('id');
$action = GETPOST('action');
$commandid = GETPOST('commandid');
$suppid = GETPOST('supplierid');
$reste = GETPOST('reste');

if ($id) {
    $dispatchline = new Commandefournisseurdispatch($db);
    $dispatchline->fetch($id);
    //after the user hit the valid button
    if ($action == 'create') {
        if (GETPOST('valid') == $langs->trans('Valid') || GETPOST('valid') == $langs->trans('Next')) {
            $newDet = new Productstockdet($db);
            //create a new detailled stock object with the input parameters
            $newDet->tms_i = dol_now();
            $newDet->fk_product = $dispatchline->fk_product;
            $newDet->fk_entrepot = $dispatchline->fk_entrepot;
            $newDet->fk_user_author_i = $user->id;
            if (GETPOST('serialNumber') != '') $newDet->serial = GETPOST('serialNumber');
            if (GETPOST('serialType') >= 0) $newDet->fk_serial_type = GETPOST('serialType');
            //calculer le prix d'après la ligne de dispatch
            $newDet->price = price2num(GETPOST('price'), 'MT');
            $newDet->fk_supplier = $suppid;
            $newDet->fk_dispatch_line = $id;
            //default $valid value is 1 in case we don't need to go through the validation process
            $valid = 1;
            //if a serial type is defined
            if ($newDet->fk_serial_type) {
                //get the appropriate serial type object
                $serial = new Serialtype($db);
                if ($serial->fetch($newDet->fk_serial_type)) {
                    //if the serial is active, check if it's valid
                    if ($serial->active) {
                        $valid = $serial->validate($newDet->serial);
                    }
                } else {
                    //error
                }
            }
            //if nothing went wrong, the object is saved in the database
            if ($valid) {
                $newid = $newDet->create($user);
                $reste --;
                unset($action);
                if ($reste > 0) {
                    $action = 'add';
                    $mesg = '<div class="ok">' . $langs->trans('DetailedLineAdded') . '</div>';
                }
            } else {
                //else, prepare the error message and go back to add mode
                $mesg = '<div class="error">' . $langs->trans('InvalidCode') . '</div>';
                $action = 'add';
            }
        }
        unset($_POST['action']);
        unset($_POST['warehouse']);
        unset($_POST['serialNumber']);
        unset($_POST['serialType']);
        unset($_POST['buyingPrice']);
        unset($_POST['commandid']);
        if ($action != 'add') Header('Location: ../fourn/commande/dispatch.php?id=' . $commandid);
    }

    /*
     * View
     */

    llxHeader();
//display the error message when there's one
    dol_htmloutput_mesg($mesg);

    if ($action == 'add') {
        $det = new Productstockdet($db);
        $form = new Form($db);
        print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="post"><table class="noborder" width="100%">';
        print '<input type="hidden" name="action" value="create"/>';
        print '<input type="hidden" name="commandid" value="' . $commandid . '"/>';
        print '<input type="hidden" name="supplierid" value="' . $suppid . '"/>';
        print '<input type="hidden" name="reste" value="' . $reste . '"/>';
        print '<tr class="liste_titre"><td>' . $langs->trans("SerialType") . '</td>';
        print '<td>' . $langs->trans("SerialNumber") . '</td>';
        print '<td>' . $langs->trans("Supplier") . '</td>';
        print '<td>' . $langs->trans("BuyingPrice") . '</td>';
        print '<td>' . $langs->trans("Warehouse") . '</td>';
        print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

        print '<tr>';
        print '<td>' . $det->selectSerialType($newDet->fk_serial_type, 'serialType') . '</td>';
        print '<td><input type="text" name="serialNumber" value="' . $newDet->serial . '"/></td>';
        $supplier = '';
        $soc = new Societe($db);
        $infosoc = $soc->fetch($suppid);
        if ($infosoc) {
            $supplier = $soc->getNomUrl();
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
        }
        print '<td>' . $supplier . '</td>';
        $prod = new Product($db);
        $prod->fetch($dispatchline->fk_product);
        $price = $prod->price;
        print '<td>' . price($price) . '</td>';
        print '<input type="hidden" name="price" value="' . $price . '"/>';
        $warehouse = '';
        $ware = new Entrepot($db);
        $wareinfo = $ware->fetch($dispatchline->fk_entrepot);
        if ($wareinfo) {
            $warehouse = $ware->getNomUrl();
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
        }
        print '<td>' . $warehouse . '</td>';
        if ($reste > 1) {
            $label = $langs->trans("Next");
        } else {
            $label = $langs->trans("Valid");
        }
        print '<td><input class = "button" type="submit" name ="valid" value="' . $label . '"/></td><td><input class ="button" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '"/></td>';
        print '</tr>';
        print '</table></form>';
    }
}

?>
