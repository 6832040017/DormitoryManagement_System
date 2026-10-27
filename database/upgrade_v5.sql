ALTER TABLE invoices ADD late_fee DECIMAL(10,2) NOT NULL DEFAULT 0,ADD late_days INT NOT NULL DEFAULT 0;
INSERT IGNORE INTO settings VALUES('late_fee','0'),('late_max','0');
