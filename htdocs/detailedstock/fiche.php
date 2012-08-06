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
global $langs, $user, $conf;
$langs->load("errors");
$langs->load("products");
$langs->load("orders");
$langs->load("bills");
$langs->load("stocks");
$langs->load("detailedStock@detailedstock");

if (!$conf->global->MAIN_MODULE_DETAILEDSTOCK) accessforbidden();

/*
 * View
 */
$action = GETPOST('action');
$id = GETPOST('id');
$confirm = GETPOST('confirm');

if ($action == 'delete' && GETPOST('confirm') == 'yes') {
	$det = new productstockdet($db);
    $det->fetch($id);
	$product = new Product($db);
    $product->fetch($det->fk_product);
    $det->delete($user);
    header('Location:detail.php?id=' . $product->id);
    exit();
}

if($action=='update' && GETPOST('valid') == $langs->trans('Valid')){
	$det = new Productstockdet($db);
	$det->fetch($id);
	$det->fk_entrepot = GETPOST('warehouse');
	$det->serial = GETPOST('serialNumber');
	$det->fk_serial_type = GETPOST('serialType');
	$det->price = price2num(GETPOST('buyingPrice'), 'MT');
	$det->fk_supplier = GETPOST('supplier');
	//default $valid value is 1 in case we don't need to go through the validation process
	$valid = 1;
	//if a serial type is defined
	if ($det->fk_serial_type) {
		//get the appropriate serial type object
		$serial = new Serialtype($db);
		if ($serial->fetch($det->fk_serial_type)) {
			//if the serial is active, check if it's valid
			if ($serial->active) {
				$valid = $serial->validate($det->serial);
			}
		} else {
			//error
		}
	}
	//if nothing went wrong, the object is saved in the database
	if ($valid) {
		$id = $det->update($user);
		unset($action);
		unset($confirm);
	} else {
		//else, prepare the error message and go back to update mode
		$mesg = '<div class="error">' . $langs->trans('InvalidCode') . '</div>';
		$action = 'modify';
		$confirm = 'yes';
	}
	unset($_POST['action']);
	unset($_POST['warehouse']);
	unset($_POST['serialNumber']);
	unset($_POST['serialType']);
	unset($_POST['buyingPrice']);
	unset($_POST['supplier']);
}

llxHeader();
dol_htmloutput_mesg($mesg);

if ($id && $action != 'modify') {
    $det = new Productstockdet($db);
    $form = new Form($db);
    $result = $det->fetch($_GET["id"]);
    if ($result > 0) {
        dol_fiche_head('', 'info', $langs->trans('DetailedStock'), 1, 'product');
        $product = new Product($db);
        $product->fetch($det->fk_product);
        print '<div class="tabBar">';
        print '<table class="border" width="100%">';
        //id
        print '<tr>';
        print '<td>ID</td>';
        $det->ref = $det->id;
        print '<td>' . $form->showrefnav($det, 'id', '', 1, 'rowid', 'id') . /* $det->id. */'</td>';
        print '</tr>';

        // Label
        print '<tr><td>' . $langs->trans("Label") . '</td><td>' . $product->libelle . '</td>';
        print '</tr>';

        //serial number
        print '<tr>';
        print '<td>' . $langs->trans('SerialNumber') . '</td>';
        print '<td>' . $det->serial . '</td>';
        print '</tr>';

        //serial type
        print '<tr>';
        print '<td>' . $langs->trans('SerialType') . '</td>';
        print '<td>' . $det->getSerialTypeLabel() . '</td>';
        print '</tr>';

        //supplier
        print '<tr>';
        print '<td>' . $langs->trans('Supplier') . '</td>';
        $supplier = '';
        $soc = new Societe($db);
        $infosoc = $soc->fetch($det->fk_supplier);
        if ($infosoc) {
            $supplier = $soc->getNomUrl();
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
        }
        print '<td>' . $supplier . '</td>';
        print '</tr>';

        //buying price
        print '<tr>';
        print '<td>' . $langs->trans('BuyingPrice') . '</td>';
        print '<td>' . price($det->price) . ' HT</td>';
        print '</tr>';

        //warehouse
        print '<tr>';
        print '<td>' . $langs->trans('Warehouse') . '</td>';
        $warehouse = '';
        $ware = new Entrepot($db);
        $wareinfo = $ware->fetch($det->fk_entrepot);
        if ($wareinfo) {
            $warehouse = $ware->getNomUrl();
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
        }
        print '<td>' . $warehouse . '</td>';
        print '</tr>';

        //input infos
        print '<tr>';
        print '<td>' . $langs->trans('InputInfos') . '</td>';
        print '<td>' . $det->getInfos('input') . '</td>';
        print '</tr>';

        //output infos
        //only print this if the element has been removed from the stock
        if ($det->tms_o) {
            print '<tr>';
			$suppr = '<span class="butActionRefused" title="'.$langs->trans("AlreadySold").'">'.$langs->trans('Delete').'</span>';
			$modify = '<span class="butActionRefused" title="'.$langs->trans("AlreadySold").'">'.$langs->trans('Modify').'</span>';
            print '<td>' . $langs->trans('OutputInfos') . '</td>';
            print '<td>' . $det->getInfos('output') . '</td>';
            print '</tr>';
        }
		else{
			$suppr = '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$det->id.'&action=ask_delete">'.$langs->trans('Delete').'</a>';
			$modify = '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$det->id.'&action=ask_modify">'.$langs->trans('Modify').'</a>';
		}

        print '</table></div>';
        print '<table width="100%"><tr><td align="right">'.$modify.$suppr.'<a class="butAction" href="/detailedstock/detail.php?id=' . $product->id . '&action="delete">' . $langs->trans("Return") . '</a></td></tr></table>';
		// if the user clicks on the delete icon, show the confirmation pop-up
		if ($action == 'ask_delete') {
			$formconfirm = $form->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $det->id, $langs->trans('Delete'), $langs->trans('ConfirmDelete'), 'delete', '', 'no', 1);
		}// if the user clicks on the modify button, show the confirmation pop-up
		else if ($action == 'ask_modify') {
			$formconfirm = $form->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $det->id, $langs->trans('Modify'), $langs->trans('ConfirmModify'), 'modify', '', 'no', 1);
		}
    } else {
        //error
      $mesg = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
      print ($mesg);
    }
}

if ($id && $action == 'modify' && $confirm == 'yes') {
    $det = new Productstockdet($db);
	$det->fetch($id);
	$form = new Form($db);
	dol_fiche_head('', 'info', $langs->trans('DetailedStock'), 1, 'product');
    $product = new Product($db);
    $product->fetch($det->fk_product);
	print '<br><form action="' . $_SERVER['PHP_SELF'] . '?id=' . $det->id . '" method="post"><table class="noborder" width="100%">';
	print '<input type="hidden" name="action" value="update"/>';
	print '<tr class="liste_titre"><td>' . $langs->trans("SerialNumber") . '</td>';
	print '<td>' . $langs->trans("SerialType") . '</td>';
	print '<td>' . $langs->trans("Supplier") . '</td>';
	print '<td>' . $langs->trans("BuyingPrice") . '</td>';
	print '<td>' . $langs->trans("Warehouse") . '</td>';
	print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

	print '<tr>';
	print '<td><input type="text" name="serialNumber" value="' . $det->serial . '"/></td>';
	print '<td>' . $det->selectSerialType($det->fk_serial_type, 'serialType') . '</td>';
	print '<td>' . $form->select_company($det->fk_supplier, 'supplier', 's.fournisseur=1') . '</td>';
	print '<td><input type="text" name="buyingPrice" value="' . $det->price . '"/></td>';
	print '<td>' . $det->selectWarehouses($det->fk_entrepot, 'warehouse', 'ps.reel > 0', 0, 0, $product->id) . '</td>';
	print '<td><input class="button" type="submit" name ="valid" value="' . $langs->trans("Valid") . '"/></td><td><input class="button" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '"/></td>';
	print '</tr>';
	print '</table></form>';
}

?>
