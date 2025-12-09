<?php
// includes/db_config.php

// --- Database Configuration ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', ''); 
define('DB_NAME', 'qu'); // Use your actual database name

try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Stop execution and show error if connection fails
    die("ERROR: Could not connect to the database. " . $e->getMessage());
}

// --- Helper Functions for Authentication ---

/**
 * Safely retrieves the role_id based on the role_name.
 */
function get_role_id_by_name($pdo, $role_name) {
    $sql = "SELECT role_id FROM roles WHERE role_name = :role_name";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":role_name", $role_name);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['role_id'] : null;
}

/**
 * Safely retrieves the role_name based on the role_id.
 */
function get_role_name_by_id($pdo, $role_id) {
    $sql = "SELECT role_name FROM roles WHERE role_id = :role_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":role_id", $role_id);
    $stmt->execute();
    return $stmt->fetchColumn();
}
?>