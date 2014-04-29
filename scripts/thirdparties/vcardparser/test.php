#!/usr/bin/php
<?php
if (! defined('NOLOGIN'))
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
if (! defined('NOCSRFCHECK'))
	define('NOCSRFCHECK', '1'); // Do not check anti CSRF attack test
if (! defined('NOTOKENRENEWAL'))
	define('NOTOKENRENEWAL', '1'); // Do not check anti POST attack test
if (! defined('NOREQUIREMENU'))
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu

$path = dirname(__FILE__) . '/';
require_once $path . '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once ('vCard.php');

/**
 * Test function for vCard content output
 * 
 * @param vCard vCard object
 */
function OutputvCard(vCard $vCard, $filename) {
	global $db, $langs;
	
	$socarray = array ();
	$contactarray = array ();
	$societe = new Societe($db);
	$contact = new Contact($db);
	$contact_created = false;
	$societe_created = true;
	
	// echo '<h2>'.$vCard -> FN[0].'</h2>';
	
	// if (count($vCard -> N)>1) print 'tu fait de la merde';
	foreach ( $vCard->N as $Name ) {
		if (! empty($Name ['LastName']) || ! empty($Name ['FirstName'])) {
			// echo '<h3>Name: '.$Name['FirstName'].' '.$Name['LastName'].'</h3>';
			$contact->lastname = $Name ['LastName'];
			$contact->firstname = $Name ['FirstName'];
			$contact_created = true;
		}
	}
	
	foreach ( $vCard->ORG as $Organization ) {
		/*echo '<h3>Organization: '.$Organization['Name'].
				($Organization['Unit1'] || $Organization['Unit2'] ?
					' ('.implode(', ', array($Organization['Unit1'], $Organization['Unit2'])).')' :
					''
				).'</h3>';*/
		if (! empty($Organization ['Name'])) {
			
			$sql = 'SELECT rowid, nom FROM llx_societe where nom=\'' . $db->escape($Organization ['Name']) . '\'';
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				if ($num) {
					$obj = $db->fetch_object($resql);
					$societe->fetch($obj->rowid);
					$societe_created = false;
				}
			}
			
			$societe->name = $Organization ['Name'];
			if ($contact->lastname . ' ' . $contact->firstname == $Organization ['Name']) {
				$societe->particulier = 1;
				$societe->typent_id = dol_getIdFromCode($db, 'TE_PRIVATE', 'c_typent');
			} else {
				$societe->typent_id = 100;
			}
		}
	}
	
	if ($vCard->TEL) {
		// echo '<p><h4>Phone</h4>';
		foreach ( $vCard->TEL as $Tel ) {
			if (is_scalar($Tel)) {
				// echo $Tel.'<br />';
			} else {
				if ($contact_created) {
					if (in_array('work', $Tel ['Type']))
						$contact->phone_pro = $Tel ['Value'];
					if (in_array('cell', $Tel ['Type']))
						$contact->phone_mobile = $Tel ['Value'];
				} else {
					$societe->phone = $Tel ['Value'];
				}
				
				// echo $Tel['Value'].' ('.implode(', ', $Tel['Type']).')<br />';
				
				if (in_array('work', $Tel ['Type']))
					$societe->phone = $Tel ['Value'];
			}
		}
		
		// echo '</p>';
	}
	
	if ($vCard->EMAIL) {
		// echo '<p><h4>Email</h4>';
		foreach ( $vCard->EMAIL as $Email ) {
			if (is_scalar($Email)) {
				// echo $Email;
			} else {
				if ($contact_created) {
					$contact->email = $Email ['Value'];
				}
				
				// echo $Email['Value'].' ('.implode(', ', $Email['Type']).')<br />';
				$societe->email = $Email ['Value'];
			}
		}
		// echo '</p>';
	}
	
	if ($vCard->URL) {
		// echo '<p><h4>URL</h4>';
		foreach ( $vCard->URL as $URL ) {
			if (is_scalar($URL)) {
				// echo $URL.'<br />';
				$societe->url = $URL;
			} else {
				// echo $URL['Value'].'<br />';
				$societe->url = $URL ['Value'];
			}
		}
		// echo '</p>';
	}
	
	if ($vCard->ADR) {
		foreach ( $vCard->ADR as $Address ) {
			/*echo '<p><h4>Address ('.implode(', ', $Address['Type']).')</h4>';
				echo 'Street address: <strong>'.($Address['StreetAddress'] ? $Address['StreetAddress'] : '-').'</strong><br />'.
					'PO Box: <strong>'.($Address['POBox'] ? $Address['POBox'] : '-').'</strong><br />'.
					'Extended address: <strong>'.($Address['ExtendedAddress'] ? $Address['ExtendedAddress'] : '-').'</strong><br />'.
					'Locality: <strong>'.($Address['Locality'] ? $Address['Locality'] : '-').'</strong><br />'.
					'Region: <strong>'.($Address['Region'] ? $Address['Region'] : '-').'</strong><br />'.
					'ZIP/Post code: <strong>'.($Address['PostalCode'] ? $Address['PostalCode'] : '-').'</strong><br />'.
					'Country: <strong>'.($Address['Country'] ? $Address['Country'] : '-').'</strong>';*/
			
			if ($contact_created) {
				$contact->address = $Address ['StreetAddress'];
				$contact->town = $Address ['Locality'];
				$contact->zip = $Address ['PostalCode'];
			}
			$societe->address = $Address ['StreetAddress'];
			$societe->town = $Address ['Locality'];
			$societe->zip = $Address ['PostalCode'];
		}
		echo '</p>';
	}
	
	$societe->country_id = 1;
	$societe->status = 1;
	$societe->import_key = 'importfromvcard';
	
	if ($contact_created) {
		$contact->status = 1;
		$contact->import_key = 'importfromvcard';
	}
	
	if (preg_match('/TIERS_FOURNISSEURS/', $filename)) {
		
		$societe->fournisseur = 1;
		$societe->client = 0;
		$societe->code_fournisseur = - 1;
	}
	
	if (preg_match('/TIERS_PROSPECTS/', $filename)) {
		
		$societe->fournisseur = 0;
		$societe->client = 2;
		$societe->code_client = - 1;
	}
	
	$user = new User($db);
	$user->fetch(1);
	
	if ($societe_created) {
		$result = $societe->create($user);
		if ($result < 0) {
			print "\n" . '<BR>ERROR SOC:' . $societe->error;
		} else {
			print "\n" . '<BR>OK:' . $societe->id . ' Nom:' . $societe->name;
		}
	}
	if ($contact_created) {
		$contact->socid = $societe->id;
		$result = $contact->create($user);
		if ($result < 0) {
			print "\n" . '<BR>ERROR CONTACT:' . $contact->error;
		} else {
			print "\n" . '<BR>OK:' . $contact->getFullName($langs);
		}
	}
	
	$array_cat = explode('/', $filename);
	$index_puget = array_search('david.puget', $array_cat);
	$first_categ = $array_cat [$index_puget + 2];
	// print $index_puget;
	// print count($array_cat);
	if (count($array_cat) > $index_puget + 4) {
		$second_categ = $array_cat [$index_puget + 3];
	}
	// var_dump($array_cat);
	
	// var_dump($first_categ);
	// var_dump($second_categ);
	
	// Do what need to be with category
	if ($societe->fournisseur == 1) {
		$type_categ = 1;
		$type_categ_txt = 'fournisseur';
	} else {
		$type_categ = 2;
		$type_categ_txt = 'societe';
	}
	
	$categ_toaddfirst = new Categorie($db);
	$sql = 'SELECT rowid, label FROM llx_categorie where type=' . $type_categ . ' AND label=\'' . $db->escape($first_categ) . '\'';
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num) {
			$obj = $db->fetch_object($resql);
			
			$categ_toaddfirst->fetch($obj->rowid);
			$categ_toaddfirst->add_type($societe, $type_categ_txt);
		} else {
			
			$categ_toaddfirst->label = $first_categ;
			$categ_toaddfirst->import_key = 'importfromvcard';
			$categ_toaddfirst->visible = 0;
			$categ_toaddfirst->fk_parent = 0;
			$categ_toaddfirst->type = $type_categ;
			
			$categ_toaddfirst->create($user);
			
			$categ_toaddfirst->add_type($societe, $type_categ_txt);
		}
	}
	
	if (! empty($second_categ)) {
		
		$categ_toaddsecond = new Categorie($db);
		
		$sqlsecond = 'SELECT rowid, label FROM llx_categorie where type=' . $type_categ . ' AND label=\'' . $db->escape($second_categ) . '\'';
		$resqlsecond = $db->query($sqlsecond);
		if ($resqlsecond) {
			$num = $db->num_rows($resqlsecond);
			if ($num) {
				$obj = $db->fetch_object($resqlsecond);
				
				$categ_toaddsecond->fetch($obj->rowid);
				$categ_toaddsecond->add_type($societe, $type_categ_txt);
			} else {
				
				$categ_toaddsecond->label = $second_categ;
				$categ_toaddsecond->import_key = 'importfromvcard';
				$categ_toaddsecond->visible = 0;
				$categ_toaddsecond->fk_parent = $categ_toaddfirst->id;
				$categ_toaddsecond->type = $type_categ;
				
				$categ_toaddsecond->create($user);
				
				$categ_toaddsecond->add_type($societe, $type_categ_txt);
			}
		}
	}
}

$di = new RecursiveDirectoryIterator(dirname(__FILE__) . '/david.puget');
var_dump($di);
foreach ( new RecursiveIteratorIterator($di) as $filename => $file ) {
	if ($file->isFile()) {
		// echo '<br/>'.$filename . ' - ' . $file->getSize() . ' bytes';
		// var_dump($file);
		
		// if (preg_match('/tourisme et loisirs/',$filename)) {
		
		echo "\n" . '<br/>' . $filename . ' - ' . $file->getSize() . ' bytes';
		$vCard = new vCard($filename, 		// Path to vCard file
		false, 		// Raw vCard text, can be used instead of a file
		array ( // Option array
		       // This lets you get single values for elements that could contain multiple values but have only one value.
		       // This defaults to false so every value that could have multiple values is returned as array.
				'Collapse' => false 
		));
		
		if (count($vCard) == 0) {
			throw new Exception('vCard test: empty vCard!');
		}		// if the file contains a single vCard, it is accessible directly.
		elseif (count($vCard) == 1) {
			OutputvCard($vCard, $filename);
		}
		// }
	}
}

/*$vCard = new vCard(
		'Example3.0.vcf', // Path to vCard file
		false, // Raw vCard text, can be used instead of a file
		array( // Option array
			// This lets you get single values for elements that could contain multiple values but have only one value.
			//	This defaults to false so every value that could have multiple values is returned as array.
			'Collapse' => false
		)
	);

	if (count($vCard) == 0)
	{
		throw new Exception('vCard test: empty vCard!');
	}
	// if the file contains a single vCard, it is accessible directly.
	elseif (count($vCard) == 1)
	{
		OutputvCard($vCard);
	}
	// if the file contains multiple vCards, they are accessible as elements of an array
	else
	{
		foreach ($vCard as $Index => $vCardPart)
		{
			OutputvCard($vCardPart);
		}
	}*/
?>