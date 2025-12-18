-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 01:00 AM
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
CREATE DEFINER=`root`@`localhost` FUNCTION `get_stok_batch` (`p_id_obat` INT, `p_nomor_batch` VARCHAR(100)) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_stok INT DEFAULT 0;
    
    SELECT 
        COALESCE(
            SUM(CASE WHEN tipe_transaksi = 'masuk' THEN jumlah ELSE 0 END) - 
            SUM(CASE WHEN tipe_transaksi = 'keluar' THEN jumlah ELSE 0 END), 
            0
        ) INTO v_stok
    FROM transaksi_obat
    WHERE id_obat = p_id_obat AND nomor_batch = p_nomor_batch;
    
    RETURN v_stok;
END$$

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
(6, 'DDDDD', NULL, '2025-11-27 10:42:00', '2025-11-27 10:42:00', 'Sentra Medika Hospital Minahasa Utara', 'distribusi', 'terjadwal', 1, '2025-11-27 02:42:58', '2025-11-27 02:42:58'),
(15, 'Pengiriman Obat', NULL, '2025-12-10 09:00:00', '2025-12-10 10:00:00', 'Instalasi Farmasi Kabupaten', 'distribusi', 'terjadwal', 1, '2025-12-09 02:03:26', '2025-12-09 02:03:26'),
(16, 'Review Formularium RS', NULL, '2025-12-22 09:00:00', '2025-12-22 10:00:00', 'Ruang Rapat Instalasi Farmasi', 'rapat', 'terjadwal', 1, '2025-12-09 02:04:28', '2025-12-09 02:04:28'),
(17, 'Pelatihan internal: Medication Safety', NULL, '2025-12-18 09:00:00', '2025-12-18 10:00:00', 'Ruang Rapat Instalasi Farmasi', 'pelatihan', 'terjadwal', 1, '2025-12-09 02:04:52', '2025-12-09 02:04:52'),
(18, 'Review SOP Instalasi Farmasi', NULL, '2025-12-01 09:00:00', '2025-12-01 10:00:00', 'Ruang Rapat Instalasi Farmasi', 'inspeksi', 'terjadwal', 1, '2025-12-09 02:05:47', '2025-12-09 02:05:47'),
(19, 'Pelatihan Sistem SIMONUT', NULL, '2025-12-03 09:00:00', '2025-12-03 10:00:00', 'Ruang Rapat Instalasi Farmasi', 'pelatihan', 'terjadwal', 1, '2025-12-09 06:02:21', '2025-12-09 06:02:21');

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
(104, 'Paracetamol Tablet', '500 mg', 'Obat DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(105, 'Ibuprofen Tablet', '400 mg', 'Obat DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(108, 'Metronidazole Tablet', '500 mg', 'Obat DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(109, 'Vitamin B Complex', 'Tablet', 'Obat DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(110, 'Kapur Sirih', 'Powder', 'Perbekkes DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(111, 'Kassa Steril', '10x10 cm', 'Perbekkes DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(112, 'Plester Rol', '1 inch', 'Perbekkes DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(113, 'Povidone Iodine', '60 ml', 'Perbekkes DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(114, 'Syringe 3 ml', 'Alat', 'Perbekkes DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(115, 'Sarung Tangan Medis', 'Latex', 'Perbekkes DAK', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(116, 'Oralit', 'Sachet', 'Droping', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(117, 'Zinc Tablet', '20 mg', 'Droping', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(118, 'Vitamin A Biru', '100.000 IU', 'Droping', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(119, 'Vitamin A Merah', '200.000 IU', 'Droping', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(120, 'Parasetamol Sirup', '120 mg/5ml', 'Droping', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(121, 'ORS Pack', 'Formula Reguler', 'Droping', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(122, 'Albendazole Tablet', '400 mg', 'Perbekkes DID', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(123, 'Dexamethasone Tablet', '0.5 mg', 'Perbekkes DID', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(124, 'Diazepam Tablet', '5 mg', 'Perbekkes DID', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(125, 'Amlodipine Tablet', '10 mg', 'Perbekkes DID', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(126, 'Captopril Tablet', '25 mg', 'Perbekkes DID', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(127, 'Simvastatin Tablet', '20 mg', 'Perbekkes DID', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(128, 'Vaksin Sinovac', '1 dosis', 'Vaksin Covid', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(129, 'Vaksin AstraZeneca', '1 dosis', 'Vaksin Covid', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(130, 'Vaksin Pfizer', '1 dosis', 'Vaksin Covid', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(131, 'Vaksin Moderna', '1 dosis', 'Vaksin Covid', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(132, 'Vaksin Janssen', '1 dosis', 'Vaksin Covid', '2025-12-09 01:27:40', '2025-12-09 01:27:40'),
(133, 'Vaksin Sinopharm', '1 dosis', 'Vaksin Covid', '2025-12-09 01:27:40', '2025-12-09 01:27:40');

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
  `nomor_batch` varchar(100) DEFAULT NULL,
  `nomor_faktur` varchar(100) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_obat`
--

INSERT INTO `transaksi_obat` (`id`, `id_obat`, `id_staff`, `tipe_transaksi`, `jumlah`, `satuan`, `tujuan`, `tanggal_transaksi`, `tanggal_kedaluwarsa`, `nomor_batch`, `nomor_faktur`, `keterangan`, `tanggal_dibuat`) VALUES
(48, 122, 9, 'masuk', 5, 'unit', NULL, '2025-12-09', '2026-02-25', 'DL1990', '2025', '', '2025-12-09 01:29:05'),
(49, 122, 9, 'masuk', 5, 'tablet', NULL, '2025-12-09', '2026-01-26', 'LOL9000', '2025', '', '2025-12-09 01:29:05'),
(50, 123, 10, 'masuk', 10, 'tablet', NULL, '2025-12-09', '2026-01-06', 'KP900', '2025', '', '2025-12-09 01:30:02'),
(51, 126, 10, 'masuk', 10, 'tablet', NULL, '2025-12-09', '2026-03-18', 'PL212', '2025', '', '2025-12-09 01:31:18'),
(52, 126, 10, 'keluar', 5, 'tablet', 'Sentra Medika Hospital Minahasa Utara', '2025-12-09', NULL, NULL, NULL, '', '2025-12-09 01:32:04'),
(53, 125, 10, 'masuk', 5, 'tablet', NULL, '2025-12-09', '2025-12-31', 'DL1990', '2025', '', '2025-12-09 05:12:25'),
(54, 125, 9, 'keluar', 5, 'tablet', 'RSU GMIM Tonsea Airmadidi', '2025-12-09', NULL, NULL, NULL, '', '2025-12-09 05:13:27'),
(55, 123, 9, 'keluar', 6, 'tablet', 'Puskesmas Kauditan', '2025-12-09', NULL, NULL, NULL, '', '2025-12-09 05:41:41'),
(56, 125, 9, 'masuk', 5, 'tablet', NULL, '2025-12-09', '2026-01-21', 'DD1999', '2025', '', '2025-12-09 06:00:45'),
(58, 122, 9, 'keluar', 5, 'tablet', 'RSU GMIM Tonsea Airmadidi', '2025-12-17', '2026-01-26', 'LOL9000', NULL, '', '2025-12-17 23:53:07');

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
  `foto_profil` varchar(255) DEFAULT 'default-avatar.png',
  `role` enum('admin','staff','kepala_instalasi') NOT NULL DEFAULT 'staff',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_diupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `foto_profil`, `role`, `status`, `tanggal_dibuat`, `tanggal_diupdate`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Darren Chio', 'darrenchicicicio@gmail.com', 'default-avatar.png', 'admin', 'aktif', '2025-11-14 23:00:53', '2025-12-09 02:06:15'),
(9, 'chris', '$2y$10$1mHbhKd6oaxTVLWPj3jwUuFbHxqP6TQCU4XhV3od3AasB1lQk.61O', 'Christiano Tumewu', 'christianotumewu@gmail.com', 'default-avatar.png', 'staff', 'aktif', '2025-12-09 00:48:58', '2025-12-09 00:48:58'),
(10, 'diva', '$2y$10$AW6LmdRP.TppgIWxiAxRkelmtpxobK8WPptjhXfF3w/ahuBlCBOLW', 'Diva Dumais', 'ddumais28@gmail.com', 'default-avatar.png', 'staff', 'aktif', '2025-12-09 01:17:00', '2025-12-09 01:17:00'),
(11, 'sela', '$2y$10$.px184MwK8ZAlQRbODYYWOVbX39sRk9.6meNNbsaIE.Fv.yRrrFY6', 'Sheyla Tarukbua', 'sheylatarukbua15@gmail.com', 'default-avatar.png', 'kepala_instalasi', 'aktif', '2025-12-09 01:17:37', '2025-12-09 06:03:01');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_batch_tracking`
-- (See below for the actual view)
--
CREATE TABLE `view_batch_tracking` (
`id_obat` int(11)
,`nama_obat` varchar(255)
,`dosis` varchar(100)
,`nomor_batch` varchar(100)
,`tanggal_kedaluwarsa` date
,`total_masuk` decimal(32,0)
,`total_keluar` decimal(32,0)
,`sisa_stok` decimal(33,0)
,`satuan` varchar(50)
,`hari_tersisa` int(7)
);

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
-- Structure for view `view_batch_tracking`
--
DROP TABLE IF EXISTS `view_batch_tracking`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_batch_tracking`  AS SELECT `o`.`id` AS `id_obat`, `o`.`nama_obat` AS `nama_obat`, `o`.`dosis` AS `dosis`, `t`.`nomor_batch` AS `nomor_batch`, `t`.`tanggal_kedaluwarsa` AS `tanggal_kedaluwarsa`, sum(case when `t`.`tipe_transaksi` = 'masuk' then `t`.`jumlah` else 0 end) AS `total_masuk`, sum(case when `t`.`tipe_transaksi` = 'keluar' then `t`.`jumlah` else 0 end) AS `total_keluar`, sum(case when `t`.`tipe_transaksi` = 'masuk' then `t`.`jumlah` else 0 end) - sum(case when `t`.`tipe_transaksi` = 'keluar' then `t`.`jumlah` else 0 end) AS `sisa_stok`, `t`.`satuan` AS `satuan`, to_days(`t`.`tanggal_kedaluwarsa`) - to_days(curdate()) AS `hari_tersisa` FROM (`transaksi_obat` `t` join `obat` `o` on(`t`.`id_obat` = `o`.`id`)) WHERE `t`.`nomor_batch` is not null GROUP BY `o`.`id`, `o`.`nama_obat`, `o`.`dosis`, `t`.`nomor_batch`, `t`.`tanggal_kedaluwarsa`, `t`.`satuan` ORDER BY `t`.`tanggal_kedaluwarsa` ASC ;

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
  ADD KEY `idx_tanggal_kedaluwarsa` (`tanggal_kedaluwarsa`),
  ADD KEY `idx_nomor_batch` (`nomor_batch`),
  ADD KEY `idx_nomor_faktur` (`nomor_faktur`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `obat`
--
ALTER TABLE `obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
