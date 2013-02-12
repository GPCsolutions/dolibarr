<?php
/* Copyright (C) 2013      Antoine Iauch        <aiauch@gpcsolutions.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *       \file        htdocs/compta/stats/cabyprodserv.php
 *       \brief       Page reporting TO by Products & Services
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("products");

// Security pack (data & check)
$socid = GETPOST('socid','int');

if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user,'compta','','','resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user,'accounting','','','comptarapport');

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->global->COMPTA_MODE;
if (GETPOST("modecompta")) $modecompta=GETPOST("modecompta");

$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
if (! $sortorder) $sortorder="asc";
if (! $sortfield) $sortfield="name";

// Date range
$year=GETPOST("year");
$month=GETPOST("month");
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$month_current = strftime("%m",dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = strftime("%m",dol_now());
	$year_start = $year;
}
$date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);
$date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q=GETPOST("q")?GETPOST("q"):0;
	if ($q==0)
	{
		// We define date_start and date_end
		$month_start=GETPOST("month")?GETPOST("month"):($conf->global->SOCIETE_FISCAL_MONTH_START?($conf->global->SOCIETE_FISCAL_MONTH_START):1);
		$year_end=$year_start;
		$month_end=$month_start;
		if (! GETPOST("month"))	// If month not forced
		{
			if (! GETPOST('year') && $month_start > $month_current)
			{
				$year_start--;
				$year_end--;
			}
			$month_end=$month_start-1;
			if ($month_end < 1) $month_end=12;
			else $year_end++;
		}
		$date_start=dol_get_first_day($year_start,$month_start,false); $date_end=dol_get_last_day($year_end,$month_end,false);
	}
	if ($q==1) { $date_start=dol_get_first_day($year_start,1,false); $date_end=dol_get_last_day($year_start,3,false); }
	if ($q==2) { $date_start=dol_get_first_day($year_start,4,false); $date_end=dol_get_last_day($year_start,6,false); }
	if ($q==3) { $date_start=dol_get_first_day($year_start,7,false); $date_end=dol_get_last_day($year_start,9,false); }
	if ($q==4) { $date_start=dol_get_first_day($year_start,10,false); $date_end=dol_get_last_day($year_start,12,false); }
}
else
{
	// TODO We define q

}

/*
 * View
 */

llxHeader();
$form=new Form($db);

// Affiche en-tete du rapport
$nom=$langs->trans("SalesTurnover").', '.$langs->trans("ByProductsAndServices");
if ($modecompta=="CREANCES-DETTES")
{
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=RECETTES-DEPENSES">','</a>').')';

    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);

    $description=$langs->trans("RulesCADue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	    $description.= $langs->trans("DepositsAreNotIncluded");
	} else {
	    $description.= $langs->trans("DepositsAreIncluded");
	}

    $builddate=time();
}
else {
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=CREANCES-DETTES">','</a>').')';

    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);

    $description=$langs->trans("RulesCAIn");
    $description.= $langs->trans("DepositsAreIncluded");

    $builddate=time();
}
$moreparam=array();
if (! empty($modecompta)) $moreparam['modecompta']=$modecompta;

report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink,$moreparam);


// Requête SQL de la mort qui tue
$catotal=0;

if ($modecompta == 'CREANCES-DETTES') 
    {
	$sql = "SELECT p.rowid as rowid, p.ref as ref, p.label as label,";
	$sql.= " sum(l.total_ht) as amount, sum(l.total_ttc) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."product as p,";
	$sql.= " ".MAIN_DB_PREFIX."facturedet as l";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON l.fk_facture = f.rowid";
	$sql.= " WHERE l.fk_product = p.rowid";
	$sql.= " AND f.fk_statut in (1,2)";
	    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2)";
	    else $sql.= " AND f.type IN (0,1,2,3)";
	    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";

    } else {

	$sql = "SELECT p.rowid as rowid, p.ref as ref, p.label as label,";
	$sql.= " sum(pf.amount) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
	$sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql.= " ".MAIN_DB_PREFIX."facturedet as l";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON l.fk_facture = f.rowid";
	$sql.= " WHERE l.fk_product = p.rowid";
	    if ($date_start && $date_end) $sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    }
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " GROUP BY p.rowid ";
    $sql.= "ORDER BY p.ref ";

$result = $db->query($sql);
    if ($result)
    {
	$num = $db->num_rows($result);
	$i=0;
	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
	        $amount_ht[$obj->rowid] = $obj->amount;
	        $amount[$obj->rowid] = $obj->amount_ttc;
	        $name[$obj->rowid] = $obj->ref . '&nbsp;-&nbsp;' . $obj->label;
	        $catotal_ht+=$obj->amount;
	        $catotal+=$obj->amount_ttc;
	        $i++;
	}
    } else {
	dol_print_error($db);
    }

/*/* On ajoute les paiements anciennes version, non lies par paiement_facture
if ($modecompta != 'CREANCES-DETTES')
{
	//$sql = "SELECT '0' as socid, 'Autres' as name, sum(p.amount) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql.= " WHERE pf.rowid IS NULL";
	$sql.= " AND p.fk_bank = b.rowid";
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
	if ($date_start && $date_end) $sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	$sql.= " GROUP BY socid, name";
	$sql.= " ORDER BY name";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i=0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$amount[$obj->rowid] += $obj->amount_ttc;
			$name[$obj->rowid] = $obj->name;
			$catotal+=$obj->amount_ttc;
			$i++;
		}
	}
	else {
		dol_print_error($db);
	}
}
 */

// show array
$i=0;
print "<table class=\"noborder\" width=\"100%\">";
    // Array header
print "<tr class=\"liste_titre\">";
print_liste_field_titre(
	$langs->trans("Product"),
	$_SERVER["PHP_SELF"],
	"name",
	"",
	'&amp;year='.($year).'&modecompta='.$modecompta,
	"",
	$sortfield,
	$sortorder
    );
if ($modecompta == 'CREANCES-DETTES') {
    print_liste_field_titre(
	    $langs->trans('AmountHT'),
	    $_SERVER["PHP_SELF"],
	    "amount_ht",
	    "",
	    '&amp;year='.($year).'&modecompta='.$modecompta,
	    'align="right"',
	    $sortfield,
	    $sortorder
	);
}
print_liste_field_titre(
	$langs->trans("AmountTTC"),
	$_SERVER["PHP_SELF"],
	"amount_ttc",
	"",
	'&amp;year='.($year).'&modecompta='.$modecompta,
	'align="right"',
	$sortfield,
	$sortorder
    );
print_liste_field_titre(
	$langs->trans("Percentage"),
	$_SERVER["PHP_SELF"],
	"amount_ttc",
	"",
	'&amp;year='.($year).'&modecompta='.$modecompta,
	'align="right"',
	$sortfield,
	$sortorder
    );
// TODO: statistics?
print "</tr>\n";

    // Array Data
$var=true;

if (count($amount))
{
	$arrayforsort=$name;

	// defining arrayforsort
	if ($sortfield == 'nom' && $sortorder == 'asc') {
		asort($name);
		$arrayforsort=$name;
	}
	if ($sortfield == 'nom' && $sortorder == 'desc') {
		arsort($name);
		$arrayforsort=$name;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'asc') {
	    asort($amount_ht);
	    $arrayforsort=$amount_ht;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'desc') {
	    arsort($amount_ht);
	    $arrayforsort=$amount_ht;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'asc') {
		asort($amount);
		$arrayforsort=$amount;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'desc') {
		arsort($amount);
		$arrayforsort=$amount;
	}

	foreach($arrayforsort as $key=>$value)
	{
		$var=!$var;
		print "<tr ".$bc[$var].">";
// TODO : Show product name !
		// Third party
		 $fullname=$name[$key];
		if ($key >= 0) {
		    $linkname='<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$key.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$fullname.'</a>';
		} else {
		    $linkname=$langs->trans("PaymentsNotLinkedToProduct");
		}
		
	    print "<td>".$linkname."</td>\n";

	    // Amount w/o VAT
	    print '<td align="right">';
	    if ($modecompta != 'CREANCES-DETTES') {
		if ($key > 0) print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?productid='.$key.'">';
		else print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?productid=-1">';
	    } else {
		if ($key > 0) print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?productid='.$key.'">';
		else print '<a href="#">';
	    }
	    print price($amount_ht[$key]);
	    print '</td>';

	    // Amount with VAT
	    print '<td align="right">';
	    if ($modecompta != 'CREANCES-DETTES')
	    {
	    if ($key > 0) print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?productid='.$key.'">';
	    else print '<a href="'.DOL_URL_ROOT.'/compta/paiement/liste.php?productid=1">';
	    } else 	{
		if ($key > 0) print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?productid='.$key.'">';
	    else print '<a href="#">';
	    }
	    print price($amount[$key]);
	    print '</a>';
	    print '</td>';

	    // Percent;
	    print '<td align="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal, 2).'%' : '&nbsp;').'</td>';

	    // TODO: statistics?

	    print "</tr>\n";
	    $i++;
	}

	// Total
	print '<tr class="liste_total">';
	print '<td>'.$langs->trans("Total").'</td>';
	print '<td align="right">'.price($catotal_ht).'</td>';
	print '<td align="right">'.price($catotal).'</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	$db->free($result);
}
print "</table>";

llxFooter();
$db->close();
?>
