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
 *  \ingroup    detailedStock
 */
require_once(DOL_DOCUMENT_ROOT . '/detailedstock/class/productstockdet.class.php');

/**
 *  Class of triggers for detailedStock module to react to invoices and invoice lines deletion
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

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "InvoiceDelete";
        $this->description = "Reacts to invoices and invoice lines deletion";
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
    function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        global $db;
        // Bills
        if ($action == 'BILL_DELETE') {
            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
            $object->fetch_lines();
            $result = 0;
            //when the invoice is deleted, go through each line to delete the output informations of the related detailedstock lines
            foreach ($object->lines as $line) {
                $det = new Productstockdet($db);
                $det->fetchByFkInvoiceline($line->rowid);
                if($det){
                  $det->tms_o = NULL;
                  $det->fk_user_author_o = NULL;
                  unset($det->fk_invoice_line);
                  $result = $det->update($user);
                }
            }
            return 1;
        } elseif ($action == 'LINEBILL_DELETE') {
            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
            $det = new Productstockdet($db);
            //delete the output informations of the related detailedstock line
            $det->fetchByFkInvoiceline($object->rowid);
            if($det){
              $det->tms_o = NULL;
              $det->fk_user_author_o = NULL;
              unset($det->fk_invoice_line);
              $result = $det->update($user);
            }
            
            return 1;
        }

        return 0;
    }

}

?>
