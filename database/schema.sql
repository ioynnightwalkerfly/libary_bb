CREATE DATABASE IF NOT EXISTS libary_bb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE libary_bb;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    student_id VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(60) DEFAULT NULL,
    description TEXT,
    category_id INT DEFAULT NULL,
    total_quantity INT NOT NULL,
    quantity_available INT NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS borrows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrow_code VARCHAR(50) UNIQUE,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status ENUM('borrowed', 'returned') NOT NULL DEFAULT 'borrowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_borrow_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_borrow_book FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE CASCADE
);

INSERT INTO categories (name) VALUES
('Computer Science'),
('Engineering'),
('Business'),
('Humanities'),
('Science')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (name, email, student_id, password_hash, role, status)
VALUES ('Admin User', 'admin@university.edu', 'ADMIN001', '$2y$12$4mR7gK72i4M1/IDoG4358efUgYbeOrqv36SToFgySrm5nXI4Fhulq', 'admin', 'active')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO books (title, author, isbn, description, category_id, total_quantity, quantity_available)
VALUES
('Introduction to Algorithms', 'Thomas H. Cormen', '9780262033848', 'Comprehensive guide to modern algorithms.', 1, 5, 5),
('Database System Concepts', 'Abraham Silberschatz', '9780078022159', 'Core text on database systems.', 1, 4, 4)
ON DUPLICATE KEY UPDATE
title = VALUES(title),
author = VALUES(author),
isbn = VALUES(isbn),
description = VALUES(description),
category_id = VALUES(category_id),
total_quantity = VALUES(total_quantity),
quantity_available = VALUES(quantity_available);
