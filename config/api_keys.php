<?php
// Financial Data API Keys
define('TWELVE_DATA_API_KEY', '7a0311c2af9a48eab8277a4bfe598a30'); // Live API Key - 800 requests/day
define('TWELVE_DATA_API_URL', 'https://api.twelvedata.com');

define('ALPHA_VANTAGE_API_KEY', 'demo'); // Backup API - Replace with real API key  
define('ALPHA_VANTAGE_API_URL', 'https://www.alphavantage.co/query');

// FinancialModelingPrep API Keys
define('FMP_API_KEY', 'Pt5IwxHnQLEUskikphYk55M186mqPCWL'); // Live FMP API key - 100 requests/day
define('FMP_API_URL', 'https://financialmodelingprep.com/api/v3');
define('FMP_REQUESTS_PER_DAY', 100); // Free plan limit

// Payment API configuration (demo)
define('PAPARA_API_KEY', 'demo_key');
define('PAPARA_API_URL', 'https://merchant-api.papara.com');

// Exchange Rate API (Free)
define('EXCHANGE_API_URL', 'https://api.exchangerate-api.com/v4/latest/USD'); // Free, no key required
define('EXCHANGE_API_BACKUP', 'https://api.fxapi.com/v1/latest?access_key=fxapi-key&base=USD&symbols=TRY'); // Backup API
define('EXCHANGE_RATE_CACHE_TIME', 300); // 5 minutes cache

// Site configuration
define('SITE_NAME', 'GlobalBorsa');
define('SITE_URL', 'http://localhost');
define('ADMIN_EMAIL', 'admin@globalborsa.com');

// Trading fees (percentage)
define('TRADING_FEE', 0.25); // 0.25%
define('WITHDRAWAL_FEE_TL', 5.00); // 5 TL fixed fee
define('WITHDRAWAL_FEE_USD', 2.00); // 2 USD fixed fee

// Minimum amounts
define('MIN_TRADE_AMOUNT', 10.00); // Minimum 10 TL trade
define('MIN_WITHDRAWAL_AMOUNT', 50.00); // Minimum 50 TL withdrawal
define('MIN_DEPOSIT_AMOUNT', 20.00); // Minimum 20 TL deposit
?>
