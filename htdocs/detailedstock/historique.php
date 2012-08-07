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
require_once(DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
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
if ($_GET["id"] || $_GET["ref"]) {
	$product = new Product($db);
	if ($_GET["ref"]) $result = $product->fetch('', $_GET["ref"]);
	if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

	$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';

	if ($result > 0) {
		$head = product_prepare_head($product, $user);
		$titre = $langs->trans("CardProduct" . $product->type);
		$picto = ($product->type == 1 ? 'service' : 'product');
		llxHeader("", $langs->trans("CardProduct" . $product->type), $help_url);
		dol_fiche_head($head, 'detail', $titre, 0, $picto);
		//display the error message when there's one
		dol_htmloutput_mesg($mesg);


		// Ref
		if ($product->isproduct()) {
			
			include(DOL_DOCUMENT_ROOT . '/detailedstock/tpl/infosProduct.tpl.php');

			$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'product_stock_det where fk_product = ' . $product->id . ' and tms_o is not null and entity = ' . $conf->entity;
			$sql.= ' order by tms_o DESC';
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					print '<br><table class="noborder" width="100%">';
					print '<caption><b><u>' . $langs->trans('History') . '</u></b></caption>';
					print '<tr class="liste_titre"><td width="33%">' . $langs->trans('Element') . '</td>';
					print '<td align="center">' . $langs->trans("SerialNumber") . '</td>';
					print '<td align="right">' . $langs->trans("Invoice") . '</td>';
					print '</tr>';
					while ($obj = $db->fetch_object($resql)) {
						$det = new Productstockdet($db);
						$res = $det->fetch($obj->rowid);
						if ($res) {
							$detId = '<a href="/detailedstock/fiche.php?id=' . $det->id . '">' . img_object($langs->trans("ShowProduct"),
									'product') . '</a>';
							print '<tr><td width="33%">' . $detId . '</td>';
							print '<td align="center">' . $form->textwithpicto($det->serial, $det->getSerialTypeLabel(), 1) . '</td>';
							print '<td align="right">';
							$invoiceline = new FactureLigne($db);
							$fetchline = $invoiceline->fetch($det->fk_invoice_line);
							if($fetchline){
								$invoice = new Facture($db);
								$fetch = $invoice->fetch($invoiceline->fk_facture);
								if($fetch){
									print $invoice->getNomUrl();
								}
							}
							print '</td>';
							print '</tr>';
						} else {
							$this->error = "Error " . $this->db->lasterror();
							dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
						}
					}
					print '</table>';
				}
			} else {
				//error
			}
		}
		else{
			
		}
	}
}
print '<br><table width="100%"><td align="right"><a class="butAction" href="/detailedstock/detail.php?id=' . $product->id . '">' . $langs->trans("Return") . '</a></td></tr></table>';
?>
