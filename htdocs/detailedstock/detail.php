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
$langs->load("products");
$langs->load("orders");
$langs->load("bills");
$langs->load("stocks");

/*
 * View
 */
if (!$conf->global->MAIN_MODULE_DETAILEDSTOCK) accessforbidden();
$form = new Form($db);
$action = GETPOST('action');
if ($_GET["id"] || $_GET["ref"]) {
    $product = new Product($db);
    if ($_GET["ref"]) $result = $product->fetch('', $_GET["ref"]);
    if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

    $help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';

    if ($result > 0) {
        $head = product_prepare_head($product, $user);
        $titre = $langs->trans("CardProduct" . $product->type);
        $picto = ($product->type == 1 ? 'service' : 'product');

        if ($product->isproduct()) {
            //after the user hit the valid button
            if ($action == 'create' && GETPOST('valid') == $langs->trans('Valid')) {
                $newDet = new Productstockdet($db);
                //create a new detailled stock object with the input parameters
                $newDet->tms_i = dol_now();
                $newDet->fk_product = $product->id;
                if (GETPOST('warehouse')) $newDet->fk_entrepot = GETPOST('warehouse');
                $newDet->fk_user_author_i = $user->id;
                if (GETPOST('serialNumber') != '') $newDet->serial = GETPOST('serialNumber');
                if (GETPOST('serialType') >= 0) $newDet->fk_serial_type = GETPOST('serialType');
                $newDet->price = price2num(GETPOST('buyingPrice'), 'MT');
                if(GETPOST('supplier') > 0)$newDet->fk_supplier = GETPOST('supplier');
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
                    $id = $newDet->create($user);
                    unset($action);
                } else {
                    //else, prepare the error message and go back to add mode
                    $mesg = '<div class="error">' . $langs->trans('InvalidCode') . '</div>';
                    $action = 'add';
                }
                unset($_POST['action']);
                unset($_POST['warehouse']);
                unset($_POST['serialNumber']);
                unset($_POST['serialType']);
                unset($_POST['buyingPrice']);
                unset($_POST['supplier']);
            }
        }
        llxHeader("", $langs->trans("CardProduct" . $product->type), $help_url);
        dol_fiche_head($head, 'detail', $titre, 0, $picto);
        //display the error message when there's one
        dol_htmloutput_mesg($mesg);


        

        // Ref
        if ($product->isproduct()) {
			print '<table class="border" width="100%">';
            include(DOL_DOCUMENT_ROOT . '/detailedstock/tpl/infosProduct.tpl.php');
            //view mode
            if ($action != 'add') {
				$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'product_stock_det where fk_product = ' . $product->id.' and tms_o is null and entity = '.$conf->entity;
				$resql = $db->query($sql);
                if ($resql) {
                    if ($db->num_rows($resql) < $product->stock_reel)
                            print '<table width="100%"><tr><td align="right"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $product->id . '&action=add">' . $langs->trans("Add") . '</a></td></tr></table>';
					else
						print '<table width="100%"><tr><td align="right"><span class="butActionRefused" title="'.$langs->trans("OnlyExistingStock").'">'.$langs->trans('Add').'</span></td></tr></table>';
                    //display each detailled stock line related to this product
                    if ($db->num_rows($resql) > 0) {
                        print '<br><table class="noborder" width="100%">';
						print '<caption><b><u>'.$langs->trans('CurrentDetails').'</u></b></caption>';
                        print '<tr class="liste_titre"><td>'.$langs->trans('Element').'</td>';
                        print '<td align="right">' . $langs->trans("SerialNumber") . '</td>';
                        print '<td align="right">' . $langs->trans("Supplier") . '</td>';
                        print '<td align="right">' . $langs->trans("BuyingPrice") . '</td>';
                        print '<td align="right">' . $langs->trans("Warehouse") . '</td>';
                        print '</tr>';
                        while ($obj = $db->fetch_object($resql)) {
                            $det = new Productstockdet($db);
                            $res = $det->fetch($obj->rowid);
                            if ($res) {
                                $detId = '<a href="/detailedstock/fiche.php?id=' . $det->id . '">' . img_object($langs->trans("ShowProduct"),
                                        'product') . '</a>';
                                print '<tr><td>' . $detId . '</td>';
                                print '<td align="right">' . $form->textwithpicto($det->serial,
                                        $det->getSerialTypeLabel(), 1) . '</td>';
                                $soc = new Societe($db);
                                $infosoc = $soc->fetch($det->fk_supplier);
                                if ($infosoc) {
                                    print '<td align="right">' . $soc->getNomUrl() . '</td>';
                                } else {
                                    $this->error = "Error " . $this->db->lasterror();
                                    dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
                                }
                                print '<td align="right">' . price($det->price) . ' HT</td>';
                                $entrepot = new Entrepot($db);
                                $infoentrepot = $entrepot->fetch($det->fk_entrepot);
                                if ($infoentrepot) {
                                    print '<td align="right">' . $entrepot->getNomUrl() . '</td>';
                                } else {
                                    $this->error = "Error " . $this->db->lasterror();
                                    dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
                                }
                                print '</tr>';
                            } else {
                                $this->error = "Error " . $this->db->lasterror();
                                dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
                            }
                        }
						print '</table>';
						print '<br><table width="100%"><tr><td align="right"><a class="butAction" href="/detailedstock/historique.php?id=' . $product->id . '">' . $langs->trans("SeeHistory") . '</a></td></tr></table>';
                    }
                } else {
                    //error
                }
            }
            //add mode
            else {
                $det = new Productstockdet($db);
                print '<br><form action="' . $_SERVER['PHP_SELF'] . '?id=' . $product->id . '" method="post"><table class="noborder" width="100%">';
                print '<input type="hidden" name="action" value="create"/>';
                print '<tr class="liste_titre"><td>' . $langs->trans("SerialNumber") . '</td>';
				print '<td>' . $langs->trans("SerialType") . '</td>';
                print '<td>' . $langs->trans("Supplier") . '</td>';
                print '<td>' . $langs->trans("BuyingPrice") . '</td>';
                print '<td>' . $langs->trans("Warehouse") . '</td>';
                print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

                print '<tr>';
				print '<td><input type="text" name="serialNumber" value="' . $newDet->serial . '"/></td>';
                print '<td>' . $det->selectSerialType($newDet->fk_serial_type, 'serialType') . '</td>';
                print '<td>' . $form->select_company($newDet->fk_supplier, 'supplier', 's.fournisseur=1', 1) . '</td>';
                print '<td><input type="text" name="buyingPrice" value="' . $newDet->price . '"/></td>';
                print '<td>' . $det->selectWarehouses($newDet->fk_entrepot, 'warehouse', 'ps.reel > 0', 1, 0, $product->id) . '</td>';
                print '<td><input class="button" type="submit" name ="valid" value="' . $langs->trans("Valid") . '"/></td><td><input class="button" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '"/></td>';
                print '</tr>';
                print '</table></form>';
            }
        } else {
            print '<tr>';
            print '<td width="30%">' . $langs->trans("UnavailableForServices") . '</td><td>';
            print $form->showrefnav($product, 'ref', '', 1, 'ref', ' ');
            print '</td>';
            print '</tr>';
            print '</table>';
            print '</div>';
        }
    }
}

?>
