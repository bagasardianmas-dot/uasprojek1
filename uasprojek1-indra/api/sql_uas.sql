CREATE DATABASE IF NOT EXISTS rest_api;
USE rest_api;

-- 1. Tabel Kategori Buku
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL
);

-- 2. Tabel Buku
CREATE TABLE IF NOT EXISTS buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    penulis VARCHAR(100),
    id_kategori INT,
    stok INT DEFAULT 0,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE SET NULL
);

-- 3. Tabel Pengguna (Anggota Perpustakaan)
CREATE TABLE IF NOT EXISTS pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

-- 4. Tabel Transaksi Keluar & Masuk Buku
CREATE TABLE IF NOT EXISTS transaksi_buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pengguna INT NOT NULL,
    id_buku INT NOT NULL,
    jenis_transaksi ENUM('keluar', 'masuk') NOT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id) ON DELETE CASCADE
);

-- Contoh data awal (opsional, boleh dihapus)
INSERT INTO kategori (nama_kategori) VALUES ('Fiksi'), ('Non-Fiksi'), ('Teknologi');
INSERT INTO buku (judul, penulis, id_kategori, stok) VALUES
('Laskar Pelangi', 'Andrea Hirata', 1, 5),
('Belajar PHP Dasar', 'Budi Raharjo', 3, 3);
INSERT INTO pengguna (nama, email) VALUES ('Budi Santoso', 'budi@email.com');
