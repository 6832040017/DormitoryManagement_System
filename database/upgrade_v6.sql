ALTER TABLE leases ADD deposit_status ENUM('held','refunded','forfeited') NOT NULL DEFAULT 'held',ADD deposit_settled_at DATETIME NULL,ADD deposit_note VARCHAR(255) NULL,ADD ended_at DATE NULL;
