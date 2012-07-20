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
 *  \file       htdocs/detailedstock/class/commandefournisseurdispatch.class.php
 *  \ingroup    detailedStock
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");

/**
 * class to handle the commande_fournisseur_dispatch table informations
 */
class Commandefournisseurdispatch extends CommonObject
{

  public $db;       //!< To store db handler
  public $error;       //!< To return error code (or message)
  public $errors = array();    //!< To return several error codes (or messages)
  public $id;
  public $fk_commande;
  public $fk_product;
  public $qty;
  public $fk_entrepot;
  public $fk_user;
  public $datec = '';

  /**
   *  Constructor
   *
   *  @param	DoliDb		$db      Database handler
   */
  public function __construct($db)
  {
    $this->db = $db;
    return 1;
  }

  /**
   *  Create object into database
   *
   *  @param	User	$user        User that create
   *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
   *  @return int      		   	 <0 if KO, Id of created object if OK
   */
  public function create($user, $notrigger = 0)
  {
    global $conf, $langs;
    $error = 0;

    // Clean parameters

    if (isset($this->fk_commande)) $this->fk_commande = trim($this->fk_commande);
    if (isset($this->fk_product)) $this->fk_product = trim($this->fk_product);
    if (isset($this->qty)) $this->qty = trim($this->qty);
    if (isset($this->fk_entrepot)) $this->fk_entrepot = trim($this->fk_entrepot);
    if (isset($this->fk_user)) $this->fk_user = trim($this->fk_user);



    // Check parameters
    // Put here code to add control on parameters values
    // Insert request
    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch(";

    $sql.= "fk_commande,";
    $sql.= "fk_product,";
    $sql.= "qty,";
    $sql.= "fk_entrepot,";
    $sql.= "fk_user,";
    $sql.= "datec";


    $sql.= ") VALUES (";

    $sql.= " " . (!isset($this->fk_commande) ? 'NULL' : "'" . $this->fk_commande . "'") . ",";
    $sql.= " " . (!isset($this->fk_product) ? 'NULL' : "'" . $this->fk_product . "'") . ",";
    $sql.= " " . (!isset($this->qty) ? 'NULL' : "'" . $this->qty . "'") . ",";
    $sql.= " " . (!isset($this->fk_entrepot) ? 'NULL' : "'" . $this->fk_entrepot . "'") . ",";
    $sql.= " " . (!isset($this->fk_user) ? 'NULL' : "'" . $this->fk_user . "'") . ",";
    $sql.= " " . (!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : $this->db->idate($this->datec)) . "";


    $sql.= ")";

    $this->db->begin();

    dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
    $resql = $this->db->query($sql);
    if (!$resql) {
      $error++;
      $this->errors[] = "Error " . $this->db->lasterror();
    }

    if (!$error) {
      $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "commande_fournisseur_dispatch");

      if (!$notrigger) {
        // Uncomment this and change MYOBJECT to your own tag if you
        // want this action call a trigger.
        //// Call triggers
        //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        //$interface=new Interfaces($this->db);
        //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
        //// End call triggers
      }
    }

    // Commit or rollback
    if ($error) {
      foreach ($this->errors as $errmsg) {
        dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
        $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
      }
      $this->db->rollback();
      return -1 * $error;
    } else {
      $this->db->commit();
      return $this->id;
    }
  }

  /**
   *  Load object in memory from database
   *
   *  @param	int		$id    Id object
   *  @return int          	<0 if KO, >0 if OK
   */
  public function fetch($id)
  {
    global $langs;
    $sql = "SELECT";
    $sql.= " t.rowid,";

    $sql.= " t.fk_commande,";
    $sql.= " t.fk_product,";
    $sql.= " t.qty,";
    $sql.= " t.fk_entrepot,";
    $sql.= " t.fk_user,";
    $sql.= " t.datec";


    $sql.= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as t";
    $sql.= " WHERE t.rowid = " . $id;

    dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
    $resql = $this->db->query($sql);
    if ($resql) {
      if ($this->db->num_rows($resql)) {
        $obj = $this->db->fetch_object($resql);

        $this->id = $obj->rowid;

        $this->fk_commande = $obj->fk_commande;
        $this->fk_product = $obj->fk_product;
        $this->qty = $obj->qty;
        $this->fk_entrepot = $obj->fk_entrepot;
        $this->fk_user = $obj->fk_user;
        $this->datec = $this->db->jdate($obj->datec);
      }
      $this->db->free($resql);

      return 1;
    } else {
      $this->error = "Error " . $this->db->lasterror();
      dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
      return -1;
    }
  }

  /**
   *  Update object into database
   *
   *  @param	User	$user        User that modify
   *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
   *  @return int     		   	 <0 if KO, >0 if OK
   */
  public function update($user = 0, $notrigger = 0)
  {
    global $conf, $langs;
    $error = 0;

    // Clean parameters

    if (isset($this->fk_commande)) $this->fk_commande = trim($this->fk_commande);
    if (isset($this->fk_product)) $this->fk_product = trim($this->fk_product);
    if (isset($this->qty)) $this->qty = trim($this->qty);
    if (isset($this->fk_entrepot)) $this->fk_entrepot = trim($this->fk_entrepot);
    if (isset($this->fk_user)) $this->fk_user = trim($this->fk_user);



    // Check parameters
    // Put here code to add control on parameters values
    // Update request
    $sql = "UPDATE " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch SET";

    $sql.= " fk_commande=" . (isset($this->fk_commande) ? $this->fk_commande : "null") . ",";
    $sql.= " fk_product=" . (isset($this->fk_product) ? $this->fk_product : "null") . ",";
    $sql.= " qty=" . (isset($this->qty) ? $this->qty : "null") . ",";
    $sql.= " fk_entrepot=" . (isset($this->fk_entrepot) ? $this->fk_entrepot : "null") . ",";
    $sql.= " fk_user=" . (isset($this->fk_user) ? $this->fk_user : "null") . ",";
    $sql.= " datec=" . (dol_strlen($this->datec) != 0 ? "'" . $this->db->idate($this->datec) . "'" : 'null') . "";


    $sql.= " WHERE rowid=" . $this->id;

    $this->db->begin();

    dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
    $resql = $this->db->query($sql);
    if (!$resql) {
      $error++;
      $this->errors[] = "Error " . $this->db->lasterror();
    }

    if (!$error) {
      if (!$notrigger) {
        // Uncomment this and change MYOBJECT to your own tag if you
        // want this action call a trigger.
        //// Call triggers
        //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        //$interface=new Interfaces($this->db);
        //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
        //// End call triggers
      }
    }

    // Commit or rollback
    if ($error) {
      foreach ($this->errors as $errmsg) {
        dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
        $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
      }
      $this->db->rollback();
      return -1 * $error;
    } else {
      $this->db->commit();
      return 1;
    }
  }

  /**
   *  Delete object in database
   *
   * 	@param  User	$user        User that delete
   *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
   *  @return	int					 <0 if KO, >0 if OK
   */
  public function delete($user, $notrigger = 0)
  {
    global $conf, $langs;
    $error = 0;

    $this->db->begin();

    if (!$error) {
      if (!$notrigger) {
        // Uncomment this and change MYOBJECT to your own tag if you
        // want this action call a trigger.
        //// Call triggers
        //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        //$interface=new Interfaces($this->db);
        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
        //// End call triggers
      }
    }

    if (!$error) {
      $sql = "DELETE FROM " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch";
      $sql.= " WHERE rowid=" . $this->id;

      dol_syslog(get_class($this) . "::delete sql=" . $sql);
      $resql = $this->db->query($sql);
      if (!$resql) {
        $error++;
        $this->errors[] = "Error " . $this->db->lasterror();
      }
    }

    // Commit or rollback
    if ($error) {
      foreach ($this->errors as $errmsg) {
        dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
        $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
      }
      $this->db->rollback();
      return -1 * $error;
    } else {
      $this->db->commit();
      return 1;
    }
  }

  /**
   * 	Load an object from its id and create a new one in database
   *
   * 	@param	int		$fromid     Id of object to clone
   * 	@return	int					New id of clone
   */
  public function createFromClone($fromid)
  {
    global $user, $langs;

    $error = 0;

    $object = new Commandefournisseurdispatch($this->db);

    $this->db->begin();

    // Load source object
    $object->fetch($fromid);
    $object->id = 0;
    $object->statut = 0;

    // Clear fields
    // ...
    // Create clone
    $result = $object->create($user);

    // Other options
    if ($result < 0) {
      $this->error = $object->error;
      $error++;
    }

    if (!$error) {
      
    }

    // End
    if (!$error) {
      $this->db->commit();
      return $object->id;
    } else {
      $this->db->rollback();
      return -1;
    }
  }

  /**
   * 	Initialise object with example values
   * 	Id must be 0 if object instance is a specimen
   *
   * 	@return	void
   */
  public function initAsSpecimen()
  {
    $this->id = 0;

    $this->fk_commande = '';
    $this->fk_product = '';
    $this->qty = '';
    $this->fk_entrepot = '';
    $this->fk_user = '';
    $this->datec = '';
  }

}

?>
