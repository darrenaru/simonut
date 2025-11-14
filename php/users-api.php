<?php
// ================================================
// FILE: users-api.php
// API untuk operasi CRUD users (Admin & Staff)
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ================================================
// GET - Ambil data users
// ================================================
if ($method === 'GET') {
    
    // Ambil semua users
    if ($action === 'read') {
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        
        $sql = "SELECT id, username, nama_lengkap, email, role, status, tanggal_dibuat FROM users";
        
        if ($role) {
            $sql .= " WHERE role = '$role'";
        }
        
        $sql .= " ORDER BY nama_lengkap ASC";
        
        $result = $conn->query($sql);
        
        $data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'username' => $row['username'],
                    'nama_lengkap' => $row['nama_lengkap'],
                    'email' => $row['email'],
                    'role' => $row['role'],
                    'status' => $row['status'],
                    'tanggal_dibuat' => $row['tanggal_dibuat']
                ];
            }
        }
        
        send_response(true, 'Data berhasil diambil', $data);
    }
    
    // Ambil satu user berdasarkan ID
    elseif ($action === 'read_single' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $sql = "SELECT id, username, nama_lengkap, email, role, status, tanggal_dibuat FROM users WHERE id = $id";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                'id' => (int)$row['id'],
                'username' => $row['username'],
                'nama_lengkap' => $row['nama_lengkap'],
                'email' => $row['email'],
                'role' => $row['role'],
                'status' => $row['status'],
                'tanggal_dibuat' => $row['tanggal_dibuat']
            ];
            send_response(true, 'Data berhasil diambil', $data);
        } else {
            send_response(false, 'Data tidak ditemukan', null);
        }
    }
    
    // Ambil hanya staff aktif
    elseif ($action === 'get_staff') {
        $sql = "SELECT id, nama_lengkap, email FROM users WHERE role = 'staff' AND status = 'aktif' ORDER BY nama_lengkap ASC";
        $result = $conn->query($sql);
        
        $data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'nama_lengkap' => $row['nama_lengkap'],
                    'email' => $row['email']
                ];
            }
        }
        
        send_response(true, 'Data berhasil diambil', $data);
    }
}

// ================================================
// POST - Tambah user baru
// ================================================
elseif ($method === 'POST') {
    
    if ($action === 'create') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $username = clean_input($input['username']);
        $password = password_hash($input['password'], PASSWORD_DEFAULT);
        $nama_lengkap = clean_input($input['nama_lengkap']);
        $email = clean_input($input['email']);
        $role = clean_input($input['role']);
        $status = isset($input['status']) ? clean_input($input['status']) : 'aktif';
        
        if (empty($username) || empty($input['password']) || empty($nama_lengkap) || empty($email) || empty($role)) {
            send_response(false, 'Semua field wajib diisi', null);
        }
        
        // Cek apakah username sudah ada
        $check_sql = "SELECT id FROM users WHERE username = '$username'";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            send_response(false, 'Username sudah digunakan', null);
        }
        
        // Cek apakah email sudah ada
        $check_sql = "SELECT id FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            send_response(false, 'Email sudah digunakan', null);
        }
        
        $sql = "INSERT INTO users (username, password, nama_lengkap, email, role, status) 
                VALUES ('$username', '$password', '$nama_lengkap', '$email', '$role', '$status')";
        
        if ($conn->query($sql) === TRUE) {
            $new_id = $conn->insert_id;
            send_response(true, 'User berhasil ditambahkan', ['id' => $new_id]);
        } else {
            send_response(false, 'Gagal menambahkan user: ' . $conn->error, null);
        }
    }
}

// ================================================
// PUT - Update data user
// ================================================
elseif ($method === 'PUT') {
    
    if ($action === 'update') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)$input['id'];
        $username = clean_input($input['username']);
        $nama_lengkap = clean_input($input['nama_lengkap']);
        $email = clean_input($input['email']);
        $role = clean_input($input['role']);
        $status = clean_input($input['status']);
        
        if (empty($username) || empty($nama_lengkap) || empty($email) || empty($role)) {
            send_response(false, 'Semua field wajib diisi', null);
        }
        
        // Cek apakah username sudah digunakan user lain
        $check_sql = "SELECT id FROM users WHERE username = '$username' AND id != $id";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            send_response(false, 'Username sudah digunakan', null);
        }
        
        // Cek apakah email sudah digunakan user lain
        $check_sql = "SELECT id FROM users WHERE email = '$email' AND id != $id";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            send_response(false, 'Email sudah digunakan', null);
        }
        
        $sql = "UPDATE users SET 
                username = '$username', 
                nama_lengkap = '$nama_lengkap', 
                email = '$email', 
                role = '$role', 
                status = '$status' 
                WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            send_response(true, 'Data berhasil diupdate', null);
        } else {
            send_response(false, 'Gagal mengupdate data: ' . $conn->error, null);
        }
    }
    
    // Update password
    elseif ($action === 'update_password') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)$input['id'];
        $new_password = password_hash($input['new_password'], PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = '$new_password' WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            send_response(true, 'Password berhasil diupdate', null);
        } else {
            send_response(false, 'Gagal mengupdate password: ' . $conn->error, null);
        }
    }
}

// ================================================
// DELETE - Hapus user
// ================================================
elseif ($method === 'DELETE') {
    
    if ($action === 'delete') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)$input['id'];
        
        // Tidak bisa menghapus user dengan id 1 (admin utama)
        if ($id === 1) {
            send_response(false, 'Admin utama tidak dapat dihapus', null);
        }
        
        $sql = "DELETE FROM users WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            send_response(true, 'User berhasil dihapus', null);
        } else {
            send_response(false, 'Gagal menghapus user: ' . $conn->error, null);
        }
    }
}

else {
    send_response(false, 'Invalid request', null);
}

$conn->close();
?>