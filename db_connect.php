<?php
/**
$host = '0.0.0.0';
$dbname = 'giglyte';
$username = 'root'; 
$password = 'root'; 
**/
$host = "localhost";
$dbname = "giglytec_main";
$username = "giglytec_main";   
$password = "Database@giglyte.co";       
error_reporting(0);
try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create admins table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'buyer', 'seller') DEFAULT 'buyer',
        status ENUM('active', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    
    // Check if admin user exists, if not, seed one
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        $adminUsername = 'admin';
        $adminEmail = 'admin@jobpost.com';
        $adminPassword = password_hash('Admin123!', PASSWORD_DEFAULT); // Default password: Admin123!
        $stmt = $conn->prepare("INSERT INTO admins (username, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
        $stmt->execute([$adminUsername, $adminEmail, $adminPassword]);
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>