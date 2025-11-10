<?php
// ================================================
// FILE: api.php
// API untuk operasi CRUD obat
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ================================================
// GET - Ambil data obat
// ================================================
if ($method === 'GET') {
    
    if ($action === 'read') {
        $sql = "SELECT * FROM obat ORDER BY nama_obat ASC";
        $result = $conn->query($sql);
        
        $data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'nama' => $row['nama_obat'],
                    'dosis' => $row['dosis'],
                    'kategori' => $row['kategori']
                ];
            }
        }
        
        send_response(true, 'Data berhasil diambil', $data);
    }
    
    // Ambil satu obat berdasarkan ID
    elseif ($action === 'read_single' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM obat WHERE id = $id";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                'id' => (int)$row['id'],
                'nama' => $row['nama_obat'],
                'dosis' => $row['dosis'],
                'kategori' => $row['kategori']
            ];
            send_response(true, 'Data berhasil diambil', $data);
        } else {
            send_response(false, 'Data tidak ditemukan', null);
        }
    }
}

// ================================================
// POST - Tambah data obat baru
// ================================================
elseif ($method === 'POST') {
    
    if ($action === 'create') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $nama = clean_input($input['nama']);
        $dosis = clean_input($input['dosis']);
        $kategori = clean_input($input['kategori']);
        
        if (empty($nama) || empty($dosis) || empty($kategori)) {
            send_response(false, 'Semua field wajib diisi', null);
        }
        
        $sql = "INSERT INTO obat (nama_obat, dosis, kategori) VALUES ('$nama', '$dosis', '$kategori')";
        
        if ($conn->query($sql) === TRUE) {
            $new_id = $conn->insert_id;
            send_response(true, 'Data berhasil ditambahkan', ['id' => $new_id]);
        } else {
            send_response(false, 'Gagal menambahkan data: ' . $conn->error, null);
        }
    }
}

// ================================================
// PUT - Update data obat
// ================================================
elseif ($method === 'PUT') {
    
    if ($action === 'update') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)$input['id'];
        $nama = clean_input($input['nama']);
        $dosis = clean_input($input['dosis']);
        $kategori = clean_input($input['kategori']);
        
        if (empty($nama) || empty($dosis) || empty($kategori)) {
            send_response(false, 'Semua field wajib diisi', null);
        }
        
        $sql = "UPDATE obat SET nama_obat = '$nama', dosis = '$dosis', kategori = '$kategori' WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            send_response(true, 'Data berhasil diupdate', null);
        } else {
            send_response(false, 'Gagal mengupdate data: ' . $conn->error, null);
        }
    }
}

// ================================================
// DELETE - Hapus data obat
// ================================================
elseif ($method === 'DELETE') {
    
    if ($action === 'delete') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)$input['id'];
        
        $sql = "DELETE FROM obat WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            send_response(true, 'Data berhasil dihapus', null);
        } else {
            send_response(false, 'Gagal menghapus data: ' . $conn->error, null);
        }
    }
}

else {
    send_response(false, 'Invalid request', null);
}

$conn->close();
?>