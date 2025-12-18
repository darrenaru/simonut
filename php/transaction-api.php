<?php
// ================================================
// transaction-api.php - WITH MULTI-BATCH SUPPORT FOR OUTGOING DRUGS
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$dbname = 'motara';
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
function getStokObat($conn, $id_obat, $exclude_id = null) {
    try {
        if ($exclude_id) {
            $sql = "SELECT 
                    COALESCE(
                        SUM(CASE WHEN tipe_transaksi = 'masuk' THEN jumlah ELSE 0 END) - 
                        SUM(CASE WHEN tipe_transaksi = 'keluar' THEN jumlah ELSE 0 END), 
                        0
                    ) as stok
                    FROM transaksi_obat 
                    WHERE id_obat = :id_obat AND id != :exclude_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id_obat' => $id_obat, ':exclude_id' => $exclude_id]);
        } else {
            $sql = "SELECT get_stok_obat(:id_obat) as stok";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id_obat' => $id_obat]);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['stok'];
    } catch(PDOException $e) {
        return 0;
    }
}

// ================================================
// FUNCTION: Get stok per batch
// ================================================
function getStokBatch($conn, $id_obat, $nomor_batch, $exclude_id = null) {
    try {
        $sql = "SELECT 
                COALESCE(
                    SUM(CASE WHEN tipe_transaksi = 'masuk' THEN jumlah ELSE 0 END) - 
                    SUM(CASE WHEN tipe_transaksi = 'keluar' THEN jumlah ELSE 0 END), 
                    0
                ) as stok
                FROM transaksi_obat 
                WHERE id_obat = :id_obat 
                  AND nomor_batch = :nomor_batch";
        
        if ($exclude_id) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $conn->prepare($sql);
        $params = [':id_obat' => $id_obat, ':nomor_batch' => $nomor_batch];
        
        if ($exclude_id) {
            $params[':exclude_id'] = $exclude_id;
        }
        
        $stmt->execute($params);
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
// CREATE - Tambah transaksi baru dengan MULTIPLE ITEMS & MULTI-BATCH
// ================================================
else if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $conn->beginTransaction();
        
        $transactionData = $data['transaction'];
        $items = $data['items'];
        
        if (empty($items)) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Tidak ada item obat yang ditambahkan']);
            exit();
        }
        
        $insertedCount = 0;
        
        foreach ($items as $item) {
            // VALIDASI STOK UNTUK SETIAP ITEM
            if ($transactionData['tipe_transaksi'] === 'keluar') {
                // Untuk obat keluar, validasi stok per batch
                if (isset($item['nomor_batch'])) {
                    $stok_batch = getStokBatch($conn, $item['id_obat'], $item['nomor_batch']);
                    
                    $sql = "SELECT nama_obat FROM obat WHERE id = :id_obat";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':id_obat' => $item['id_obat']]);
                    $obat = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$obat) {
                        $conn->rollBack();
                        echo json_encode(['success' => false, 'message' => "Obat tidak ditemukan: ID {$item['id_obat']}"]);
                        exit();
                    }
                    
                    if ($stok_batch < $item['jumlah']) {
                        $conn->rollBack();
                        echo json_encode([
                            'success' => false, 
                            'message' => "Stok batch tidak mencukupi! {$obat['nama_obat']} - Batch {$item['nomor_batch']}: Stok tersedia: {$stok_batch}, diminta: {$item['jumlah']}"
                        ]);
                        exit();
                    }
                } else {
                    // Fallback: validasi stok total jika tidak ada batch
                    $stok_tersedia = getStokObat($conn, $item['id_obat']);
                    
                    $sql = "SELECT nama_obat FROM obat WHERE id = :id_obat";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':id_obat' => $item['id_obat']]);
                    $obat = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$obat) {
                        $conn->rollBack();
                        echo json_encode(['success' => false, 'message' => "Obat tidak ditemukan: ID {$item['id_obat']}"]);
                        exit();
                    }
                    
                    if ($stok_tersedia < $item['jumlah']) {
                        $conn->rollBack();
                        echo json_encode([
                            'success' => false, 
                            'message' => "Stok tidak mencukupi! {$obat['nama_obat']} - Stok tersedia: {$stok_tersedia}, diminta: {$item['jumlah']}"
                        ]);
                        exit();
                    }
                }
            }
            
            // Handle tanggal kedaluwarsa, nomor batch, dan nomor faktur
            $tanggal_kedaluwarsa = isset($item['tanggal_kedaluwarsa']) && !empty($item['tanggal_kedaluwarsa']) 
                ? $item['tanggal_kedaluwarsa'] 
                : null;
            
            $nomor_batch = isset($item['nomor_batch']) && !empty($item['nomor_batch']) 
                ? $item['nomor_batch'] 
                : null;
            
            $nomor_faktur = isset($item['nomor_faktur']) && !empty($item['nomor_faktur']) 
                ? $item['nomor_faktur'] 
                : null;
            
            $sql = "INSERT INTO transaksi_obat 
                    (id_obat, id_staff, tipe_transaksi, jumlah, satuan, tujuan, tanggal_transaksi, 
                     tanggal_kedaluwarsa, nomor_batch, nomor_faktur, keterangan) 
                    VALUES 
                    (:id_obat, :id_staff, :tipe_transaksi, :jumlah, :satuan, :tujuan, :tanggal_transaksi, 
                     :tanggal_kedaluwarsa, :nomor_batch, :nomor_faktur, :keterangan)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':id_obat' => $item['id_obat'],
                ':id_staff' => $transactionData['id_staff'],
                ':tipe_transaksi' => $transactionData['tipe_transaksi'],
                ':jumlah' => $item['jumlah'],
                ':satuan' => $item['satuan'],
                ':tujuan' => isset($transactionData['tujuan']) ? $transactionData['tujuan'] : null,
                ':tanggal_transaksi' => $transactionData['tanggal_transaksi'],
                ':tanggal_kedaluwarsa' => $tanggal_kedaluwarsa,
                ':nomor_batch' => $nomor_batch,
                ':nomor_faktur' => $nomor_faktur,
                ':keterangan' => isset($item['keterangan']) ? $item['keterangan'] : null
            ]);
            
            $insertedCount++;
        }
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan', 'count' => $insertedCount]);
    } catch(PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// UPDATE - Edit transaksi (single item)
// ================================================
else if ($action === 'update' && $method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $conn->beginTransaction();
        
        $id = $data['id'];
        
        // Get data transaksi lama
        $sql = "SELECT * FROM transaksi_obat WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$old_data) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Data transaksi tidak ditemukan']);
            exit();
        }
        
        // VALIDASI STOK UNTUK TRANSAKSI KELUAR
        if ($data['tipe_transaksi'] === 'keluar') {
            $stok_tersedia = getStokObat($conn, $data['id_obat'], $id);
            
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
        
        // Handle tanggal kedaluwarsa, nomor batch, dan nomor faktur
        $tanggal_kedaluwarsa = null;
        $nomor_batch = null;
        $nomor_faktur = null;
        
        if ($data['tipe_transaksi'] === 'masuk') {
            $tanggal_kedaluwarsa = isset($data['tanggal_kedaluwarsa']) && !empty($data['tanggal_kedaluwarsa']) 
                ? $data['tanggal_kedaluwarsa'] 
                : null;
            
            $nomor_batch = isset($data['nomor_batch']) && !empty($data['nomor_batch']) 
                ? $data['nomor_batch'] 
                : null;
            
            $nomor_faktur = isset($data['nomor_faktur']) && !empty($data['nomor_faktur']) 
                ? $data['nomor_faktur'] 
                : null;
        } else if ($data['tipe_transaksi'] === 'keluar' && isset($data['nomor_batch'])) {
            $nomor_batch = $data['nomor_batch'];
            $tanggal_kedaluwarsa = isset($data['tanggal_kedaluwarsa']) ? $data['tanggal_kedaluwarsa'] : null;
        }
        
        $sql = "UPDATE transaksi_obat SET 
                id_obat = :id_obat,
                id_staff = :id_staff,
                tipe_transaksi = :tipe_transaksi,
                jumlah = :jumlah,
                satuan = :satuan,
                tujuan = :tujuan,
                tanggal_transaksi = :tanggal_transaksi,
                tanggal_kedaluwarsa = :tanggal_kedaluwarsa,
                nomor_batch = :nomor_batch,
                nomor_faktur = :nomor_faktur,
                keterangan = :keterangan
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':id_obat' => $data['id_obat'],
            ':id_staff' => $data['id_staff'],
            ':tipe_transaksi' => $data['tipe_transaksi'],
            ':jumlah' => $data['jumlah'],
            ':satuan' => $data['satuan'],
            ':tujuan' => isset($data['tujuan']) ? $data['tujuan'] : null,
            ':tanggal_transaksi' => $data['tanggal_transaksi'],
            ':tanggal_kedaluwarsa' => $tanggal_kedaluwarsa,
            ':nomor_batch' => $nomor_batch,
            ':nomor_faktur' => $nomor_faktur,
            ':keterangan' => isset($data['keterangan']) ? $data['keterangan'] : null
        ]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Data berhasil diupdate']);
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
        
        $sql = "SELECT id_obat, tipe_transaksi, jumlah FROM transaksi_obat WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaksi) {
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
        
        $sql = "SELECT COUNT(*) as total FROM transaksi_obat 
                WHERE tipe_transaksi = 'keluar' AND tanggal_transaksi = :today";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $keluar = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
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

// ================================================
// CHECK STOCK
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
// GET BATCH INFO - Get all batches for a specific medicine (WITH STOCK)
// ================================================
else if ($action === 'get_batch_info' && $method === 'GET') {
    $id_obat = isset($_GET['id_obat']) ? $_GET['id_obat'] : 0;
    
    try {
        $sql = "SELECT 
                    t.nomor_batch,
                    t.tanggal_kedaluwarsa,
                    t.satuan,
                    SUM(CASE WHEN t.tipe_transaksi = 'masuk' THEN t.jumlah ELSE 0 END) as total_masuk,
                    SUM(CASE WHEN t.tipe_transaksi = 'keluar' THEN t.jumlah ELSE 0 END) as total_keluar,
                    (SUM(CASE WHEN t.tipe_transaksi = 'masuk' THEN t.jumlah ELSE 0 END) - 
                     SUM(CASE WHEN t.tipe_transaksi = 'keluar' THEN t.jumlah ELSE 0 END)) as sisa_stok,
                    DATEDIFF(t.tanggal_kedaluwarsa, CURDATE()) as hari_tersisa
                FROM transaksi_obat t
                WHERE t.id_obat = :id_obat 
                  AND t.nomor_batch IS NOT NULL
                  AND t.tanggal_kedaluwarsa IS NOT NULL
                GROUP BY t.nomor_batch, t.tanggal_kedaluwarsa, t.satuan
                HAVING sisa_stok > 0
                ORDER BY t.tanggal_kedaluwarsa ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_obat' => $id_obat]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $result]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ================================================
// GET BATCH STOCK - Get stock for specific batch
// ================================================
else if ($action === 'get_batch_stock' && $method === 'GET') {
    $id_obat = isset($_GET['id_obat']) ? $_GET['id_obat'] : 0;
    $nomor_batch = isset($_GET['nomor_batch']) ? $_GET['nomor_batch'] : '';
    
    try {
        $sql = "SELECT 
                    COALESCE(
                        SUM(CASE WHEN tipe_transaksi = 'masuk' THEN jumlah ELSE 0 END) - 
                        SUM(CASE WHEN tipe_transaksi = 'keluar' THEN jumlah ELSE 0 END), 
                        0
                    ) as stok
                FROM transaksi_obat
                WHERE id_obat = :id_obat AND nomor_batch = :nomor_batch";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_obat' => $id_obat, ':nomor_batch' => $nomor_batch]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $result]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action or method']);
}

$conn = null;
?>