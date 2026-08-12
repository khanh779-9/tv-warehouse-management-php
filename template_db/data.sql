-- TV Warehouse Management Portfolio
-- Target: MySQL 8.x
-- Domain: television warehouse/distribution only; every physical TV is serial-tracked.
-- Includes: database creation, final schema, indexes, foreign keys and realistic seed data.

DROP DATABASE IF EXISTS warehouse_portfolio;
CREATE DATABASE warehouse_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE warehouse_portfolio;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(255) NOT NULL,
 email VARCHAR(255) NOT NULL UNIQUE,
 email_verified_at TIMESTAMP NULL,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','manager','staff','viewer') NOT NULL DEFAULT 'staff',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 remember_token VARCHAR(100) NULL,
 created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE password_reset_tokens (email VARCHAR(255) PRIMARY KEY,token VARCHAR(255) NOT NULL,created_at TIMESTAMP NULL) ENGINE=InnoDB;
CREATE TABLE personal_access_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,tokenable_type VARCHAR(255) NOT NULL,tokenable_id BIGINT UNSIGNED NOT NULL,name TEXT NOT NULL,
 token VARCHAR(64) NOT NULL UNIQUE,abilities TEXT NULL,last_used_at TIMESTAMP NULL,expires_at TIMESTAMP NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,
 INDEX idx_pat_tokenable(tokenable_type,tokenable_id),INDEX idx_pat_expires(expires_at)
) ENGINE=InnoDB;

CREATE TABLE warehouses (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(30) NOT NULL UNIQUE,name VARCHAR(120) NOT NULL,address VARCHAR(255) NULL,phone VARCHAR(30) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE warehouse_locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,warehouse_id BIGINT UNSIGNED NOT NULL,code VARCHAR(60) NOT NULL,zone VARCHAR(60) NULL,aisle VARCHAR(40) NULL,rack VARCHAR(40) NULL,shelf VARCHAR(40) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_location(warehouse_id,code),FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL UNIQUE,description TEXT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE suppliers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(30) NOT NULL UNIQUE,name VARCHAR(160) NOT NULL,contact_name VARCHAR(120) NULL,email VARCHAR(255) NULL,phone VARCHAR(30) NULL,address VARCHAR(255) NULL,
 tax_code VARCHAR(50) NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE customers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(30) NOT NULL UNIQUE,name VARCHAR(160) NOT NULL,email VARCHAR(255) NULL,phone VARCHAR(30) NULL,address VARCHAR(255) NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,sku VARCHAR(60) NOT NULL UNIQUE,barcode VARCHAR(100) NULL UNIQUE,name VARCHAR(180) NOT NULL,
 brand VARCHAR(80) NOT NULL,model_code VARCHAR(80) NOT NULL,product_type VARCHAR(40) NOT NULL DEFAULT 'TV',color VARCHAR(60) NULL,screen_size_inch SMALLINT UNSIGNED NOT NULL,
 resolution VARCHAR(40) NOT NULL DEFAULT '4K UHD',panel_type VARCHAR(40) NOT NULL DEFAULT 'LED',operating_system VARCHAR(80) NULL,refresh_rate_hz SMALLINT UNSIGNED NOT NULL DEFAULT 60,
 is_serialized TINYINT(1) NOT NULL DEFAULT 1,warranty_months SMALLINT UNSIGNED NOT NULL DEFAULT 12,specs JSON NULL,category_id BIGINT UNSIGNED NULL,unit VARCHAR(30) NOT NULL DEFAULT 'piece',
 cost_price DECIMAL(15,2) NOT NULL DEFAULT 0,selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,min_stock DECIMAL(15,3) NOT NULL DEFAULT 0,description TEXT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,INDEX idx_products_name(name,is_active),INDEX idx_products_model(brand,model_code),INDEX idx_products_type(product_type,is_serialized)
) ENGINE=InnoDB;
CREATE TABLE stocks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,warehouse_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
 reserved_quantity DECIMAL(15,3) NOT NULL DEFAULT 0,avg_cost DECIMAL(15,2) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_stock(warehouse_id,product_id),FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CHECK (quantity >= 0),CHECK (reserved_quantity >= 0),CHECK (reserved_quantity <= quantity)
) ENGINE=InnoDB;

CREATE TABLE purchase_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,po_number VARCHAR(60) NOT NULL UNIQUE,supplier_id BIGINT UNSIGNED NOT NULL,warehouse_id BIGINT UNSIGNED NOT NULL,
 status ENUM('DRAFT','ORDERED','PARTIAL','RECEIVED','CANCELLED') NOT NULL DEFAULT 'DRAFT',approval_status VARCHAR(30) NOT NULL DEFAULT 'PENDING',ordered_at DATE NOT NULL,expected_at DATE NULL,notes TEXT NULL,
 created_by BIGINT UNSIGNED NOT NULL,approved_by BIGINT UNSIGNED NULL,approved_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(supplier_id) REFERENCES suppliers(id),FOREIGN KEY(warehouse_id) REFERENCES warehouses(id),FOREIGN KEY(created_by) REFERENCES users(id),FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE purchase_order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,purchase_order_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,quantity DECIMAL(15,3) NOT NULL,received_quantity DECIMAL(15,3) NOT NULL DEFAULT 0,unit_cost DECIMAL(15,2) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_po_product(purchase_order_id,product_id),
 FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE sales_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,so_number VARCHAR(60) NOT NULL UNIQUE,customer_id BIGINT UNSIGNED NULL,warehouse_id BIGINT UNSIGNED NOT NULL,
 status ENUM('DRAFT','CONFIRMED','PARTIAL','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',channel VARCHAR(40) NOT NULL DEFAULT 'DEALER',external_reference VARCHAR(100) NULL,
 ordered_at DATE NOT NULL,notes TEXT NULL,created_by BIGINT UNSIGNED NOT NULL,reserved_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,FOREIGN KEY(warehouse_id) REFERENCES warehouses(id),FOREIGN KEY(created_by) REFERENCES users(id),INDEX idx_so_channel(channel,status)
) ENGINE=InnoDB;
CREATE TABLE sales_order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,sales_order_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,quantity DECIMAL(15,3) NOT NULL,issued_quantity DECIMAL(15,3) NOT NULL DEFAULT 0,unit_price DECIMAL(15,2) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_so_product(sales_order_id,product_id),
 FOREIGN KEY(sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;
CREATE TABLE stock_reservations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,sales_order_id BIGINT UNSIGNED NOT NULL,sales_order_item_id BIGINT UNSIGNED NOT NULL,warehouse_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,
 quantity DECIMAL(15,3) NOT NULL,status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE',expires_at TIMESTAMP NULL,created_by BIGINT UNSIGNED NOT NULL,released_at TIMESTAMP NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,FOREIGN KEY(sales_order_item_id) REFERENCES sales_order_items(id) ON DELETE CASCADE,FOREIGN KEY(warehouse_id) REFERENCES warehouses(id),FOREIGN KEY(product_id) REFERENCES products(id),FOREIGN KEY(created_by) REFERENCES users(id),
 INDEX idx_reservation_stock(warehouse_id,product_id,status),INDEX idx_reservation_order(sales_order_id,status)
) ENGINE=InnoDB;

CREATE TABLE stock_transfers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,transfer_number VARCHAR(60) NOT NULL UNIQUE,from_warehouse_id BIGINT UNSIGNED NOT NULL,to_warehouse_id BIGINT UNSIGNED NOT NULL,
 status ENUM('DRAFT','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',approval_status VARCHAR(30) NOT NULL DEFAULT 'PENDING',notes TEXT NULL,created_by BIGINT UNSIGNED NOT NULL,approved_by BIGINT UNSIGNED NULL,
 approved_at TIMESTAMP NULL,completed_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(from_warehouse_id) REFERENCES warehouses(id),FOREIGN KEY(to_warehouse_id) REFERENCES warehouses(id),FOREIGN KEY(created_by) REFERENCES users(id),FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE stock_transfer_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,stock_transfer_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,quantity DECIMAL(15,3) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_transfer_product(stock_transfer_id,product_id),FOREIGN KEY(stock_transfer_id) REFERENCES stock_transfers(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE product_serials (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id BIGINT UNSIGNED NOT NULL,warehouse_id BIGINT UNSIGNED NULL,warehouse_location_id BIGINT UNSIGNED NULL,
 serial_number VARCHAR(120) NOT NULL UNIQUE,`condition` VARCHAR(30) NOT NULL DEFAULT 'NEW',status VARCHAR(30) NOT NULL DEFAULT 'IN_STOCK',
 purchase_order_item_id BIGINT UNSIGNED NULL,sales_order_item_id BIGINT UNSIGNED NULL,received_at TIMESTAMP NULL,sold_at TIMESTAMP NULL,warranty_start_at DATE NULL,warranty_end_at DATE NULL,notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(product_id) REFERENCES products(id),FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,FOREIGN KEY(warehouse_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
 FOREIGN KEY(purchase_order_item_id) REFERENCES purchase_order_items(id) ON DELETE SET NULL,FOREIGN KEY(sales_order_item_id) REFERENCES sales_order_items(id) ON DELETE SET NULL,
 INDEX idx_serial_stock(product_id,warehouse_id,status),INDEX idx_serial_condition(`condition`,status)
) ENGINE=InnoDB;

CREATE TABLE device_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_serial_id BIGINT UNSIGNED NOT NULL,event_type VARCHAR(40) NOT NULL,from_warehouse_id BIGINT UNSIGNED NULL,to_warehouse_id BIGINT UNSIGNED NULL,
 reference_type VARCHAR(60) NULL,reference_id BIGINT UNSIGNED NULL,metadata JSON NULL,performed_by BIGINT UNSIGNED NULL,occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(product_serial_id) REFERENCES product_serials(id) ON DELETE CASCADE,FOREIGN KEY(from_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,FOREIGN KEY(to_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,FOREIGN KEY(performed_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_device_event_serial(product_serial_id,occurred_at),INDEX idx_device_event_ref(reference_type,reference_id)
) ENGINE=InnoDB;

CREATE TABLE stock_counts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,count_number VARCHAR(60) NOT NULL UNIQUE,warehouse_id BIGINT UNSIGNED NOT NULL,status ENUM('DRAFT','FINALIZED') NOT NULL DEFAULT 'DRAFT',notes TEXT NULL,created_by BIGINT UNSIGNED NOT NULL,finalized_at TIMESTAMP NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(warehouse_id) REFERENCES warehouses(id),FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE stock_count_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,stock_count_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,system_quantity DECIMAL(15,3) NOT NULL,counted_quantity DECIMAL(15,3) NOT NULL,difference DECIMAL(15,3) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_count_product(stock_count_id,product_id),FOREIGN KEY(stock_count_id) REFERENCES stock_counts(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;
CREATE TABLE stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,warehouse_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,type ENUM('IN','OUT','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT_POSITIVE','ADJUSTMENT_NEGATIVE') NOT NULL,
 quantity DECIMAL(15,3) NOT NULL,unit_cost DECIMAL(15,2) NOT NULL DEFAULT 0,reference_type VARCHAR(40) NOT NULL,reference_id BIGINT UNSIGNED NOT NULL,note VARCHAR(255) NULL,performed_by BIGINT UNSIGNED NOT NULL,occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(warehouse_id) REFERENCES warehouses(id),FOREIGN KEY(product_id) REFERENCES products(id),FOREIGN KEY(performed_by) REFERENCES users(id),INDEX idx_movement_lookup(product_id,warehouse_id,occurred_at),INDEX idx_movement_ref(reference_type,reference_id)
) ENGINE=InnoDB;

CREATE TABLE customer_returns (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,return_number VARCHAR(60) NOT NULL UNIQUE,customer_id BIGINT UNSIGNED NULL,sales_order_id BIGINT UNSIGNED NULL,warehouse_id BIGINT UNSIGNED NOT NULL,status VARCHAR(30) NOT NULL DEFAULT 'RECEIVED',
 reason VARCHAR(80) NOT NULL,notes TEXT NULL,created_by BIGINT UNSIGNED NOT NULL,inspected_by BIGINT UNSIGNED NULL,received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,inspected_at TIMESTAMP NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,FOREIGN KEY(sales_order_id) REFERENCES sales_orders(id) ON DELETE SET NULL,FOREIGN KEY(warehouse_id) REFERENCES warehouses(id),FOREIGN KEY(created_by) REFERENCES users(id),FOREIGN KEY(inspected_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE customer_return_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,customer_return_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,product_serial_id BIGINT UNSIGNED NULL,quantity DECIMAL(15,3) NOT NULL DEFAULT 1,item_reason VARCHAR(100) NULL,disposition VARCHAR(30) NOT NULL DEFAULT 'PENDING',inspection_note TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(customer_return_id) REFERENCES customer_returns(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id),FOREIGN KEY(product_serial_id) REFERENCES product_serials(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE warranty_claims (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,claim_number VARCHAR(60) NOT NULL UNIQUE,customer_id BIGINT UNSIGNED NULL,product_serial_id BIGINT UNSIGNED NOT NULL,status VARCHAR(30) NOT NULL DEFAULT 'RECEIVED',issue_description TEXT NOT NULL,diagnosis TEXT NULL,resolution TEXT NULL,
 created_by BIGINT UNSIGNED NOT NULL,handled_by BIGINT UNSIGNED NULL,received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,completed_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,FOREIGN KEY(product_serial_id) REFERENCES product_serials(id) ON DELETE CASCADE,FOREIGN KEY(created_by) REFERENCES users(id),FOREIGN KEY(handled_by) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_warranty_status(status,received_at)
) ENGINE=InnoDB;
CREATE TABLE audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NULL,action VARCHAR(80) NOT NULL,entity_type VARCHAR(100) NOT NULL,entity_id BIGINT UNSIGNED NULL,before_values JSON NULL,after_values JSON NULL,ip_address VARCHAR(64) NULL,user_agent VARCHAR(500) NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX idx_audit_entity(entity_type,entity_id),INDEX idx_audit_user(user_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE jobs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,queue VARCHAR(255) NOT NULL,payload LONGTEXT NOT NULL,attempts TINYINT UNSIGNED NOT NULL,reserved_at INT UNSIGNED NULL,available_at INT UNSIGNED NOT NULL,created_at INT UNSIGNED NOT NULL,INDEX jobs_queue_index(queue)) ENGINE=InnoDB;
CREATE TABLE failed_jobs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,uuid VARCHAR(255) NOT NULL UNIQUE,connection TEXT NOT NULL,queue TEXT NOT NULL,payload LONGTEXT NOT NULL,exception LONGTEXT NOT NULL,failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
SET FOREIGN_KEY_CHECKS=1;

-- ================================================================
-- SAMPLE DATA
-- Password for all accounts: password
-- This portfolio uses fictional TV brand/model names and is not affiliated with any manufacturer.
-- ================================================================
INSERT INTO users(name,email,password,role,is_active) VALUES
('Portfolio Admin','admin@warehouse.local','$2y$12$oY/o1DuQgPhqJOOhbWE2Lej7DqGJ.qiswRL9/eKgfjBzBzAMLICiG','admin',1),
('Warehouse Manager','manager@warehouse.local','$2y$12$oY/o1DuQgPhqJOOhbWE2Lej7DqGJ.qiswRL9/eKgfjBzBzAMLICiG','manager',1),
('Warehouse Staff','staff@warehouse.local','$2y$12$oY/o1DuQgPhqJOOhbWE2Lej7DqGJ.qiswRL9/eKgfjBzBzAMLICiG','staff',1),
('Read Only User','viewer@warehouse.local','$2y$12$oY/o1DuQgPhqJOOhbWE2Lej7DqGJ.qiswRL9/eKgfjBzBzAMLICiG','viewer',1);

INSERT INTO warehouses(code,name,address,phone) VALUES
('HCM-DC','HCM TV Distribution Center','Thu Duc City, Ho Chi Minh City','028-1111-2222'),
('HCM-SOUTH','HCM South TV Hub','District 7, Ho Chi Minh City','028-2222-3333'),
('HCM-SERVICE','HCM TV Service / Return Center','Tan Phu, Ho Chi Minh City','028-3333-4444');
INSERT INTO warehouse_locations(warehouse_id,code,zone,aisle,rack,shelf) VALUES
(1,'TV-43-A01','TV-43','A','01','FLOOR'),(1,'TV-55-B01','TV-55','B','01','FLOOR'),(1,'TV-65-C01','TV-65','C','01','FLOOR'),(1,'TV-75-D01','TV-75','D','01','FLOOR'),
(2,'TV-SOUTH-01','TV','S','01','FLOOR'),(3,'QUARANTINE-01','RETURN','Q','01','FLOOR');
INSERT INTO categories(name,description) VALUES
('LED / 4K Smart TV','Mainstream smart televisions'),('QLED / Mini LED TV','High-brightness premium televisions'),('OLED TV','Premium self-emissive panel televisions');
INSERT INTO suppliers(code,name,contact_name,email,phone,address,tax_code) VALUES
('SUP-TV','Regional TV Supply Co.','Nguyen An','tv-supply@example.local','0900000001','Ho Chi Minh City','0310000001');
INSERT INTO customers(code,name,email,phone,address) VALUES
('DLR-001','Saigon Electronics Dealer','buyer@dealer.local','0911000001','District 1, HCMC'),
('STORE-002','District 7 TV Experience Store','ops@store.local','0911000002','District 7, HCMC'),
('ECOM-003','Online TV Sales Channel','ecom@example.local','0911000003','Ho Chi Minh City');

INSERT INTO products(sku,barcode,name,brand,model_code,product_type,color,screen_size_inch,resolution,panel_type,operating_system,refresh_rate_hz,is_serialized,warranty_months,specs,category_id,unit,cost_price,selling_price,min_stock,description) VALUES
('TV-A43-4K','893100000001','NovaVision A43 Smart TV 4K','NovaVision','A43-4K','TV','Black',43,'4K UHD','LED','Google TV',60,1,24,JSON_OBJECT('hdr',JSON_ARRAY('HDR10'),'hdmi_ports',3,'wifi','Wi-Fi 5','bluetooth','5.1'),1,'piece',6200000,7990000,8,'43-inch LED smart TV'),
('TV-S50-4K','893100000002','NovaVision S50 Smart TV 4K','NovaVision','S50-4K','TV','Black',50,'4K UHD','LED','Google TV',60,1,24,JSON_OBJECT('hdr',JSON_ARRAY('HDR10','HLG'),'hdmi_ports',3,'speaker_w',20),1,'piece',7600000,9490000,6,'50-inch LED smart TV'),
('TV-Q55-120','893100000003','NovaVision Q55 QLED 4K 120Hz','NovaVision','Q55-120','TV','Graphite',55,'4K UHD','QLED','Google TV',120,1,24,JSON_OBJECT('hdr',JSON_ARRAY('HDR10+','Dolby Vision'),'hdmi_ports',4,'hdmi_21',2,'vrr',true),2,'piece',10400000,13990000,5,'55-inch QLED gaming TV'),
('TV-M65-144','893100000004','NovaVision M65 Mini LED 4K 144Hz','NovaVision','M65-144','TV','Black',65,'4K UHD','Mini LED','Google TV',144,1,24,JSON_OBJECT('local_dimming_zones',384,'hdr',JSON_ARRAY('HDR10+','Dolby Vision'),'hdmi_ports',4,'vrr',true),2,'piece',16700000,21990000,4,'65-inch Mini LED premium TV'),
('TV-O65-120','893100000005','NovaVision O65 OLED 4K 120Hz','NovaVision','O65-120','TV','Black',65,'4K UHD','OLED','Google TV',120,1,24,JSON_OBJECT('hdr',JSON_ARRAY('Dolby Vision','HDR10'),'hdmi_ports',4,'earc',true,'vrr',true),3,'piece',23800000,30990000,3,'65-inch OLED premium TV'),
('TV-M75-144','893100000006','NovaVision M75 Mini LED 4K 144Hz','NovaVision','M75-144','TV','Black',75,'4K UHD','Mini LED','Google TV',144,1,24,JSON_OBJECT('local_dimming_zones',512,'hdr',JSON_ARRAY('HDR10+','Dolby Vision'),'hdmi_ports',4,'vrr',true),2,'piece',25900000,33990000,2,'75-inch Mini LED premium TV');

INSERT INTO stocks(warehouse_id,product_id,quantity,reserved_quantity,avg_cost) VALUES
(1,1,18,0,6200000),(1,2,14,0,7600000),(1,3,12,3,10400000),(1,4,8,0,16700000),(1,5,5,0,23800000),(1,6,4,0,25900000),
(2,1,6,0,6200000),(2,2,5,0,7600000),(2,3,4,0,10400000),(2,4,3,0,16700000),(2,5,2,0,23800000),(2,6,1,0,25900000);

-- Generate physical TV serial records matching current on-hand stock.
DELIMITER $$
CREATE PROCEDURE seed_tv_serials()
BEGIN
 DECLARE p INT DEFAULT 1;
 DECLARE i INT;
 DECLARE main_qty INT;
 DECLARE south_qty INT;
 DECLARE sku_code VARCHAR(60);
 DECLARE size_in INT;
 WHILE p <= 6 DO
  SELECT sku,screen_size_inch INTO sku_code,size_in FROM products WHERE id=p;
  SELECT CAST(quantity AS UNSIGNED) INTO main_qty FROM stocks WHERE warehouse_id=1 AND product_id=p;
  SELECT CAST(quantity AS UNSIGNED) INTO south_qty FROM stocks WHERE warehouse_id=2 AND product_id=p;
  SET i=1;
  WHILE i <= main_qty + south_qty DO
   INSERT INTO product_serials(product_id,warehouse_id,warehouse_location_id,serial_number,`condition`,status,received_at)
   VALUES(p,IF(i<=main_qty,1,2),IF(i<=main_qty,CASE WHEN size_in<=43 THEN 1 WHEN size_in<=55 THEN 2 WHEN size_in<=65 THEN 3 ELSE 4 END,5),CONCAT(sku_code,'-SN-',LPAD(i,5,'0')),'NEW','IN_STOCK',NOW()-INTERVAL 20 DAY);
   SET i=i+1;
  END WHILE;
  SET p=p+1;
 END WHILE;
END$$
DELIMITER ;
CALL seed_tv_serials();
DROP PROCEDURE seed_tv_serials;

-- Historical sold TV retained for warranty/service traceability; not part of current stock balance.
INSERT INTO product_serials(product_id,warehouse_id,warehouse_location_id,serial_number,`condition`,status,received_at,sold_at,warranty_start_at,warranty_end_at)
VALUES(3,3,6,'TV-Q55-120-SOLD-00001','RETURNED','REPAIR',NOW()-INTERVAL 90 DAY,NOW()-INTERVAL 30 DAY,CURDATE()-INTERVAL 30 DAY,CURDATE()+INTERVAL 23 MONTH);

INSERT INTO purchase_orders(po_number,supplier_id,warehouse_id,status,approval_status,ordered_at,expected_at,notes,created_by,approved_by,approved_at) VALUES
('PO-TV-20260825-001',1,1,'ORDERED','APPROVED','2026-08-25','2026-08-30','55-inch and 65-inch TV replenishment',3,2,NOW()),
('PO-TV-20260825-002',1,1,'DRAFT','PENDING','2026-08-25','2026-09-03','Large-screen TV replenishment awaiting approval',3,NULL,NULL);
INSERT INTO purchase_order_items(purchase_order_id,product_id,quantity,received_quantity,unit_cost) VALUES
(1,3,8,0,10200000),(1,4,5,0,16500000),(2,6,4,0,25500000);

INSERT INTO sales_orders(so_number,customer_id,warehouse_id,status,channel,external_reference,ordered_at,notes,created_by,reserved_at) VALUES
('SO-TV-DEMO-RESERVED',1,1,'CONFIRMED','DEALER','DEALER-TV-PO-2026-081','2026-08-25','Dealer QLED TV replenishment reservation',3,NOW());
INSERT INTO sales_order_items(sales_order_id,product_id,quantity,issued_quantity,unit_price) VALUES(1,3,3,0,13990000);
INSERT INTO stock_reservations(sales_order_id,sales_order_item_id,warehouse_id,product_id,quantity,status,expires_at,created_by) VALUES(1,1,1,3,3,'ACTIVE',NOW()+INTERVAL 2 DAY,3);

INSERT INTO stock_transfers(transfer_number,from_warehouse_id,to_warehouse_id,status,approval_status,notes,created_by,approved_by,approved_at) VALUES
('TR-TV-DEMO-001',1,2,'DRAFT','APPROVED','Move 65-inch display stock to South TV Hub',3,2,NOW());
INSERT INTO stock_transfer_items(stock_transfer_id,product_id,quantity) VALUES(1,4,1);

INSERT INTO stock_movements(warehouse_id,product_id,type,quantity,unit_cost,reference_type,reference_id,note,performed_by,occurred_at) VALUES
(1,1,'IN',18,6200000,'OPENING_BALANCE',1,'Initial 43-inch TV stock',1,'2026-08-05 09:00:00'),
(1,3,'IN',12,10400000,'OPENING_BALANCE',2,'Initial 55-inch QLED TV stock',1,'2026-08-05 09:10:00'),
(1,4,'IN',8,16700000,'OPENING_BALANCE',3,'Initial 65-inch Mini LED TV stock',1,'2026-08-06 10:00:00'),
(2,3,'IN',4,10400000,'OPENING_BALANCE',4,'South Hub QLED TV stock',1,'2026-08-07 09:00:00');

INSERT INTO warranty_claims(claim_number,customer_id,product_serial_id,status,issue_description,diagnosis,created_by,handled_by,received_at)
SELECT 'WC-TV-DEMO-001',1,id,'DIAGNOSING','TV powers on but panel intermittently loses image.','Pending panel/power-board verification',3,2,NOW()-INTERVAL 1 DAY FROM product_serials WHERE serial_number='TV-Q55-120-SOLD-00001';

INSERT INTO device_events(product_serial_id,event_type,from_warehouse_id,to_warehouse_id,reference_type,reference_id,metadata,performed_by,occurred_at)
SELECT id,'SOLD',1,NULL,'HISTORICAL_SALE',1,JSON_OBJECT('channel','DEALER'),3,NOW()-INTERVAL 30 DAY FROM product_serials WHERE serial_number='TV-Q55-120-SOLD-00001';
INSERT INTO device_events(product_serial_id,event_type,from_warehouse_id,to_warehouse_id,reference_type,reference_id,metadata,performed_by,occurred_at)
SELECT ps.id,'WARRANTY_OPENED',NULL,3,'WARRANTY_CLAIM',wc.id,JSON_OBJECT('status','DIAGNOSING'),3,NOW()-INTERVAL 1 DAY FROM product_serials ps JOIN warranty_claims wc ON wc.product_serial_id=ps.id WHERE ps.serial_number='TV-Q55-120-SOLD-00001';

INSERT INTO audit_logs(user_id,action,entity_type,entity_id,after_values,ip_address,user_agent,created_at) VALUES
(3,'SO_CREATED_AND_RESERVED','SalesOrder',1,JSON_OBJECT('so_number','SO-TV-DEMO-RESERVED','channel','DEALER','reserved_quantity',3),'127.0.0.1','Seed script',NOW()),
(2,'PO_APPROVED','PurchaseOrder',1,JSON_OBJECT('po_number','PO-TV-20260825-001','approval_status','APPROVED'),'127.0.0.1','Seed script',NOW());
