-- Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
-- Copyright (C) 2012      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.
CREATE TABLE IF NOT EXISTS llx_product_stock_det (
  rowid integer NOT NULL auto_increment primary key ,
  tms_i TIMESTAMP NOT NULL ,
  tms_o TIMESTAMP NULL ,
  fk_product integer NOT NULL ,
  fk_entrepot integer NOT NULL ,
  fk_user_author_i integer NOT NULL ,
  fk_user_author_o integer NULL ,
  serial VARCHAR(128) NULL ,
  fk_serial_type integer NULL ,
  price DOUBLE NULL ,
  fk_invoice_line integer NULL,
  fk_command_line integer NULL,
  fk_supplier integer NOT NULL)
ENGINE = innodb;
