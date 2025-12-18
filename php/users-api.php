<?php
// ================================================
// FILE: users-api.php
// API untuk operasi CRUD users (Admin & Staff) dengan foto profil
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

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
        
        $sql = "SELECT id, username, nama_lengkap, email, foto_profil, role, status, tanggal_dibuat FROM users";
        
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
                    'foto_profil' => $row['foto_profil'] ?: 'default-avatar.png',
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
        $sql = "SELECT id, username, nama_lengkap, email, foto_profil, role, status, tanggal_dibuat FROM users WHERE id = $id";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                'id' => (int)$row['id'],
                'username' => $row['username'],
                'nama_lengkap' => $row['nama_lengkap'],
                'email' => $row['email'],
                'foto_profil' => $row['foto_profil'] ?: 'default-avatar.png',
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
        $sql = "SELECT id, nama_lengkap, email, foto_profil FROM users WHERE role = 'staff' AND status = 'aktif' ORDER BY nama_lengkap ASC";
        $result = $conn->query($sql);
        
        $data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'nama_lengkap' => $row['nama_lengkap'],
                    'email' => $row['email'],
                    'foto_profil' => $row['foto_profil'] ?: 'default-avatar.png'
                ];
            }
        }
        
        send_response(true, 'Data berhasil diambil', $data);
    }
    
    // Ambil foto profil user
    elseif ($action === 'get_foto' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $sql = "SELECT foto_profil FROM users WHERE id = $id";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $foto_profil = $row['foto_profil'] ?: 'default-avatar.png';
            
            // Cek file ada atau tidak
            $file_path = __DIR__ . '/uploads/profiles/' . $foto_profil;
            if (!file_exists($file_path)) {
                $foto_profil = 'default-avatar.png';
            }
            
            send_response(true, 'Foto berhasil diambil', ['foto_profil' => $foto_profil]);
        } else {
            send_response(false, 'User tidak ditemukan', null);
        }
    }
}

// ================================================
// POST - Tambah user baru (dengan upload foto)
// ================================================
elseif ($method === 'POST') {
    
    if ($action === 'create') {
        // Gunakan multipart form data untuk upload file
        if (isset($_FILES['foto_profil'])) {
            $username = clean_input($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $nama_lengkap = clean_input($_POST['nama_lengkap']);
            $email = clean_input($_POST['email']);
            $role = clean_input($_POST['role']);
            $status = isset($_POST['status']) ? clean_input($_POST['status']) : 'aktif';
            
            if (empty($username) || empty($_POST['password']) || empty($nama_lengkap) || empty($email) || empty($role)) {
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
            
            // Handle upload foto
            $foto_profil = 'default-avatar.png';
            if ($_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['foto_profil'];
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Validasi file
                if (!in_array($file_extension, $allowed_extensions)) {
                    send_response(false, 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.', null);
                }
                
                if ($file['size'] > $max_size) {
                    send_response(false, 'Ukuran file terlalu besar. Maksimal 2MB.', null);
                }
                
                // Generate unique filename
                $foto_profil = 'profile_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_dir = __DIR__ . '/uploads/profiles/';
                
                // Create directory if not exists
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $target_file = $upload_dir . $foto_profil;
                
                // Move uploaded file
                if (!move_uploaded_file($file['tmp_name'], $target_file)) {
                    $foto_profil = 'default-avatar.png';
                }
            }
            
            $sql = "INSERT INTO users (username, password, nama_lengkap, email, foto_profil, role, status) 
                    VALUES ('$username', '$password', '$nama_lengkap', '$email', '$foto_profil', '$role', '$status')";
            
            if ($conn->query($sql) === TRUE) {
                $new_id = $conn->insert_id;
                send_response(true, 'User berhasil ditambahkan', ['id' => $new_id, 'foto_profil' => $foto_profil]);
            } else {
                send_response(false, 'Gagal menambahkan user: ' . $conn->error, null);
            }
        } else {
            // Fallback untuk JSON input (tanpa foto)
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
    
    // Upload foto profil
    elseif ($action === 'upload_foto') {
        if (isset($_FILES['foto_profil']) && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $file = $_FILES['foto_profil'];
            
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validasi file
            if (!in_array($file_extension, $allowed_extensions)) {
                send_response(false, 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.', null);
            }
            
            if ($file['size'] > $max_size) {
                send_response(false, 'Ukuran file terlalu besar. Maksimal 2MB.', null);
            }
            
            // Generate unique filename
            $foto_profil = 'profile_' . $id . '_' . time() . '.' . $file_extension;
            $upload_dir = __DIR__ . '/uploads/profiles/';
            
            // Create directory if not exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Hapus foto lama jika ada
            $old_sql = "SELECT foto_profil FROM users WHERE id = $id";
            $old_result = $conn->query($old_sql);
            if ($old_result->num_rows > 0) {
                $old_row = $old_result->fetch_assoc();
                $old_foto = $old_row['foto_profil'];
                if ($old_foto && $old_foto !== 'default-avatar.png') {
                    $old_file = $upload_dir . $old_foto;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
            }
            
            $target_file = $upload_dir . $foto_profil;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                // Update database
                $sql = "UPDATE users SET foto_profil = '$foto_profil' WHERE id = $id";
                
                if ($conn->query($sql) === TRUE) {
                    send_response(true, 'Foto profil berhasil diupload', ['foto_profil' => $foto_profil]);
                } else {
                    send_response(false, 'Gagal menyimpan data foto: ' . $conn->error, null);
                }
            } else {
                send_response(false, 'Gagal upload file', null);
            }
        } else {
            send_response(false, 'File atau ID tidak ditemukan', null);
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
        
        // Hapus foto profil jika bukan default
        $sql_check = "SELECT foto_profil FROM users WHERE id = $id";
        $result_check = $conn->query($sql_check);
        if ($result_check->num_rows > 0) {
            $row = $result_check->fetch_assoc();
            $foto_profil = $row['foto_profil'];
            
            if ($foto_profil && $foto_profil !== 'default-avatar.png') {
                $file_path = __DIR__ . '/uploads/profiles/' . $foto_profil;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
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