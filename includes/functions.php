<?php
require_once 'config/database.php';
require_once 'config/api_keys.php';
require_once 'config/languages.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}


// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// Format number with Turkish locale
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

// Format price based on value
function formatPrice($price) {
    if ($price >= 1000) {
        return formatNumber($price, 2);
    } elseif ($price >= 1) {
        return formatNumber($price, 4);
    } else {
        return formatNumber($price, 8);
    }
}

// Format percentage change
function formatChange($change) {
    $sign = $change >= 0 ? '+' : '';
    $class = $change >= 0 ? 'text-success' : 'text-danger';
    return '<span class="' . $class . '">' . $sign . ' %' . formatNumber($change, 2) . '</span>';
}

// Format volume
function formatVolume($volume) {
    if ($volume >= 1000000000) {
        return formatNumber($volume / 1000000000, 1) . 'B';
    } elseif ($volume >= 1000000) {
        return formatNumber($volume / 1000000, 1) . 'M';
    } elseif ($volume >= 1000) {
        return formatNumber($volume / 1000, 1) . 'K';
    } else {
        return formatNumber($volume, 0);
    }
}

// Get user balance
function getUserBalance($user_id, $currency = 'tl') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT balance_" . $currency . " FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['balance_' . $currency] : 0;
}

// Update user balance
function updateUserBalance($user_id, $currency, $amount, $operation = 'add') {
    $database = new Database();
    $db = $database->getConnection();
    
    $operator = $operation == 'add' ? '+' : '-';
    $query = "UPDATE users SET balance_" . $currency . " = balance_" . $currency . " " . $operator . " ? WHERE id = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$amount, $user_id]);
}

// Financial market categories
function getFinancialCategories() {
    return [
        'us_stocks' => 'ABD Hisse Senetleri',
        'eu_stocks' => 'Avrupa Hisse Senetleri', 
        'world_stocks' => 'Dünya Hisse Senetleri',
        'commodities' => 'Emtialar',
        'forex_major' => 'Forex Majör Çiftler',
        'forex_minor' => 'Forex Minör Çiftler',
        'forex_exotic' => 'Forex Egzotik Çiftler',
        'indices' => 'Dünya Endeksleri'
    ];
}

// Financial market category icons
function getCategoryIcons() {
    return [
        'us_stocks' => 'fas fa-flag-usa',
        'eu_stocks' => 'fas fa-landmark',
        'world_stocks' => 'fas fa-globe-americas',
        'commodities' => 'fas fa-coins',
        'forex_major' => 'fas fa-exchange-alt',
        'forex_minor' => 'fas fa-chart-line',
        'forex_exotic' => 'fas fa-chart-area',
        'indices' => 'fas fa-chart-bar'
    ];
}

// Financial market category descriptions
function getCategoryDescriptions() {
    return [
        'us_stocks' => 'Apple, Microsoft, Tesla gibi ABD şirketleri',
        'eu_stocks' => 'SAP, ASML, Shell gibi Avrupa şirketleri',
        'world_stocks' => 'Toyota, Samsung, Alibaba gibi dünya şirketleri',
        'commodities' => 'Altın, petrol, doğalgaz ve tarım ürünleri',
        'forex_major' => 'EUR/USD, GBP/USD gibi ana para çiftleri',
        'forex_minor' => 'EUR/GBP, AUD/JPY gibi yan para çiftleri',
        'forex_exotic' => 'USD/TRY, EUR/TRY gibi egzotik çiftler',
        'indices' => 'S&P 500, NASDAQ, DAX gibi endeksler'
    ];
}

// Fetch financial data with demo data for testing
function fetchFinancialData($symbols, $category) {
    if (empty($symbols)) return false;
    
    $results = [];
    
    // Demo data generator for testing purposes
    foreach ($symbols as $symbol) {
        // Generate realistic demo data
        $basePrice = getBasePriceForSymbol($symbol, $category);
        $change = (rand(-500, 500) / 100); // -5% to +5%
        $price = $basePrice + ($basePrice * $change / 100);
        
        $results[] = [
            'symbol' => $symbol,
            'longName' => getCompanyName($symbol, $category),
            'shortName' => getCompanyName($symbol, $category),
            'regularMarketPrice' => round($price, 4),
            'regularMarketChange' => round($price - $basePrice, 4),
            'regularMarketChangePercent' => round($change, 2),
            'regularMarketVolume' => rand(100000, 10000000),
            'regularMarketDayHigh' => round($price * 1.02, 4),
            'regularMarketDayLow' => round($price * 0.98, 4),
            'marketCap' => rand(1000000000, 500000000000)
        ];
    }
    
    return $results;
}

// Get base price for symbol based on category
function getBasePriceForSymbol($symbol, $category) {
    $prices = [
        'us_stocks' => [
            'AAPL' => 175.00, 'MSFT' => 338.00, 'GOOGL' => 138.00, 'AMZN' => 145.00, 'TSLA' => 248.00,
            'META' => 298.00, 'NVDA' => 435.00, 'JPM' => 148.00, 'JNJ' => 158.00, 'V' => 250.00,
            'WMT' => 158.00, 'PG' => 152.00, 'UNH' => 515.00, 'DIS' => 96.00, 'HD' => 315.00,
            'PYPL' => 62.00, 'BAC' => 29.00, 'ADBE' => 485.00, 'CRM' => 218.00, 'NFLX' => 385.00
        ],
        'forex_major' => [
            'EURUSD=X' => 1.0925, 'GBPUSD=X' => 1.2785, 'USDJPY=X' => 148.25, 'USDCHF=X' => 0.8695,
            'AUDUSD=X' => 0.6685, 'USDCAD=X' => 1.3485, 'NZDUSD=X' => 0.6125, 'EURJPY=X' => 162.15
        ],
        'forex_exotic' => [
            'USDTRY=X' => 27.45, 'EURTRY=X' => 29.95, 'GBPTRY=X' => 35.15, 'USDSEK=X' => 10.85,
            'USDNOK=X' => 10.65, 'USDPLN=X' => 4.15, 'EURSEK=X' => 11.85, 'USDZAR=X' => 18.25,
            'USDMXN=X' => 17.85, 'USDHUF=X' => 365.25
        ],
        'commodities' => [
            'GC=F' => 1985.50, 'SI=F' => 23.85, 'CL=F' => 78.25, 'BZ=F' => 82.15, 'NG=F' => 2.85,
            'HG=F' => 3.82, 'ZW=F' => 585.25, 'ZC=F' => 485.75, 'SB=F' => 22.85, 'KC=F' => 165.25
        ],
        'indices' => [
            '^DJI' => 34875.25, '^GSPC' => 4485.85, '^IXIC' => 13985.75, '^RUT' => 1885.65, '^VIX' => 18.25,
            '^GDAXI' => 15875.85, '^FTSE' => 7485.25, '^FCHI' => 7285.95, '^N225' => 32885.75, '^HSI' => 18275.85
        ]
    ];
    
    return $prices[$category][$symbol] ?? 100.00;
}

// Get company/instrument name
function getCompanyName($symbol, $category) {
    $names = [
        'us_stocks' => [
            'AAPL' => 'Apple Inc.', 'MSFT' => 'Microsoft Corporation', 'GOOGL' => 'Alphabet Inc.',
            'AMZN' => 'Amazon.com Inc.', 'TSLA' => 'Tesla Inc.', 'META' => 'Meta Platforms Inc.',
            'NVDA' => 'NVIDIA Corporation', 'JPM' => 'JPMorgan Chase & Co.', 'JNJ' => 'Johnson & Johnson',
            'V' => 'Visa Inc.', 'WMT' => 'Walmart Inc.', 'PG' => 'Procter & Gamble Co.',
            'UNH' => 'UnitedHealth Group Inc.', 'DIS' => 'Walt Disney Co.', 'HD' => 'Home Depot Inc.',
            'PYPL' => 'PayPal Holdings Inc.', 'BAC' => 'Bank of America Corp.', 'ADBE' => 'Adobe Inc.',
            'CRM' => 'Salesforce Inc.', 'NFLX' => 'Netflix Inc.'
        ],
        'eu_stocks' => [
            'SAP.DE' => 'SAP SE', 'ASML.AS' => 'ASML Holding NV', 'MC.PA' => 'LVMH', 'NESN.SW' => 'Nestlé S.A.',
            'ROG.SW' => 'Roche Holding AG', 'AZN.L' => 'AstraZeneca PLC', 'SHEL.L' => 'Shell plc',
            'RDSA.AS' => 'Royal Dutch Shell', 'SIE.DE' => 'Siemens AG', 'OR.PA' => "L'Oréal S.A."
        ],
        'world_stocks' => [
            'TSM' => 'Taiwan Semiconductor', 'BABA' => 'Alibaba Group', 'TCEHY' => 'Tencent Holdings',
            '7203.T' => 'Toyota Motor Corp', 'SNY' => 'Sanofi SA', 'TM' => 'Toyota Motor Corp',
            'SONY' => 'Sony Group Corp', 'ING' => 'ING Groep NV', 'UL' => 'Unilever PLC', 'RIO.L' => 'Rio Tinto PLC'
        ],
        'forex_major' => [
            'EURUSD=X' => 'EUR/USD', 'GBPUSD=X' => 'GBP/USD', 'USDJPY=X' => 'USD/JPY', 'USDCHF=X' => 'USD/CHF',
            'AUDUSD=X' => 'AUD/USD', 'USDCAD=X' => 'USD/CAD', 'NZDUSD=X' => 'NZD/USD', 'EURJPY=X' => 'EUR/JPY'
        ],
        'forex_minor' => [
            'EURGBP=X' => 'EUR/GBP', 'GBPJPY=X' => 'GBP/JPY', 'EURCHF=X' => 'EUR/CHF', 'AUDJPY=X' => 'AUD/JPY',
            'GBPCHF=X' => 'GBP/CHF', 'EURAUD=X' => 'EUR/AUD', 'CADJPY=X' => 'CAD/JPY', 'AUDCAD=X' => 'AUD/CAD',
            'NZDJPY=X' => 'NZD/JPY', 'CHFJPY=X' => 'CHF/JPY'
        ],
        'forex_exotic' => [
            'USDTRY=X' => 'USD/TRY', 'EURTRY=X' => 'EUR/TRY', 'GBPTRY=X' => 'GBP/TRY', 'USDSEK=X' => 'USD/SEK',
            'USDNOK=X' => 'USD/NOK', 'USDPLN=X' => 'USD/PLN', 'EURSEK=X' => 'EUR/SEK', 'USDZAR=X' => 'USD/ZAR',
            'USDMXN=X' => 'USD/MXN', 'USDHUF=X' => 'USD/HUF'
        ],
        'commodities' => [
            'GC=F' => 'Gold Futures', 'SI=F' => 'Silver Futures', 'CL=F' => 'Crude Oil WTI',
            'BZ=F' => 'Brent Crude Oil', 'NG=F' => 'Natural Gas', 'HG=F' => 'Copper Futures',
            'ZW=F' => 'Wheat Futures', 'ZC=F' => 'Corn Futures', 'SB=F' => 'Sugar Futures', 'KC=F' => 'Coffee Futures'
        ],
        'indices' => [
            '^DJI' => 'Dow Jones Industrial Average', '^GSPC' => 'S&P 500', '^IXIC' => 'NASDAQ Composite',
            '^RUT' => 'Russell 2000', '^VIX' => 'CBOE Volatility Index', '^GDAXI' => 'DAX Performance Index',
            '^FTSE' => 'FTSE 100', '^FCHI' => 'CAC 40', '^N225' => 'Nikkei 225', '^HSI' => 'Hang Seng Index'
        ]
    ];
    
    return $names[$category][$symbol] ?? $symbol;
}

// Get logo URL for instrument
function getLogoUrl($symbol, $category) {
    $logos = [
        'us_stocks' => [
            'AAPL' => 'https://logo.clearbit.com/apple.com',
            'MSFT' => 'https://logo.clearbit.com/microsoft.com',
            'GOOGL' => 'https://logo.clearbit.com/google.com',
            'AMZN' => 'https://logo.clearbit.com/amazon.com',
            'TSLA' => 'https://logo.clearbit.com/tesla.com',
            'META' => 'https://logo.clearbit.com/meta.com',
            'NVDA' => 'https://logo.clearbit.com/nvidia.com',
            'JPM' => 'https://logo.clearbit.com/jpmorganchase.com',
            'JNJ' => 'https://logo.clearbit.com/jnj.com',
            'V' => 'https://logo.clearbit.com/visa.com',
            'WMT' => 'https://logo.clearbit.com/walmart.com',
            'PG' => 'https://logo.clearbit.com/pg.com',
            'UNH' => 'https://logo.clearbit.com/unitedhealthgroup.com',
            'DIS' => 'https://logo.clearbit.com/disney.com',
            'HD' => 'https://logo.clearbit.com/homedepot.com',
            'PYPL' => 'https://logo.clearbit.com/paypal.com',
            'BAC' => 'https://logo.clearbit.com/bankofamerica.com',
            'ADBE' => 'https://logo.clearbit.com/adobe.com',
            'CRM' => 'https://logo.clearbit.com/salesforce.com',
            'NFLX' => 'https://logo.clearbit.com/netflix.com'
        ],
        'eu_stocks' => [
            'SAP.DE' => 'https://logo.clearbit.com/sap.com',
            'ASML.AS' => 'https://logo.clearbit.com/asml.com',
            'MC.PA' => 'https://logo.clearbit.com/lvmh.com',
            'NESN.SW' => 'https://logo.clearbit.com/nestle.com',
            'ROG.SW' => 'https://logo.clearbit.com/roche.com',
            'AZN.L' => 'https://logo.clearbit.com/astrazeneca.com',
            'SHEL.L' => 'https://logo.clearbit.com/shell.com',
            'RDSA.AS' => 'https://logo.clearbit.com/shell.com',
            'SIE.DE' => 'https://logo.clearbit.com/siemens.com',
            'OR.PA' => 'https://logo.clearbit.com/loreal.com'
        ],
        'world_stocks' => [
            'TSM' => 'https://logo.clearbit.com/tsmc.com',
            'BABA' => 'https://logo.clearbit.com/alibaba.com',
            'TCEHY' => 'https://logo.clearbit.com/tencent.com',
            '7203.T' => 'https://logo.clearbit.com/toyota.com',
            'SNY' => 'https://logo.clearbit.com/sanofi.com',
            'TM' => 'https://logo.clearbit.com/toyota.com',
            'SONY' => 'https://logo.clearbit.com/sony.com',
            'ING' => 'https://logo.clearbit.com/ing.com',
            'UL' => 'https://logo.clearbit.com/unilever.com',
            'RIO.L' => 'https://logo.clearbit.com/riotinto.com'
        ],
        'forex_major' => [
            'EURUSD=X' => 'https://flagcdn.com/w40/eu.png',
            'GBPUSD=X' => 'https://flagcdn.com/w40/gb.png',
            'USDJPY=X' => 'https://flagcdn.com/w40/jp.png',
            'USDCHF=X' => 'https://flagcdn.com/w40/ch.png',
            'AUDUSD=X' => 'https://flagcdn.com/w40/au.png',
            'USDCAD=X' => 'https://flagcdn.com/w40/ca.png',
            'NZDUSD=X' => 'https://flagcdn.com/w40/nz.png',
            'EURJPY=X' => 'https://flagcdn.com/w40/eu.png'
        ],
        'forex_minor' => [
            'EURGBP=X' => 'https://flagcdn.com/w40/eu.png',
            'GBPJPY=X' => 'https://flagcdn.com/w40/gb.png',
            'EURCHF=X' => 'https://flagcdn.com/w40/eu.png',
            'AUDJPY=X' => 'https://flagcdn.com/w40/au.png',
            'GBPCHF=X' => 'https://flagcdn.com/w40/gb.png',
            'EURAUD=X' => 'https://flagcdn.com/w40/eu.png',
            'CADJPY=X' => 'https://flagcdn.com/w40/ca.png',
            'AUDCAD=X' => 'https://flagcdn.com/w40/au.png',
            'NZDJPY=X' => 'https://flagcdn.com/w40/nz.png',
            'CHFJPY=X' => 'https://flagcdn.com/w40/ch.png'
        ],
        'forex_exotic' => [
            'USDTRY=X' => 'https://flagcdn.com/w40/tr.png',
            'EURTRY=X' => 'https://flagcdn.com/w40/tr.png',
            'GBPTRY=X' => 'https://flagcdn.com/w40/tr.png',
            'USDSEK=X' => 'https://flagcdn.com/w40/se.png',
            'USDNOK=X' => 'https://flagcdn.com/w40/no.png',
            'USDPLN=X' => 'https://flagcdn.com/w40/pl.png',
            'EURSEK=X' => 'https://flagcdn.com/w40/se.png',
            'USDZAR=X' => 'https://flagcdn.com/w40/za.png',
            'USDMXN=X' => 'https://flagcdn.com/w40/mx.png',
            'USDHUF=X' => 'https://flagcdn.com/w40/hu.png'
        ],
        'commodities' => [
            'GC=F' => 'https://cdn.jsdelivr.net/gh/dmhendricks/file-icon-vectors@master/dist/icons/vivid/gold.svg',
            'SI=F' => 'https://cdn.jsdelivr.net/gh/dmhendricks/file-icon-vectors@master/dist/icons/vivid/silver.svg',
            'CL=F' => 'https://img.icons8.com/color/48/oil-pump.png',
            'BZ=F' => 'https://img.icons8.com/color/48/oil-pump.png',
            'NG=F' => 'https://img.icons8.com/color/48/gas.png',
            'HG=F' => 'https://img.icons8.com/color/48/copper.png',
            'ZW=F' => 'https://img.icons8.com/color/48/wheat.png',
            'ZC=F' => 'https://img.icons8.com/color/48/corn.png',
            'SB=F' => 'https://img.icons8.com/color/48/sugar-cubes.png',
            'KC=F' => 'https://img.icons8.com/color/48/coffee-beans.png'
        ],
        'indices' => [
            '^DJI' => 'https://img.icons8.com/color/48/stocks-growth.png',
            '^GSPC' => 'https://img.icons8.com/color/48/combo-chart.png',
            '^IXIC' => 'https://img.icons8.com/color/48/nasdaq.png',
            '^RUT' => 'https://img.icons8.com/color/48/line-chart.png',
            '^VIX' => 'https://img.icons8.com/color/48/volatility.png',
            '^GDAXI' => 'https://flagcdn.com/w40/de.png',
            '^FTSE' => 'https://flagcdn.com/w40/gb.png',
            '^FCHI' => 'https://flagcdn.com/w40/fr.png',
            '^N225' => 'https://flagcdn.com/w40/jp.png',
            '^HSI' => 'https://flagcdn.com/w40/hk.png'
        ]
    ];
    
    return $logos[$category][$symbol] ?? '';
}

// Get predefined symbols for each category (Yahoo Finance format)
function getCategorySymbols($category) {
    $symbols = [
        'us_stocks' => ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA', 'META', 'NVDA', 'JPM', 'JNJ', 'V', 'WMT', 'PG', 'UNH', 'DIS', 'HD', 'PYPL', 'BAC', 'ADBE', 'CRM', 'NFLX'],
        'eu_stocks' => ['SAP.DE', 'ASML.AS', 'MC.PA', 'NESN.SW', 'ROG.SW', 'AZN.L', 'SHEL.L', 'RDSA.AS', 'SIE.DE', 'OR.PA'],
        'world_stocks' => ['TSM', 'BABA', 'TCEHY', '7203.T', 'SNY', 'TM', 'SONY', 'ING', 'UL', 'RIO.L'],
        'commodities' => ['GC=F', 'SI=F', 'CL=F', 'BZ=F', 'NG=F', 'HG=F', 'ZW=F', 'ZC=F', 'SB=F', 'KC=F'],
        'forex_major' => ['EURUSD=X', 'GBPUSD=X', 'USDJPY=X', 'USDCHF=X', 'AUDUSD=X', 'USDCAD=X', 'NZDUSD=X', 'EURJPY=X'],
        'forex_minor' => ['EURGBP=X', 'GBPJPY=X', 'EURCHF=X', 'AUDJPY=X', 'GBPCHF=X', 'EURAUD=X', 'CADJPY=X', 'AUDCAD=X', 'NZDJPY=X', 'CHFJPY=X'],
        'forex_exotic' => ['USDTRY=X', 'EURTRY=X', 'GBPTRY=X', 'USDSEK=X', 'USDNOK=X', 'USDPLN=X', 'EURSEK=X', 'USDZAR=X', 'USDMXN=X', 'USDHUF=X'],
        'indices' => ['^DJI', '^GSPC', '^IXIC', '^RUT', '^VIX', '^GDAXI', '^FTSE', '^FCHI', '^N225', '^HSI']
    ];
    
    return $symbols[$category] ?? [];
}

// Update financial market data in database
function updateFinancialData($category = 'us_stocks') {
    $symbols = getCategorySymbols($category);
    
    if (empty($symbols)) {
        return false;
    }
    
    $financialData = fetchFinancialData($symbols, $category);
    
    if (!$financialData) {
        return false;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    foreach ($financialData as $instrument) {
        if (!isset($instrument['symbol'])) continue;
        
        $symbol = $instrument['symbol'];
        $name = $instrument['longName'] ?? $instrument['shortName'] ?? $symbol;
        
        // Yahoo Finance API field mapping
        $price = floatval($instrument['regularMarketPrice'] ?? $instrument['price'] ?? 0);
        $change = floatval($instrument['regularMarketChange'] ?? 0);
        $change_percent = floatval($instrument['regularMarketChangePercent'] ?? 0);
        $volume = floatval($instrument['regularMarketVolume'] ?? 0);
        $high = floatval($instrument['regularMarketDayHigh'] ?? $price);
        $low = floatval($instrument['regularMarketDayLow'] ?? $price);
        $market_cap = floatval($instrument['marketCap'] ?? 0);
        $logo_url = getLogoUrl($symbol, $category);
        
        $query = "INSERT INTO markets (symbol, name, price, change_24h, volume_24h, high_24h, low_24h, market_cap, category, logo_url) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                  price = VALUES(price), 
                  change_24h = VALUES(change_24h), 
                  volume_24h = VALUES(volume_24h), 
                  high_24h = VALUES(high_24h), 
                  low_24h = VALUES(low_24h), 
                  market_cap = VALUES(market_cap),
                  logo_url = VALUES(logo_url),
                  updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$symbol, $name, $price, $change_percent, $volume, $high, $low, $market_cap, $category, $logo_url]);
    }
    
    return true;
}

// Get market data from database
function getMarketData($category = 'us_stocks', $limit = 50) {
    $database = new Database();
    $db = $database->getConnection();
    
    $limit = (int)$limit;
    
    $query = "SELECT * FROM markets WHERE category = ? ORDER BY market_cap DESC, volume_24h DESC LIMIT " . $limit;
    $stmt = $db->prepare($query);
    $stmt->execute([$category]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all market data for multiple categories
function getAllMarketsData($categories = []) {
    if (empty($categories)) {
        $categories = array_keys(getFinancialCategories());
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $placeholders = str_repeat('?,', count($categories) - 1) . '?';
    $query = "SELECT * FROM markets WHERE category IN ($placeholders) ORDER BY category, volume_24h DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($categories);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by category
    $grouped = [];
    foreach ($results as $market) {
        $grouped[$market['category']][] = $market;
    }
    
    return $grouped;
}

// Get single market data
function getSingleMarket($symbol) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM markets WHERE symbol = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$symbol]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Execute trade
function executeTrade($user_id, $symbol, $type, $amount, $price) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        $total = $amount * $price;
        $fee = $total * (TRADING_FEE / 100);
        $total_with_fee = $total + $fee;
        
        if ($type == 'buy') {
            // Check TL balance
            $tl_balance = getUserBalance($user_id, 'tl');
            if ($tl_balance < $total_with_fee) {
                throw new Exception('Insufficient TL balance');
            }
            
            // Deduct TL, add crypto
            updateUserBalance($user_id, 'tl', $total_with_fee, 'subtract');
            
            $crypto_currency = strtolower(explode('_', $symbol)[0]);
            updateUserBalance($user_id, $crypto_currency, $amount, 'add');
            
        } else { // sell
            // Check crypto balance
            $crypto_currency = strtolower(explode('_', $symbol)[0]);
            $crypto_balance = getUserBalance($user_id, $crypto_currency);
            if ($crypto_balance < $amount) {
                throw new Exception('Insufficient crypto balance');
            }
            
            // Deduct crypto, add TL
            updateUserBalance($user_id, $crypto_currency, $amount, 'subtract');
            updateUserBalance($user_id, 'tl', $total - $fee, 'add');
        }
        
        // Record transaction
        $query = "INSERT INTO transactions (user_id, type, symbol, amount, price, total, fee) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $type, $symbol, $amount, $price, $total, $fee]);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

// Get user transactions
function getUserTransactions($user_id, $limit = 50) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Convert limit to integer to avoid SQL syntax error
    $limit = (int)$limit;
    
    $query = "SELECT t.*, m.name as market_name FROM transactions t 
              LEFT JOIN markets m ON t.symbol = m.symbol 
              WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT " . $limit;
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Log activity
function logActivity($user_id, $action, $details = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

// FinancialModelingPrep API Functions
/**
 * Convert our symbols to FMP-compatible format
 */
function convertSymbolToFMP($symbol, $category) {
    $conversions = [
        'us_stocks' => [
            'AAPL' => 'AAPL', 'MSFT' => 'MSFT', 'GOOGL' => 'GOOGL', 'AMZN' => 'AMZN', 
            'TSLA' => 'TSLA', 'META' => 'META', 'NVDA' => 'NVDA', 'JPM' => 'JPM',
            'JNJ' => 'JNJ', 'V' => 'V', 'WMT' => 'WMT', 'PG' => 'PG', 'UNH' => 'UNH',
            'DIS' => 'DIS', 'HD' => 'HD', 'PYPL' => 'PYPL', 'BAC' => 'BAC', 
            'ADBE' => 'ADBE', 'CRM' => 'CRM', 'NFLX' => 'NFLX'
        ],
        'eu_stocks' => [
            'SAP.DE' => 'SAP', 'ASML.AS' => 'ASML', 'MC.PA' => 'MC', 'NESN.SW' => 'NESN',
            'ROG.SW' => 'ROG', 'AZN.L' => 'AZN', 'SHEL.L' => 'SHEL', 'RDSA.AS' => 'RDSA',
            'SIE.DE' => 'SIE', 'OR.PA' => 'OR'
        ],
        'world_stocks' => [
            'TSM' => 'TSM', 'BABA' => 'BABA', 'TCEHY' => 'TCEHY', '7203.T' => '7203.T',
            'SNY' => 'SNY', 'TM' => 'TM', 'SONY' => 'SONY', 'ING' => 'ING',
            'UL' => 'UL', 'RIO.L' => 'RIO'
        ],
        'forex_major' => [
            'EURUSD=X' => 'EURUSD', 'GBPUSD=X' => 'GBPUSD', 'USDJPY=X' => 'USDJPY',
            'USDCHF=X' => 'USDCHF', 'AUDUSD=X' => 'AUDUSD', 'USDCAD=X' => 'USDCAD',
            'NZDUSD=X' => 'NZDUSD', 'EURJPY=X' => 'EURJPY'
        ],
        'forex_minor' => [
            'EURGBP=X' => 'EURGBP', 'GBPJPY=X' => 'GBPJPY', 'EURCHF=X' => 'EURCHF',
            'AUDJPY=X' => 'AUDJPY', 'GBPCHF=X' => 'GBPCHF', 'EURAUD=X' => 'EURAUD',
            'CADJPY=X' => 'CADJPY', 'AUDCAD=X' => 'AUDCAD', 'NZDJPY=X' => 'NZDJPY',
            'CHFJPY=X' => 'CHFJPY'
        ],
        'forex_exotic' => [
            'USDTRY=X' => 'USDTRY', 'EURTRY=X' => 'EURTRY', 'GBPTRY=X' => 'GBPTRY',
            'USDSEK=X' => 'USDSEK', 'USDNOK=X' => 'USDNOK', 'USDPLN=X' => 'USDPLN',
            'EURSEK=X' => 'EURSEK', 'USDZAR=X' => 'USDZAR', 'USDMXN=X' => 'USDMXN',
            'USDHUF=X' => 'USDHUF'
        ],
        'commodities' => [
            'GC=F' => 'GCUSD', 'SI=F' => 'SIUSD', 'CL=F' => 'CLUSD', 'BZ=F' => 'BZUSD',
            'NG=F' => 'NGUSD', 'HG=F' => 'HGUSD', 'ZW=F' => 'ZWUSD', 'ZC=F' => 'ZCUSD',
            'SB=F' => 'SBUSD', 'KC=F' => 'KCUSD'
        ],
        'indices' => [
            '^DJI' => 'DJI', '^GSPC' => 'SPX', '^IXIC' => 'IXIC', '^RUT' => 'RUT',
            '^VIX' => 'VIX', '^GDAXI' => 'GDAXI', '^FTSE' => 'UKX', '^FCHI' => 'CAC',
            '^N225' => 'N225', '^HSI' => 'HSI'
        ]
    ];
    
    return $conversions[$category][$symbol] ?? $symbol;
}

/**
 * Make FMP API request with error handling and rate limiting
 */
function makeFMPRequest($endpoint, $params = []) {
    static $request_count = 0;
    static $last_request_time = 0;
    
    // Simple rate limiting - 1 request per second
    $current_time = time();
    if ($current_time === $last_request_time) {
        sleep(1);
    }
    $last_request_time = $current_time;
    
    $request_count++;
    
    // Add API key to parameters
    $params['apikey'] = FMP_API_KEY;
    
    // Build URL
    $url = FMP_API_URL . $endpoint . '?' . http_build_query($params);
    
    // Make request with timeout and user agent
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'GlobalBorsa/1.0',
            'header' => 'Accept: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log("FMP API Error: Failed to fetch $url");
        return ['success' => false, 'error' => 'HTTP request failed'];
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("FMP API Error: JSON decode error - " . json_last_error_msg());
        return ['success' => false, 'error' => 'JSON decode error'];
    }
    
    // Check for API error messages
    if (isset($data['Error Message'])) {
        error_log("FMP API Error: " . $data['Error Message']);
        return ['success' => false, 'error' => $data['Error Message']];
    }
    
    return ['success' => true, 'data' => $data];
}

/**
 * Fetch financial data from FMP API with batch optimization
 */
function fetchFinancialDataFromFMP($symbols, $category) {
    if (empty($symbols)) return false;
    
    // Convert symbols to FMP format
    $fmp_symbols = [];
    $symbol_mapping = [];
    
    foreach ($symbols as $original_symbol) {
        $fmp_symbol = convertSymbolToFMP($original_symbol, $category);
        $fmp_symbols[] = $fmp_symbol;
        $symbol_mapping[$fmp_symbol] = $original_symbol;
    }
    
    $results = [];
    
    // Handle different categories with appropriate endpoints
    if (in_array($category, ['forex_major', 'forex_minor', 'forex_exotic'])) {
        // Forex data - batch request to fx endpoint
        foreach ($fmp_symbols as $fmp_symbol) {
            if (strlen($fmp_symbol) >= 6) {
                $from = substr($fmp_symbol, 0, 3);
                $to = substr($fmp_symbol, 3, 3);
                
                $result = makeFMPRequest('/fx', ['from' => $from, 'to' => $to]);
                
                if ($result['success'] && !empty($result['data'])) {
                    $data = is_array($result['data']) ? $result['data'][0] : $result['data'];
                    $original_symbol = $symbol_mapping[$fmp_symbol];
                    
                    $results[] = [
                        'symbol' => $original_symbol,
                        'longName' => getCompanyName($original_symbol, $category),
                        'regularMarketPrice' => $data['rate'] ?? $data['price'] ?? 0,
                        'regularMarketChange' => 0, // FMP doesn't provide change for forex
                        'regularMarketChangePercent' => 0,
                        'regularMarketVolume' => 0,
                        'regularMarketDayHigh' => $data['rate'] ?? $data['price'] ?? 0,
                        'regularMarketDayLow' => $data['rate'] ?? $data['price'] ?? 0,
                        'marketCap' => 0
                    ];
                }
            }
        }
    } else {
        // Stocks, commodities, indices - batch quote request
        $symbols_string = implode(',', $fmp_symbols);
        $result = makeFMPRequest('/quote/' . $symbols_string);
        
        if ($result['success'] && !empty($result['data'])) {
            foreach ($result['data'] as $quote) {
                $fmp_symbol = $quote['symbol'] ?? '';
                $original_symbol = $symbol_mapping[$fmp_symbol] ?? $fmp_symbol;
                
                // Map FMP fields to our expected format
                $results[] = [
                    'symbol' => $original_symbol,
                    'longName' => $quote['name'] ?? getCompanyName($original_symbol, $category),
                    'regularMarketPrice' => $quote['price'] ?? 0,
                    'regularMarketChange' => $quote['change'] ?? 0,
                    'regularMarketChangePercent' => $quote['changesPercentage'] ?? 0,
                    'regularMarketVolume' => $quote['volume'] ?? 0,
                    'regularMarketDayHigh' => $quote['dayHigh'] ?? ($quote['price'] ?? 0),
                    'regularMarketDayLow' => $quote['dayLow'] ?? ($quote['price'] ?? 0),
                    'marketCap' => $quote['marketCap'] ?? 0
                ];
            }
        }
    }
    
    return $results;
}

/**
 * Update financial data using FMP API (optimized for batch requests)
 */
function updateFinancialDataWithFMP($category = 'us_stocks') {
    $symbols = getCategorySymbols($category);
    
    if (empty($symbols)) {
        return false;
    }
    
    // Use FMP API instead of demo data
    $financialData = fetchFinancialDataFromFMP($symbols, $category);
    
    if (!$financialData) {
        // Fallback to demo data if FMP fails
        error_log("FMP API failed for category $category, using demo data");
        $financialData = fetchFinancialData($symbols, $category);
    }
    
    if (!$financialData) {
        return false;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    foreach ($financialData as $instrument) {
        if (!isset($instrument['symbol'])) continue;
        
        $symbol = $instrument['symbol'];
        $name = $instrument['longName'] ?? $instrument['shortName'] ?? $symbol;
        
        // Process the financial data
        $price = floatval($instrument['regularMarketPrice'] ?? $instrument['price'] ?? 0);
        $change = floatval($instrument['regularMarketChange'] ?? 0);
        $change_percent = floatval($instrument['regularMarketChangePercent'] ?? 0);
        $volume = floatval($instrument['regularMarketVolume'] ?? 0);
        $high = floatval($instrument['regularMarketDayHigh'] ?? $price);
        $low = floatval($instrument['regularMarketDayLow'] ?? $price);
        $market_cap = floatval($instrument['marketCap'] ?? 0);
        $logo_url = getLogoUrl($symbol, $category);
        
        $query = "INSERT INTO markets (symbol, name, price, change_24h, volume_24h, high_24h, low_24h, market_cap, category, logo_url) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                  price = VALUES(price), 
                  change_24h = VALUES(change_24h), 
                  volume_24h = VALUES(volume_24h), 
                  high_24h = VALUES(high_24h), 
                  low_24h = VALUES(low_24h), 
                  market_cap = VALUES(market_cap),
                  logo_url = VALUES(logo_url),
                  updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$symbol, $name, $price, $change_percent, $volume, $high, $low, $market_cap, $category, $logo_url]);
    }
    
    return true;
}

/**
 * BATCH API SYSTEM - Ultra optimized for FMP API limits
 * Updates ALL categories with minimal API requests
 */
function updateAllMarketsWithBatchFMP() {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [
        'total_requests' => 0,
        'updated_symbols' => 0,
        'errors' => [],
        'categories' => []
    ];
    
    try {
        // 1. Get all FMP symbols from database grouped by category
        $stock_categories = ['us_stocks', 'eu_stocks', 'world_stocks'];
        $forex_categories = ['forex_major', 'forex_minor', 'forex_exotic'];
        $commodity_categories = ['commodities'];
        $index_categories = ['indices'];
        
        // Batch 1: All Stock symbols in one request
        $stock_symbols = [];
        $stock_mapping = []; // fmp_symbol => original_data
        
        foreach($stock_categories as $category) {
            $query = "SELECT symbol, fmp_symbol FROM markets WHERE category = ? AND fmp_symbol IS NOT NULL";
            $stmt = $db->prepare($query);
            $stmt->execute([$category]);
            $symbols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($symbols as $row) {
                $stock_symbols[] = $row['fmp_symbol'];
                $stock_mapping[$row['fmp_symbol']] = [
                    'original_symbol' => $row['symbol'],
                    'category' => $category
                ];
            }
        }
        
        // Make batch stock request
        if (!empty($stock_symbols)) {
            $symbols_string = implode(',', array_slice($stock_symbols, 0, 50)); // FMP limit ~50 symbols
            $result = makeFMPRequest('/quote/' . $symbols_string);
            $results['total_requests']++;
            
            if ($result['success'] && !empty($result['data'])) {
                foreach($result['data'] as $quote) {
                    $fmp_symbol = $quote['symbol'] ?? '';
                    if (isset($stock_mapping[$fmp_symbol])) {
                        $original_data = $stock_mapping[$fmp_symbol];
                        updateMarketRecord($db, $original_data['original_symbol'], $quote, $original_data['category']);
                        $results['updated_symbols']++;
                    }
                }
                $results['categories'][] = 'stocks (batch)';
            } else {
                $results['errors'][] = 'Stock batch failed: ' . ($result['error'] ?? 'Unknown error');
            }
        }
        
        // Batch 2: Forex symbols (need individual requests due to different endpoint)
        foreach($forex_categories as $category) {
            $query = "SELECT symbol, fmp_symbol FROM markets WHERE category = ? AND fmp_symbol IS NOT NULL LIMIT 10";
            $stmt = $db->prepare($query);
            $stmt->execute([$category]);
            $forex_symbols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($forex_symbols as $row) {
                $fmp_symbol = $row['fmp_symbol'];
                if (strlen($fmp_symbol) >= 6) {
                    $from = substr($fmp_symbol, 0, 3);
                    $to = substr($fmp_symbol, 3, 3);
                    
                    $result = makeFMPRequest('/fx', ['from' => $from, 'to' => $to]);
                    $results['total_requests']++;
                    
                    if ($result['success'] && !empty($result['data'])) {
                        $data = is_array($result['data']) ? $result['data'][0] : $result['data'];
                        
                        // Convert forex data to standard format
                        $quote = [
                            'symbol' => $fmp_symbol,
                            'price' => $data['rate'] ?? $data['price'] ?? 0,
                            'change' => 0,
                            'changesPercentage' => 0,
                            'volume' => 0,
                            'dayHigh' => $data['rate'] ?? $data['price'] ?? 0,
                            'dayLow' => $data['rate'] ?? $data['price'] ?? 0,
                            'marketCap' => 0,
                            'name' => getCompanyName($row['symbol'], $category)
                        ];
                        
                        updateMarketRecord($db, $row['symbol'], $quote, $category);
                        $results['updated_symbols']++;
                    }
                    
                    usleep(100000); // 0.1 second delay for rate limiting
                }
            }
            $results['categories'][] = $category;
        }
        
        // Batch 3: Commodities in one request
        $query = "SELECT symbol, fmp_symbol FROM markets WHERE category = 'commodities' AND fmp_symbol IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $commodity_symbols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($commodity_symbols)) {
            $fmp_symbols = array_column($commodity_symbols, 'fmp_symbol');
            $symbols_string = implode(',', $fmp_symbols);
            
            $result = makeFMPRequest('/quote/' . $symbols_string);
            $results['total_requests']++;
            
            if ($result['success'] && !empty($result['data'])) {
                foreach($result['data'] as $quote) {
                    $fmp_symbol = $quote['symbol'] ?? '';
                    foreach($commodity_symbols as $row) {
                        if ($row['fmp_symbol'] === $fmp_symbol) {
                            updateMarketRecord($db, $row['symbol'], $quote, 'commodities');
                            $results['updated_symbols']++;
                            break;
                        }
                    }
                }
                $results['categories'][] = 'commodities (batch)';
            } else {
                $results['errors'][] = 'Commodities batch failed: ' . ($result['error'] ?? 'Unknown error');
            }
        }
        
        // Batch 4: Indices in one request
        $query = "SELECT symbol, fmp_symbol FROM markets WHERE category = 'indices' AND fmp_symbol IS NOT NULL";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $index_symbols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($index_symbols)) {
            $fmp_symbols = array_column($index_symbols, 'fmp_symbol');
            $symbols_string = implode(',', $fmp_symbols);
            
            $result = makeFMPRequest('/quote/' . $symbols_string);
            $results['total_requests']++;
            
            if ($result['success'] && !empty($result['data'])) {
                foreach($result['data'] as $quote) {
                    $fmp_symbol = $quote['symbol'] ?? '';
                    foreach($index_symbols as $row) {
                        if ($row['fmp_symbol'] === $fmp_symbol) {
                            updateMarketRecord($db, $row['symbol'], $quote, 'indices');
                            $results['updated_symbols']++;
                            break;
                        }
                    }
                }
                $results['categories'][] = 'indices (batch)';
            } else {
                $results['errors'][] = 'Indices batch failed: ' . ($result['error'] ?? 'Unknown error');
            }
        }
        
    } catch (Exception $e) {
        $results['errors'][] = 'Critical error: ' . $e->getMessage();
        error_log("Batch FMP update error: " . $e->getMessage());
    }
    
    return $results;
}

/**
 * Helper function to update market record in database
 */
function updateMarketRecord($db, $original_symbol, $quote, $category) {
    $name = $quote['name'] ?? getCompanyName($original_symbol, $category);
    $price = floatval($quote['price'] ?? 0);
    $change = floatval($quote['change'] ?? 0);
    $change_percent = floatval($quote['changesPercentage'] ?? 0);
    $volume = floatval($quote['volume'] ?? 0);
    $high = floatval($quote['dayHigh'] ?? $price);
    $low = floatval($quote['dayLow'] ?? $price);
    $market_cap = floatval($quote['marketCap'] ?? 0);
    $logo_url = getLogoUrl($original_symbol, $category);
    
    $query = "UPDATE markets SET 
              name = ?, price = ?, change_24h = ?, volume_24h = ?, 
              high_24h = ?, low_24h = ?, market_cap = ?, logo_url = ?, 
              updated_at = CURRENT_TIMESTAMP 
              WHERE symbol = ?";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([$name, $price, $change_percent, $volume, $high, $low, $market_cap, $logo_url, $original_symbol]);
}

/**
 * Get FMP symbol for a given original symbol (uses database)
 */
function getFMPSymbolFromDB($original_symbol) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT fmp_symbol FROM markets WHERE symbol = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$original_symbol]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['fmp_symbol'] : null;
}

/**
 * Update FMP symbols for all existing records
 */
function populateFMPSymbols() {
    $database = new Database();
    $db = $database->getConnection();
    
    $updated = 0;
    
    // Get all records without fmp_symbol
    $query = "SELECT symbol, category FROM markets WHERE fmp_symbol IS NULL OR fmp_symbol = ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $markets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($markets as $market) {
        $fmp_symbol = convertSymbolToFMP($market['symbol'], $market['category']);
        
        if ($fmp_symbol && $fmp_symbol !== $market['symbol']) {
            $updateQuery = "UPDATE markets SET fmp_symbol = ? WHERE symbol = ?";
            $updateStmt = $db->prepare($updateQuery);
            if ($updateStmt->execute([$fmp_symbol, $market['symbol']])) {
                $updated++;
            }
        }
    }
    
    return $updated;
}

// ===============================
// PARAMETRIC TRADING SYSTEM
// ===============================

/**
 * Get system parameter value
 */
function getSystemParameter($parameter_name, $default = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT parameter_value FROM system_parameters WHERE parameter_name = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$parameter_name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['parameter_value'] : $default;
}

/**
 * Set system parameter value
 */
function setSystemParameter($parameter_name, $parameter_value) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO system_parameters (parameter_name, parameter_value, updated_at) 
              VALUES (?, ?, CURRENT_TIMESTAMP)
              ON DUPLICATE KEY UPDATE 
              parameter_value = VALUES(parameter_value), 
              updated_at = CURRENT_TIMESTAMP";
    $stmt = $db->prepare($query);
    return $stmt->execute([$parameter_name, $parameter_value]);
}

/**
 * Get current trading currency (1=TL, 2=USD)
 */
function getTradingCurrency() {
    return (int)getSystemParameter('trading_currency', '1');
}

/**
 * Get currency symbol for display
 */
function getCurrencySymbol($currency_mode = null) {
    if ($currency_mode === null) {
        $currency_mode = getTradingCurrency();
    }
    
    return $currency_mode == 1 ? 'TL' : 'USD';
}

/**
 * Get currency field name for database
 */
function getCurrencyField($currency_mode = null) {
    if ($currency_mode === null) {
        $currency_mode = getTradingCurrency();
    }
    
    return $currency_mode == 1 ? 'tl' : 'usd';
}

// ===============================
// EXCHANGE RATE API SYSTEM
// ===============================

/**
 * Fetch USD/TRY rate from free API with caching
 */
function fetchUSDTRYRate() {
    // Check cache first
    $last_update = (int)getSystemParameter('rate_last_update', '0');
    $current_time = time();
    
    // If cache is fresh (less than 5 minutes), return cached rate
    if (($current_time - $last_update) < EXCHANGE_RATE_CACHE_TIME) {
        $cached_rate = getSystemParameter('usdtry_rate', '27.45');
        return (float)$cached_rate;
    }
    
    // Try primary API (exchangerate-api.com - free, no key required)
    $rate = fetchFromExchangeAPI();
    
    // If primary fails, try backup or use cached/default
    if (!$rate) {
        $rate = (float)getSystemParameter('usdtry_rate', '27.45');
    }
    
    // Update cache
    setSystemParameter('usdtry_rate', $rate);
    setSystemParameter('rate_last_update', $current_time);
    
    return $rate;
}

/**
 * Fetch rate from primary exchange API
 */
function fetchFromExchangeAPI() {
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'GlobalBorsa/1.0'
            ]
        ]);
        
        $response = @file_get_contents(EXCHANGE_API_URL, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Exchange rate API returns rates object with currency codes
        if (isset($data['rates']['TRY'])) {
            return (float)$data['rates']['TRY'];
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Exchange API Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current USD/TRY rate (cached)
 */
function getUSDTRYRate() {
    return fetchUSDTRYRate();
}

/**
 * Convert USD to TL
 */
function convertUSDToTL($usd_amount) {
    $rate = getUSDTRYRate();
    return $usd_amount * $rate;
}

/**
 * Convert TL to USD
 */
function convertTLToUSD($tl_amount) {
    $rate = getUSDTRYRate();
    return $tl_amount / $rate;
}

// ===============================
// UPDATED TRADING FUNCTIONS
// ===============================

/**
 * Get user balance based on current trading currency setting
 */
function getTradingBalance($user_id) {
    $currency_field = getCurrencyField();
    return getUserBalance($user_id, $currency_field);
}

/**
 * Updated execute trade function with parametric currency support
 */
function executeTradeParametric($user_id, $symbol, $type, $amount, $price_usd, $leverage = 1, $is_leverage_trade = false) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        $trading_currency = getTradingCurrency();
        $currency_field = getCurrencyField($trading_currency);
        
        // Calculate total in USD first
        $total_usd = $amount * $price_usd;
        $fee_usd = $total_usd * (TRADING_FEE / 100);
        
        // Debug logging
        error_log("TRADE DEBUG: user_id=$user_id, symbol=$symbol, type=$type, amount=$amount, price_usd=$price_usd");
        error_log("TRADE DEBUG: trading_currency=$trading_currency, total_usd=$total_usd, fee_usd=$fee_usd");
        
        if ($trading_currency == 1) { // TL mode
            // Convert to TL
            $usd_to_tl_rate = getUSDTRYRate();
            $total_tl = $total_usd * $usd_to_tl_rate;
            $fee_tl = $fee_usd * $usd_to_tl_rate;
            $total_with_fee = $total_tl + $fee_tl;
            
            error_log("TRADE DEBUG TL MODE: usd_to_tl_rate=$usd_to_tl_rate, total_tl=$total_tl, fee_tl=$fee_tl, total_with_fee=$total_with_fee");
            
            if ($type == 'buy') {
                // Check TL balance
                $balance = getUserBalance($user_id, 'tl');
                error_log("TRADE DEBUG BUY: current_tl_balance=$balance, required=$total_with_fee");
                error_log("TRADE DEBUG BUY: balance_type=" . gettype($balance) . ", total_type=" . gettype($total_with_fee));
                error_log("TRADE DEBUG BUY: balance_float=" . (float)$balance . ", total_float=" . (float)$total_with_fee);
                error_log("TRADE DEBUG BUY: comparison_result=" . ($balance < $total_with_fee ? 'FAIL' : 'PASS'));
                
                // Ensure both values are floats for comparison
                $balance_float = (float)$balance;
                $total_with_fee_float = (float)$total_with_fee;
                
                if ($balance_float < $total_with_fee_float) {
                    error_log("TRADE ERROR: Balance check failed. $balance_float < $total_with_fee_float");
                    
                    // Add detailed debugging
                    error_log("TRADE DEBUG DETAIL: balance_raw='$balance', total_raw='$total_with_fee'");
                    error_log("TRADE DEBUG DETAIL: balance_float='$balance_float', total_float='$total_with_fee_float'");
                    error_log("TRADE DEBUG DETAIL: usd_amount='$amount', price='$price_usd', total_usd='$total_usd'");
                    error_log("TRADE DEBUG DETAIL: rate='$usd_to_tl_rate', total_tl='$total_tl', fee_tl='$fee_tl'");
                    
                    throw new Exception("Insufficient TL balance. Have: $balance_float TL, Need: $total_with_fee_float TL");
                }
                
                error_log("TRADE DEBUG: Balance check PASSED! Proceeding with trade...");
                error_log("TRADE SUCCESS: $balance_float >= $total_with_fee_float");
                
                // Deduct TL, add crypto
                updateUserBalance($user_id, 'tl', $total_with_fee, 'subtract');
                $crypto_currency = strtolower(explode('_', $symbol)[0]);
                updateUserBalance($user_id, $crypto_currency, $amount, 'add');
                
                $final_total = $total_tl;
                $final_fee = $fee_tl;
                
            } else { // sell
                // Check crypto balance
                $crypto_currency = strtolower(explode('_', $symbol)[0]);
                $crypto_balance = getUserBalance($user_id, $crypto_currency);
                if ($crypto_balance < $amount) {
                    throw new Exception('Insufficient crypto balance');
                }
                
                // Deduct crypto, add TL
                updateUserBalance($user_id, $crypto_currency, $amount, 'subtract');
                updateUserBalance($user_id, 'tl', $total_tl - $fee_tl, 'add');
                
                $final_total = $total_tl;
                $final_fee = $fee_tl;
            }
            
        } else { // USD mode
            $total_with_fee = $total_usd + $fee_usd;
            
            if ($type == 'buy') {
                // Check USD balance
                $balance = getUserBalance($user_id, 'usd');
                if ($balance < $total_with_fee) {
                    throw new Exception('Insufficient USD balance');
                }
                
                // Deduct USD, add crypto
                updateUserBalance($user_id, 'usd', $total_with_fee, 'subtract');
                $crypto_currency = strtolower(explode('_', $symbol)[0]);
                updateUserBalance($user_id, $crypto_currency, $amount, 'add');
                
                $final_total = $total_usd;
                $final_fee = $fee_usd;
                
            } else { // sell
                // Check crypto balance
                $crypto_currency = strtolower(explode('_', $symbol)[0]);
                $crypto_balance = getUserBalance($user_id, $crypto_currency);
                if ($crypto_balance < $amount) {
                    throw new Exception('Insufficient crypto balance');
                }
                
                // Deduct crypto, add USD
                updateUserBalance($user_id, $crypto_currency, $amount, 'subtract');
                updateUserBalance($user_id, 'usd', $total_usd - $fee_usd, 'add');
                
                $final_total = $total_usd;
                $final_fee = $fee_usd;
            }
        }
        
        // Record transaction
        $query = "INSERT INTO transactions (user_id, type, symbol, amount, price, total, fee) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $type, $symbol, $amount, $price_usd, $final_total, $final_fee]);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Trade execution error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get formatted balance for header display
 */
function getFormattedHeaderBalance($user_id) {
    $trading_currency = getTradingCurrency();
    $currency_field = getCurrencyField($trading_currency);
    $currency_symbol = getCurrencySymbol($trading_currency);
    
    $balance = getUserBalance($user_id, $currency_field);
    
    return formatNumber($balance) . ' ' . $currency_symbol;
}

/**
 * Get minimum trade amount in current currency
 */
function getMinTradeAmount() {
    $trading_currency = getTradingCurrency();
    
    if ($trading_currency == 1) { // TL
        return MIN_TRADE_AMOUNT;
    } else { // USD
        return MIN_TRADE_AMOUNT / getUSDTRYRate(); // Convert TL to USD
    }
}

/**
 * Format Turkish number (fixing missing function)
 */
function formatTurkishNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

// ===============================
// SIMPLE CLEAN TRADING SYSTEM
// ===============================

// ===============================
// PORTFOLIO MANAGEMENT FUNCTIONS
// ===============================

/**
 * Get user portfolio
 */
function getUserPortfolio($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT p.*, m.name, m.price as current_price, m.change_24h, m.logo_url 
              FROM user_portfolio p 
              LEFT JOIN markets m ON p.symbol = m.symbol 
              WHERE p.user_id = ? AND p.quantity > 0
              ORDER BY p.total_invested DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get portfolio value
 */
function getPortfolioValue($user_id) {
    $portfolio = getUserPortfolio($user_id);
    $total_value = 0;
    $total_invested = 0;
    
    foreach ($portfolio as $holding) {
        $current_value = $holding['quantity'] * $holding['current_price'];
        $total_value += $current_value;
        $total_invested += $holding['total_invested'];
    }
    
    return [
        'current_value' => $total_value,
        'total_invested' => $total_invested,
        'profit_loss' => $total_value - $total_invested,
        'profit_loss_percentage' => $total_invested > 0 ? (($total_value - $total_invested) / $total_invested) * 100 : 0
    ];
}

/**
 * Update user portfolio
 */
function updateUserPortfolio($user_id, $symbol, $quantity, $price, $action) {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($action == 'buy') {
        // Check if user already has this asset
        $query = "SELECT * FROM user_portfolio WHERE user_id = ? AND symbol = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $symbol]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing holding - calculate new average price
            $old_quantity = $existing['quantity'];
            $old_invested = $existing['total_invested'];
            $new_quantity = $old_quantity + $quantity;
            $new_invested = $old_invested + ($quantity * $price);
            $new_avg_price = $new_invested / $new_quantity;
            
            $query = "UPDATE user_portfolio SET 
                      quantity = ?, 
                      avg_price = ?, 
                      total_invested = ?,
                      updated_at = CURRENT_TIMESTAMP 
                      WHERE user_id = ? AND symbol = ?";
            $stmt = $db->prepare($query);
            return $stmt->execute([$new_quantity, $new_avg_price, $new_invested, $user_id, $symbol]);
        } else {
            // Create new holding
            $query = "INSERT INTO user_portfolio (user_id, symbol, quantity, avg_price, total_invested) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            return $stmt->execute([$user_id, $symbol, $quantity, $price, $quantity * $price]);
        }
    } else { // sell
        // Reduce quantity
        $query = "SELECT * FROM user_portfolio WHERE user_id = ? AND symbol = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $symbol]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing && $existing['quantity'] >= $quantity) {
            $new_quantity = $existing['quantity'] - $quantity;
            
            if ($new_quantity <= 0) {
                // Remove holding completely
                $query = "DELETE FROM user_portfolio WHERE user_id = ? AND symbol = ?";
                $stmt = $db->prepare($query);
                return $stmt->execute([$user_id, $symbol]);
            } else {
                // Update quantity (keep same avg price)
                $new_invested = $new_quantity * $existing['avg_price'];
                $query = "UPDATE user_portfolio SET 
                          quantity = ?, 
                          total_invested = ?,
                          updated_at = CURRENT_TIMESTAMP 
                          WHERE user_id = ? AND symbol = ?";
                $stmt = $db->prepare($query);
                return $stmt->execute([$new_quantity, $new_invested, $user_id, $symbol]);
            }
        }
    }
    
    return false;
}

/**
 * Get portfolio holding for specific symbol
 */
function getPortfolioHolding($user_id, $symbol) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM user_portfolio WHERE user_id = ? AND symbol = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $symbol]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Execute simple trade with portfolio update
 */
function executeSimpleTrade($user_id, $symbol, $action, $usd_amount, $usd_price) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $db->beginTransaction();
        
        // Calculate quantity
        $quantity = $usd_amount / $usd_price;
        
        // Get trading currency setting (1=TL, 2=USD)
        $trading_currency = getTradingCurrency();
        
        if ($trading_currency == 1) { // TL Mode
            // Convert USD to TL
            $usd_to_tl_rate = getUSDTRYRate();
            $tl_amount = $usd_amount * $usd_to_tl_rate;
            $fee_tl = 0; // No fee
            $total_tl = $tl_amount + $fee_tl;
            
            if ($action == 'buy') {
                // Check TL balance
                $tl_balance = getUserBalance($user_id, 'tl');
                
                if ($tl_balance < $total_tl) {
                    $db->rollback();
                    return false;
                }
                
                // Deduct TL from user balance
                $balance_update = updateUserBalance($user_id, 'tl', $total_tl, 'subtract');
                if (!$balance_update) {
                    $db->rollback();
                    return false;
                }
                
                // Update portfolio - add to holdings
                $portfolio_update = updateUserPortfolio($user_id, $symbol, $quantity, $usd_price, 'buy');
                if (!$portfolio_update) {
                    $db->rollback();
                    return false;
                }
                
                // Record transaction (always in USD for consistency)
                $query = "INSERT INTO transactions (user_id, type, symbol, amount, price, total, fee) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $transaction_insert = $stmt->execute([$user_id, $action, $symbol, $quantity, $usd_price, $usd_amount, 0]);
                
                if (!$transaction_insert) {
                    $db->rollback();
                    return false;
                }
                
            } else { // sell
                // Check if user has enough in portfolio
                $holding = getPortfolioHolding($user_id, $symbol);
                if (!$holding || $holding['quantity'] < $quantity) {
                    $db->rollback();
                    return false;
                }
                
                // Update portfolio - remove from holdings
                $portfolio_update = updateUserPortfolio($user_id, $symbol, $quantity, $usd_price, 'sell');
                if (!$portfolio_update) {
                    $db->rollback();
                    return false;
                }
                
                // Add TL to balance (minus fee)
                $balance_update = updateUserBalance($user_id, 'tl', $tl_amount - $fee_tl, 'add');
                if (!$balance_update) {
                    $db->rollback();
                    return false;
                }
                
                // Record transaction (always in USD for consistency)
                $query = "INSERT INTO transactions (user_id, type, symbol, amount, price, total, fee) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $transaction_insert = $stmt->execute([$user_id, $action, $symbol, $quantity, $usd_price, $usd_amount, 0]);
                if (!$transaction_insert) {
                    $db->rollback();
                    return false;
                }
            }
            
        } else { // USD Mode
            $fee_usd = 0; // No fee
            $total_usd = $usd_amount + $fee_usd;
            
            if ($action == 'buy') {
                // Check USD balance
                $usd_balance = getUserBalance($user_id, 'usd');
                
                if ($usd_balance < $total_usd) {
                    $db->rollback();
                    return false;
                }
                
                // Deduct USD from user balance
                $balance_update = updateUserBalance($user_id, 'usd', $total_usd, 'subtract');
                if (!$balance_update) {
                    $db->rollback();
                    return false;
                }
                
                // Update portfolio - add to holdings
                $portfolio_update = updateUserPortfolio($user_id, $symbol, $quantity, $usd_price, 'buy');
                if (!$portfolio_update) {
                    $db->rollback();
                    return false;
                }
                
                // Record transaction
                $query = "INSERT INTO transactions (user_id, type, symbol, amount, price, total, fee) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $transaction_insert = $stmt->execute([$user_id, $action, $symbol, $quantity, $usd_price, $usd_amount, $fee_usd]);
                
                if (!$transaction_insert) {
                    $db->rollback();
                    return false;
                }
                
            } else { // sell
                // Check if user has enough in portfolio
                $holding = getPortfolioHolding($user_id, $symbol);
                if (!$holding || $holding['quantity'] < $quantity) {
                    $db->rollback();
                    return false;
                }
                
                // Update portfolio - remove from holdings
                $portfolio_update = updateUserPortfolio($user_id, $symbol, $quantity, $usd_price, 'sell');
                if (!$portfolio_update) {
                    $db->rollback();
                    return false;
                }
                
                // Add USD to balance (minus fee)
                $balance_update = updateUserBalance($user_id, 'usd', $usd_amount - $fee_usd, 'add');
                if (!$balance_update) {
                    $db->rollback();
                    return false;
                }
                
                // Record transaction
                $query = "INSERT INTO transactions (user_id, type, symbol, amount, price, total, fee) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $transaction_insert = $stmt->execute([$user_id, $action, $symbol, $quantity, $usd_price, $usd_amount, $fee_usd]);
                if (!$transaction_insert) {
                    $db->rollback();
                    return false;
                }
            }
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

// ===============================
// KALDIRAÇ SİSTEMİ (LEVERAGE SYSTEM)
// ===============================

/**
 * Get user's leverage positions
 */
function getUserLeveragePositions($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM leverage_positions WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update leverage position PnL
 */
function updateLeveragePositionPnL($position_id, $unrealized_pnl) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE leverage_positions SET unrealized_pnl = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$unrealized_pnl, $position_id]);
}

/**
 * Close leverage position
 */
function closeLeveragePosition($position_id, $close_price, $user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Get position details
        $query = "SELECT * FROM leverage_positions WHERE id = ? AND user_id = ? AND status = 'OPEN'";
        $stmt = $db->prepare($query);
        $stmt->execute([$position_id, $user_id]);
        $position = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$position) {
            throw new Exception('Position not found or already closed');
        }
        
        // Calculate final PnL
        $price_change = $close_price - $position['entry_price'];
        if ($position['trade_type'] === 'SHORT') {
            $price_change = -$price_change;
        }
        $realized_pnl = ($price_change / $position['entry_price']) * $position['position_size'];
        
        // Calculate closing fee
        $closing_fee = $position['position_size'] * 0.001; // 0.1% fee
        $net_pnl = $realized_pnl - $closing_fee;
        
        // Update position
        $query = "UPDATE leverage_positions SET 
                  status = 'CLOSED', 
                  realized_pnl = ?, 
                  unrealized_pnl = 0,
                  closed_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$realized_pnl, $position_id]);
        
        // Add closing transaction
        $query = "INSERT INTO leverage_transactions (
                    user_id, position_id, type, symbol, amount, price, fee, 
                    pnl, leverage_ratio, trade_type
                  ) VALUES (?, ?, 'CLOSE', ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $user_id, $position_id, $position['symbol'], $position['collateral'],
            $close_price, $closing_fee, $realized_pnl, $position['leverage_ratio'], $position['trade_type']
        ]);
        
        // Return collateral + PnL to user balance
        $return_amount = $position['collateral'] + $net_pnl;
        if ($return_amount > 0) {
            updateUserBalance($user_id, 'usd', $return_amount, 'add');
        }
        
        // Log transaction
        $transaction_type = $position['trade_type'] === 'LONG' ? 'CLOSE_LONG' : 'CLOSE_SHORT';
        $query = "INSERT INTO transactions (
                    user_id, type, symbol, amount, price, total, fee, market_name, created_at
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $user_id, $transaction_type, $position['symbol'], $position['collateral'],
            $close_price, $return_amount, $closing_fee, getSingleMarket($position['symbol'])['name'] ?? $position['symbol']
        ]);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

/**
 * Check for liquidation risk
 */
function checkLiquidationRisk($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT lp.*, m.price as current_price 
              FROM leverage_positions lp 
              LEFT JOIN markets m ON lp.symbol = m.symbol 
              WHERE lp.user_id = ? AND lp.status = 'OPEN'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $at_risk_positions = [];
    
    foreach ($positions as $position) {
        $current_price = $position['current_price'] ?? $position['entry_price'];
        $liquidation_price = $position['liquidation_price'];
        
        $is_at_risk = false;
        if ($position['trade_type'] === 'LONG' && $current_price <= $liquidation_price) {
            $is_at_risk = true;
        } elseif ($position['trade_type'] === 'SHORT' && $current_price >= $liquidation_price) {
            $is_at_risk = true;
        }
        
        if ($is_at_risk) {
            $at_risk_positions[] = $position;
        }
    }
    
    return $at_risk_positions;
}

/**
 * Calculate liquidation price for a position
 */
function calculateLiquidationPrice($entry_price, $leverage_ratio, $trade_type) {
    if ($trade_type === 'LONG') {
        return $entry_price * (1 - (1 / $leverage_ratio));
    } else { // SHORT
        return $entry_price * (1 + (1 / $leverage_ratio));
    }
}

/**
 * Get leverage system status
 */
function getLeverageSystemStatus() {
    $database = new Database();
    $db = $database->getConnection();
    
    $status = [
        'enabled' => true,
        'positions_table' => false,
        'transactions_table' => false,
        'positions_count' => 0,
        'transactions_count' => 0,
        'settings_count' => 0
    ];
    
    try {
        // Check if tables exist
        $tables = ['leverage_positions', 'leverage_transactions'];
        foreach ($tables as $table) {
            $query = "SHOW TABLES LIKE '$table'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $status[$table === 'leverage_positions' ? 'positions_table' : 'transactions_table'] = true;
                
                // Count records
                $count_query = "SELECT COUNT(*) FROM $table";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->execute();
                $status[$table === 'leverage_positions' ? 'positions_count' : 'transactions_count'] = $count_stmt->fetchColumn();
            }
        }
        
        // Check settings
        $settings_query = "SELECT COUNT(*) FROM admin_settings WHERE setting_key LIKE 'leverage%' OR setting_key LIKE '%leverage%'";
        $stmt = $db->prepare($settings_query);
        $stmt->execute();
        $status['settings_count'] = $stmt->fetchColumn();
        
    } catch (Exception $e) {
        $status['enabled'] = false;
        $status['error'] = $e->getMessage();
    }
    
    return $status;
}

/**
 * Get leverage transactions for a user
 */
function getUserLeverageTransactions($user_id, $limit = 50) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT lt.*, lp.symbol, lp.trade_type, m.name as market_name, m.logo_url 
              FROM leverage_transactions lt 
              LEFT JOIN leverage_positions lp ON lt.position_id = lp.id 
              LEFT JOIN markets m ON lp.symbol = m.symbol 
              WHERE lt.user_id = ? 
              ORDER BY lt.created_at DESC 
              LIMIT ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, (int)$limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get total leverage exposure for a user
 */
function getUserLeverageExposure($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT 
                SUM(position_size) as total_position_size,
                SUM(collateral) as total_collateral,
                SUM(unrealized_pnl) as total_unrealized_pnl,
                COUNT(*) as total_positions
              FROM leverage_positions 
              WHERE user_id = ? AND status = 'OPEN'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Auto-liquidate positions at risk
 */
function autoLiquidatePositions() {
    $database = new Database();
    $db = $database->getConnection();
    
    $liquidated_count = 0;
    
    try {
        // Get all open positions with current market prices
        $query = "SELECT lp.*, m.price as current_price 
                  FROM leverage_positions lp 
                  LEFT JOIN markets m ON lp.symbol = m.symbol 
                  WHERE lp.status = 'OPEN'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($positions as $position) {
            $current_price = $position['current_price'] ?? $position['entry_price'];
            $liquidation_price = $position['liquidation_price'];
            
            $should_liquidate = false;
            if ($position['trade_type'] === 'LONG' && $current_price <= $liquidation_price) {
                $should_liquidate = true;
            } elseif ($position['trade_type'] === 'SHORT' && $current_price >= $liquidation_price) {
                $should_liquidate = true;
            }
            
            if ($should_liquidate) {
                // Liquidate position
                $db->beginTransaction();
                
                try {
                    // Update position status
                    $update_query = "UPDATE leverage_positions SET 
                                     status = 'LIQUIDATED', 
                                     realized_pnl = ?,
                                     unrealized_pnl = 0,
                                     closed_at = CURRENT_TIMESTAMP 
                                     WHERE id = ?";
                    $stmt = $db->prepare($update_query);
                    $stmt->execute([-$position['collateral'], $position['id']]); // Total loss
                    
                    // Add liquidation transaction
                    $insert_query = "INSERT INTO leverage_transactions (
                                       user_id, position_id, type, symbol, amount, price, fee, 
                                       pnl, leverage_ratio, trade_type
                                     ) VALUES (?, ?, 'LIQUIDATION', ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($insert_query);
                    $stmt->execute([
                        $position['user_id'], $position['id'], $position['symbol'], 
                        $position['collateral'], $current_price, 0, -$position['collateral'],
                        $position['leverage_ratio'], $position['trade_type']
                    ]);
                    
                    $db->commit();
                    $liquidated_count++;
                    
                } catch (Exception $e) {
                    $db->rollback();
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Auto-liquidation error: " . $e->getMessage());
    }
    
    return $liquidated_count;
}
?>
