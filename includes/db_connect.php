<?php
// db_connect.php - Central database connection (mysqli)
// Reused across all pages via require_once

session_start();

// ---- Database credentials (Local XAMPP) ----
// For local development, use the default MySQL config from XAMPP.
// Create the database in phpMyAdmin, then import schema.sql and seed_data.sql.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'academia_portal');

// ---- Create connection ----
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ---- Check connection ----
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// ---- Set charset to utf8mb4 ----
mysqli_set_charset($conn, 'utf8mb4');


/**
 * Helper: check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Helper: require a specific role before continuing
 * Redirects to login.php if not logged in / wrong role
 */
function require_role($allowed_roles) {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    if (!in_array($_SESSION['role'], (array) $allowed_roles)) {
        // Wrong role -> send to their own dashboard
        redirect_to_dashboard();
    }
}

/**
 * Helper: redirect user to their role dashboard
 */
function redirect_to_dashboard() {
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    switch ($role) {
        case 'student':     header('Location: student_dashboard.php'); break;
        case 'industry':    header('Location: industry_dashboard.php'); break;
        case 'academician': header('Location: academician_dashboard.php'); break;
        case 'institution': header('Location: institution_dashboard.php'); break;
        default:            header('Location: index.php'); break;
    }
    exit;
}

/**
 * Helper: run a prepared statement and return result / rows
 */
function db_query($sql, $types = '', $params = array()) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '' && count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

/**
 * Helper: escape output to prevent XSS
 */
function e($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}
