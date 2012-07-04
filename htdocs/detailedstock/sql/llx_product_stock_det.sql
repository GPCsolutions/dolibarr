--Copyright (C) 2012      Cédric Salvador	    <csalvador@gpcsolutions.fr>--
CREATE TABLE IF NOT EXISTS llx_product_stock_det (
  rowid integer NOT NULL auto_increment primary key ,
  tms_i TIMESTAMP NOT NULL ,
  tms_o TIMESTAMP NULL ,
  fk_product integer NOT NULL ,
  fk_entrepot integer NOT NULL ,
  fk_user_author_i integer NULL ,
  fk_user_author_o integer NULL ,
  serial VARCHAR(128) NULL ,
  serial_type integer NULL ,
  price DOUBLE NULL )
ENGINE = innodb;
