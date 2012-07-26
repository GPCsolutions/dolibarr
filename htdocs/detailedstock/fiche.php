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
global $langs, $user;
$langs->load("errors");
$langs->load("products");
$langs->load("orders");
$langs->load("bills");
$langs->load("stocks");
$langs->load("detailedStock@detailedstock");

/*
 * View
 */

llxHeader();

if (GETPOST('id')) {
    $det = new Productstockdet($db);
    $form = new Form($db);
    $result = $det->fetch($_GET["id"]);
    //var_dump($result);
    //var_dump($det);
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
            print '<td>' . $langs->trans('OutputInfos') . '</td>';
            print '<td>' . $det->getInfos('output') . '</td>';
            print '</tr>';
        }

        print '</table></div>';
        print '<table width="100%"><tr><td align="right"><a class="butAction" href="/detailedstock/detail.php?id=' . $product->id . '">' . $langs->trans("Return") . '</a></td></tr></table>';
    } else {
        //error
      $mesg = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
      print ($mesg);
    }
}

?>
