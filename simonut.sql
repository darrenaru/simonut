-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 10:42 AM
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
-- Database: `simonut`
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
(29, 'Paracetamol', '500 mg', 'Analgesik / Antipiretik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(30, 'Ibuprofen', '200 mg', 'Analgesik / Antipiretik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(31, 'Asam Mefenamat', '500 mg', 'Analgesik / Antipiretik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(32, 'Naproxen', '250 mg', 'Analgesik / Antipiretik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(33, 'Amoxicillin', '500 mg', 'Antibiotik', '2025-11-13 02:26:08', '2025-11-19 16:04:48'),
(34, 'Azithromycin', '250 mg', 'Antibiotik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(35, 'Cefadroxil', '500 mg', 'Antibiotik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(36, 'Metronidazole', '500 mg', 'Antibiotik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(37, 'Ambroxol', '30 mg', 'Obat batuk', '2025-11-13 02:26:08', '2025-11-23 03:12:44'),
(38, 'Dextromethorphan', '15 mg', 'Obat batuk', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(39, 'Guaifenesin', '100 mg', 'Obat batuk', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(40, 'Bromhexine', '8 mg', 'Obat batuk', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(41, 'Vitamin C', '500 mg', 'Vitamin', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(42, 'Vitamin D3', '1000 IU', 'Vitamin', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(43, 'Vitamin B Complex', '-', 'Vitamin', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(44, 'Zinc', '20 mg', 'Vitamin', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(45, 'Povidone Iodine', '10%', 'Antiseptik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(46, 'Alcohol Swab', '-', 'Antiseptik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(47, 'Hydrogen Peroxide', '3%', 'Antiseptik', '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(48, 'Chlorhexidine', '0.2%', 'Antiseptik', '2025-11-13 02:26:08', '2025-11-13 02:26:08');

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
(4, 37, 2, 'masuk', 4, 'unit', NULL, '2025-11-23', NULL, '', '2025-11-23 03:12:44'),
(5, 37, 5, 'keluar', 4, 'unit', 'RSU GMIM Tonsea Airmadidi', '2025-11-23', NULL, '', '2025-11-23 03:30:03'),
(6, 33, 5, 'masuk', 4, 'unit', NULL, '2025-11-23', NULL, '', '2025-11-23 03:34:29'),
(7, 35, 4, 'masuk', 5, 'unit', NULL, '2025-11-23', NULL, '', '2025-11-23 03:34:48'),
(8, 35, 5, 'keluar', 5, 'unit', 'Sentra Medika Hospital Minahasa Utara', '2025-11-23', NULL, '', '2025-11-23 03:35:28'),
(9, 33, 4, 'keluar', 4, 'unit', 'Puskesmas Kema', '2025-11-23', NULL, '', '2025-11-23 03:35:54'),
(10, 37, 3, 'masuk', 1, 'unit', NULL, '2025-11-26', '2025-11-30', '', '2025-11-26 11:30:30'),
(11, 33, 5, 'masuk', 2, 'unit', NULL, '2025-11-26', '2025-11-27', '', '2025-11-26 11:34:01'),
(12, 37, 2, 'keluar', 1, 'unit', 'Sentra Medika Hospital Minahasa Utara', '2025-11-26', NULL, '', '2025-11-26 11:41:51'),
(13, 31, 2, 'masuk', 5, 'unit', NULL, '2025-11-27', '2025-12-23', '', '2025-11-27 00:26:24'),
(14, 37, 3, 'masuk', 4, 'unit', NULL, '2025-11-27', '2025-11-30', '', '2025-11-27 00:29:51'),
(15, 48, 2, 'masuk', 5, 'unit', NULL, '2025-11-27', '2026-09-17', '', '2025-11-27 00:30:19'),
(20, 38, 5, 'masuk', 4, 'unit', NULL, '2025-11-27', '2025-11-27', '', '2025-11-27 02:38:56'),
(21, 37, 5, 'keluar', 4, 'unit', 'Puskesmas Kema', '2025-11-27', NULL, '', '2025-11-27 03:07:29'),
(22, 33, 3, 'keluar', 1, 'unit', 'Sentra Medika Hospital Minahasa Utara', '2025-11-28', NULL, '', '2025-11-28 02:32:46'),
(23, 31, 3, 'keluar', 5, 'unit', 'Puskesmas Dimembe', '2025-11-28', NULL, '', '2025-11-28 02:38:55'),
(25, 33, 5, 'masuk', 5, 'unit', NULL, '2025-11-28', '2025-11-30', '', '2025-11-28 02:48:20'),
(26, 31, 5, 'masuk', 4, 'unit', NULL, '2025-11-30', '2026-01-14', '', '2025-11-30 09:08:25'),
(27, 35, 8, 'masuk', 5, 'unit', NULL, '2025-11-30', '2026-02-03', '', '2025-11-30 09:09:04'),
(28, 40, 5, 'masuk', 3, 'unit', NULL, '2025-11-30', '2025-12-01', '', '2025-11-30 09:39:28');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
