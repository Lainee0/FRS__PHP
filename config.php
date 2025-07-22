<?php
$host = 'localhost';
$dbname = 'frs_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ensure the table has the correct structure
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS families (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sn INT,
            row_indicator VARCHAR(50),
            last_name VARCHAR(100),
            first_name VARCHAR(100),
            middle_name VARCHAR(100),
            ext VARCHAR(10),
            relationship VARCHAR(100),
            birthday DATE,
            age INT,
            sex VARCHAR(10),
            civil_status VARCHAR(50),
            household_number VARCHAR(20),
            barangay VARCHAR(50),
            is_head TINYINT(1) DEFAULT 0,
            head_id INT NULL,
            is_leader TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (household_number),
            INDEX (barangay),
            INDEX (head_id),
            INDEX (is_head)
        )
    ");
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>