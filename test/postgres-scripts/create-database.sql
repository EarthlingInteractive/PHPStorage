CREATE DATABASE "phpstoragetest" WITH ENCODING 'UTF8' LC_COLLATE='en_US.UTF8' LC_CTYPE='en_US.UTF8' TEMPLATE=template0;
CREATE USER "phpstoragetest" WITH PASSWORD 'phpstoragetest';
GRANT ALL PRIVILEGES ON DATABASE "phpstoragetest" TO "phpstoragetest";
