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

-- 2. Tabel Simpanan (Tabungan Anggota)
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
-- Diperbarui kategori agar sinkron dengan laporan RAT dan filter unit
CREATE TABLE `transaksi_kas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `kategori` enum('penjualan_harian','penjualan_seragam','penjualan_eskul','pembayaran_titipan','belanja_stok','gaji_staff','honor_pengurus','dana_sosial','operasional_lain','modal_awal','setor_simpanan','tarik_simpanan') NOT NULL,
  `arus` enum('masuk','keluar') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Titipan (Konsinyasi Guru)
-- Struktur tetap, namun logika pembayaran akan mencatat laba di transaksi_kas
CREATE TABLE `titipan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anggota_id` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `tanggal_titip` date NOT NULL,
  `stok_awal` int(11) NOT NULL,
  `stok_terjual` int(11) DEFAULT 0,
  `harga_modal` decimal(15,2) NOT NULL, -- Hak Guru
  `harga_jual` decimal(15,2) NOT NULL,  -- Harga Koperasi
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `anggota_id` (`anggota_id`),
  CONSTRAINT `fk_titipan_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Stok Koperasi (Jajan/ATK/Toko)
CREATE TABLE `stok_koperasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_barang` varchar(100) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `harga_modal` decimal(15,2) NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Stok Sekolah (Seragam)
CREATE TABLE `stok_sekolah` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_barang` varchar(100) NOT NULL,
  `ukuran` varchar(10) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `harga_modal` decimal(15,2) NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel Stok Eskul (Atribut Eskul)
CREATE TABLE `stok_eskul` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_barang` varchar(100) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `harga_modal` decimal(15,2) NOT NULL,
  `harga_jual` decimal(15,2) NOT NULL,
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tabel Cicilan (Piutang Siswa/Anggota)
CREATE TABLE `cicilan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `barang_id` int(11) NOT NULL,
  `kategori_barang` enum('seragam','eskul') NOT NULL,
  `total_harga` decimal(15,2) NOT NULL,
  `sudah_bayar` decimal(15,2) DEFAULT 0,
  `sisa` decimal(15,2) NOT NULL,
  `status` enum('belum','lunas') DEFAULT 'belum',
  `tanggal_mulai` date NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Tabel Tutup Buku (Penguncian Periode)
CREATE TABLE `tutup_buku` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulan` int(2) NOT NULL,
  `tahun` int(4) NOT NULL,
  `saldo_akhir` decimal(15,2) NOT NULL,
  `status` enum('tutup') DEFAULT 'tutup',
  `closed_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Tabel Log Aktivitas (Audit Trail)
CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `aksi` varchar(100) NOT NULL,
  `keterangan` text NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;