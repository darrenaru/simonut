<?php
// ================================================
// FILE: php/auth-api.php
// API untuk Login dan Authentication
// ================================================

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ================================================
// POST - Login
// ================================================
if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = clean_input($input['username']);
    $password = $input['password'];
    
    if (empty($username) || empty($password)) {
        send_response(false, 'Username dan password wajib diisi', null);
    }
    
    // Cari user berdasarkan username
    $sql = "SELECT * FROM users WHERE username = '$username' AND status = 'aktif'";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        send_response(false, 'Username tidak ditemukan atau akun tidak aktif', null);
    }
    
    $user = $result->fetch_assoc();
    
    // Verifikasi password
    if (!password_verify($password, $user['password'])) {
        send_response(false, 'Password salah', null);
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // Redirect berdasarkan role
    $redirect_url = 'index.html'; // Default
    
    if ($user['role'] === 'admin') {
        $redirect_url = 'index.html'; // Admin Dashboard
    } elseif ($user['role'] === 'staff') {
        $redirect_url = 'dashboard-staff.html'; // Staff Dashboard
    } elseif ($user['role'] === 'kepala_instalasi') {
        $redirect_url = 'dashboard-kepala-instalasi.html'; // Kepala Instalasi Dashboard
    }
    
    send_response(true, 'Login berhasil', [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'redirect' => $redirect_url
    ]);
}

// ================================================
// GET - Check Login Status
// ================================================
elseif ($method === 'GET' && $action === 'check') {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        send_response(true, 'User logged in', [
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'nama_lengkap' => $_SESSION['nama_lengkap'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ]
        ]);
    } else {
        send_response(false, 'User not logged in', null);
    }
}

// ================================================
// POST - Logout
// ================================================
elseif ($method === 'POST' && $action === 'logout') {
    session_unset();
    session_destroy();
    send_response(true, 'Logout berhasil', null);
}

else {
    send_response(false, 'Invalid request', null);
}

$conn->close();
?>