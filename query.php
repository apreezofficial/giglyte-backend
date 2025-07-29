<?php
require_once "db_connect.php";

try {
    $conn->beginTransaction();

    // USERS TABLE
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('client','freelancer','admin') DEFAULT 'client',
            full_name VARCHAR(100),
            phone VARCHAR(20),
            country VARCHAR(50),
            city VARCHAR(50),
            address VARCHAR(255),
            profile_image VARCHAR(255),
            bio TEXT,
            rating DECIMAL(3,2) DEFAULT 0.00,
            total_reviews INT DEFAULT 0,
            balance DECIMAL(10,2) DEFAULT 0.00,
            is_verified BOOLEAN DEFAULT 0,
            last_seen TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    // FREELANCER PROFILE
    $conn->exec("
        CREATE TABLE IF NOT EXISTS freelancer_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(100),
            hourly_rate DECIMAL(10,2) DEFAULT 0.00,
            skills TEXT,
            experience_level ENUM('beginner','intermediate','expert') DEFAULT 'beginner',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // PORTFOLIO
    $conn->exec("
        CREATE TABLE IF NOT EXISTS portfolios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            freelancer_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            file_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // JOBS
    $conn->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            budget DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('open','in_progress','completed','cancelled') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    //Jobs Skills requirements
    $conn->exec("
  CREATE TABLE IF NOT EXISTS job_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    skill VARCHAR(50),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE)
");
    // PROPOSALS
    $conn->exec("
        CREATE TABLE IF NOT EXISTS proposals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            freelancer_id INT NOT NULL,
            cover_letter TEXT,
            proposed_amount DECIMAL(10,2),
            estimated_days INT,
            status ENUM('pending','accepted','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
$conn->exec("
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    proposal_id INT NOT NULL,
    client_id INT NOT NULL,
    freelancer_id INT NOT NULL,
 delivery_message TEXT NULL,
 delivery_file VARCHAR(255) NULL,
 delivered_at DATETIME NULL;
    status ENUM('in_progress','completed','cancelled') DEFAULT 'in_progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (proposal_id) REFERENCES proposals(id),
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (freelancer_id) REFERENCES users(id)
)
");
    // CONTRACTS
    $conn->exec("
        CREATE TABLE IF NOT EXISTS contracts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            proposal_id INT NOT NULL,
            client_id INT NOT NULL,
            freelancer_id INT NOT NULL,
            agreed_amount DECIMAL(10,2),
            status ENUM('active','completed','cancelled') DEFAULT 'active',
            start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            end_date TIMESTAMP NULL,
            FOREIGN KEY (proposal_id) REFERENCES proposals(id),
            FOREIGN KEY (client_id) REFERENCES users(id),
            FOREIGN KEY (freelancer_id) REFERENCES users(id)
        )
    ");

    // PAYMENTS
    $conn->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contract_id INT NOT NULL,
            transaction_id VARCHAR(100) NOT NULL UNIQUE,
            amount DECIMAL(10,2) NOT NULL,
            method ENUM('paypal','stripe','bank') DEFAULT 'paypal',
            status ENUM('pending','completed','failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id)
        )
    ");

    // TRANSACTIONS (Wallet)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('credit','debit') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // MESSAGES
    $conn->exec("
        CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     last_active DATETIME DEFAULT NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    is_read TINYINT(1) DEFAULT 0;
)
    ");
    // REVIEWS
    $conn->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contract_id INT NOT NULL,
            reviewer_id INT NOT NULL,
            reviewee_id INT NOT NULL,
            rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contract_id) REFERENCES contracts(id),
            FOREIGN KEY (reviewer_id) REFERENCES users(id),
            FOREIGN KEY (reviewee_id) REFERENCES users(id)
        )
    ");

    // NOTIFICATIONS
    $conn->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50),
            message TEXT,
            is_read BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $conn->commit();
   // echo 'All tables created successfully!';
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
  //  echo 'Error creating tables: ' . $e->getMessage();
}
?>