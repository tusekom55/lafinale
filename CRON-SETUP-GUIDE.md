# 🕒 FMP Cron Job Kurulum Rehberi

## 📋 Özet
Bu rehber, FMP API güncellemelerini otomatik hale getirmek için cron job kurulumunu açıklar.

**Optimizasyon:** Sadece **4 API isteği/gün** ile en önemli verileri günceller!

---

## 🚀 Hızlı Kurulum (cPanel)

### 1. cPanel'e Giriş Yapın
- Hostinger kontrol paneline giriş yapın
- **"Cron Jobs"** bölümünü bulun

### 2. Yeni Cron Job Ekleyin
```
Minute: 0
Hour: 8
Day: *  
Month: *
Weekday: *
Command: curl -s "https://khaki-mongoose-274492.hostingersite.com/cron-fmp-update.php?token=globalborsa_cron_2024_secure"
```

**Açıklama:** Her gün sabah 8:00'de çalışır (Türkiye saati)

### 3. Test Edin
Kurulumdan sonra manuel test için:
```
https://khaki-mongoose-274492.hostingersite.com/cron-fmp-update.php?token=globalborsa_cron_2024_secure
```

---

## ⚙️ Alternatif Kurulum Yöntemleri

### Seçenek 1: URL Cron (Önerilen)
```bash
# Her gün sabah 8:00
0 8 * * * curl -s "https://khaki-mongoose-274492.hostingersite.com/cron-fmp-update.php?token=globalborsa_cron_2024_secure"
```

### Seçenek 2: PHP CLI
```bash
# Sadece CLI erişimi varsa
0 8 * * * /usr/bin/php /home/username/public_html/cron-fmp-update.php
```

### Seçenek 3: Wget
```bash
# curl yoksa wget kullanın
0 8 * * * wget -O /dev/null -q "https://khaki-mongoose-274492.hostingersite.com/cron-fmp-update.php?token=globalborsa_cron_2024_secure"
```

---

## 📊 Sistem Optimizasyonu

### 🎯 Ne Güncellenir?
| Kategori | Sembol Sayısı | API İsteği |
|----------|---------------|------------|
| US Stocks | Top 20 | 1 istek |
| Commodities | Top 8 | 1 istek |
| Indices | Top 6 | 1 istek |
| USD/TRY | 1 | 1 istek |
| **TOPLAM** | **35 sembol** | **4 istek** |

### ⏰ Önerilen Çalışma Saatleri
```bash
# Günde 1 kez (önerilen)
0 8 * * * [command]

# Günde 2 kez (aktif trading için)
0 8,20 * * * [command]

# Haftalık (düşük kullanım için)
0 8 * * 1 [command]
```

---

## 📝 Log Takibi

### Log Dosyası Konumu
```
/public_html/logs/cron-fmp.log
```

### Log İçeriği Örneği
```
[2024-08-13 08:00:01] === FMP Cron Update Started ===
[2024-08-13 08:00:02] Processing US Stocks...
[2024-08-13 08:00:03] US Stocks updated: 20 symbols
[2024-08-13 08:00:04] Processing Commodities...
[2024-08-13 08:00:05] Commodities updated: 8 symbols
[2024-08-13 08:00:06] Processing Indices...
[2024-08-13 08:00:07] Indices updated: 6 symbols
[2024-08-13 08:00:08] Processing Key Forex...
[2024-08-13 08:00:09] USD/TRY updated successfully
[2024-08-13 08:00:10] === UPDATE COMPLETED ===
[2024-08-13 08:00:10] Execution time: 8.23 seconds
[2024-08-13 08:00:10] Total API requests: 4
[2024-08-13 08:00:10] Updated symbols: 35
[2024-08-13 08:00:10] Errors: 0
[2024-08-13 08:00:10] Remaining daily quota: 96/100
```

---

## 🔧 Sorun Giderme

### ❌ Yaygın Hatalar

**1. 403 Access Denied**
```
Çözüm: Token parametresini kontrol edin
URL: ?token=globalborsa_cron_2024_secure
```

**2. Logs klasörü yok**
```
Çözüm: Manuel oluşturun
mkdir /public_html/logs
chmod 755 /public_html/logs
```

**3. API limiti aşıldı**
```
Çözüm: Günde 1 kez çalışacak şekilde ayarlayın
0 8 * * * [command]
```

### ✅ Test Komutları

**Manuel Test:**
```bash
curl "https://khaki-mongoose-274492.hostingersite.com/cron-fmp-update.php?token=globalborsa_cron_2024_secure"
```

**Log Kontrolü:**
```bash
tail -f /public_html/logs/cron-fmp.log
```

---

## 🛡️ Güvenlik

### Token Güvenliği
- ✅ Sadece belirli token ile erişim
- ✅ CLI ve URL erişimi desteklenir
- ✅ 403 Forbidden default davranış

### Performans
- ✅ 120 saniye timeout
- ✅ 256MB memory limit
- ✅ Rate limiting koruması

---

## 📈 Monitoring

### Başarı Kontrolü
1. **Log dosyasını kontrol edin**
2. **Veritabanında updated_at sütununu kontrol edin**
3. **API quota kullanımını takip edin**

### Uyarı Sistemi
Eğer günlük 10+ API isteği görürseniz:
- Cron frekansını azaltın
- Sembol sayısını düşürün
- Log'larda hata kontrolü yapın

---

## 🎯 Sonuç

Bu kurulum ile:
- ✅ **Günde sadece 4 API isteği**
- ✅ **35 önemli sembol güncellemesi**
- ✅ **%96 API tasarrufu**
- ✅ **Otomatik log takibi**

**Kurulum sonrası 24 saat içinde ilk logları göreceksiniz!**
