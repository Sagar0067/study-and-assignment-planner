<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is empty

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Database
    $sql = "CREATE DATABASE IF NOT EXISTS study_planner_db";
    $pdo->exec($sql);
    echo "Database created or already exists.<br>";

    // Use Database
    $pdo->exec("USE study_planner_db");

    // Disable FK checks so we can drop in any order
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS assignments");
    $pdo->exec("DROP TABLE IF EXISTS syllabus");
    $pdo->exec("DROP TABLE IF EXISTS modules");   // from previous schema if exists
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Create Users Table
    $sqlUsers = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlUsers);
    echo "Users table created successfully.<br>";

    // Create Syllabus Table first (assignments references it)
    $sqlSyllabus = "CREATE TABLE syllabus (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        completed BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlSyllabus);
    echo "Syllabus table created successfully.<br>";

    // Create Assignments Table — syllabus_id links a task to a topic
    $sqlAssignments = "CREATE TABLE assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        syllabus_id INT NULL,
        title VARCHAR(255) NOT NULL,
        notes TEXT NULL,
        status VARCHAR(50) DEFAULT 'todo',
        priority VARCHAR(20) DEFAULT 'medium',
        deadline DATE NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (syllabus_id) REFERENCES syllabus(id) ON DELETE SET NULL
    )";
    $pdo->exec($sqlAssignments);
    echo "Assignments table created successfully.<br>";

    echo "<br><b>Database setup complete!</b>";

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// ── Safe migration: add 'notes' column to an EXISTING database without wiping data ──
try {
    $pdo2 = new PDO("mysql:host=localhost;dbname=study_planner_db", 'root', '');
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo2->exec("ALTER TABLE assignments ADD COLUMN IF NOT EXISTS notes TEXT NULL");
    echo "Migration: notes column ready.<br>";
} catch(PDOException $e) {
    // Already exists or unsupported — safe to ignore
}
?>
