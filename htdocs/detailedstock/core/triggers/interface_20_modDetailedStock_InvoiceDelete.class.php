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
 *  \file       htdocs/detailedstock/core/triggers/interface_20_modDetailedStock_InvoiceDelete.class.php
 *  \ingroup    core
 *  \brief      Fichier de InvoiceDelete de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la methode constructeur doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */
require_once(DOL_DOCUMENT_ROOT . '/detailedstock/class/productstockdet.class.php');

/**
 *  Class of triggers for InvoiceDelete module
 */
class InterfaceInvoiceDelete
{
    var $db;
    
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function InterfaceInvoiceDelete($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "InvoiceDelete";
        $this->description = "Placeholder";
        $this->version = 'development';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }
    
    
    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }
    
    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        global $db;
        // Bills
        if ($action == 'BILL_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $object->fetch_lines();
            $result = 0;
            foreach($object->lines as $line){
              $det = new Productstockdet($db);
              $det->fetchByFkInvoiceline($line->rowid);
              unset($det->tms_o);
              unset($det->fk_user_author_o);
              unset($det->fk_invoice_line);
              $result = $det->update($user);
            }
            return $result;
        }
		elseif ($action == 'LINEBILL_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $det = new Productstockdet($db);
            $det->fetchByFkInvoiceline($object->rowid);
            $det->tms_o = NULL;
            $det->fk_user_author_o = NULL;
            unset($det->fk_invoice_line);
            $result = $det->update($user);
            return $result;
        }

		return 0;
    }

}
?>
