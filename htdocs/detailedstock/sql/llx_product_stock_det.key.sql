--Copyright (C) 2012      Cédric Salvador	    <csalvador@gpcsolutions.fr>--

ALTER TABLE llx_product_stock_det ADD INDEX idx_product_stock_det_fk_invoice_line (fk_invoice_line);
ALTER TABLE llx_product_stock_det ADD INDEX idx_product_stock_det_fk_supplier (fk_supplier);
ALTER TABLE llx_product_stock_det ADD INDEX idx_product_stock_det_fk_product (fk_product);
ALTER TABLE llx_product_stock_det ADD INDEX idx_product_stock_det_fk_entrepot (fk_entrepot);
ALTER TABLE llx_product_stock_det ADD INDEX idx_product_stock_det_fk_user_author_i (fk_user_author_i);
ALTER TABLE llx_product_stock_det ADD INDEX idx_product_stock_det_fk_user_author_o (fk_user_author_o);
ALTER TABLE llx_product_stock_det ADD INDEX idx_product_stock_det_fk_command_line (fk_command_line);
ALTER TABLE llx_product_stock_det ADD INDEX idx_product_stock_det_fk_serial_type (fk_serial_type);

ALTER TABLE llx_product_stock_det ADD CONSTRAINT fk_product_stock_det_fk_invoice_line FOREIGN KEY(fk_invoice_line) REFERENCES llx_facturedet (rowid);
ALTER TABLE llx_product_stock_det ADD CONSTRAINT fk_product_stock_det_fk_supplier FOREIGN KEY(fk_supplier) REFERENCES llx_societe (rowid);
ALTER TABLE llx_product_stock_det ADD CONSTRAINT fk_product_stock_det_fk_product FOREIGN KEY(fk_product) REFERENCES llx_product (rowid);
ALTER TABLE llx_product_stock_det ADD CONSTRAINT fk_product_stock_det_fk_entrepot FOREIGN KEY(fk_entrepot) REFERENCES llx_entrepot (rowid);
ALTER TABLE llx_product_stock_det ADD CONSTRAINT fk_product_stock_det_fk_user_author_i FOREIGN KEY(fk_user_author_i) REFERENCES llx_user (rowid);
ALTER TABLE llx_product_stock_det ADD CONSTRAINT fk_product_stock_det_fk_user_author_o FOREIGN KEY(fk_user_author_o) REFERENCES llx_user (rowid);
ALTER TABLE llx_product_stock_det ADD CONSTRAINT fk_product_stock_det_fk_command_line FOREIGN KEY(fk_command_line) REFERENCES llx_commandedet (rowid);
ALTER TABLE llx_product_stock_det ADD CONSTRAINT fk_product_stock_det_fk_serial_type FOREIGN KEY(fk_serial_type) REFERENCES llx_c_serial_type (rowid);