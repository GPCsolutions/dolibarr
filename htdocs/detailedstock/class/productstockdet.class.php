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
 *  \file       htdocs/detailedstock/class/productstockdet.class.php
 *  \ingroup    detailedstock
 *  \brief
 *				Initialy built by build_class_from_table on 2012-07-04 10:05
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Put here description of your class
 */
class Productstockdet extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='productstockdet';			//!< Id that identify managed objects
	//var $table_element='productstockdet';	//!< Name of table without prefix where object is stored

    var $id;

	var $tms_i='';
	var $tms_o='';
	var $fk_product;
	var $fk_entrepot;
	var $fk_user_author_i;
	var $fk_user_author_o;
	var $serial;
	var $serial_type;
	var $price;




    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
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
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_product)) $this->fk_product=trim($this->fk_product);
		if (isset($this->fk_entrepot)) $this->fk_entrepot=trim($this->fk_entrepot);
		if (isset($this->fk_user_author_i)) $this->fk_user_author_i=trim($this->fk_user_author_i);
		if (isset($this->fk_user_author_o)) $this->fk_user_author_o=trim($this->fk_user_author_o);
		if (isset($this->serial)) $this->serial=trim($this->serial);
		if (isset($this->serial_type)) $this->serial_type=trim($this->serial_type);
		if (isset($this->price)) $this->price=trim($this->price);



		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_stock_det(";

		$sql.= "tms_i,";
		$sql.= "tms_o,";
		$sql.= "fk_product,";
		$sql.= "fk_entrepot,";
		$sql.= "fk_user_author_i,";
		$sql.= "fk_user_author_o,";
		$sql.= "serial,";
		$sql.= "serial_type,";
		$sql.= "price";


        $sql.= ") VALUES (";

		$sql.= " ".(! isset($this->tms_i) || dol_strlen($this->tms_i)==0?'NULL':$this->db->idate($this->tms_i)).",";
		$sql.= " ".(! isset($this->tms_o) || dol_strlen($this->tms_o)==0?'NULL':$this->db->idate($this->tms_o)).",";
		$sql.= " ".(! isset($this->fk_product)?'NULL':"'".$this->fk_product."'").",";
		$sql.= " ".(! isset($this->fk_entrepot)?'NULL':"'".$this->fk_entrepot."'").",";
		$sql.= " ".(! isset($this->fk_user_author_i)?'NULL':"'".$this->fk_user_author_i."'").",";
		$sql.= " ".(! isset($this->fk_user_author_o)?'NULL':"'".$this->fk_user_author_o."'").",";
		$sql.= " ".(! isset($this->serial)?'NULL':"'".$this->db->escape($this->serial)."'").",";
		$sql.= " ".(! isset($this->serial_type)?'NULL':"'".$this->serial_type."'").",";
		$sql.= " ".(! isset($this->price)?'NULL':"'".$this->price."'")."";


		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."product_stock_det");

			if (! $notrigger)
			{
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
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
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
    function fetch($id)
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
		$sql.= " t.serial_type,";
		$sql.= " t.price";


        $sql.= " FROM ".MAIN_DB_PREFIX."product_stock_det as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;

				$this->tms_i = $this->db->jdate($obj->tms_i);
				$this->tms_o = $this->db->jdate($obj->tms_o);
				$this->fk_product = $obj->fk_product;
				$this->fk_entrepot = $obj->fk_entrepot;
				$this->fk_user_author_i = $obj->fk_user_author_i;
				$this->fk_user_author_o = $obj->fk_user_author_o;
				$this->serial = $obj->serial;
				$this->serial_type = $obj->serial_type;
				$this->price = $obj->price;


            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
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
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_product)) $this->fk_product=trim($this->fk_product);
		if (isset($this->fk_entrepot)) $this->fk_entrepot=trim($this->fk_entrepot);
		if (isset($this->fk_user_author_i)) $this->fk_user_author_i=trim($this->fk_user_author_i);
		if (isset($this->fk_user_author_o)) $this->fk_user_author_o=trim($this->fk_user_author_o);
		if (isset($this->serial)) $this->serial=trim($this->serial);
		if (isset($this->serial_type)) $this->serial_type=trim($this->serial_type);
		if (isset($this->price)) $this->price=trim($this->price);



		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."product_stock_det SET";

		$sql.= " tms_i=".(dol_strlen($this->tms_i)!=0 ? "'".$this->db->idate($this->tms_i)."'" : 'null').",";
		$sql.= " tms_o=".(dol_strlen($this->tms_o)!=0 ? "'".$this->db->idate($this->tms_o)."'" : 'null').",";
		$sql.= " fk_product=".(isset($this->fk_product)?$this->fk_product:"null").",";
		$sql.= " fk_entrepot=".(isset($this->fk_entrepot)?$this->fk_entrepot:"null").",";
		$sql.= " fk_user_author_i=".(isset($this->fk_user_author_i)?$this->fk_user_author_i:"null").",";
		$sql.= " fk_user_author_o=".(isset($this->fk_user_author_o)?$this->fk_user_author_o:"null").",";
		$sql.= " serial=".(isset($this->serial)?"'".$this->db->escape($this->serial)."'":"null").",";
		$sql.= " serial_type=".(isset($this->serial_type)?$this->serial_type:"null").",";
		$sql.= " price=".(isset($this->price)?$this->price:"null")."";


        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
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
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }


 	/**
	 *  Delete object in database
	 *
     *	@param  User	$user        User that delete
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
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

		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_stock_det";
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Productstockdet($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{


		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->tms_i='';
		$this->tms_o='';
		$this->fk_product='';
		$this->fk_entrepot='';
		$this->fk_user_author_i='';
		$this->fk_user_author_o='';
		$this->serial='';
		$this->serial_type='';
		$this->price='';


	}

}
?>
