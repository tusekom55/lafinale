<?php
/* ===== AYARLAR ===== */
$dbHost = 'localhost';
$dbUser = 'DB_KULLANICI';
$dbPass = 'DB_SIFRE';
$dbName = 'DB_ADI';
$apiKey = 'YOUR_FMP_API_KEY'; // https://financialmodelingprep.com

// İsteğe bağlı: sadece belli kategori (ör. us_stocks) için çalıştırmak istersen URL'ye ?cat=us_stocks ekle
$catParam = isset($_GET['cat']) ? $_GET['cat'] : null;
// Ekstra dışlanacak semboller (virgüllü) ?exclude=TSLA,NVDA
$excludeParam = isset($_GET['exclude']) ? $_GET['exclude'] : '';

/* ===== DB BAĞLANTI ===== */
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) { http_response_code(500); exit('DB bağlantı hatası: '.$mysqli->connect_error); }
$mysqli->set_charset('utf8mb4');

/* ===== SEMBOL LİSTESİ (DB'den) ===== */
$ex = ['ADBE']; // her halükârda ADBE hariç
if ($excludeParam) {
  foreach (explode(',', $excludeParam) as $e) {
    $e = strtoupper(trim($e)); if ($e) $ex[] = $e;
  }
}
$exList = "'" . implode("','", array_map([$mysqli,'real_escape_string'], array_unique($ex))) . "'";

$where = "WHERE symbol NOT IN ($exList)";
if ($catParam) $where .= " AND category='".$mysqli->real_escape_string($catParam)."'";

// Mevcut satırlardan symbol + category + name alıyoruz (category'yi korumak için)
$sql = "SELECT symbol, category, name FROM markets $where";
$res = $mysqli->query($sql);
$existing = [];
while ($row = $res->fetch_assoc()) $existing[$row['symbol']] = $row;

$symbols = array_keys($existing);
if (!$symbols) { header('Content-Type: application/json'); echo json_encode(['updated'=>0,'msg'=>'Güncellenecek sembol bulunamadı']); exit; }

/* ===== FMP'DEN QUOTE AL (batch) =====
   /api/v3/quote/AAPL,MSFT,... => price, dayHigh, dayLow, volume, changesPercentage, marketCap, name
*/
function fmp_fetch_quotes($symbols, $apiKey) {
  $out = [];
  foreach (array_chunk($symbols, 100) as $chunk) {
    $url = 'https://financialmodelingprep.com/api/v3/quote/' . implode(',', $chunk) . '?apikey=' . urlencode($apiKey);
    $json = @file_get_contents($url);
    if ($json === false) continue;
    $arr = json_decode($json, true);
    if (!is_array($arr)) continue;

    foreach ($arr as $q) {
      if (empty($q['symbol'])) continue;
      $sym = strtoupper($q['symbol']);
      $chg = isset($q['changesPercentage']) ? (float)str_replace(['%','+'], '', $q['changesPercentage']) : 0.0;
      $out[$sym] = [
        'symbol' => $sym,
        'name'   => $q['name'] ?? $sym,
        'price'  => (float)($q['price']   ?? 0),
        'high'   => (float)($q['dayHigh'] ?? 0),
        'low'    => (float)($q['dayLow']  ?? 0),
        'vol'    => (float)($q['volume']  ?? 0),
        'chg'    => $chg,
        'mcap'   => (float)($q['marketCap'] ?? 0),
      ];
    }
  }
  return $out;
}

$quotes = fmp_fetch_quotes($symbols, $apiKey);

/* ===== UPSERT =====
   markets.symbol UNIQUE -> INSERT ... ON DUPLICATE KEY UPDATE
*/
$sql = "INSERT INTO markets
 (symbol, name, price, change_24h, volume_24h, high_24h, low_24h, market_cap, category)
 VALUES (?,?,?,?,?,?,?,?,?)
 ON DUPLICATE KEY UPDATE
  name=VALUES(name),
  price=VALUES(price),
  change_24h=VALUES(change_24h),
  volume_24h=VALUES(volume_24h),
  high_24h=VALUES(high_24h),
  low_24h=VALUES(low_24h),
  market_cap=VALUES(market_cap),
  category=VALUES(category)";

$stmt = $mysqli->prepare($sql);
if (!$stmt) { http_response_code(500); exit('Prepare hatası: '.$mysqli->error); }

$updated = 0; $skipped = [];
foreach ($symbols as $s) {
  if (!isset($quotes[$s])) { $skipped[] = $s; continue; }
  $q = $quotes[$s];

  // Mevcut kategoriyi koru; yoksa catParam ya da 'us_stocks'
  $cat = $existing[$s]['category'] ?? ($catParam ?: 'us_stocks');

  $stmt->bind_param(
    'ssdddddds',
    $q['symbol'], $q['name'], $q['price'], $q['chg'], $q['vol'],
    $q['high'], $q['low'], $q['mcap'], $cat
  );
  if ($stmt->execute()) $updated++;
}
$stmt->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'ok' => true,
  'updated' => $updated,
  'count' => count($symbols),
  'skipped' => $skipped,
  'category' => $catParam ?: 'ALL'
], JSON_UNESCAPED_UNICODE);
