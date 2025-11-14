<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Koneksi database
$host = 'localhost';
$dbname = 'simonut';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

// ================================================
// GET STAFF LIST - Ambil daftar staff
// ================================================
if ($action === 'get_staff' && $method === 'GET') {
    try {
        $sql = "SELECT id, nama_lengkap, email FROM users WHERE role = 'staff' AND status = 'aktif' ORDER BY nama_lengkap ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $result]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// CREATE BATCH - Tambah multiple transaksi sekaligus
// ================================================
else if ($action === 'create_batch' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $conn->beginTransaction();
        
        $items = $data['items'];
        $tipe_transaksi = $data['tipe_transaksi'];
        $tanggal_transaksi = $data['tanggal_transaksi'];
        $id_staff = $data['id_staff'];
        $keterangan = isset($data['keterangan']) ? $data['keterangan'] : null;
        $tujuan = isset($data['tujuan']) ? $data['tujuan'] : null;
        
        $success_count = 0;
        
        foreach ($items as $item) {
            // Insert transaksi
            $sql = "INSERT INTO transaksi_obat (id_obat, id_staff, tipe_transaksi, jumlah, satuan, tujuan, tanggal_transaksi, keterangan) 
                    VALUES (:id_obat, :id_staff, :tipe_transaksi, :jumlah, :satuan, :tujuan, :tanggal_transaksi, :keterangan)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':id_obat' => $item['idObat'],
                ':id_staff' => $id_staff,
                ':tipe_transaksi' => $tipe_transaksi,
                ':jumlah' => $item['jumlah'],
                ':satuan' => $item['satuan'],
                ':tujuan' => $tujuan,
                ':tanggal_transaksi' => $tanggal_transaksi,
                ':keterangan' => $keterangan
            ]);
            
            // Update stok obat
            if ($tipe_transaksi === 'masuk') {
                $sql = "UPDATE obat SET stok = stok + :jumlah WHERE id = :id_obat";
            } else {
                $sql = "UPDATE obat SET stok = stok - :jumlah WHERE id = :id_obat";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':jumlah' => $item['jumlah'],
                ':id_obat' => $item['idObat']
            ]);
            
            $success_count++;
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "$success_count transaksi berhasil disimpan",
            'count' => $success_count
        ]);
    } catch(PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// CREATE - Tambah transaksi baru (single item)
// ================================================
else if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $conn->beginTransaction();
        
        // Insert transaksi
        $sql = "INSERT INTO transaksi_obat (id_obat, id_staff, tipe_transaksi, jumlah, satuan, tujuan, tanggal_transaksi, keterangan) 
                VALUES (:id_obat, :id_staff, :tipe_transaksi, :jumlah, :satuan, :tujuan, :tanggal_transaksi, :keterangan)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id_obat' => $data['id_obat'],
            ':id_staff' => $data['id_staff'],
            ':tipe_transaksi' => $data['tipe_transaksi'],
            ':jumlah' => $data['jumlah'],
            ':satuan' => $data['satuan'],
            ':tujuan' => isset($data['tujuan']) ? $data['tujuan'] : null,
            ':tanggal_transaksi' => $data['tanggal_transaksi'],
            ':keterangan' => isset($data['keterangan']) ? $data['keterangan'] : null
        ]);
        
        // Update stok obat
        if ($data['tipe_transaksi'] === 'masuk') {
            $sql = "UPDATE obat SET stok = stok + :jumlah WHERE id = :id_obat";
        } else {
            $sql = "UPDATE obat SET stok = stok - :jumlah WHERE id = :id_obat";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':jumlah' => $data['jumlah'],
            ':id_obat' => $data['id_obat']
        ]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan']);
    } catch(PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// READ - Ambil semua transaksi
// ================================================
else if ($action === 'read' && $method === 'GET') {
    $tipe = isset($_GET['tipe']) ? $_GET['tipe'] : 'keluar';
    $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : null;
    
    try {
        $sql = "SELECT t.*, o.nama_obat, o.dosis, o.kategori, o.stok, u.nama_lengkap as nama_staff, u.email as email_staff
                FROM transaksi_obat t 
                JOIN obat o ON t.id_obat = o.id 
                JOIN users u ON t.id_staff = u.id
                WHERE t.tipe_transaksi = :tipe";
        
        if ($tanggal) {
            $sql .= " AND t.tanggal_transaksi = :tanggal";
        }
        
        $sql .= " ORDER BY t.tanggal_transaksi DESC, t.tanggal_dibuat DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':tipe', $tipe);
        
        if ($tanggal) {
            $stmt->bindParam(':tanggal', $tanggal);
        }
        
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $result]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// READ SINGLE - Ambil detail transaksi
// ================================================
else if ($action === 'read_single' && $method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    
    try {
        $sql = "SELECT t.*, o.nama_obat, o.dosis, o.kategori, o.stok, u.nama_lengkap as nama_staff, u.email as email_staff
                FROM transaksi_obat t 
                JOIN obat o ON t.id_obat = o.id 
                JOIN users u ON t.id_staff = u.id
                WHERE t.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// DELETE - Hapus transaksi
// ================================================
else if ($action === 'delete' && $method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    
    try {
        $conn->beginTransaction();
        
        // Ambil data transaksi untuk rollback stok
        $sql = "SELECT id_obat, tipe_transaksi, jumlah FROM transaksi_obat WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaksi) {
            // Rollback stok
            if ($transaksi['tipe_transaksi'] === 'masuk') {
                $sql = "UPDATE obat SET stok = stok - :jumlah WHERE id = :id_obat";
            } else {
                $sql = "UPDATE obat SET stok = stok + :jumlah WHERE id = :id_obat";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':jumlah' => $transaksi['jumlah'],
                ':id_obat' => $transaksi['id_obat']
            ]);
            
            // Hapus transaksi
            $sql = "DELETE FROM transaksi_obat WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $conn->commit();
            
            echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
    } catch(PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// SUMMARY - Ringkasan transaksi hari ini
// ================================================
else if ($action === 'summary' && $method === 'GET') {
    try {
        $today = date('Y-m-d');
        
        // Total keluar
        $sql = "SELECT COUNT(*) as total FROM transaksi_obat 
                WHERE tipe_transaksi = 'keluar' AND tanggal_transaksi = :today";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $keluar = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total masuk
        $sql = "SELECT COUNT(*) as total FROM transaksi_obat 
                WHERE tipe_transaksi = 'masuk' AND tanggal_transaksi = :today";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $masuk = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total transaksi
        $total = $keluar + $masuk;
        
        echo json_encode([
            'success' => true, 
            'data' => [
                'keluar' => $keluar,
                'masuk' => $masuk,
                'total' => $total
            ]
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action or method']);
}

$conn = null;
?>