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
 *  \file       dev/skeletons/productstockdet.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *              Initialy built by build_class_from_table on 2012-07-09 17:36
 */
// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

/**
 *  Put here description of your class
 */
class Productstockdet extends CommonObject
{

    public $db;                                //!< To store db handler
    public $error;                             //!< To return error code (or message)
    public $errors = array();                  //!< To return several error codes (or messages)
    //var $element='productstockdet';       //!< Id that identify managed objects
    public $table_element='product_stock_det'; //!< Name of table without prefix where object is stored
    public  $id;
    public $tms_i = '';
    public $tms_o = '';
    public $fk_product;
    public $fk_entrepot;
    public $fk_user_author_i;
    public $fk_user_author_o;
    public $serial;
    public $fk_serial_type;
    public $price;
    public $fk_invoice_line;
    public $fk_dispatch_line;
    public $fk_supplier;

    /**
     *  Constructor
     *
     *  @param  DoliDb      $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *  Create object into database
     *
     *  @param  User    $user        User that create
     *  @param  int     $notrigger   0=launch triggers after, 1=disable triggers
     *  @return int                  <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->fk_product)) $this->fk_product = trim($this->fk_product);
        if (isset($this->fk_entrepot)) $this->fk_entrepot = trim($this->fk_entrepot);
        if (isset($this->fk_user_author_i)) $this->fk_user_author_i = trim($this->fk_user_author_i);
        if (isset($this->fk_user_author_o)) $this->fk_user_author_o = trim($this->fk_user_author_o);
        if (isset($this->serial)) $this->serial = trim($this->serial);
        if (isset($this->fk_serial_type)) $this->fk_serial_type = trim($this->fk_serial_type);
        if (isset($this->price)) $this->price = trim($this->price);
        if (isset($this->fk_invoice_line)) $this->fk_invoice_line = trim($this->fk_invoice_line);
        if (isset($this->fk_dispatch_line)) $this->fk_dispatch_line = trim($this->fk_dispatch_line);
        if (isset($this->fk_supplier)) $this->fk_supplier = trim($this->fk_supplier);



        // Check parameters
        // Put here code to add control on parameters values
        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_stock_det(";

        $sql.= "tms_i,";
        $sql.= "tms_o,";
        $sql.= "fk_product,";
        $sql.= "fk_entrepot,";
        $sql.= "fk_user_author_i,";
        $sql.= "fk_user_author_o,";
        $sql.= "serial,";
        $sql.= "fk_serial_type,";
        $sql.= "price,";
        $sql.= "fk_invoice_line,";
        $sql.= "fk_dispatch_line,";
        $sql.= "fk_supplier";


        $sql.= ") VALUES (";

        $sql.= " " . ( ! isset($this->tms_i) || dol_strlen($this->tms_i) == 0 ? 'NULL' : $this->db->idate($this->tms_i)) . ",";
        $sql.= " " . ( ! isset($this->tms_o) || dol_strlen($this->tms_o) == 0 ? 'NULL' : $this->db->idate($this->tms_o)) . ",";
        $sql.= " " . ( ! isset($this->fk_product) ? 'NULL' : "'" . $this->fk_product . "'") . ",";
        $sql.= " " . ( ! isset($this->fk_entrepot) ? 'NULL' : "'" . $this->fk_entrepot . "'") . ",";
        $sql.= " " . ( ! isset($this->fk_user_author_i) ? 'NULL' : "'" . $this->fk_user_author_i . "'") . ",";
        $sql.= " " . ( ! isset($this->fk_user_author_o) ? 'NULL' : "'" . $this->fk_user_author_o . "'") . ",";
        $sql.= " " . ( ! isset($this->serial) ? 'NULL' : "'" . $this->db->escape($this->serial) . "'") . ",";
        $sql.= " " . ( ! isset($this->fk_serial_type) ? 'NULL' : "'" . $this->fk_serial_type . "'") . ",";
        $sql.= " " . ( ! isset($this->price) ? 'NULL' : "'" . $this->price . "'") . ",";
        $sql.= " " . ( ! isset($this->fk_invoice_line) ? 'NULL' : "'" . $this->fk_invoice_line . "'") . ",";
        $sql.= " " . ( ! isset($this->fk_dispatch_line) ? 'NULL' : "'" . $this->fk_dispatch_line . "'") . ",";
        $sql.= " " . ( ! isset($this->fk_supplier) ? 'NULL' : "'" . $this->fk_supplier . "'") . "";


        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ( ! $resql) {
            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if ( ! $error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "product_stock_det");

            if ( ! $notrigger) {
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
     *  @param  int     $id    Id object
     *  @return int             <0 if KO, >0 if OK
     */
    public function fetch($id)
    {
        global $langs;
        $sql = "SELECT";
        $sql.= " t.rowid,";

        $sql.= " t.tms_i,";
        $sql.= " t.tms_o,";
        $sql.= " t.fk_product,";
        $sql.= " t.fk_entrepot,";
        $sql.= " t.fk_user_author_i,";
        $sql.= " t.fk_user_author_o,";
        $sql.= " t.serial,";
        $sql.= " t.fk_serial_type,";
        $sql.= " t.price,";
        $sql.= " t.fk_invoice_line,";
        $sql.= " t.fk_dispatch_line,";
        $sql.= " t.fk_supplier";


        $sql.= " FROM " . MAIN_DB_PREFIX . "product_stock_det as t";
        $sql.= " WHERE t.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;

                $this->tms_i = $this->db->jdate($obj->tms_i);
                $this->tms_o = $this->db->jdate($obj->tms_o);
                $this->fk_product = $obj->fk_product;
                $this->fk_entrepot = $obj->fk_entrepot;
                $this->fk_user_author_i = $obj->fk_user_author_i;
                $this->fk_user_author_o = $obj->fk_user_author_o;
                $this->serial = $obj->serial;
                $this->fk_serial_type = $obj->fk_serial_type;
                $this->price = $obj->price;
                $this->fk_invoice_line = $obj->fk_invoice_line;
                $this->fk_dispatch_line = $obj->fk_dispatch_line;
                $this->fk_supplier = $obj->fk_supplier;
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
     *  @param  User    $user        User that modify
     *  @param  int     $notrigger   0=launch triggers after, 1=disable triggers
     *  @return int                  <0 if KO, >0 if OK
     */
    public function update($user = 0, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->fk_product)) $this->fk_product = trim($this->fk_product);
        if (isset($this->fk_entrepot)) $this->fk_entrepot = trim($this->fk_entrepot);
        if (isset($this->fk_user_author_i)) $this->fk_user_author_i = trim($this->fk_user_author_i);
        if (isset($this->fk_user_author_o)) $this->fk_user_author_o = trim($this->fk_user_author_o);
        if (isset($this->serial)) $this->serial = trim($this->serial);
        if (isset($this->fk_serial_type)) $this->fk_serial_type = trim($this->fk_serial_type);
        if (isset($this->price)) $this->price = trim($this->price);
        if (isset($this->fk_invoice_line)) $this->fk_invoice_line = trim($this->fk_invoice_line);
        if (isset($this->fk_dispatch_line)) $this->fk_dispatch_line = trim($this->fk_dispatch_line);
        if (isset($this->fk_supplier)) $this->fk_supplier = trim($this->fk_supplier);



        // Check parameters
        // Put here code to add control on parameters values
        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "product_stock_det SET";

        $sql.= " tms_i=" . (dol_strlen($this->tms_i) != 0 ? "'" . $this->db->idate($this->tms_i) . "'" : 'null') . ",";
        $sql.= " tms_o=" . (dol_strlen($this->tms_o) != 0 ? "'" . $this->db->idate($this->tms_o) . "'" : 'null') . ",";
        $sql.= " fk_product=" . (isset($this->fk_product) ? $this->fk_product : "null") . ",";
        $sql.= " fk_entrepot=" . (isset($this->fk_entrepot) ? $this->fk_entrepot : "null") . ",";
        $sql.= " fk_user_author_i=" . (isset($this->fk_user_author_i) ? $this->fk_user_author_i : "null") . ",";
        $sql.= " fk_user_author_o=" . (isset($this->fk_user_author_o) ? $this->fk_user_author_o : "null") . ",";
        $sql.= " serial=" . (isset($this->serial) ? "'" . $this->db->escape($this->serial) . "'" : "null") . ",";
        $sql.= " fk_serial_type=" . (isset($this->fk_serial_type) ? $this->fk_serial_type : "null") . ",";
        $sql.= " price=" . (isset($this->price) ? $this->price : "null") . ",";
        $sql.= " fk_invoice_line=" . (isset($this->fk_invoice_line) ? $this->fk_invoice_line : "null") . ",";
        $sql.= " fk_dispatch_line=" . (isset($this->fk_dispatch_line) ? $this->fk_dispatch_line : "null") . ",";
        $sql.= " fk_supplier=" . (isset($this->fk_supplier) ? $this->fk_supplier : "null") . "";


        $sql.= " WHERE rowid=" . $this->id;
        $this->db->begin();

        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ( ! $resql) {
            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if ( ! $error) {
            if ( ! $notrigger) {
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
     *  @param  User    $user       User that delete
     *  @param  int     $notrigger  0=launch triggers after, 1=disable triggers
     *  @return int                  <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        if ( ! $error) {
            if ( ! $notrigger) {
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

        if ( ! $error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "product_stock_det";
            $sql.= " WHERE rowid=" . $this->id;

            dol_syslog(get_class($this) . "::delete sql=" . $sql);
            $resql = $this->db->query($sql);
            if ( ! $resql) {
                $error ++;
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
     *  Load an object from its id and create a new one in database
     *
     *  @param  int     $fromid     Id of object to clone
     *  @return int                 New id of clone
     */
    public function createFromClone($fromid)
    {
        global $user, $langs;

        $error = 0;

        $object = new Productstockdet($this->db);

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
            $error ++;
        }

        if ( ! $error) {

        }

        // End
        if ( ! $error) {
            $this->db->commit();
            return $object->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Initialise object with example values
     *  Id must be 0 if object instance is a specimen
     *
     *  @return void
     */
    public function initAsSpecimen()
    {
        $this->id = 0;

        $this->tms_i = '';
        $this->tms_o = '';
        $this->fk_product = '';
        $this->fk_entrepot = '';
        $this->fk_user_author_i = '';
        $this->fk_user_author_o = '';
        $this->serial = '';
        $this->fk_serial_type = '';
        $this->price = '';
        $this->fk_invoice_line = '';
        $this->fk_dispatch_line = '';
        $this->fk_supplier = '';
    }

    /**
     *Returns the label of the associated serial type
     * @return string $label 
     */
    public function getSerialTypeLabel(){
    $label = '';
    if ($this->fk_serial_type) {
      $sql = 'select label from ' . MAIN_DB_PREFIX . 'c_serial_type where rowid = ' . $this->fk_serial_type;
      $resql = $this->db->query($sql);
      if ($resql) {
        if ($this->db->num_rows($resql) > 0) {
          $res = $this->db->fetch_object($resql);
          $label = $res->label;
        }
        return $label;
      } else {
        $this->error = "Error " . $this->db->lasterror();
        dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
        return -1;
      }
    }
    return $label;
  }

  /**
   * Creates an html select element to choose which serial type to use
   * @param int $selected  id of the selected option
   * @param string $htmlname  the select html name
   * @return string an html select
   */
    public function selectSerialType($selected, $htmlname)
    {
        $return = '';
        $sql = 'select rowid, label from ' . MAIN_DB_PREFIX . 'c_serial_type';
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $return = '<select class="flat" name="' . $htmlname . '">';
            $return .= '<option value="0" selected="selected"></option>';
            while ($obj = $this->db->fetch_object($resql)) {
                $option = '<option value="' . $obj->rowid . '" ';
                if ($obj->rowid == $selected) $option .= "selected=selected";
                $option .= '>' . $obj->label . '</option>';
                $return .= $option;
            }
            $return .= '</select>';
            return $return;
        }
        else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }

    //had to copy 2 functions from html.formproduct.class.php to modify because they're terrible and I don't want to modify dolibarr core files.

    /**
 * Load in cache array list of warehouses
 * If fk_product is not 0, we do not use cache
 *
 * @param	int		$fk_product		Add quantity of stock in label for product with id fk_product. Nothing if 0.
 * @param   string  $filter         Additional filter option
 * @return  int  		    		Nb of loaded lines, 0 if already loaded, <0 if KO
 */
  function loadWarehouses($fk_product = 0, $filter='')
  {
    global $conf, $langs;

    if (empty($fk_product) && count($this->cache_warehouses))
        return 0;    // Cache already loaded and we do not want a list with information specific to a product

    $sql = "SELECT e.rowid, e.label";
    if ($fk_product) $sql.= ", ps.reel";
    $sql.= " FROM " . MAIN_DB_PREFIX . "entrepot as e";
    if ($fk_product) {
      $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps on ps.fk_entrepot = e.rowid";
      $sql.= " AND ps.fk_product = '" . $fk_product . "'";
    }
    $sql.= " WHERE e.entity = " . $conf->entity;
    $sql.= " AND e.statut = 1";
    //if a filter is defined, use it
    if(!empty($filter))$sql.= " AND ".$filter;
    $sql.= " ORDER BY e.label";

    dol_syslog(get_class($this) . '::loadWarehouses sql=' . $sql, LOG_DEBUG);
    $resql = $this->db->query($sql);
    if ($resql) {
      $num = $this->db->num_rows($resql);
      $i = 0;
      while ($i < $num) {
        $obj = $this->db->fetch_object($resql);

        $this->cache_warehouses[$obj->rowid]['id'] = $obj->rowid;
        $this->cache_warehouses[$obj->rowid]['label'] = $obj->label;
        if ($fk_product) $this->cache_warehouses[$obj->rowid]['stock'] = $obj->reel;
        $i++;
      }
      return $num;
    }
    else {
      dol_print_error($this->db);
      return -1;
    }
  }


  /**
   * Creates an html select element to choose a warehouse
   * @global $langs
   * @global User $user
   * @param int $selected       id of the selected option
   * @param string $htmlname    html name of the select
   * @param string $filter      optional filter
   * @param int $empty
   * @param int $disabled
   * @param int $fk_product     product id
   * @return string   html select element
   */
  function selectWarehouses($selected = '', $htmlname = 'idwarehouse', $filter = '', $empty = 0, $disabled = 0,
      $fk_product = 0)
  {
    global $langs, $user;

    dol_syslog(get_class($this) . "::selectWarehouses $selected, $htmlname, $filter, $empty, $disabled, $fk_product",
        LOG_DEBUG);

    $this->loadWarehouses($fk_product, $filter);

    $out = '<select class="flat"' . ($disabled ? ' disabled="disabled"' : '') . ' id="' . $htmlname . '" name="' . ($htmlname . ($disabled ? '_disabled' : '')) . '">';
    if ($empty) $out.='<option value="">&nbsp;</option>';
    foreach ($this->cache_warehouses as $id => $arraytypes) {
      $out.='<option value="' . $id . '"';
      // Si selected est text, on compare avec code, sinon avec id
      if ($selected == $id) $out.=' selected="selected"';
      $out.='>';
      $out.=$arraytypes['label'];
      if ($fk_product)
          $out.=' (' . $langs->trans("Stock") . ': ' . ($arraytypes['stock'] > 0 ? $arraytypes['stock'] : '?') . ')';
      $out.='</option>';
    }
    $out.='</select>';
    if ($disabled) $out.='<input type="hidden" name="' . $htmlname . '" value="' . $selected . '">';

    return $out;
  }

  function getInfos($subject){
    $date ='';
    $author='';
    $doluser = new User($this->db);
    if($subject == 'input'){
      $date = date('d/m/y',$this->tms_i);
      $id = $this->fk_user_author_i;
    }
    else{
      $date = date('d/m/y',$this->tms_o);
      $id = $this->fk_user_author_o;
    }
    $doluser->fetch($id);
    $author = $doluser->getFullName($langs, 0, 1);
    $infos = $author.' ('.$date.')';
    return $infos;
  }

  function records($fk_dispatch_line){
    $sql = 'select rowid from '.MAIN_DB_PREFIX.'product_stock_det where fk_dispatch_line = '.$fk_dispatch_line;
    $resql = $this->db->query($sql);
    if($resql){
      return $this->db->num_rows($resql);
    }
    else{
      $this->error = "Error " . $this->db->lasterror();
      dol_syslog(get_class($this) . "::exists " . $this->error, LOG_ERR);
      return -1;
    }
  }
  
  function selectSerial($selected, $idproduct){
    global $langs;
    $res ='<select class="flat" onchange="javascript: lockQte();" id="serial" name="serial">';
    $res .= '<option value="0" >'.$langs->trans('SerialNumber').'</option>';
    if($idproduct){
      $sql = 'select rowid, serial from ' . MAIN_DB_PREFIX . 'product_stock_det where fk_product=' . $idproduct;
      $sql .= ' and tms_o is NULL';
      $resql = $this->db->query($sql);
      if ($resql && $this->db->num_rows($resql) > 0) {
        while ($obj = $this->db->fetch_object($resql)) {
          if ($selected == $obj->rowid) $sel = 'selected="selected" ';
          $res .='<option '.$sel.'value="' . $obj->rowid . '">' . $obj->serial . '</option>';
        }
      }
    }
    $res .= '</select>';
    return $res;
  }
  
  function selectSerialJSON($selected, $idproduct, $searchkey=''){
    global $langs;
    $res ='<select id="serial" name="serial">';
    $res .= '<option value="0" >'.$langs->trans('SerialNumber').'</option>';
    $sql = 'select rowid, serial from ' . MAIN_DB_PREFIX . 'product_stock_det';
    $sql .= ' where tms_o is NULL';
    if($idproduct != ''){
      $sql .= ' and fk_product = '.$idproduct;
    }
    if($searchkey != ''){
      $sql .= ' and serial like "%'.$searchkey.'%"';
    }
    $outjson = array();
    $resql = $this->db->query($sql);
    if ($resql && $this->db->num_rows($resql) > 0) {
      while ($obj = $this->db->fetch_object($resql)) {
        if ($selected == $obj->rowid) $sel = 'selected="selected" ';
        $res .='<option '.$sel.'value="' . $obj->rowid . '">' . $obj->serial . '</option>';
        $outkey = $obj->rowid;
        $outval = $obj->serial;
        $label = $obj->serial;
        if ($searchkey && $searchkey != '') $label=preg_replace('/('.preg_quote($searchkey).')/i','<strong>$1</strong>',$label,1);
        array_push($outjson,array('key'=>$outkey,'value'=>$outval, 'label'=>$label));
      }
    }
    $res .= '</select>';
    //print $res;
    return $outjson;
  }
  
  function exists($serial){
    $sql = 'select rowid from '.MAIN_DB_PREFIX.'product_stock_det where serial = '.$serial;
    $resql = $this->db->query($sql);
    if($resql && $this->db->num_rows($resql) > 0){
      $obj = $this->db->fetch_object($db);
      return $obj->rowid;
    }
    else{
      return -1;
    }
  }
  
  public function fetchByFkInvoiceline($fk_invoiceline)
  {
    global $langs;
    $sql = "SELECT";
    $sql.= " t.rowid,";

    $sql.= " t.tms_i,";
    $sql.= " t.tms_o,";
    $sql.= " t.fk_product,";
    $sql.= " t.fk_entrepot,";
    $sql.= " t.fk_user_author_i,";
    $sql.= " t.fk_user_author_o,";
    $sql.= " t.serial,";
    $sql.= " t.fk_serial_type,";
    $sql.= " t.price,";
    $sql.= " t.fk_invoice_line,";
    $sql.= " t.fk_dispatch_line,";
    $sql.= " t.fk_supplier";


    $sql.= " FROM " . MAIN_DB_PREFIX . "product_stock_det as t";
    $sql.= " WHERE t.fk_invoice_line = " . $fk_invoiceline;

    dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
    $resql = $this->db->query($sql);
    if ($resql) {
      if ($this->db->num_rows($resql)) {
        $obj = $this->db->fetch_object($resql);

        $this->id = $obj->rowid;

        $this->tms_i = $this->db->jdate($obj->tms_i);
        $this->tms_o = $this->db->jdate($obj->tms_o);
        $this->fk_product = $obj->fk_product;
        $this->fk_entrepot = $obj->fk_entrepot;
        $this->fk_user_author_i = $obj->fk_user_author_i;
        $this->fk_user_author_o = $obj->fk_user_author_o;
        $this->serial = $obj->serial;
        $this->fk_serial_type = $obj->fk_serial_type;
        $this->price = $obj->price;
        $this->fk_invoice_line = $obj->fk_invoice_line;
        $this->fk_dispatch_line = $obj->fk_dispatch_line;
        $this->fk_supplier = $obj->fk_supplier;
      }
      $this->db->free($resql);

      return 1;
    } else {
      $this->error = "Error " . $this->db->lasterror();
      dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
      return -1;
    }
  }

}

?>
