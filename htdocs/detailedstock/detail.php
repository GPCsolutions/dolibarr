<?php
/*Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
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
 * */

//TODO MODIFS CLE TABLE VOIR AVEC RAPH

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT . "/detailedstock/class/productstockdet.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions.lib.php");
global $langs, $user;
$langs->load("products");
$langs->load("orders");
$langs->load("bills");
$langs->load("stocks");
/*
 * View
 */

$form = new Form($db);


if ($_GET["id"] || $_GET["ref"]) {
  $product = new Product($db);
  if ($_GET["ref"]) $result = $product->fetch('', $_GET["ref"]);
  if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

  $help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
  llxHeader("", $langs->trans("CardProduct" . $product->type), $help_url);

  if ($result > 0) {
    $head = product_prepare_head($product, $user);
    $titre = $langs->trans("CardProduct" . $product->type);
    $picto = ($product->type == 1 ? 'service' : 'product');
    dol_fiche_head($head, 'detail', $titre, 0, $picto);

if(GETPOST('action')=='create'){
  $newDet = new Productstockdet($db);
  $newDet->tms_i = dol_now();
  $newDet->fk_product = $product->id;
  $newDet->fk_entrepot = GETPOST('warehouse');
  $newDet->fk_user_author_i = $user->id;
  $newDet->serial = GETPOST('serialNumber');
  $newDet->fk_serial_type = GETPOST('serialType');
  $newDet->price = price2num(GETPOST('buyingPrice'),'MT');
  $newDet->fk_supplier = GETPOST('supplier');
  $newDet->create($user);
  unset($_POST['action']);
  unset($_POST['warehouse']);
  unset($_POST['serialNumber']);
  unset($_POST['serialType']);
  unset($_POST['buyingPrice']);
  unset($_POST['supplier']);
  //TODO validation using Luhn algo
}
    
    print($mesg);

    print '<table class="border" width="100%">';

    // Ref
    print '<tr>';
    print '<td width="30%">' . $langs->trans("Ref") . '</td><td>';
    print $form->showrefnav($product, 'ref', '', 1, 'ref');
    print '</td>';
    print '</tr>';

    // Label
    print '<tr><td>' . $langs->trans("Label") . '</td><td>' . $product->libelle . '</td>';
    print '</tr>';

    // Status (to sell)
    print '<tr><td>' . $langs->trans("Status") . ' (' . $langs->trans("Sell") . ')</td><td>';
    print $product->getLibStatut(2, 0);
    print '</td></tr>';

    // Status (to buy)
    print '<tr><td>' . $langs->trans("Status") . ' (' . $langs->trans("Buy") . ')</td><td>';
    print $product->getLibStatut(2, 1);
    print '</td></tr>';

    // PMP
    print '<tr><td>' . $langs->trans("AverageUnitPricePMP") . '</td>';
    print '<td>' . price($product->pmp) . ' ' . $langs->trans("HT") . '</td>';
    print '</tr>';

    // Sell price
    print '<tr><td>' . $langs->trans("SellPriceMin") . '</td>';
    print '<td>';
    if (empty($conf->global->PRODUIT_MULTIPRICES)) print price($product->price) . ' ' . $langs->trans("HT");
    else print $langs->trans("Variable");
    print '</td>';
    print '</tr>';

    // Real stock
    $product->load_stock();
    print '<tr><td>' . $langs->trans("PhysicalStock") . '</td>';
    print '<td>' . $product->stock_reel;
    if ($product->seuil_stock_alerte && ($product->stock_reel < $product->seuil_stock_alerte))
        print ' ' . img_warning($langs->trans("StockTooLow"));
    print '</td>';
    print '</tr>';

    //undetailled stock
    $sql = 'select rowid from ' . MAIN_DB_PREFIX . 'product_stock_det where fk_product = ' . $product->id;
    $resql = $db->query($sql);
    $num = $db->num_rows($resql);
    $reste = $product->stock_reel - $num;
    print '<tr><td>' . $langs->trans("UndetailledStock") . '</td>';
    print '<td>'.$reste.'</td>';
    print '</tr>'; 

    // Calculating a theorical value of stock if stock increment is done on real sending
    if ($conf->global->STOCK_CALCULATE_ON_SHIPMENT) {
      $stock_commande_client = $stock_commande_fournisseur = 0;

      if ($conf->commande->enabled) {
        $result = $product->load_stats_commande(0, '1,2');
        if ($result < 0) dol_print_error($db, $product->error);
        $stock_commande_client = $product->stats_commande['qty'];
      }
      if ($conf->fournisseur->enabled) {
        $result = $product->load_stats_commande_fournisseur(0, '3');
        if ($result < 0) dol_print_error($db, $product->error);
        $stock_commande_fournisseur = $product->stats_commande_fournisseur['qty'];
      }

      $product->stock_theorique = $product->stock_reel - ($stock_commande_client + $stock_sending_client) + $stock_commande_fournisseur;

      // Stock theorique
      print '<tr><td>' . $langs->trans("VirtualStock") . '</td>';
      print "<td>" . $product->stock_theorique;
      if ($product->stock_theorique < $product->seuil_stock_alerte) {
        print ' ' . img_warning($langs->trans("StockTooLow"));
      }
      print '</td>';
      print '</tr>';

      print '<tr><td>';
      if ($product->stock_theorique != $product->stock_reel) print $langs->trans("StockDiffPhysicTeoric");
      else print $langs->trans("RunningOrders");
      print '</td>';
      print '<td>';

      $found = 0;

      // Nbre de commande clients en cours
      if ($conf->commande->enabled) {
        if ($found) print '<br>'; else $found = 1;
        print $langs->trans("CustomersOrdersRunning") . ': ' . ($stock_commande_client + $stock_sending_client);
        $result = $product->load_stats_commande(0, '0');
        if ($result < 0) dol_print_error($db, $product->error);
        print ' (' . $langs->trans("Draft") . ': ' . $product->stats_commande['qty'] . ')';
        //print '<br>';
        //print $langs->trans("CustomersSendingRunning").': '.$stock_sending_client;
      }

      // Nbre de commande fournisseurs en cours
      if ($conf->fournisseur->enabled) {
        if ($found) print '<br>'; else $found = 1;
        print $langs->trans("SuppliersOrdersRunning") . ': ' . $stock_commande_fournisseur;
        $result = $product->load_stats_commande_fournisseur(0, '0,1,2');
        if ($result < 0) dol_print_error($db, $product->error);
        print ' (' . $langs->trans("DraftOrWaitingApproved") . ': ' . $product->stats_commande_fournisseur['qty'] . ')';
      }
    }
    print '</td></tr></table>';
  }
  print '</div>';
  //view mode
  if (GETPOST('action') != 'add') {
    //$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'product_stock_det where fk_product = ' . $product->id;
    //$resql = $db->query($sql);
    if ($resql) {
      if ($db->num_rows($resql) < $product->stock_reel)
          print '<table width="100%"><tr><td align="right"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $product->id . '&action=add">' . $langs->trans("Add") . '</a></td></tr></table>';

      if ($db->num_rows($resql) > 0) {
        print '<br><table class="noborder" width="100%">';
        print '<tr class="liste_titre"><td>' . $langs->trans("Id") . '</td>';
        print '<td align="right">' . $langs->trans("N°") . '</td>';
        print '<td align="right">' . $langs->trans("Supplier") . '</td>';
        print '<td align="right">' . $langs->trans("BuyingPrice") . '</td>';
        print '<td align="right">' . $langs->trans("Warehouse") . '</td>';
        print '</tr>';
        while ($obj = $db->fetch_object($resql)) {
          $det = new Productstockdet($db);
          $res = $det->fetch($obj->rowid);
          if ($res) {
            print '<tr><td align=>' . $det->id . '</td>';
            print '<td align="right">' . $form->textwithpicto($det->serial, $det->getSerialTypeLabel(), 1) . '</td>';
            $soc = new Societe($db);
            $infosoc = $soc->fetch($det->fk_supplier);
            if ($infosoc) {
              print '<td align="right">' . $soc->getNomUrl() . '</td>';
            } else {
              $this->error="Error ".$this->db->lasterror();
              dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            }
            print '<td align="right">' . $det->price . '</td>';
            $entrepot = new Entrepot($db);
            $infoentrepot = $entrepot->fetch($det->fk_entrepot);
            if ($infoentrepot) {
              print '<td align="right">' . $entrepot->getNomUrl() . '</td>';
            } else {
              $this->error="Error ".$this->db->lasterror();
              dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            }
            print '</tr>';
          } else {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
          }
        }
      }
    } else {
      //error
    }
  }
  //add mode
  else {
    $newDet = new Productstockdet($db);
    $formproduct=new FormProduct($db);
    print '<br><form action="'.$_SERVER['PHP_SELF'] . '?id=' . $product->id.'" method="post"><table class="noborder" width="100%">';
    print '<input type="hidden" name="action" value="create"/>';
    print '<tr class="liste_titre"><td>' . $langs->trans("SerialType") . '</td>';
    print '<td>'.$langs->trans("SerialNumber").'</td>';
    print '<td>' . $langs->trans("Supplier") . '</td>';
    print '<td>'.$langs->trans("BuyingPrice").'</td>';
    print '<td>'.$langs->trans("Warehouse").'</td>';
    print '<td>&nbsp;</td><td>&nbsp;</td></tr>';
    
    print '<tr>';
    print '<td>'.$newDet->selectSerialType('','serialType').'</td>';
    print '<td><input type="text" name="serialNumber" /></td>';
    print '<td>'.$form->select_company('','supplier', 's.fournisseur=1').'</td>';
    print '<td><input type="text" name="buyingPrice" /></td>';
    print '<td>'.$formproduct->selectWarehouses('','warehouse','',1).'</td>';    print '<td><input type="submit" value="'.$langs->trans("Valid").'"/></td><td><input type="submit" name="cancel" value="' . $langs->trans("Cancel") . '"/></td>';
    print '</tr>';
    print '</table></form>';
  }
}
?>
