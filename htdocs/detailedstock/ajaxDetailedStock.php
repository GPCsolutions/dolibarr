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

/**
 *       \file       htdocs/product/ajaxproducts.php
 *       \brief      File to return Ajax response on product list request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (empty($_GET['keysearch']) && ! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php');
require_once(DOL_DOCUMENT_ROOT.'/detailedstock/class/productstockdet.class.php');

$langs->load("products");
$langs->load("main");


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

//print '<body class="nocellnopadd">'."\n";

dol_syslog(join(',',$_GET));
//print_r($_GET);

if (! isset($_GET['htmlname'])) return;

$htmlname = $_GET['htmlname'];
$match = preg_grep('/('.$htmlname.'[0-9]+)/',array_keys($_GET));
sort($match);
$idprod = $match[0];
//if (! isset($_GET[$htmlname]) && ! isset($_GET[$idprod])) return;

// When used from jQuery, the search term is added as GET param "term".
$searchkey=$_GET[$idprod];
if (empty($searchkey)) $searchkey=$_GET[$htmlname];
dol_syslog($_GET['fk_product']);
$outjson=isset($_GET['outjson'])?$_GET['outjson']:0;

// Get list of product.

$det = new Productstockdet($db);

$arrayresult=$det->selectSerialJSON("",$_GET['fk_product'], $searchkey);


$db->close();

if ($outjson) print json_encode($arrayresult);

//print "</body>";
//print "</html>";
?>