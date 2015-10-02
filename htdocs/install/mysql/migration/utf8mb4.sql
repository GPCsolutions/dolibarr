-- ============================================================================
-- Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.

-- Migrates MySQL or MariaDB databases to utf8mb4 encoding
-- for full unicode support
-- ============================================================================

-- Prevent data loss (truncation)
SET SESSION sql_mode = 'STRICT_ALL_TABLES';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES';

-- Reduce indexed fields size to fit into the default 767 bytes index
ALTER TABLE llx_bookmark MODIFY COLUMN url VARCHAR(191) NOT NULL;
ALTER TABLE llx_boxes_def MODIFY COLUMN file VARCHAR(191) NOT NULL;
ALTER TABLE llx_budget_lines MODIFY COLUMN fk_project_ids VARCHAR(191) NOT NULL;
ALTER TABLE llx_c_email_templates MODIFY COLUMN label VARCHAR(191);
ALTER TABLE llx_c_ziptown MODIFY COLUMN town VARCHAR(191) NOT NULL;
ALTER TABLE llx_categorie MODIFY COLUMN label VARCHAR(191) NOT NULL;
ALTER TABLE llx_commande_fournisseur MODIFY COLUMN ref VARCHAR(191) NOT NULL;
ALTER TABLE llx_const MODIFY COLUMN name VARCHAR(191) NOT NULL;
ALTER TABLE llx_element_tag MODIFY COLUMN tag VARCHAR(191) NOT NULL;
ALTER TABLE llx_entrepot MODIFY COLUMN label VARCHAR(191) NOT NULL;
ALTER TABLE llx_facture_fourn MODIFY COLUMN ref VARCHAR(191) NOT NULL;
ALTER TABLE llx_facture_fourn MODIFY COLUMN ref_supplier VARCHAR(191) NOT NULL;
ALTER TABLE llx_holiday_config MODIFY COLUMN name VARCHAR(191) NOT NULL UNIQUE;
ALTER TABLE llx_holiday_events MODIFY COLUMN name VARCHAR(191) NOT NULL;
ALTER TABLE llx_menu MODIFY COLUMN url VARCHAR(191) NOT NULL;
ALTER TABLE llx_opensurvey_comments MODIFY COLUMN id_sondage VARCHAR(191) NOT NULL;
ALTER TABLE llx_product MODIFY COLUMN label VARCHAR(191) NOT NULL;
ALTER TABLE llx_product MODIFY COLUMN barcode VARCHAR(191) NOT NULL;
ALTER TABLE llx_societe MODIFY COLUMN barcode VARCHAR(191);
ALTER TABLE llx_user_param MODIFY COLUMN param VARCHAR(191) NOT NULL;
ALTER TABLE llx_usergroup MODIFY COLUMN nom VARCHAR(191) NOT NULL;

-- TODO:
-- For each database:
-- ALTER DATABASE database_name
-- CHARACTER SET = utf8mb4
-- COLLATE = utf8mb4_unicode_ci;
-- For each table:
-- ALTER TABLE table_name CONVERT TO CHARACTER SET utf8mb4
-- COLLATE utf8mb4_unicode_ci;
