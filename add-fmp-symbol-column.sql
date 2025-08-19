-- Add fmp_symbol column to markets table
ALTER TABLE markets 
ADD COLUMN fmp_symbol VARCHAR(20) AFTER symbol,
ADD INDEX idx_fmp_symbol (fmp_symbol);

-- Update all existing records with FMP-compatible symbols

-- US Stocks (direct mapping)
UPDATE markets SET fmp_symbol = symbol WHERE category = 'us_stocks';

-- EU Stocks (remove exchange suffix)
UPDATE markets SET fmp_symbol = 'SAP' WHERE symbol = 'SAP.DE' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'ASML' WHERE symbol = 'ASML.AS' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'MC' WHERE symbol = 'MC.PA' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'NESN' WHERE symbol = 'NESN.SW' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'ROG' WHERE symbol = 'ROG.SW' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'AZN' WHERE symbol = 'AZN.L' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'SHEL' WHERE symbol = 'SHEL.L' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'RDSA' WHERE symbol = 'RDSA.AS' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'SIE' WHERE symbol = 'SIE.DE' AND category = 'eu_stocks';
UPDATE markets SET fmp_symbol = 'OR' WHERE symbol = 'OR.PA' AND category = 'eu_stocks';

-- World Stocks (mixed mapping)
UPDATE markets SET fmp_symbol = 'TSM' WHERE symbol = 'TSM' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = 'BABA' WHERE symbol = 'BABA' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = 'TCEHY' WHERE symbol = 'TCEHY' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = '7203.T' WHERE symbol = '7203.T' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = 'SNY' WHERE symbol = 'SNY' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = 'TM' WHERE symbol = 'TM' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = 'SONY' WHERE symbol = 'SONY' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = 'ING' WHERE symbol = 'ING' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = 'UL' WHERE symbol = 'UL' AND category = 'world_stocks';
UPDATE markets SET fmp_symbol = 'RIO' WHERE symbol = 'RIO.L' AND category = 'world_stocks';

-- Forex Major (remove =X suffix)
UPDATE markets SET fmp_symbol = 'EURUSD' WHERE symbol = 'EURUSD=X' AND category = 'forex_major';
UPDATE markets SET fmp_symbol = 'GBPUSD' WHERE symbol = 'GBPUSD=X' AND category = 'forex_major';
UPDATE markets SET fmp_symbol = 'USDJPY' WHERE symbol = 'USDJPY=X' AND category = 'forex_major';
UPDATE markets SET fmp_symbol = 'USDCHF' WHERE symbol = 'USDCHF=X' AND category = 'forex_major';
UPDATE markets SET fmp_symbol = 'AUDUSD' WHERE symbol = 'AUDUSD=X' AND category = 'forex_major';
UPDATE markets SET fmp_symbol = 'USDCAD' WHERE symbol = 'USDCAD=X' AND category = 'forex_major';
UPDATE markets SET fmp_symbol = 'NZDUSD' WHERE symbol = 'NZDUSD=X' AND category = 'forex_major';
UPDATE markets SET fmp_symbol = 'EURJPY' WHERE symbol = 'EURJPY=X' AND category = 'forex_major';

-- Forex Minor (remove =X suffix)
UPDATE markets SET fmp_symbol = 'EURGBP' WHERE symbol = 'EURGBP=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'GBPJPY' WHERE symbol = 'GBPJPY=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'EURCHF' WHERE symbol = 'EURCHF=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'AUDJPY' WHERE symbol = 'AUDJPY=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'GBPCHF' WHERE symbol = 'GBPCHF=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'EURAUD' WHERE symbol = 'EURAUD=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'CADJPY' WHERE symbol = 'CADJPY=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'AUDCAD' WHERE symbol = 'AUDCAD=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'NZDJPY' WHERE symbol = 'NZDJPY=X' AND category = 'forex_minor';
UPDATE markets SET fmp_symbol = 'CHFJPY' WHERE symbol = 'CHFJPY=X' AND category = 'forex_minor';

-- Forex Exotic (remove =X suffix)
UPDATE markets SET fmp_symbol = 'USDTRY' WHERE symbol = 'USDTRY=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'EURTRY' WHERE symbol = 'EURTRY=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'GBPTRY' WHERE symbol = 'GBPTRY=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'USDSEK' WHERE symbol = 'USDSEK=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'USDNOK' WHERE symbol = 'USDNOK=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'USDPLN' WHERE symbol = 'USDPLN=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'EURSEK' WHERE symbol = 'EURSEK=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'USDZAR' WHERE symbol = 'USDZAR=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'USDMXN' WHERE symbol = 'USDMXN=X' AND category = 'forex_exotic';
UPDATE markets SET fmp_symbol = 'USDHUF' WHERE symbol = 'USDHUF=X' AND category = 'forex_exotic';

-- Commodities (convert to USD pairs)
UPDATE markets SET fmp_symbol = 'GCUSD' WHERE symbol = 'GC=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'SIUSD' WHERE symbol = 'SI=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'CLUSD' WHERE symbol = 'CL=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'BZUSD' WHERE symbol = 'BZ=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'NGUSD' WHERE symbol = 'NG=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'HGUSD' WHERE symbol = 'HG=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'ZWUSD' WHERE symbol = 'ZW=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'ZCUSD' WHERE symbol = 'ZC=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'SBUSD' WHERE symbol = 'SB=F' AND category = 'commodities';
UPDATE markets SET fmp_symbol = 'KCUSD' WHERE symbol = 'KC=F' AND category = 'commodities';

-- Indices (remove ^ prefix and convert some)
UPDATE markets SET fmp_symbol = 'DJI' WHERE symbol = '^DJI' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'SPX' WHERE symbol = '^GSPC' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'IXIC' WHERE symbol = '^IXIC' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'RUT' WHERE symbol = '^RUT' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'VIX' WHERE symbol = '^VIX' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'GDAXI' WHERE symbol = '^GDAXI' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'UKX' WHERE symbol = '^FTSE' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'CAC' WHERE symbol = '^FCHI' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'N225' WHERE symbol = '^N225' AND category = 'indices';
UPDATE markets SET fmp_symbol = 'HSI' WHERE symbol = '^HSI' AND category = 'indices';

-- Verify results
SELECT 'Conversion Summary' as info;
SELECT category, COUNT(*) as total_symbols, 
       COUNT(fmp_symbol) as converted_symbols,
       COUNT(CASE WHEN fmp_symbol IS NULL THEN 1 END) as missing_symbols
FROM markets 
GROUP BY category
ORDER BY category;

-- Show sample conversions
SELECT 'Sample Conversions' as info;
SELECT category, symbol, fmp_symbol 
FROM markets 
WHERE fmp_symbol IS NOT NULL 
ORDER BY category, symbol 
LIMIT 20;
