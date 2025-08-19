-- Users tablosuna ad ve soyad kolonları ekleme
ALTER TABLE users 
ADD COLUMN first_name VARCHAR(50) NULL AFTER id,
ADD COLUMN last_name VARCHAR(50) NULL AFTER first_name;

-- Mevcut kullanıcılar için varsayılan değerler
UPDATE users 
SET first_name = 'Kullanıcı', 
    last_name = username 
WHERE first_name IS NULL OR last_name IS NULL;

-- Kolonları zorunlu hale getirme (isteğe bağlı)
-- ALTER TABLE users 
-- MODIFY COLUMN first_name VARCHAR(50) NOT NULL,
-- MODIFY COLUMN last_name VARCHAR(50) NOT NULL;

-- Değişiklikleri kontrol etme
SELECT id, first_name, last_name, username, email FROM users LIMIT 5;
