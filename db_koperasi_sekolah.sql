-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 09:33 AM
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
-- Database: `db_koperasi_sekolah`
--

-- --------------------------------------------------------

--
-- Table structure for table `anggota`
--

CREATE TABLE `anggota` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pengurus','staff','guru') NOT NULL DEFAULT 'guru',
  `no_hp` varchar(20) DEFAULT NULL,
  `status_aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`id`, `nama_lengkap`, `username`, `password`, `role`, `no_hp`, `status_aktif`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 1, '2026-02-09 05:13:00'),
(2, 'David Staff', 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '081234567891', 1, '2026-02-10 08:32:38'),
(3, 'Bu Guru Ani', 'guru1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '081234567892', 1, '2026-02-10 08:32:38'),
(4, 'Pak Budi Santoso', 'guru2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '081234567893', 1, '2026-02-10 08:32:38'),
(5, 'Siti Pengurus', 'pengurus', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pengurus', '081234567894', 1, '2026-02-10 08:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `cicilan`
--

CREATE TABLE `cicilan` (
  `id` int(11) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `kategori_barang` varchar(50) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `total_tagihan` decimal(15,2) NOT NULL,
  `terbayar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sisa` decimal(15,2) NOT NULL,
  `status` enum('lunas','belum') DEFAULT 'belum',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `aksi` varchar(50) NOT NULL,
  `keterangan` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_pengambilan`
--

CREATE TABLE `riwayat_pengambilan` (
  `id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `jumlah_ambil` int(11) NOT NULL DEFAULT 1,
  `status_bayar` enum('Lunas','Belum Lunas') NOT NULL DEFAULT 'Lunas',
  `catatan` text DEFAULT NULL,
  `tanggal_ambil` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `simpanan`
--

CREATE TABLE `simpanan` (
  `id` int(11) NOT NULL,
  `anggota_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jenis_simpanan` enum('pokok','wajib','hari_raya') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `tipe_transaksi` enum('setor','tarik') NOT NULL DEFAULT 'setor',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_barang`
--

CREATE TABLE `stok_barang` (
  `id` int(11) NOT NULL,
  `kode_barang` varchar(50) DEFAULT NULL,
  `kategori` enum('seragam_sekolah','seragam_eskul','atk') NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `ukuran` varchar(10) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `harga_modal` decimal(15,2) NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_barang`
--

INSERT INTO `stok_barang` (`id`, `kode_barang`, `kategori`, `nama_barang`, `ukuran`, `stok`, `harga_modal`, `harga_jual`, `updated_at`) VALUES
(1, 'SRG-S-1770625323', 'seragam_sekolah', 'kaus kaki', 'S', 11, 0.00, 120000.00, '2026-02-09 08:23:00');

-- --------------------------------------------------------

--
-- Table structure for table `stok_eskul`
--

CREATE TABLE `stok_eskul` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(100) DEFAULT NULL,
  `harga_modal` decimal(10,2) DEFAULT NULL,
  `harga_jual` decimal(10,2) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_eskul`
--

INSERT INTO `stok_eskul` (`id`, `nama_barang`, `harga_modal`, `harga_jual`, `stok`, `updated_at`) VALUES
(1, 'Baju Silat (Pencak Silat)', 150000.00, 180000.00, 10, '2026-02-10 08:32:38'),
(2, 'Kacu Pramuka & Ring', 15000.00, 25000.00, 40, '2026-02-10 08:32:38'),
(3, 'Seragam PMR', 130000.00, 160000.00, 15, '2026-02-10 08:32:38'),
(4, 'Kaos Tim Futsal', 75000.00, 95000.00, 20, '2026-02-10 08:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `stok_koperasi`
--

CREATE TABLE `stok_koperasi` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(100) DEFAULT NULL,
  `harga_modal` decimal(10,2) DEFAULT NULL,
  `harga_jual` decimal(10,2) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_koperasi`
--

INSERT INTO `stok_koperasi` (`id`, `nama_barang`, `harga_modal`, `harga_jual`, `stok`, `updated_at`) VALUES
(1, 'Pulpen Standard', 2000.00, 3000.00, 50, '2026-02-10 08:32:38'),
(2, 'Buku Tulis Sinar Dunia 38', 3500.00, 5000.00, 100, '2026-02-10 08:32:38'),
(3, 'Air Mineral Gelas', 500.00, 1000.00, 48, '2026-02-10 08:32:38'),
(4, 'Keripik Singkong Pedas', 4000.00, 5000.00, 20, '2026-02-10 08:32:38'),
(5, 'Pensil 2B Faber Castell', 3000.00, 4500.00, 30, '2026-02-10 08:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `stok_sekolah`
--

CREATE TABLE `stok_sekolah` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(100) DEFAULT NULL,
  `harga_modal` decimal(10,2) DEFAULT NULL,
  `harga_jual` decimal(10,2) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_sekolah`
--

INSERT INTO `stok_sekolah` (`id`, `nama_barang`, `harga_modal`, `harga_jual`, `stok`, `updated_at`) VALUES
(1, 'Seragam Batik - Ukuran L', 85000.00, 100000.00, 25, '2026-02-10 08:32:38'),
(2, 'Seragam Batik - Ukuran XL', 90000.00, 105000.00, 20, '2026-02-10 08:32:38'),
(3, 'Seragam Olahraga - Set L', 120000.00, 150000.00, 30, '2026-02-10 08:32:38'),
(4, 'Topi Sekolah Logo Bordir', 15000.00, 25000.00, 50, '2026-02-10 08:32:38'),
(5, 'Dasi Sekolah', 10000.00, 15000.00, 50, '2026-02-10 08:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `titipan`
--

CREATE TABLE `titipan` (
  `id` int(11) NOT NULL,
  `anggota_id` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `tanggal_titip` date NOT NULL,
  `stok_awal` int(11) NOT NULL,
  `stok_terjual` int(11) DEFAULT 0,
  `harga_modal` decimal(15,2) NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `status_bayar` enum('belum','lunas') DEFAULT 'belum',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `titipan`
--

INSERT INTO `titipan` (`id`, `anggota_id`, `nama_barang`, `tanggal_titip`, `stok_awal`, `stok_terjual`, `harga_modal`, `harga_jual`, `status_bayar`, `created_at`) VALUES
(1, 3, 'Donat Kentang Bu Ani', '2026-02-10', 20, 5, 2500.00, 3500.00, 'belum', '2026-02-10 08:32:38'),
(2, 4, 'Nasi Uduk Pak Budi', '2026-02-10', 15, 15, 6000.00, 8000.00, 'belum', '2026-02-10 08:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_kas`
--

CREATE TABLE `transaksi_kas` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `arus` enum('masuk','keluar') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutup_buku`
--

CREATE TABLE `tutup_buku` (
  `id` int(11) NOT NULL,
  `bulan` int(2) NOT NULL,
  `tahun` int(4) NOT NULL,
  `saldo_awal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_masuk` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_keluar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo_akhir` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `cicilan`
--
ALTER TABLE `cicilan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `riwayat_pengambilan`
--
ALTER TABLE `riwayat_pengambilan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `simpanan`
--
ALTER TABLE `simpanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anggota_id` (`anggota_id`);

--
-- Indexes for table `stok_barang`
--
ALTER TABLE `stok_barang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stok_eskul`
--
ALTER TABLE `stok_eskul`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stok_koperasi`
--
ALTER TABLE `stok_koperasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stok_sekolah`
--
ALTER TABLE `stok_sekolah`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `titipan`
--
ALTER TABLE `titipan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anggota_id` (`anggota_id`);

--
-- Indexes for table `transaksi_kas`
--
ALTER TABLE `transaksi_kas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tutup_buku`
--
ALTER TABLE `tutup_buku`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `periode` (`bulan`,`tahun`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `cicilan`
--
ALTER TABLE `cicilan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `riwayat_pengambilan`
--
ALTER TABLE `riwayat_pengambilan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `simpanan`
--
ALTER TABLE `simpanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_barang`
--
ALTER TABLE `stok_barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stok_eskul`
--
ALTER TABLE `stok_eskul`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stok_koperasi`
--
ALTER TABLE `stok_koperasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stok_sekolah`
--
ALTER TABLE `stok_sekolah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `titipan`
--
ALTER TABLE `titipan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaksi_kas`
--
ALTER TABLE `transaksi_kas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tutup_buku`
--
ALTER TABLE `tutup_buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `simpanan`
--
ALTER TABLE `simpanan`
  ADD CONSTRAINT `fk_simpanan_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `titipan`
--
ALTER TABLE `titipan`
  ADD CONSTRAINT `fk_titipan_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
