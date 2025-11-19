-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2025 at 05:39 PM
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
(2, 'Pelatihan Sistem SIMONUT', 'Pelatihan penggunaan sistem informasi untuk staff baru', '2025-11-22 13:00:00', '2025-11-22 16:00:00', 'Dimembe Gacor', 'pelatihan', 'terjadwal', 1, '2025-11-16 16:21:42', '2025-11-16 16:29:01'),
(3, 'Distribusi Obat ke Puskesmas', 'Distribusi obat rutin ke puskesmas wilayah utara', '2025-11-25 08:00:00', '2025-11-25 15:00:00', 'Puskesmas Kauditan', 'distribusi', 'terjadwal', 1, '2025-11-16 16:21:42', '2025-11-16 16:21:42'),
(4, 'Inspeksi Gudang Obat', 'Inspeksi rutin kondisi penyimpanan dan stok obat', '2025-11-27 10:00:00', '2025-11-27 12:00:00', 'Gudang Obat Utama', 'inspeksi', 'terjadwal', 1, '2025-11-16 16:21:42', '2025-11-16 16:21:42'),
(5, 'Gacor', NULL, '2025-11-24 09:00:00', '2025-11-24 10:00:00', 'RSU GMIM Tonsea Airmadidi', 'pelatihan', 'terjadwal', 1, '2025-11-16 16:38:01', '2025-11-16 16:38:01');

-- --------------------------------------------------------

--
-- Table structure for table `obat`
--

CREATE TABLE `obat` (
  `id` int(11) NOT NULL,
  `nama_obat` varchar(255) NOT NULL,
  `dosis` varchar(100) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_diupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `obat`
--

INSERT INTO `obat` (`id`, `nama_obat`, `dosis`, `kategori`, `stok`, `tanggal_dibuat`, `tanggal_diupdate`) VALUES
(29, 'Paracetamol', '500 mg', 'Analgesik / Antipiretik', 120, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(30, 'Ibuprofen', '200 mg', 'Analgesik / Antipiretik', 90, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(31, 'Asam Mefenamat', '500 mg', 'Analgesik / Antipiretik', 75, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(32, 'Naproxen', '250 mg', 'Analgesik / Antipiretik', 50, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(33, 'Amoxicillin', '500 mg', 'Antibiotik', 80, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(34, 'Azithromycin', '250 mg', 'Antibiotik', 60, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(35, 'Cefadroxil', '500 mg', 'Antibiotik', 45, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(36, 'Metronidazole', '500 mg', 'Antibiotik', 55, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(37, 'Ambroxol', '30 mg', 'Obat batuk', 67, '2025-11-13 02:26:08', '2025-11-14 23:11:14'),
(38, 'Dextromethorphan', '15 mg', 'Obat batuk', 60, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(39, 'Guaifenesin', '100 mg', 'Obat batuk', 90, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(40, 'Bromhexine', '8 mg', 'Obat batuk', 65, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(41, 'Vitamin C', '500 mg', 'Vitamin', 150, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(42, 'Vitamin D3', '1000 IU', 'Vitamin', 100, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(43, 'Vitamin B Complex', '-', 'Vitamin', 120, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(44, 'Zinc', '20 mg', 'Vitamin', 80, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(45, 'Povidone Iodine', '10%', 'Antiseptik', 50, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(46, 'Alcohol Swab', '-', 'Antiseptik', 200, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(47, 'Hydrogen Peroxide', '3%', 'Antiseptik', 40, '2025-11-13 02:26:08', '2025-11-13 02:26:08'),
(48, 'Chlorhexidine', '0.2%', 'Antiseptik', 35, '2025-11-13 02:26:08', '2025-11-13 02:26:08');

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
  `keterangan` text DEFAULT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_obat`
--

INSERT INTO `transaksi_obat` (`id`, `id_obat`, `id_staff`, `tipe_transaksi`, `jumlah`, `satuan`, `tujuan`, `tanggal_transaksi`, `keterangan`, `tanggal_dibuat`) VALUES
(1, 37, 4, 'keluar', 3, 'unit', 'Puskesmas Kauditan', '2025-11-14', '', '2025-11-14 23:11:14');

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
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
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
(2, 'staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Edo Kota', 'kota@simonut.com', 'staff', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-14 23:10:43'),
(3, 'staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Liza Kedaluwarsa', 'ica@simonut.com', 'staff', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-14 23:10:51'),
(4, 'staff3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Diva Dumais', 'james@simonut.com', 'staff', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-14 23:10:36'),
(5, 'staff4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Brilyan Mangkey', 'billy@simonut.com', 'staff', 'aktif', NULL, '2025-11-14 23:00:53', '2025-11-14 23:10:06');

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
  ADD KEY `idx_obat_kategori` (`kategori`),
  ADD KEY `idx_stok` (`stok`);

--
-- Indexes for table `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaksi_obat` (`id_obat`),
  ADD KEY `idx_transaksi_staff` (`id_staff`),
  ADD KEY `idx_tipe_transaksi` (`tipe_transaksi`),
  ADD KEY `idx_tanggal` (`tanggal_transaksi`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `obat`
--
ALTER TABLE `obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
