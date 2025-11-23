<?php
// ================================================
// transaction-api.php (New Structure)
// Stok dihitung dari transaksi, bukan dari tabel obat
// ================================================

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
// FUNCTION: Get stok obat dari transaksi
// ================================================
function getStokObat($conn, $id_obat) {
    try {
        $sql = "SELECT get_stok_obat(:id_obat) as stok";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_obat' => $id_obat]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['stok'];
    } catch(PDOException $e) {
        return 0;
    }
}

// ================================================
// GET STAFF LIST
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
// CREATE BATCH - Tambah multiple transaksi
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
        
        // VALIDASI STOK UNTUK TRANSAKSI KELUAR
        if ($tipe_transaksi === 'keluar') {
            $insufficient_stock = [];
            
            foreach ($items as $item) {
                // Cek stok saat ini dari transaksi
                $stok_tersedia = getStokObat($conn, $item['idObat']);
                
                // Get nama obat
                $sql = "SELECT nama_obat FROM obat WHERE id = :id_obat";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':id_obat' => $item['idObat']]);
                $obat = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$obat) {
                    $conn->rollBack();
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Obat dengan ID ' . $item['idObat'] . ' tidak ditemukan'
                    ]);
                    exit();
                }
                
                // Cek apakah stok mencukupi
                if ($stok_tersedia < $item['jumlah']) {
                    $insufficient_stock[] = [
                        'nama' => $obat['nama_obat'],
                        'stok_tersedia' => $stok_tersedia,
                        'jumlah_diminta' => $item['jumlah']
                    ];
                }
            }
            
            // Jika ada stok yang tidak mencukupi
            if (!empty($insufficient_stock)) {
                $conn->rollBack();
                
                $error_message = "Stok tidak mencukupi untuk obat berikut:\n";
                foreach ($insufficient_stock as $stock) {
                    $error_message .= "- {$stock['nama']}: Stok tersedia {$stock['stok_tersedia']}, diminta {$stock['jumlah_diminta']}\n";
                }
                
                echo json_encode([
                    'success' => false, 
                    'message' => $error_message,
                    'insufficient_stock' => $insufficient_stock
                ]);
                exit();
            }
        }
        
        $success_count = 0;
        
        // Insert semua transaksi
        foreach ($items as $item) {
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
// CREATE - Tambah transaksi baru (single)
// ================================================
else if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $conn->beginTransaction();
        
        // VALIDASI STOK UNTUK TRANSAKSI KELUAR
        if ($data['tipe_transaksi'] === 'keluar') {
            $stok_tersedia = getStokObat($conn, $data['id_obat']);
            
            $sql = "SELECT nama_obat FROM obat WHERE id = :id_obat";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id_obat' => $data['id_obat']]);
            $obat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$obat) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'Obat tidak ditemukan']);
                exit();
            }
            
            if ($stok_tersedia < $data['jumlah']) {
                $conn->rollBack();
                echo json_encode([
                    'success' => false, 
                    'message' => "Stok tidak mencukupi! {$obat['nama_obat']} - Stok tersedia: {$stok_tersedia}, diminta: {$data['jumlah']}"
                ]);
                exit();
            }
        }
        
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
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan']);
    } catch(PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// CHECK STOCK - Cek stok obat
// ================================================
else if ($action === 'check_stock' && $method === 'GET') {
    $id_obat = isset($_GET['id_obat']) ? $_GET['id_obat'] : 0;
    
    try {
        $sql = "SELECT id, nama_obat FROM obat WHERE id = :id_obat";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_obat' => $id_obat]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $stok = getStokObat($conn, $id_obat);
            
            echo json_encode([
                'success' => true, 
                'data' => [
                    'id' => $result['id'],
                    'nama_obat' => $result['nama_obat'],
                    'stok' => $stok
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Obat tidak ditemukan']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// READ - Ambil semua transaksi
// ================================================
else if ($action === 'read' && $method === 'GET') {
    $tipe = isset($_GET['tipe']) ? $_GET['tipe'] : 'keluar';
    $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : null;
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    
    try {
        $sql = "SELECT t.*, o.nama_obat, o.dosis, o.kategori, 
                       u.nama_lengkap as nama_staff, u.email as email_staff
                FROM transaksi_obat t 
                JOIN obat o ON t.id_obat = o.id 
                JOIN users u ON t.id_staff = u.id
                WHERE t.tipe_transaksi = :tipe";
        
        if ($tanggal) {
            $sql .= " AND t.tanggal_transaksi = :tanggal";
        }
        
        if ($start_date && $end_date) {
            $sql .= " AND t.tanggal_transaksi BETWEEN :start_date AND :end_date";
        }
        
        $sql .= " ORDER BY t.tanggal_transaksi DESC, t.tanggal_dibuat DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':tipe', $tipe);
        
        if ($tanggal) {
            $stmt->bindParam(':tanggal', $tanggal);
        }
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $result]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// READ SINGLE - Detail transaksi
// ================================================
else if ($action === 'read_single' && $method === 'GET') {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    
    try {
        $sql = "SELECT t.*, o.nama_obat, o.dosis, o.kategori, 
                       u.nama_lengkap as nama_staff, u.email as email_staff
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
        
        // Cek apakah transaksi ini akan membuat stok negatif setelah dihapus
        $sql = "SELECT id_obat, tipe_transaksi, jumlah FROM transaksi_obat WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaksi) {
            // Jika hapus transaksi masuk, cek apakah stok cukup untuk rollback
            if ($transaksi['tipe_transaksi'] === 'masuk') {
                $stok_tersedia = getStokObat($conn, $transaksi['id_obat']);
                
                if ($stok_tersedia < $transaksi['jumlah']) {
                    $conn->rollBack();
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Tidak dapat menghapus transaksi masuk ini karena akan membuat stok negatif. Stok saat ini: ' . $stok_tersedia
                    ]);
                    exit();
                }
            }
            
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
// SUMMARY - Ringkasan transaksi
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