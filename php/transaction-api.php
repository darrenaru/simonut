<?php
// ================================================
// FILE: transaction-api.php
// API untuk operasi CRUD transaksi obat
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ================================================
// GET - Ambil data transaksi
// ================================================
if ($method === 'GET') {
    
    // Ambil semua transaksi berdasarkan tipe
    if ($action === 'read') {
        $tipe = isset($_GET['tipe']) ? $_GET['tipe'] : 'keluar';
        $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
        
        $sql = "SELECT t.*, o.nama_obat, o.dosis, o.kategori 
                FROM transaksi_obat t 
                INNER JOIN obat o ON t.id_obat = o.id 
                WHERE t.tipe_transaksi = '$tipe'";
        
        if (!empty($tanggal)) {
            $sql .= " AND t.tanggal_transaksi = '$tanggal'";
        }
        
        $sql .= " ORDER BY t.tanggal_transaksi DESC, t.tanggal_dibuat DESC";
        
        $result = $conn->query($sql);
        
        $data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'id_obat' => (int)$row['id_obat'],
                    'nama_obat' => $row['nama_obat'],
                    'dosis' => $row['dosis'],
                    'kategori' => $row['kategori'],
                    'tipe_transaksi' => $row['tipe_transaksi'],
                    'jumlah' => (int)$row['jumlah'],
                    'satuan' => $row['satuan'],
                    'tanggal_transaksi' => $row['tanggal_transaksi'],
                    'keterangan' => $row['keterangan'],
                    'nama_staff' => $row['nama_staff'],
                    'tanggal_dibuat' => $row['tanggal_dibuat']
                ];
            }
        }
        
        send_response(true, 'Data berhasil diambil', $data);
    }
    
    // Ambil satu transaksi berdasarkan ID
    elseif ($action === 'read_single' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $sql = "SELECT t.*, o.nama_obat, o.dosis, o.kategori 
                FROM transaksi_obat t 
                INNER JOIN obat o ON t.id_obat = o.id 
                WHERE t.id = $id";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $data = [
                'id' => (int)$row['id'],
                'id_obat' => (int)$row['id_obat'],
                'nama_obat' => $row['nama_obat'],
                'dosis' => $row['dosis'],
                'kategori' => $row['kategori'],
                'tipe_transaksi' => $row['tipe_transaksi'],
                'jumlah' => (int)$row['jumlah'],
                'satuan' => $row['satuan'],
                'tanggal_transaksi' => $row['tanggal_transaksi'],
                'keterangan' => $row['keterangan'],
                'nama_staff' => $row['nama_staff'],
                'tanggal_dibuat' => $row['tanggal_dibuat']
            ];
            send_response(true, 'Data berhasil diambil', $data);
        } else {
            send_response(false, 'Data tidak ditemukan', null);
        }
    }
    
    // Ambil summary untuk hari ini
    elseif ($action === 'summary') {
        $today = date('Y-m-d');
        
        // Total keluar hari ini
        $sqlKeluar = "SELECT COUNT(*) as total FROM transaksi_obat 
                      WHERE tipe_transaksi = 'keluar' 
                      AND tanggal_transaksi = '$today'";
        $resultKeluar = $conn->query($sqlKeluar);
        $keluar = $resultKeluar->fetch_assoc()['total'];
        
        // Total masuk hari ini
        $sqlMasuk = "SELECT COUNT(*) as total FROM transaksi_obat 
                     WHERE tipe_transaksi = 'masuk' 
                     AND tanggal_transaksi = '$today'";
        $resultMasuk = $conn->query($sqlMasuk);
        $masuk = $resultMasuk->fetch_assoc()['total'];
        
        $data = [
            'keluar' => (int)$keluar,
            'masuk' => (int)$masuk,
            'total' => (int)($keluar + $masuk)
        ];
        
        send_response(true, 'Summary berhasil diambil', $data);
    }
}

// ================================================
// POST - Tambah transaksi baru
// ================================================
elseif ($method === 'POST') {
    
    if ($action === 'create') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id_obat = (int)$input['id_obat'];
        $tipe_transaksi = clean_input($input['tipe_transaksi']);
        $jumlah = (int)$input['jumlah'];
        $satuan = clean_input($input['satuan']);
        $tanggal_transaksi = clean_input($input['tanggal_transaksi']);
        $nama_staff = clean_input($input['nama_staff']);
        $keterangan = clean_input($input['keterangan']);
        
        if (empty($id_obat) || empty($tipe_transaksi) || empty($jumlah) || empty($satuan) || empty($tanggal_transaksi) || empty($nama_staff)) {
            send_response(false, 'Field wajib tidak boleh kosong', null);
        }
        
        // Validasi tipe transaksi
        if (!in_array($tipe_transaksi, ['keluar', 'masuk'])) {
            send_response(false, 'Tipe transaksi tidak valid', null);
        }
        
        $sql = "INSERT INTO transaksi_obat (id_obat, tipe_transaksi, jumlah, satuan, tanggal_transaksi, nama_staff, keterangan) 
                VALUES ($id_obat, '$tipe_transaksi', $jumlah, '$satuan', '$tanggal_transaksi', '$nama_staff', '$keterangan')";
        
        if ($conn->query($sql) === TRUE) {
            $new_id = $conn->insert_id;
            
            // Update stok obat
            if ($tipe_transaksi === 'masuk') {
                $updateStok = "UPDATE obat SET stok = stok + $jumlah WHERE id = $id_obat";
            } else {
                $updateStok = "UPDATE obat SET stok = stok - $jumlah WHERE id = $id_obat";
            }
            $conn->query($updateStok);
            
            send_response(true, 'Data berhasil ditambahkan', ['id' => $new_id]);
        } else {
            send_response(false, 'Gagal menambahkan data: ' . $conn->error, null);
        }
    }
}

// ================================================
// DELETE - Hapus transaksi
// ================================================
elseif ($method === 'DELETE') {
    
    if ($action === 'delete') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)$input['id'];
        
        // Ambil data transaksi sebelum dihapus untuk update stok
        $sqlGet = "SELECT id_obat, tipe_transaksi, jumlah FROM transaksi_obat WHERE id = $id";
        $resultGet = $conn->query($sqlGet);
        
        if ($resultGet->num_rows > 0) {
            $row = $resultGet->fetch_assoc();
            $id_obat = $row['id_obat'];
            $tipe = $row['tipe_transaksi'];
            $jumlah = $row['jumlah'];
            
            // Hapus transaksi
            $sql = "DELETE FROM transaksi_obat WHERE id = $id";
            
            if ($conn->query($sql) === TRUE) {
                // Kembalikan stok
                if ($tipe === 'masuk') {
                    $updateStok = "UPDATE obat SET stok = stok - $jumlah WHERE id = $id_obat";
                } else {
                    $updateStok = "UPDATE obat SET stok = stok + $jumlah WHERE id = $id_obat";
                }
                $conn->query($updateStok);
                
                send_response(true, 'Data berhasil dihapus', null);
            } else {
                send_response(false, 'Gagal menghapus data: ' . $conn->error, null);
            }
        } else {
            send_response(false, 'Data tidak ditemukan', null);
        }
    }
}

else {
    send_response(false, 'Invalid request', null);
}

$conn->close();
?>