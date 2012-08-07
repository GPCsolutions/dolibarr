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
print '<table class="border" width="100%">';
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
			$det = new Productstockdet($db);
			$num = $det->count($product->id);
			$reste = $product->stock_reel - $num;
			print '<tr><td>' . $langs->trans("UndetailledStock") . '</td>';
			print '<td>' . $reste . '</td>';
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
			print '</div>';
?>
