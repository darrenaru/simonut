<?php
// ================================================
// FILE: api.php (New Structure)
// API untuk operasi CRUD obat (tanpa stok di tabel obat)
// Stok dihitung dinamis dari transaksi_obat
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ================================================
// GET - Ambil data obat dengan stok dinamis
// ================================================
if ($method === 'GET') {
    
    if ($action === 'read') {
        // Gunakan view untuk mendapatkan data obat beserta stok
        $sql = "SELECT 
                    id, 
                    nama_obat as nama, 
                    dosis, 
                    kategori,
                    stok_tersedia as stok,
                    total_masuk,
                    total_keluar
                FROM view_stok_obat 
                ORDER BY nama_obat ASC";
        
        $result = $conn->query($sql);
        
        $data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'nama' => $row['nama'],
                    'dosis' => $row['dosis'],
                    'kategori' => $row['kategori'],
                    'stok' => (int)$row['stok'],
                    'total_masuk' => (int)$row['total_masuk'],
                    'total_keluar' => (int)$row['total_keluar']
                ];
            }
        }
        
        send_response(true, 'Data berhasil diambil', $data);
    }
    
    // Ambil satu obat berdasarkan ID dengan stok
    elseif ($action === 'read_single' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $sql = "SELECT 
                    id, 
                    nama_obat as nama, 
                    dosis, 
                    kategori,
                    stok_tersedia as stok,
                    total_masuk,
                    total_keluar
                FROM view_stok_obat 
                WHERE id = $id";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                'id' => (int)$row['id'],
                'nama' => $row['nama'],
                'dosis' => $row['dosis'],
                'kategori' => $row['kategori'],
                'stok' => (int)$row['stok'],
                'total_masuk' => (int)$row['total_masuk'],
                'total_keluar' => (int)$row['total_keluar']
            ];
            send_response(true, 'Data berhasil diambil', $data);
        } else {
            send_response(false, 'Data tidak ditemukan', null);
        }
    }
    
    // Get stok untuk obat tertentu
    elseif ($action === 'get_stok' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $sql = "SELECT get_stok_obat($id) as stok";
        $result = $conn->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            send_response(true, 'Stok berhasil diambil', ['stok' => (int)$row['stok']]);
        } else {
            send_response(false, 'Gagal mengambil stok', null);
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
        
        // Cek duplikat
        $check_sql = "SELECT id FROM obat WHERE nama_obat = '$nama' AND dosis = '$dosis'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            send_response(false, 'Obat dengan nama dan dosis yang sama sudah ada', null);
        }
        
        // Insert hanya nama, dosis, kategori (tanpa stok)
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
        
        // Update hanya nama, dosis, kategori (tanpa stok)
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
        
        // Cek apakah ada transaksi untuk obat ini
        $check_sql = "SELECT COUNT(*) as total FROM transaksi_obat WHERE id_obat = $id";
        $check_result = $conn->query($check_sql);
        $check_data = $check_result->fetch_assoc();
        
        if ($check_data['total'] > 0) {
            send_response(false, 'Tidak dapat menghapus obat yang sudah memiliki transaksi. Total transaksi: ' . $check_data['total'], null);
        }
        
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