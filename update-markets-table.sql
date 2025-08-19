-- Update markets table for new categories
ALTER TABLE markets MODIFY COLUMN category ENUM(
    'us_stocks',
    'eu_stocks', 
    'world_stocks',
    'commodities',
    'forex_major',
    'forex_minor',
    'forex_exotic',
    'indices'
) DEFAULT 'us_stocks';

-- Clean old data if exists
DELETE FROM markets WHERE category NOT IN (
    'us_stocks', 'eu_stocks', 'world_stocks', 'commodities',
    'forex_major', 'forex_minor', 'forex_exotic', 'indices'
);

-- Add unique constraint on symbol to prevent duplicates
ALTER TABLE markets ADD UNIQUE KEY unique_symbol (symbol);
