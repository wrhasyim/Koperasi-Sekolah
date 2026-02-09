-- 1. Tabel Anggota (User System)
CREATE TABLE `anggota` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pengurus','staff','guru') NOT NULL DEFAULT 'guru',
  `no_hp` varchar(20) DEFAULT NULL,
  `status_aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed User Default (Password: 123456)
INSERT INTO `anggota` (`nama_lengkap`, `username`, `password`, `role`) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('David Staff', 'david', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Bu Guru Ani', 'ani', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru');

-- 2. Tabel Simpanan (Sihara, Simjib, Simpok)
CREATE TABLE `simpanan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anggota_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jenis_simpanan` enum('pokok','wajib','hari_raya') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `tipe_transaksi` enum('setor','tarik') NOT NULL DEFAULT 'setor',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `anggota_id` (`anggota_id`),
  CONSTRAINT `fk_simpanan_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Transaksi Kas (Jurnal Umum)
CREATE TABLE `transaksi_kas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `kategori` enum('penjualan_harian','belanja_stok','gaji_staff','honor_pengurus','dana_sosial','qris_masuk','operasional_lain') NOT NULL,
  `arus` enum('masuk','keluar') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL, -- Siapa yang input
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Titipan (Konsinyasi Guru)
CREATE TABLE `titipan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anggota_id` int(11) NOT NULL, -- Pemilik Barang
  `nama_barang` varchar(100) NOT NULL,
  `tanggal_titip` date NOT NULL,
  `stok_awal` int(11) NOT NULL,
  `stok_terjual` int(11) DEFAULT 0,
  `harga_modal` decimal(15,2) NOT NULL, -- Hak Guru
  `harga_jual` decimal(15,2) NOT NULL,  -- Harga Koperasi
  `status_bayar` enum('belum','lunas') DEFAULT 'belum',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `anggota_id` (`anggota_id`),
  CONSTRAINT `fk_titipan_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Stok Barang (Inventory Seragam)
CREATE TABLE `stok_barang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(50) DEFAULT NULL,
  `kategori` enum('seragam_sekolah','seragam_eskul','atk') NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `ukuran` varchar(10) DEFAULT NULL, -- S, M, L, XL
  `stok` int(11) DEFAULT 0,
  `harga_modal` decimal(15,2) NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;