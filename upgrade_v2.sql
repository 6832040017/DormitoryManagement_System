ALTER TABLE rooms ADD use_common TINYINT DEFAULT 1,ADD use_wifi TINYINT DEFAULT 1;
ALTER TABLE invoices ADD common DECIMAL(10,2) DEFAULT 0,ADD wifi DECIMAL(10,2) DEFAULT 0,ADD water_prev INT,ADD water_curr INT,ADD elec_prev INT,ADD elec_curr INT;
CREATE TABLE IF NOT EXISTS room_images(id INT AUTO_INCREMENT PRIMARY KEY,room_id INT NOT NULL,file VARCHAR(100) NOT NULL,FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE);
INSERT IGNORE INTO settings VALUES('common_fee','100'),('wifi_fee','100'),('pay_name',''),('bank_name',''),('bank_no','');
