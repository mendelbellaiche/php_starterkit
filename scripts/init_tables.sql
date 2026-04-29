-- 1. Suppression des tables si elles existent (pour repartir de zéro si besoin)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS login_attempts_ip;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. Création de la table des utilisateurs
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(100) NOT NULL,
                       email VARCHAR(150) NOT NULL UNIQUE,
                       password VARCHAR(255) NOT NULL,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Création de la table du compteur de tentative de connexion
CREATE TABLE IF NOT EXISTS login_attempts (
                                              id INT AUTO_INCREMENT PRIMARY KEY,
                                              email_hash VARCHAR(64) NOT NULL,
                                              ip_address VARCHAR(45) NOT NULL,
                                              attempts INT NOT NULL DEFAULT 0,
                                              first_attempt_at DATETIME NOT NULL,
                                              last_attempt_at DATETIME NOT NULL,
                                              blocked_until DATETIME NULL,
                                              UNIQUE KEY uniq_email_ip (email_hash, ip_address),
                                              INDEX idx_blocked_until (blocked_until),
                                              INDEX idx_last_attempt_at (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Création de la table du compteur de tentative par IP seule
CREATE TABLE IF NOT EXISTS login_attempts_ip (
                                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                                 ip_address VARCHAR(45) NOT NULL,
                                                 attempts INT NOT NULL DEFAULT 0,
                                                 first_attempt_at DATETIME NOT NULL,
                                                 last_attempt_at DATETIME NOT NULL,
                                                 blocked_until DATETIME NULL,
                                                 UNIQUE KEY uniq_ip (ip_address),
                                                 INDEX idx_ip_blocked_until (blocked_until),
                                                 INDEX idx_ip_last_attempt_at (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
