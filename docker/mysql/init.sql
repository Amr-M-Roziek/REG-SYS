-- Initialize databases and users expected by the application
CREATE DATABASE IF NOT EXISTS regsys_reg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS regsys_participant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS regsys_poster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS regsys_poster26 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS regsys_email CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'regsys_reg'@'%' IDENTIFIED WITH mysql_native_password BY 'regsys@2025';
CREATE USER IF NOT EXISTS 'regsys_part'@'%' IDENTIFIED WITH mysql_native_password BY 'regsys@2025';
CREATE USER IF NOT EXISTS 'regsys_poster'@'%' IDENTIFIED WITH mysql_native_password BY 'regsys@2025';
CREATE USER IF NOT EXISTS 'regsys_email'@'%' IDENTIFIED WITH mysql_native_password BY 'regsys@2025';

GRANT ALL PRIVILEGES ON regsys_reg.* TO 'regsys_reg'@'%';
GRANT ALL PRIVILEGES ON regsys_participant.* TO 'regsys_part'@'%';
GRANT ALL PRIVILEGES ON regsys_poster.* TO 'regsys_poster'@'%';
GRANT ALL PRIVILEGES ON regsys_poster26.* TO 'regsys_poster'@'%';
GRANT ALL PRIVILEGES ON regsys_email.* TO 'regsys_email'@'%';

FLUSH PRIVILEGES;

-- Minimal users tables for each database
USE regsys_reg;
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fname VARCHAR(100),
  lname VARCHAR(100),
  nationality VARCHAR(100),
  email VARCHAR(190) UNIQUE,
  profession VARCHAR(150),
  organization VARCHAR(200),
  category VARCHAR(100),
  password VARCHAR(255),
  contactno VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

USE regsys_participant;
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fname VARCHAR(100),
  lname VARCHAR(100),
  fullname VARCHAR(200),
  nationality VARCHAR(100),
  email VARCHAR(190) UNIQUE,
  profession VARCHAR(150),
  organization VARCHAR(200),
  category VARCHAR(100),
  password VARCHAR(255),
  contactno VARCHAR(50),
  userip VARCHAR(64),
  companyref VARCHAR(128),
  paypalref VARCHAR(128),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

USE regsys_poster;
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fname VARCHAR(100),
  nationality VARCHAR(100),
  coauth1name VARCHAR(200),
  coauth1nationality VARCHAR(100),
  coauth2name VARCHAR(200),
  coauth2nationality VARCHAR(100),
  coauth3name VARCHAR(200),
  coauth3nationality VARCHAR(100),
  coauth4name VARCHAR(200),
  coauth4nationality VARCHAR(100),
  coauth5name VARCHAR(200),
  coauth5nationality VARCHAR(100),
  email VARCHAR(190) UNIQUE,
  profession VARCHAR(150),
  organization VARCHAR(200),
  category VARCHAR(100),
  password VARCHAR(255),
  contactno VARCHAR(50),
  userip VARCHAR(64),
  companyref VARCHAR(128),
  paypalref VARCHAR(128),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

USE regsys_email;
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fname VARCHAR(100),
  lname VARCHAR(100),
  nationality VARCHAR(100),
  email VARCHAR(190) UNIQUE,
  profession VARCHAR(150),
  organization VARCHAR(200),
  category VARCHAR(100),
  password VARCHAR(255),
  contactno VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
