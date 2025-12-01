<?php
// ================================================
// upload-photo.php - Upload Profile Photo
// ================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, 'Invalid request method', null);
}

if (!isset($_FILES['photo']) || !isset($_POST['user_id'])) {
    send_response(false, 'Photo dan user_id wajib diisi', null);
}

$user_id = (int)$_POST['user_id'];
$file = $_FILES['photo'];

// Validasi file
if ($file['error'] !== UPLOAD_ERR_OK) {
    send_response(false, 'Error uploading file', null);
}

// Validasi tipe file
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = mime_content_type($file['tmp_name']);

if (!in_array($file_type, $allowed_types)) {
    send_response(false, 'Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF', null);
}

// Validasi ukuran file (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    send_response(false, 'Ukuran file maksimal 2MB', null);
}

// Buat folder uploads jika belum ada
$upload_dir = __DIR__ . '/../uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate nama file unik
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// Hapus foto lama jika ada
$sql = "SELECT foto_profil FROM users WHERE id = $user_id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if ($user['foto_profil']) {
        $old_file = __DIR__ . '/../' . $user['foto_profil'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }
}

// Upload file baru
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Update database
    $foto_profil_path = 'uploads/profiles/' . $new_filename;
    $sql = "UPDATE users SET foto_profil = '$foto_profil_path' WHERE id = $user_id";
    
    if ($conn->query($sql) === TRUE) {
        send_response(true, 'Foto profil berhasil diupload', [
            'foto_profil' => $foto_profil_path
        ]);
    } else {
        // Hapus file jika update database gagal
        unlink($upload_path);
        send_response(false, 'Gagal menyimpan ke database: ' . $conn->error, null);
    }
} else {
    send_response(false, 'Gagal mengupload file', null);
}

$conn->close();
?>