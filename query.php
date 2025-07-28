<?php
require_once "db_connect.php";

try {
    // Users table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('buyer', 'seller', 'admin') DEFAULT 'buyer',
            full_name VARCHAR(100),
            bio TEXT,
            profile_image VARCHAR(255),
            rating DECIMAL(2,1) DEFAULT 0.0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Categories table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL
        )
    ");

    // Gigs table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS gigs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seller_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            category_id INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            delivery_time INT NOT NULL,
            thumbnail VARCHAR(255),
            status ENUM('active', 'paused', 'deleted') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(id),
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");

    // Orders table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            buyer_id INT NOT NULL,
            gig_id INT NOT NULL,
            seller_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'active', 'delivered', 'completed', 'cancelled') DEFAULT 'pending',
            requirements TEXT,
            delivery_file VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (buyer_id) REFERENCES users(id),
            FOREIGN KEY (gig_id) REFERENCES gigs(id),
            FOREIGN KEY (seller_id) REFERENCES users(id)
        )
    ");

    // Messages table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            message TEXT NOT NULL,
            attachment VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        )
    ");

    // Reviews table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            reviewer_id INT NOT NULL,
            reviewee_id INT NOT NULL,
            rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (reviewer_id) REFERENCES users(id),
            FOREIGN KEY (reviewee_id) REFERENCES users(id)
        )
    ");

    // Payments table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            transaction_id VARCHAR(100) NOT NULL UNIQUE,
            amount DECIMAL(10,2) NOT NULL,
            method ENUM('paypal', 'stripe', 'bank') DEFAULT 'paypal',
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )
    ");

    // Gig images table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS gig_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gig_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (gig_id) REFERENCES gigs(id)
        )
    ");

  //  echo "All tables created successfully!";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
