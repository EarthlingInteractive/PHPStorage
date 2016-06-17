CREATE DATABASE IF NOT EXISTS phpstoragetest CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci';
GRANT ALL PRIVILEGES ON phpstoragetest.* TO 'phpstoragetest'@'localhost' IDENTIFIED BY 'phpstoragetest';
FLUSH PRIVILEGES;
