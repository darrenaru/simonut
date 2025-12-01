-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 07:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `motara`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_insert_transaksi` (IN `p_id_obat` INT, IN `p_id_staff` INT, IN `p_tipe_transaksi` VARCHAR(10), IN `p_jumlah` INT, IN `p_satuan` VARCHAR(50), IN `p_tujuan` VARCHAR(255), IN `p_tanggal_transaksi` DATE, IN `p_keterangan` TEXT, OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(500))   BEGIN
    DECLARE v_stok_tersedia INT DEFAULT 0;
    DECLARE v_nama_obat VARCHAR(255);
    
    -- Get current stock
    SELECT get_stok_obat(p_id_obat) INTO v_stok_tersedia;
    
    -- Get medicine name
    SELECT nama_obat INTO v_nama_obat FROM obat WHERE id = p_id_obat;
    
    -- Validate stock for 'keluar' transaction
    IF p_tipe_transaksi = 'keluar' AND v_stok_tersedia < p_jumlah THEN
        SET p_success = FALSE;
        SET p_message = CONCAT('Stok tidak mencukupi! ', v_nama_obat, ' - Stok tersedia: ', v_stok_tersedia, ', diminta: ', p_jumlah);
    ELSE
        -- Insert transaction
        INSERT INTO transaksi_obat (
            id_obat, id_staff, tipe_transaksi, jumlah, satuan, 
            tujuan, tanggal_transaksi, keterangan
        ) VALUES (
            p_id_obat, p_id_staff, p_tipe_transaksi, p_jumlah, p_satuan,
            p_tujuan, p_tanggal_transaksi, p_keterangan
        );
        
        SET p_success = TRUE;
        SET p_message = 'Transaksi berhasil disimpan';
    END IF;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_stok_obat` (`obat_id` INT) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE stok INT DEFAULT 0;
    
    SELECT 
        COALESCE(
            SUM(CASE WHEN tipe_transaksi = 'masuk' THEN jumlah ELSE 0 END) - 
            SUM(CASE WHEN tipe_transaksi = 'keluar' THEN jumlah ELSE 0 END), 
            0
        ) INTO stok
    FROM transaksi_obat
    WHERE id_obat = obat_id;
    
    RETURN stok;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `jenis` enum('rapat','pelatihan','distribusi','inspeksi','lainnya') NOT NULL DEFAULT 'lainnya',
  `status` enum('terjadwal','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'terjadwal',
  `id_pembuat` int(11) NOT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_diupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kegiatan`
--

INSERT INTO `kegiatan` (`id`, `judul`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `lokasi`, `jenis`, `status`, `id_pembuat`, `tanggal_dibuat`, `tanggal_diupdate`) VALUES
(2, 'Pelatihan Sistem SIMONUT', 'Pelatihan penggunaan sistem informasi untuk staff baru', '2025-11-22 13:00:00', '2025-11-22 16:00:00', 'Gudang Obat Utama', 'pelatihan', 'terjadwal', 1, '2025-11-16 16:21:42', '2025-11-27 01:18:21'),
(3, 'Distribusi Obat ke Puskesmas', 'Distribusi obat rutin ke puskesmas wilayah utara', '2025-11-25 08:00:00', '2025-11-25 15:00:00', 'Puskesmas Kauditan', 'distribusi', 'terjadwal', 1, '2025-11-16 16:21:42', '2025-11-16 16:21:42'),
(4, 'Inspeksi Gudang Obat', 'Inspeksi rutin kondisi penyimpanan dan stok obat', '2025-11-27 10:00:00', '2025-11-27 12:00:00', 'Gudang Obat Utama', 'inspeksi', 'terjadwal', 1, '2025-11-16 16:21:42', '2025-11-16 16:21:42'),
(6, 'DDDDD', NULL, '2025-11-27 10:42:00', '2025-11-27 10:42:00', 'Sentra Medika Hospital Minahasa Utara', 'distribusi', 'terjadwal', 1, '2025-11-27 02:42:58', '2025-11-27 02:42:58');

-- --------------------------------------------------------

--
-- Table structure for table `obat`
--

CREATE TABLE `obat` (
  `id` int(11) NOT NULL,
  `nama_obat` varchar(255) NOT NULL,
  `dosis` varchar(100) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_diupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `obat`
--

INSERT INTO `obat` (`id`, `nama_obat`, `dosis`, `kategori`, `tanggal_dibuat`, `tanggal_diupdate`) VALUES
(51, 'Paracetamol 500 mg', '500 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(52, 'Amoxicillin 500 mg', '500 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(53, 'Ibuprofen 400 mg', '400 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(54, 'Cetirizine 10 mg', '10 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(55, 'ORS Reguler', '1 sachet', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(56, 'Metformin 500 mg', '500 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(57, 'Ranitidine 150 mg', '150 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(58, 'Amlodipine 10 mg', '10 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(59, 'Salbutamol Tablet', '4 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(60, 'Captopril 25 mg', '25 mg', 'Obat DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(61, 'Perbekkes Kit A', '1 paket', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(62, 'Perbekkes Kit B', '1 paket', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(63, 'Perbekkes Kit C', '1 paket', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(64, 'Alat Tensi Digital', '1 unit', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(65, 'Stetoskop Standard', '1 unit', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(66, 'Thermometer Infrared', '1 unit', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(67, 'Gunting Lurus', '1 unit', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(68, 'Pinset Medis', '1 unit', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(69, 'Kantong Mayat', '1 lembar', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(70, 'Masker Bedah Box', '1 box', 'Perbekkes DAK', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(71, 'Vitamin C Tablet', '250 mg', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(72, 'Zinc Tablet', '20 mg', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(73, 'Antasida Doen', '500 mg', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(74, 'Albendazole Tablet', '400 mg', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(75, 'Kloramfenikol Salep Mata', '1%', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(76, 'Kapsul Omega 3', '1000 mg', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(77, 'Sanitizer 100 ml', '100 ml', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(78, 'Masker N95', '1 unit', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(79, 'Suplemen Multivitamin', '1 tablet', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(80, 'Obat Batuk Sirup', '60 ml', 'Droping', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(81, 'Perbekkes Kit DARURAT', '1 paket', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(82, 'Perbekkes Kit Trauma', '1 paket', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(83, 'Perbekkes Kit Ambulance', '1 paket', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(84, 'Perbekkes Kit Persalinan', '1 paket', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(85, 'Alat Nebulizer', '1 unit', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(86, 'Tandu Lipat', '1 unit', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(87, 'Kotak P3K Besar', '1 unit', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(88, 'SpO2 Finger', '1 unit', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(89, 'Face Shield', '1 unit', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(90, 'Hazmat Suit', '1 unit', 'Perbekkes DID', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(91, 'Vaksin Covid Sinovac', '1 dosis', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(92, 'Vaksin Covid AstraZeneca', '1 dosis', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(93, 'Vaksin Covid Pfizer', '1 dosis', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(94, 'Vaksin Covid Moderna', '1 dosis', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(95, 'Vaksin Covid Janssen', '1 dosis', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(96, 'Vaksin Covid Anak', '1 dosis', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(97, 'Diluent Vaksin', '1 vial', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(98, 'Syringe Auto-disable 0.5 mL', '1 unit', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(99, 'Cotton Ball Steril', '1 pack', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19'),
(100, 'Alcohol Swab', '1 sachet', 'Vaksin Covid', '2025-12-01 06:00:19', '2025-12-01 06:00:19');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_obat`
--

CREATE TABLE `transaksi_obat` (
  `id` int(11) NOT NULL,
  `id_obat` int(11) NOT NULL,
  `id_staff` int(11) NOT NULL,
  `tipe_transaksi` enum('masuk','keluar') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `satuan` varchar(50) NOT NULL DEFAULT 'unit',
  `tujuan` varchar(255) DEFAULT NULL,
  `tanggal_transaksi` date NOT NULL,
  `tanggal_kedaluwarsa` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_obat`
--

INSERT INTO `transaksi_obat` (`id`, `id_obat`, `id_staff`, `tipe_transaksi`, `jumlah`, `satuan`, `tujuan`, `tanggal_transaksi`, `tanggal_kedaluwarsa`, `keterangan`, `tanggal_dibuat`) VALUES
(31, 64, 3, 'masuk', 1, 'unit', NULL, '2025-12-01', '2025-12-31', '', '2025-12-01 06:01:51');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_obat_backup`
--

CREATE TABLE `transaksi_obat_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `id_obat` int(11) NOT NULL,
  `id_staff` int(11) NOT NULL,
  `tipe_transaksi` enum('masuk','keluar') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `satuan` varchar(50) NOT NULL DEFAULT 'unit',
  `tujuan` varchar(255) DEFAULT NULL,
  `tanggal_transaksi` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_obat_backup`
--

INSERT INTO `transaksi_obat_backup` (`id`, `id_obat`, `id_staff`, `tipe_transaksi`, `jumlah`, `satuan`, `tujuan`, `tanggal_transaksi`, `keterangan`, `tanggal_dibuat`) VALUES
(4, 37, 2, 'masuk', 4, 'unit', NULL, '2025-11-23', '', '2025-11-23 03:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('admin','staff','kepala_instalasi') NOT NULL DEFAULT 'staff',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `foto_profil` varchar(255) DEFAULT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_diupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `role`, `status`, `foto_profil`, `tanggal_dibuat`, `tanggal_diupdate`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Darren', 'admin@simonut.com', 'admin', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-14 23:10:15'),
(2, 'staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chio', 'kota@simonut.com', 'staff', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-26 14:49:52'),
(3, 'staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Aconk', 'conk@simonut.com', 'staff', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-26 14:50:06'),
(4, 'staff3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Diva Dumais', 'dumais@simonut.com', 'staff', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-26 14:50:23'),
(5, 'staff4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Brilyan Mangkey', 'billy@simonut.com', 'staff', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-14 23:10:06'),
(7, 'kepala', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Instalasi', 'kepala@simonut.com', 'kepala_instalasi', 'aktif', NULL, '2025-11-27 00:46:20', '2025-11-27 00:46:20'),
(8, 'chris', '$2y$10$JEGhjIJdg4.R9I7FOG1cM.rUKT/5yQNy0QkvZQ0kJLx9pESCjrBDq', 'Christiano Tumewu', 'christ@gmail.com', 'staff', 'aktif', NULL, '2025-11-27 02:45:06', '2025-11-27 02:45:06');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_stok_obat`
-- (See below for the actual view)
--
CREATE TABLE `view_stok_obat` (
`id` int(11)
,`nama_obat` varchar(255)
,`dosis` varchar(100)
,`kategori` varchar(100)
,`tanggal_dibuat` timestamp
,`tanggal_diupdate` timestamp
,`total_masuk` decimal(32,0)
,`total_keluar` decimal(32,0)
,`stok_tersedia` decimal(33,0)
,`tanggal_kedaluwarsa_terdekat` date
);

-- --------------------------------------------------------

--
-- Structure for view `view_stok_obat`
--
DROP TABLE IF EXISTS `view_stok_obat`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_stok_obat`  AS SELECT `o`.`id` AS `id`, `o`.`nama_obat` AS `nama_obat`, `o`.`dosis` AS `dosis`, `o`.`kategori` AS `kategori`, `o`.`tanggal_dibuat` AS `tanggal_dibuat`, `o`.`tanggal_diupdate` AS `tanggal_diupdate`, coalesce(sum(case when `t`.`tipe_transaksi` = 'masuk' then `t`.`jumlah` else 0 end),0) AS `total_masuk`, coalesce(sum(case when `t`.`tipe_transaksi` = 'keluar' then `t`.`jumlah` else 0 end),0) AS `total_keluar`, coalesce(sum(case when `t`.`tipe_transaksi` = 'masuk' then `t`.`jumlah` else 0 end) - sum(case when `t`.`tipe_transaksi` = 'keluar' then `t`.`jumlah` else 0 end),0) AS `stok_tersedia`, min(case when `t`.`tipe_transaksi` = 'masuk' and `t`.`tanggal_kedaluwarsa` is not null then `t`.`tanggal_kedaluwarsa` end) AS `tanggal_kedaluwarsa_terdekat` FROM (`obat` `o` left join `transaksi_obat` `t` on(`o`.`id` = `t`.`id_obat`)) GROUP BY `o`.`id`, `o`.`nama_obat`, `o`.`dosis`, `o`.`kategori`, `o`.`tanggal_dibuat`, `o`.`tanggal_diupdate` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tanggal` (`tanggal_mulai`,`tanggal_selesai`),
  ADD KEY `idx_jenis` (`jenis`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_kegiatan_pembuat` (`id_pembuat`);

--
-- Indexes for table `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_obat_nama` (`nama_obat`),
  ADD KEY `idx_obat_kategori` (`kategori`);

--
-- Indexes for table `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaksi_obat` (`id_obat`),
  ADD KEY `idx_transaksi_staff` (`id_staff`),
  ADD KEY `idx_tipe_transaksi` (`tipe_transaksi`),
  ADD KEY `idx_tanggal` (`tanggal_transaksi`),
  ADD KEY `idx_transaksi_obat_id` (`id_obat`),
  ADD KEY `idx_transaksi_tipe` (`tipe_transaksi`),
  ADD KEY `idx_tanggal_kedaluwarsa` (`tanggal_kedaluwarsa`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kegiatan`
--
ALTER TABLE `kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `obat`
--
ALTER TABLE `obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD CONSTRAINT `fk_kegiatan_pembuat` FOREIGN KEY (`id_pembuat`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  ADD CONSTRAINT `fk_transaksi_obat` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_transaksi_staff` FOREIGN KEY (`id_staff`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
