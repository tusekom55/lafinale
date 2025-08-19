<?php
/* ===== AYARLAR ===== */
$dbHost = 'localhost';
$dbUser = 'u225998063_seccc';
$dbPass = '123456Tubb';
$dbName = 'u225998063_hurrra';
$fmpKey = 'Pt5IwxHnQLEUskikphYk55M186mqPCWL'; // https://financialmodelingprep.com

$debug = isset($_GET['debug']);     // ?debug=1 -> URL ve hata dökümü yaz
$insecure = isset($_GET['insecure']); // ?insecure=1 -> TLS verify kapat (teşhis amaçlı)

/* ===== DB ===== */
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) { http_response_code(500); exit('DB bağlantı hatası: '.$mysqli->connect_error); }
$mysqli->set_charset('utf8mb4');

/* ===== fmp_symbol listesini çek =====
   Boş olmayan tüm fmp_symbol değerlerini alıyoruz (tek istekle FMP'ye gideceğiz).
*/
$sql = "SELECT DISTINCT fmp_symbol FROM markets WHERE fmp_symbol IS NOT NULL AND fmp_symbol <> ''";
$res = $mysqli->query($sql);
$fmpSymbols = [];
while ($row = $res->fetch_assoc()) { $fmpSymbols[] = strtoupper(trim($row['fmp_symbol'])); }
if (!$fmpSymbols) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'msg'=>'fmp_symbol bulunamadı']); exit; }

/* ===== Yardımcı: cURL ===== */
function curl_get($url, $insecure=false){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER => ['User-Agent: markets-updater/2.1']
  ]);
  if ($insecure) { curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); }
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = ($body === false) ? curl_error($ch) : null;
  curl_close($ch);
  return [$body,$code,$err];
}

/* ===== TEK İSTEK: tüm fmp_symbol'lar tek URL'de ===== */
$encoded = array_map('rawurlencode', $fmpSymbols);            // ^, . gibi karakterler güvenli olsun
$url = 'https://financialmodelingprep.com/api/v3/quote/' . implode(',', $encoded) . '?apikey=' . urlencode($fmpKey);
list($json,$code,$err) = curl_get($url, $insecure);

if ($debug) {
  $masked = preg_replace('/apikey=[^&]+/i', 'apikey=****', $url);
  error_log("DEBUG ONECALL URL: ".$masked." HTTP:".$code." ERR:".($err?:'none'));
}

if ($json === false || $code !== 200) {
  http_response_code(502);
  exit('API okunamadı (HTTP '.$code.')');
}

$arr = json_decode($json, true);
if (!is_array($arr)) { http_response_code(502); exit('API formatı beklenmedik'); }

/* ===== FMP cevabını map'le: fmp_symbol -> değerler ===== */
$qmap = [];
foreach ($arr as $q) {
  if (empty($q['symbol'])) continue;
  $fs = strtoupper($q['symbol']); // FMP'nin döndürdüğü sembol
  $chg = isset($q['changesPercentage']) ? (float)str_replace(['%','+',' '], '', $q['changesPercentage']) : 0.0;
  $qmap[$fs] = [
    'name'  => $q['name'] ?? $fs,
    'price' => (float)($q['price']   ?? 0),
    'high'  => (float)($q['dayHigh'] ?? 0),
    'low'   => (float)($q['dayLow']  ?? 0),
    'vol'   => (float)($q['volume']  ?? 0),
    'chg'   => $chg,
    'mcap'  => (float)($q['marketCap'] ?? 0),
  ];
}

/* ===== UPDATE: fmp_symbol eşleşeni güncelle =====
   INSERT yapmıyoruz; sadece mevcut satırları update.
*/
$upd = $mysqli->prepare("
  UPDATE markets
     SET name=?,
         price=?,
         change_24h=?,
         volume_24h=?,
         high_24h=?,
         low_24h=?,
         market_cap=?,
         updated_at=NOW()
   WHERE fmp_symbol=?");
if (!$upd) { http_response_code(500); exit('Prepare hatası: '.$mysqli->error); }

$updated = 0; $skipped = [];
foreach ($fmpSymbols as $fs) {
  if (!isset($qmap[$fs])) { $skipped[] = $fs; continue; }
  $v = $qmap[$fs];
  $upd->bind_param(
    'sdddddds',
    $v['name'], $v['price'], $v['chg'], $v['vol'], $v['high'], $v['low'], $v['mcap'], $fs
  );
  if ($upd->execute() && $upd->affected_rows >= 0) $updated++;
}
$upd->close();

/* ===== SONUÇ ===== */
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'ok' => true,
  'http' => $code,
  'updated' => $updated,
  'total_requested' => count($fmpSymbols),
  'skipped_no_data' => $skipped
], JSON_UNESCAPED_UNICODE);
