<?php
// Header agar output berupa JSON
header("Content-Type: application/json; charset=UTF-8");

// 1. Koneksi Database SQL via Laragon (Default: root / tanpa password)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "rest_api";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Koneksi database gagal"]);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$input  = json_decode(file_get_contents("php://input"), true);

// Helper: kirim respons JSON lalu berhenti
function respond($data) {
    echo json_encode($data);
    exit;
}

// 2. Routing Fitur (4 resource: kategori, buku, pengguna, transaksi)
switch ($action) {

    // ============================
    // FITUR 1: KATEGORI
    // ============================
    case 'kategori':
        if ($method === 'GET') {
            if ($id) {
                $stmt = $conn->prepare("SELECT * FROM kategori WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $data = $stmt->get_result()->fetch_assoc();
                respond(["status" => "success", "data" => $data]);
            } else {
                $result = $conn->query("SELECT * FROM kategori ORDER BY id DESC");
                respond(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
            }
        } elseif ($method === 'POST') {
            $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
            $stmt->bind_param("s", $input['nama_kategori']);
            $stmt->execute();
            respond(["status" => "success", "message" => "Kategori berhasil ditambahkan", "id" => $conn->insert_id]);
        } elseif ($method === 'PUT') {
            $stmt = $conn->prepare("UPDATE kategori SET nama_kategori = ? WHERE id = ?");
            $stmt->bind_param("si", $input['nama_kategori'], $id);
            $stmt->execute();
            respond(["status" => "success", "message" => "Kategori berhasil diupdate"]);
        } elseif ($method === 'DELETE') {
            $stmt = $conn->prepare("DELETE FROM kategori WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            respond(["status" => "success", "message" => "Kategori berhasil dihapus"]);
        }
        break;

    // ============================
    // FITUR 2: BUKU
    // ============================
    case 'buku':
        if ($method === 'GET') {
            if ($id) {
                $stmt = $conn->prepare(
                    "SELECT b.*, k.nama_kategori FROM buku b
                     LEFT JOIN kategori k ON b.id_kategori = k.id
                     WHERE b.id = ?"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $data = $stmt->get_result()->fetch_assoc();
                respond(["status" => "success", "data" => $data]);
            } else {
                $sql = "SELECT b.*, k.nama_kategori FROM buku b
                        LEFT JOIN kategori k ON b.id_kategori = k.id
                        ORDER BY b.id DESC";
                $result = $conn->query($sql);
                respond(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
            }
        } elseif ($method === 'POST') {
            $stmt = $conn->prepare("INSERT INTO buku (judul, penulis, id_kategori, stok) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $input['judul'], $input['penulis'], $input['id_kategori'], $input['stok']);
            $stmt->execute();
            respond(["status" => "success", "message" => "Buku berhasil ditambahkan", "id" => $conn->insert_id]);
        } elseif ($method === 'PUT') {
            $stmt = $conn->prepare("UPDATE buku SET judul = ?, penulis = ?, id_kategori = ?, stok = ? WHERE id = ?");
            $stmt->bind_param("ssiii", $input['judul'], $input['penulis'], $input['id_kategori'], $input['stok'], $id);
            $stmt->execute();
            respond(["status" => "success", "message" => "Buku berhasil diupdate"]);
        } elseif ($method === 'DELETE') {
            $stmt = $conn->prepare("DELETE FROM buku WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            respond(["status" => "success", "message" => "Buku berhasil dihapus"]);
        }
        break;

    // ============================
    // FITUR 3: DATA PENGGUNA
    // ============================
    case 'pengguna':
        if ($method === 'GET') {
            if ($id) {
                $stmt = $conn->prepare("SELECT * FROM pengguna WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $data = $stmt->get_result()->fetch_assoc();
                respond(["status" => "success", "data" => $data]);
            } else {
                $result = $conn->query("SELECT * FROM pengguna ORDER BY id DESC");
                respond(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
            }
        } elseif ($method === 'POST') {
            $stmt = $conn->prepare("INSERT INTO pengguna (nama, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $input['nama'], $input['email']);
            $stmt->execute();
            respond(["status" => "success", "message" => "Pengguna berhasil ditambahkan", "id" => $conn->insert_id]);
        } elseif ($method === 'PUT') {
            $stmt = $conn->prepare("UPDATE pengguna SET nama = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $input['nama'], $input['email'], $id);
            $stmt->execute();
            respond(["status" => "success", "message" => "Pengguna berhasil diupdate"]);
        } elseif ($method === 'DELETE') {
            $stmt = $conn->prepare("DELETE FROM pengguna WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            respond(["status" => "success", "message" => "Pengguna berhasil dihapus"]);
        }
        break;

    // ============================
    // FITUR 4: KELUAR & MASUK BUKU
    // ============================
    case 'transaksi':
        if ($method === 'GET') {
            if ($id) {
                $sql = "SELECT t.id, t.id_pengguna, p.nama AS peminjam, t.id_buku, b.judul AS judul_buku,
                               t.jenis_transaksi, t.tanggal
                        FROM transaksi_buku t
                        JOIN pengguna p ON t.id_pengguna = p.id
                        JOIN buku b ON t.id_buku = b.id
                        WHERE t.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $data = $stmt->get_result()->fetch_assoc();
                respond(["status" => "success", "data" => $data]);
            } else {
                $sql = "SELECT t.id, t.id_pengguna, p.nama AS peminjam, t.id_buku, b.judul AS judul_buku,
                               t.jenis_transaksi, t.tanggal
                        FROM transaksi_buku t
                        JOIN pengguna p ON t.id_pengguna = p.id
                        JOIN buku b ON t.id_buku = b.id
                        ORDER BY t.tanggal DESC";
                $result = $conn->query($sql);
                respond(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
            }
        } elseif ($method === 'POST') {
            $stmt = $conn->prepare("INSERT INTO transaksi_buku (id_pengguna, id_buku, jenis_transaksi) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $input['id_pengguna'], $input['id_buku'], $input['jenis_transaksi']);
            $stmt->execute();
            respond(["status" => "success", "message" => "Transaksi " . $input['jenis_transaksi'] . " buku dicatat", "id" => $conn->insert_id]);
        } elseif ($method === 'PUT') {
            $stmt = $conn->prepare("UPDATE transaksi_buku SET id_pengguna = ?, id_buku = ?, jenis_transaksi = ? WHERE id = ?");
            $stmt->bind_param("iisi", $input['id_pengguna'], $input['id_buku'], $input['jenis_transaksi'], $id);
            $stmt->execute();
            respond(["status" => "success", "message" => "Transaksi berhasil diupdate"]);
        } elseif ($method === 'DELETE') {
            $stmt = $conn->prepare("DELETE FROM transaksi_buku WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            respond(["status" => "success", "message" => "Transaksi berhasil dihapus"]);
        }
        break;

    default:
        respond(["status" => "error", "message" => "Action tidak valid. Gunakan ?action=kategori, ?action=buku, ?action=pengguna, atau ?action=transaksi"]);
}

$conn->close();