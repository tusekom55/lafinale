-- Ödeme Yöntemleri Tablosu
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('bank','digital','crypto') NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `iban` text DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `icon` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4 Büyük Türk Bankası
INSERT INTO `payment_methods` (`type`, `name`, `code`, `iban`, `account_name`, `icon`, `sort_order`, `is_active`) VALUES
('bank', 'Türkiye İş Bankası', 'ISBANK', 'TR64 0006 4000 0011 2345 6789 01', 'GlobalBorsa Yatırım Ltd. Şti.', '🏦', 1, 1),
('bank', 'Garanti BBVA', 'GARANTI', 'TR32 0062 0410 0000 1234 5678 90', 'GlobalBorsa Yatırım Ltd. Şti.', '🏛️', 2, 1),
('bank', 'Yapı Kredi Bankası', 'YAPIKREDI', 'TR56 0006 7010 0000 0017 2950 01', 'GlobalBorsa Yatırım Ltd. Şti.', '🏪', 3, 1),
('bank', 'Akbank', 'AKBANK', 'TR43 0004 6007 5888 8000 0000 01', 'GlobalBorsa Yatırım Ltd. Şti.', '🏢', 4, 1);

-- Dijital Ödeme Yöntemleri
INSERT INTO `payment_methods` (`type`, `name`, `code`, `iban`, `account_name`, `icon`, `sort_order`, `is_active`) VALUES
('digital', 'Papara', '1234567890', '', 'GlobalBorsa Hesabı', '📱', 1, 1),
('digital', 'İninal', '9876543210', '', 'GlobalBorsa Hesabı', '💳', 2, 1);

-- 10 Büyük Kripto Para
INSERT INTO `payment_methods` (`type`, `name`, `code`, `iban`, `account_name`, `icon`, `sort_order`, `is_active`) VALUES
('crypto', 'Bitcoin', 'BTC', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 'Bitcoin Network', '₿', 1, 1),
('crypto', 'Ethereum', 'ETH', '0x742d35Cc6475C9C3e7F5e3F3e3F5e3F3e3F5e3F3', 'ERC-20', '⟠', 2, 1),
('crypto', 'Tether', 'USDT', '0x742d35Cc6475C9C3e7F5e3F3e3F5e3F3e3F5e3F4', 'ERC-20', '₮', 3, 1),
('crypto', 'BNB', 'BNB', 'bnb1grpf0955h0ykzq3ar5nmum7y6gdfl6lxfn46h2', 'BEP-20', '🔶', 4, 1),
('crypto', 'Solana', 'SOL', '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM', 'Solana Network', '◎', 5, 1),
('crypto', 'Cardano', 'ADA', 'addr1qyqszqgpqyqszqgpqyqszqgpqyqszqgpqyqszqgpqyqszqgpqyqszqgp', 'Cardano Network', '🔷', 6, 1),
('crypto', 'Polygon', 'MATIC', '0x742d35Cc6475C9C3e7F5e3F3e3F5e3F3e3F5e3F5', 'Polygon Network', '🟣', 7, 1),
('crypto', 'Avalanche', 'AVAX', 'X-avax1wlkj5kq9dlm5qm5qm5qm5qm5qm5qm5qm5qm5qm', 'Avalanche Network', '🔺', 8, 1),
('crypto', 'Dogecoin', 'DOGE', 'DH5yaieqoZN36fDVciNyRueRGvGLR3mr7L', 'Dogecoin Network', '🐕', 9, 1),
('crypto', 'TRON', 'TRX', 'TLa2f6VPqDgRE67v1736s7bJ8Ray5wYjU7', 'TRC-20', '⚡', 10, 1);

-- Admin Panel menüsüne link eklemek için
-- admin.php dosyasında bu linki ekleyin: admin_payment_methods.php
