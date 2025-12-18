<?php
// ================================================
// FILE: config.php
// Koneksi ke Database SIMONUT
// ================================================

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Username default XAMPP
define('DB_PASS', '');              // Password default XAMPP (kosong)
define('DB_NAME', 'motara'); 

// Membuat koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]));
}

// Set charset ke UTF-8
$conn->set_charset("utf8mb4");

// Fungsi untuk membersihkan input
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Fungsi untuk mengirim response JSON
function send_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>