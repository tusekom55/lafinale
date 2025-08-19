-- Add new fields to deposits table to support TL-to-USD conversion
-- Run this script to update the database schema for parametric deposit system

ALTER TABLE deposits 
ADD COLUMN deposit_type VARCHAR(20) DEFAULT 'normal' AFTER reference,
ADD COLUMN tl_amount DECIMAL(15,4) DEFAULT NULL AFTER deposit_type,
ADD COLUMN usd_amount DECIMAL(15,4) DEFAULT NULL AFTER tl_amount,
ADD COLUMN exchange_rate DECIMAL(10,6) DEFAULT NULL AFTER usd_amount;

-- Add indexes for better performance
ALTER TABLE deposits 
ADD INDEX idx_deposit_type (deposit_type),
ADD INDEX idx_created_at (created_at);

-- Update existing records to have deposit_type = 'normal'
UPDATE deposits SET deposit_type = 'normal' WHERE deposit_type IS NULL;

-- Make deposit_type NOT NULL after updating existing records
ALTER TABLE deposits MODIFY COLUMN deposit_type VARCHAR(20) NOT NULL DEFAULT 'normal';

-- Add comments for documentation
ALTER TABLE deposits 
MODIFY COLUMN deposit_type VARCHAR(20) NOT NULL DEFAULT 'normal' COMMENT 'Type: normal, tl_to_usd',
MODIFY COLUMN tl_amount DECIMAL(15,4) DEFAULT NULL COMMENT 'TL amount paid by user (for tl_to_usd type)',
MODIFY COLUMN usd_amount DECIMAL(15,4) DEFAULT NULL COMMENT 'USD amount credited to user (for tl_to_usd type)',
MODIFY COLUMN exchange_rate DECIMAL(10,6) DEFAULT NULL COMMENT 'USD/TRY exchange rate used for conversion';
