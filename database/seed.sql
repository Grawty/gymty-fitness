-- mysql -u root -p gymty < database/seed.sql

USE `gymty`;

INSERT INTO `memberships` (`name`, `price`, `duration_months`, `features`, `is_popular`)
VALUES
    ('Başlangıç', 899.00, 1,
     'Salon kullanımı|Soyunma odası ve duş|Ücretsiz vücut analizi|Mobil uygulama erişimi', 0),
    ('Pro', 1299.00, 1,
     'Sınırsız salon kullanımı|Kişisel antrenör desteği (4 seans)|Grup dersleri|Sauna ve dinlenme alanı|Beslenme danışmanlığı', 1),
    ('Elite', 1999.00, 1,
     '7/24 salon erişimi|Sınırsız kişisel antrenör|Tüm grup dersleri|Sauna, masaj ve toparlanma|Aylık performans raporu|Misafir daveti (2 kişi)', 0);

-- Yönetici hesabı için şifre özeti üretip aşağıdaki sorguyu çalıştırın:
-- php -r "echo password_hash('GucluBirSifre123', PASSWORD_DEFAULT), PHP_EOL;"
--
-- INSERT INTO `users` (`full_name`, `tc_no`, `phone`, `password`, `role`)
-- VALUES ('Sistem Yöneticisi', '11111111110', '05000000000', '<HASH>', 'admin');
