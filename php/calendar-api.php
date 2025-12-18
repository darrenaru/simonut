<?php
// ================================================
// FILE: calendar-api.php
// API untuk operasi CRUD kegiatan kalender
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
// Set timezone to Asia/Makassar (WITA - Waktu Indonesia Tengah)
date_default_timezone_set('Asia/Makassar');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ================================================
// GET - Ambil data kegiatan
// ================================================
if ($method === 'GET') {
    
    // Ambil semua kegiatan
    if ($action === 'read') {
        $bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : null;
        $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : null;
        
        $sql = "SELECT k.*, u.nama_lengkap as nama_pembuat 
                FROM kegiatan k 
                JOIN users u ON k.id_pembuat = u.id";
        
        if ($bulan && $tahun) {
            $sql .= " WHERE MONTH(k.tanggal_mulai) = $bulan AND YEAR(k.tanggal_mulai) = $tahun";
        }
        
        $sql .= " ORDER BY k.tanggal_mulai ASC";
        
        $result = $conn->query($sql);
        
        $data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'judul' => $row['judul'],
                    'deskripsi' => $row['deskripsi'],
                    'tanggal_mulai' => $row['tanggal_mulai'],
                    'tanggal_selesai' => $row['tanggal_selesai'],
                    'lokasi' => $row['lokasi'],
                    'jenis' => $row['jenis'],
                    'status' => $row['status'],
                    'id_pembuat' => (int)$row['id_pembuat'],
                    'nama_pembuat' => $row['nama_pembuat'],
                    'tanggal_dibuat' => $row['tanggal_dibuat']
                ];
            }
        }
        
        send_response(true, 'Data berhasil diambil', $data);
    }
    
    // Ambil satu kegiatan berdasarkan ID
    elseif ($action === 'read_single' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $sql = "SELECT k.*, u.nama_lengkap as nama_pembuat, u.email as email_pembuat
                FROM kegiatan k 
                JOIN users u ON k.id_pembuat = u.id
                WHERE k.id = $id";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                'id' => (int)$row['id'],
                'judul' => $row['judul'],
                'deskripsi' => $row['deskripsi'],
                'tanggal_mulai' => $row['tanggal_mulai'],
                'tanggal_selesai' => $row['tanggal_selesai'],
                'lokasi' => $row['lokasi'],
                'jenis' => $row['jenis'],
                'status' => $row['status'],
                'id_pembuat' => (int)$row['id_pembuat'],
                'nama_pembuat' => $row['nama_pembuat'],
                'email_pembuat' => $row['email_pembuat'],
                'tanggal_dibuat' => $row['tanggal_dibuat']
            ];
            send_response(true, 'Data berhasil diambil', $data);
        } else {
            send_response(false, 'Data tidak ditemukan', null);
        }
    }
}

// ================================================
// POST - Tambah kegiatan baru (Admin only)
// ================================================
elseif ($method === 'POST') {
    
    if ($action === 'create') {
        // Get raw input
        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);
        
        // Debug log
        error_log("Raw input: " . $raw_input);
        error_log("Decoded input: " . print_r($input, true));
        
        if (!$input) {
            send_response(false, 'Invalid JSON data', null);
        }
        
        $judul = isset($input['judul']) ? clean_input($input['judul']) : '';
        $deskripsi = isset($input['deskripsi']) && !empty($input['deskripsi']) ? clean_input($input['deskripsi']) : null;
        $tanggal_mulai = isset($input['tanggal_mulai']) ? $input['tanggal_mulai'] : '';
        $tanggal_selesai = isset($input['tanggal_selesai']) ? $input['tanggal_selesai'] : '';
        $lokasi = isset($input['lokasi']) && !empty($input['lokasi']) ? clean_input($input['lokasi']) : null;
        $jenis = isset($input['jenis']) ? clean_input($input['jenis']) : '';
        $status = isset($input['status']) ? clean_input($input['status']) : 'terjadwal';
        $id_pembuat = isset($input['id_pembuat']) ? (int)$input['id_pembuat'] : 0;
        
        if (empty($judul) || empty($tanggal_mulai) || empty($tanggal_selesai) || empty($jenis) || $id_pembuat === 0) {
            send_response(false, 'Field wajib harus diisi (judul, tanggal, jenis, id_pembuat). Received: judul=' . $judul . ', jenis=' . $jenis . ', id_pembuat=' . $id_pembuat, null);
        }
        
        
        // Gunakan prepared statement untuk menghindari SQL injection dan masalah timezone
        $stmt = $conn->prepare("INSERT INTO kegiatan (judul, deskripsi, tanggal_mulai, tanggal_selesai, lokasi, jenis, status, id_pembuat) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssssssi", $judul, $deskripsi, $tanggal_mulai, $tanggal_selesai, $lokasi, $jenis, $status, $id_pembuat);

        error_log("Inserting: judul=$judul, tanggal_mulai=$tanggal_mulai, tanggal_selesai=$tanggal_selesai");

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            send_response(true, 'Kegiatan berhasil ditambahkan', ['id' => $new_id]);
        } else {
            error_log("SQL Error: " . $stmt->error);
            $stmt->close();
            send_response(false, 'Gagal menambahkan kegiatan: ' . $conn->error, null);
        }
    }
}

// ================================================
// PUT - Update kegiatan (Admin only)
// ================================================
elseif ($method === 'PUT') {
    
    if ($action === 'update') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)$input['id'];
        $judul = isset($input['judul']) ? clean_input($input['judul']) : '';
        $deskripsi = isset($input['deskripsi']) && !empty($input['deskripsi']) ? clean_input($input['deskripsi']) : null;
        $tanggal_mulai = isset($input['tanggal_mulai']) ? $input['tanggal_mulai'] : '';
        $tanggal_selesai = isset($input['tanggal_selesai']) ? $input['tanggal_selesai'] : '';
        $lokasi = isset($input['lokasi']) && !empty($input['lokasi']) ? clean_input($input['lokasi']) : null;
        $jenis = isset($input['jenis']) ? clean_input($input['jenis']) : '';
        $status = isset($input['status']) ? clean_input($input['status']) : 'terjadwal';
        
        if (empty($judul) || empty($tanggal_mulai) || empty($tanggal_selesai) || empty($jenis)) {
            send_response(false, 'Field wajib harus diisi', null);
        }
        
        // Gunakan prepared statement
        $stmt = $conn->prepare("UPDATE kegiatan SET 
                judul = ?, 
                deskripsi = ?, 
                tanggal_mulai = ?, 
                tanggal_selesai = ?, 
                lokasi = ?, 
                jenis = ?, 
                status = ? 
                WHERE id = ?");

        $stmt->bind_param("sssssssi", $judul, $deskripsi, $tanggal_mulai, $tanggal_selesai, $lokasi, $jenis, $status, $id);

        if ($stmt->execute()) {
            $stmt->close();
            send_response(true, 'Kegiatan berhasil diupdate', null);
        } else {
            error_log("SQL Error: " . $stmt->error);
            $stmt->close();
            send_response(false, 'Gagal mengupdate kegiatan: ' . $conn->error, null);
        }
    }
}

// ================================================
// DELETE - Hapus kegiatan (Admin only)
// ================================================
elseif ($method === 'DELETE') {
    
    if ($action === 'delete') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)$input['id'];
        
        $sql = "DELETE FROM kegiatan WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            send_response(true, 'Kegiatan berhasil dihapus', null);
        } else {
            send_response(false, 'Gagal menghapus kegiatan: ' . $conn->error, null);
        }
    }
}

else {
    send_response(false, 'Invalid request', null);
}

$conn->close();
?>