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
$page=GETPOST('page','int');
$negpage=GETPOST('negpage','int');
if ($negpage)
{
    $page=$_GET["nbpage"] - $negpage;
    if ($page > $_GET["nbpage"]) $page = $_GET["nbpage"];
}

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
			$det = new Productstockdet($db);
			$nbline = $det->count($product->id, 'tms_o is not null');
			$viewline = empty($conf->global->MAIN_SIZE_LISTE_LIMIT)?20:$conf->global->MAIN_SIZE_LISTE_LIMIT;
			$total_lines = $nbline;
		
			if ($nbline > $viewline ) $limit = $nbline - $viewline ;
			else $limit = $viewline;
			
			if ($page > 0)
			{
				$limitsql = $nbline - ($page * $viewline);
				if ($limitsql < $viewline) $limitsql = $viewline;
					$nbline = $limitsql;
			}
			else
			{
				$page = 0;
				$limitsql = $nbline;
			}

			$sql = 'select rowid from ' . MAIN_DB_PREFIX . 'product_stock_det where fk_product = ' . $product->id . ' and tms_o is not null and entity = ' . $conf->entity;
			$sql.= ' order by tms_o DESC';
			$sql.= $db->plimit($limitsql, 0);
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					print '<br><table class="noborder" width="100%">';
					//$navig='';
					$navig.='<form action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'" name="newpage" method="GET">';
					$nbpage=floor($total_lines/$viewline)+($total_lines % $viewline > 0?1:0);  // Nombre de page total
					//print 'nbpage='.$nbpage.' viewline='.$viewline.' limitsql='.$limitsql;
					if ($limitsql > $viewline) $navig.='<a href="historique.php?id='.$product->id.'&amp;page='.($page+1).'">'.img_previous().'</a>';
					$navig.= $langs->trans("Page")." "; // ' Page ';
					$navig.='<input type="text" name="negpage" size="1" class="flat" value="'.($nbpage-$page).'">';
					$navig.='<input type="hidden" name="nbpage"  value="'.$nbpage.'">';
					$navig.='<input type="hidden" name="id" value="'.$product->id.'">';
					$navig.='/'.$nbpage.' ';
					if ($total_lines > $limitsql )
					{
						$navig.= '<a href="historique.php?id='.$product->id.'&page='.($page-1).'">'.img_next().'</a>';
					}
					$navig.='</form>';
					print '<caption><b><u>' . $langs->trans('History') . '</u></b></caption>';
					print '<tr><td colspan="9" align="right">'.$navig.'</td></tr>';
					print '<tr class="liste_titre"><td>' . $langs->trans('Element') . '</td>';
					print '<td align="right">'.$langs->trans('Date').'</td>';
					print '<td align="right">' . $langs->trans("SerialNumber") . '</td>';
					print '<td align="right">' . $langs->trans("Invoice") . '</td>';
					print '</tr>';
					$i = 0;
					while ($obj = $db->fetch_object($resql)) {
						if($i >= ($nbline - $viewline)){
							$det = new Productstockdet($db);
							$res = $det->fetch($obj->rowid);
							if ($res) {
								$detId = '<a href="/detailedstock/fiche.php?id=' . $det->id . '">' . img_object($langs->trans("ShowProduct"),
										'product') . '</a>';
								print '<tr><td>' . $detId . '</td>';
								print '<td align="right">'.date('d/m/y', $det->tms_o).'</td>';
								print '<td align="right">' . $form->textwithpicto($det->serial, $det->getSerialTypeLabel(), 1) . '</td>';
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
						$i++;
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
