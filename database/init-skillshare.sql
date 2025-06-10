DROP database if exists skillsharedb;
CREATE DATABASE IF NOT EXISTS skillsharedb;
USE skillsharedb;

-- ✅ Utilisateurs
CREATE TABLE `user` (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    avatar VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    `role`
    created_at DATETIME  
   
);
-- ✅ Compétences proposées ou recherchées
CREATE TABLE skill (
    id_skill INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    infos TEXT,
    etat ENUM('offer', 'request') NOT NULL,  -- 'offer' = je propose, 'request' = je cherche
    created_at DATETIME ,
    updated_at DATETIME ,
    FOREIGN KEY (id_user) REFERENCES `user`(id_user) 
);
-- ✅ Demandes d’échange
CREATE TABLE `exchange` (
    id_exchange INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_skill INT NOT NULL,
    etat ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    infos TEXT,
    created_at DATETIME ,
    updated_at DATETIME ,
    FOREIGN KEY (id_user) REFERENCES `user`(id_user) ,
    FOREIGN KEY (id_skill) REFERENCES skill(id_skill) 
);
-- ✅ Notes sur les échanges terminés
CREATE TABLE rating (
    id_rating INT AUTO_INCREMENT PRIMARY KEY,
    id_exchange INT NOT NULL,
    id_user INT NOT NULL,
    rating_value TINYINT NOT NULL CHECK (rating_value BETWEEN 1 AND 5),
    commentaire TEXT,
    created_at DATETIME ,
    updated_at DATETIME ,
    FOREIGN KEY (id_exchange) REFERENCES `exchange`(id_exchange) ,
    FOREIGN KEY (id_user) REFERENCES `user`(id_user) 
);