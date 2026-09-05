<?php
// Header agar output berupa JSON
header("Content-Type: application/json; charset=UTF-8");

// 1. Koneksi Database SQL via Laragon (Default: root / tanpa password)
$host = "localhost";
$user = "root";
$pass = "kusuma16";
$db   = "rest_api";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Koneksi database gagal"]);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$input  = json_decode(file_get_contents("php://input"), true) ?? [];

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function validateRequired(array $input, array $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        respond([
            "status"  => "error",
            "message" => "Field wajib diisi: " . implode(', ', $missing)
        ], 422);
    }
}

function requireId($id) {
    if (!$id) {
        respond(["status" => "error", "message" => "Parameter id wajib disertakan"], 400);
    }
}


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

                if (!$data) {
                    respond(["status" => "error", "message" => "Kategori tidak ditemukan"], 404);
                }
                respond(["status" => "success", "data" => $data]);
            } else {
                $result = $conn->query("SELECT * FROM kategori ORDER BY id DESC");
                respond(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
            }

        } elseif ($method === 'POST') {
            validateRequired($input, ['nama_kategori']);

            $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
            $stmt->bind_param("s", $input['nama_kategori']);

            if ($stmt->execute()) {
                respond(["status" => "success", "message" => "Kategori berhasil ditambahkan", "id" => $conn->insert_id], 201);
            } else {
                respond(["status" => "error", "message" => "Gagal menambahkan kategori: " . $stmt->error], 500);
            }

        } elseif ($method === 'PUT') {
            requireId($id);
            validateRequired($input, ['nama_kategori']);

            $stmt = $conn->prepare("UPDATE kategori SET nama_kategori = ? WHERE id = ?");
            $stmt->bind_param("si", $input['nama_kategori'], $id);

            if (!$stmt->execute()) {
                respond(["status" => "error", "message" => "Gagal mengupdate kategori: " . $stmt->error], 500);
            }
            if ($stmt->affected_rows === 0) {
                respond(["status" => "error", "message" => "Kategori tidak ditemukan"], 404);
            }
            respond(["status" => "success", "message" => "Kategori berhasil diupdate"]);

        } elseif ($method === 'DELETE') {
            requireId($id);

            $stmt = $conn->prepare("DELETE FROM kategori WHERE id = ?");
            $stmt->bind_param("i", $id);

            if (!$stmt->execute()) {
                respond(["status" => "error", "message" => "Gagal menghapus kategori: " . $stmt->error], 500);
            }
            if ($stmt->affected_rows === 0) {
                respond(["status" => "error", "message" => "Kategori tidak ditemukan"], 404);
            }
            respond(["status" => "success", "message" => "Kategori berhasil dihapus"]);

        } else {
            respond(["status" => "error", "message" => "Method tidak diizinkan"], 405);
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

                if (!$data) {
                    respond(["status" => "error", "message" => "Buku tidak ditemukan"], 404);
                }
                respond(["status" => "success", "data" => $data]);
            } else {
                $sql = "SELECT b.*, k.nama_kategori FROM buku b
                        LEFT JOIN kategori k ON b.id_kategori = k.id
                        ORDER BY b.id DESC";
                $result = $conn->query($sql);
                respond(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
            }

        } elseif ($method === 'POST') {
            validateRequired($input, ['judul', 'id_kategori', 'stok']);

            $stmt = $conn->prepare("INSERT INTO buku (judul, penulis, id_kategori, stok) VALUES (?, ?, ?, ?)");
            $penulis = $input['penulis'] ?? null;
            $stmt->bind_param("ssii", $input['judul'], $penulis, $input['id_kategori'], $input['stok']);

            if ($stmt->execute()) {
                respond(["status" => "success", "message" => "Buku berhasil ditambahkan", "id" => $conn->insert_id], 201);
            } else {
                respond(["status" => "error", "message" => "Gagal menambahkan buku: " . $stmt->error], 500);
            }

        } elseif ($method === 'PUT') {
            requireId($id);
            validateRequired($input, ['judul', 'id_kategori', 'stok']);

            $stmt = $conn->prepare("UPDATE buku SET judul = ?, penulis = ?, id_kategori = ?, stok = ? WHERE id = ?");
            $penulis = $input['penulis'] ?? null;
            $stmt->bind_param("ssiii", $input['judul'], $penulis, $input['id_kategori'], $input['stok'], $id);

            if (!$stmt->execute()) {
                respond(["status" => "error", "message" => "Gagal mengupdate buku: " . $stmt->error], 500);
            }
            if ($stmt->affected_rows === 0) {
                respond(["status" => "error", "message" => "Buku tidak ditemukan atau tidak ada perubahan"], 404);
            }
            respond(["status" => "success", "message" => "Buku berhasil diupdate"]);

        } elseif ($method === 'DELETE') {
            requireId($id);

            $stmt = $conn->prepare("DELETE FROM buku WHERE id = ?");
            $stmt->bind_param("i", $id);

            if (!$stmt->execute()) {
                respond(["status" => "error", "message" => "Gagal menghapus buku: " . $stmt->error], 500);
            }
            if ($stmt->affected_rows === 0) {
                respond(["status" => "error", "message" => "Buku tidak ditemukan"], 404);
            }
            respond(["status" => "success", "message" => "Buku berhasil dihapus"]);

        } else {
            respond(["status" => "error", "message" => "Method tidak diizinkan"], 405);
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

                if (!$data) {
                    respond(["status" => "error", "message" => "Pengguna tidak ditemukan"], 404);
                }
                respond(["status" => "success", "data" => $data]);
            } else {
                $result = $conn->query("SELECT * FROM pengguna ORDER BY id DESC");
                respond(["status" => "success", "data" => $result->fetch_all(MYSQLI_ASSOC)]);
            }

        } elseif ($method === 'POST') {
            validateRequired($input, ['nama', 'email']);

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                respond(["status" => "error", "message" => "Format email tidak valid"], 422);
            }

            $stmt = $conn->prepare("INSERT INTO pengguna (nama, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $input['nama'], $input['email']);

            if ($stmt->execute()) {
                respond(["status" => "success", "message" => "Pengguna berhasil ditambahkan", "id" => $conn->insert_id], 201);
            } else {
                // errno 1062 = duplicate entry (email unique constraint)
                if ($conn->errno === 1062) {
                    respond(["status" => "error", "message" => "Email sudah terdaftar"], 409);
                }
                respond(["status" => "error", "message" => "Gagal menambahkan pengguna: " . $stmt->error], 500);
            }

        } elseif ($method === 'PUT') {
            requireId($id);
            validateRequired($input, ['nama', 'email']);

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                respond(["status" => "error", "message" => "Format email tidak valid"], 422);
            }

            $stmt = $conn->prepare("UPDATE pengguna SET nama = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $input['nama'], $input['email'], $id);

            if (!$stmt->execute()) {
                if ($conn->errno === 1062) {
                    respond(["status" => "error", "message" => "Email sudah terdaftar"], 409);
                }
                respond(["status" => "error", "message" => "Gagal mengupdate pengguna: " . $stmt->error], 500);
            }
            if ($stmt->affected_rows === 0) {
                respond(["status" => "error", "message" => "Pengguna tidak ditemukan atau tidak ada perubahan"], 404);
            }
            respond(["status" => "success", "message" => "Pengguna berhasil diupdate"]);

        } elseif ($method === 'DELETE') {
            requireId($id);

            $stmt = $conn->prepare("DELETE FROM pengguna WHERE id = ?");
            $stmt->bind_param("i", $id);

            if (!$stmt->execute()) {
                respond(["status" => "error", "message" => "Gagal menghapus pengguna: " . $stmt->error], 500);
            }
            if ($stmt->affected_rows === 0) {
                respond(["status" => "error", "message" => "Pengguna tidak ditemukan"], 404);
            }
            respond(["status" => "success", "message" => "Pengguna berhasil dihapus"]);

        } else {
            respond(["status" => "error", "message" => "Method tidak diizinkan"], 405);
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

                if (!$data) {
                    respond(["status" => "error", "message" => "Transaksi tidak ditemukan"], 404);
                }
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
            validateRequired($input, ['id_pengguna', 'id_buku', 'jenis_transaksi']);

            if (!in_array($input['jenis_transaksi'], ['keluar', 'masuk'], true)) {
                respond(["status" => "error", "message" => "jenis_transaksi harus 'keluar' atau 'masuk'"], 422);
            }
-
            $stmtCek = $conn->prepare("SELECT stok FROM buku WHERE id = ?");
            $stmtCek->bind_param("i", $input['id_buku']);
            $stmtCek->execute();
            $bukuData = $stmtCek->get_result()->fetch_assoc();

            if (!$bukuData) {
                respond(["status" => "error", "message" => "id_buku tidak valid"], 422);
            }

            if ($input['jenis_transaksi'] === 'keluar' && $bukuData['stok'] <= 0) {
                respond(["status" => "error", "message" => "Stok buku habis, tidak bisa dipinjam"], 422);
            }

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO transaksi_buku (id_pengguna, id_buku, jenis_transaksi) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $input['id_pengguna'], $input['id_buku'], $input['jenis_transaksi']);
                $stmt->execute();

                if ($input['jenis_transaksi'] === 'keluar') {
                    $stmtStok = $conn->prepare("UPDATE buku SET stok = stok - 1 WHERE id = ? AND stok > 0");
                } else {
                    $stmtStok = $conn->prepare("UPDATE buku SET stok = stok + 1 WHERE id = ?");
                }
                $stmtStok->bind_param("i", $input['id_buku']);
                $stmtStok->execute();


                $conn->commit();

                respond([
                    "status"  => "success",
                    "message" => "Transaksi " . $input['jenis_transaksi'] . " buku dicatat, stok diperbarui",
                    "id"      => $conn->insert_id
                ], 201);
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();

                if ($conn->errno === 1452) {
                    respond(["status" => "error", "message" => "id_pengguna atau id_buku tidak valid"], 422);
                }
                respond(["status" => "error", "message" => "Gagal mencatat transaksi: " . $e->getMessage()], 500);
            }

        } elseif ($method === 'PUT') {
            requireId($id);
            validateRequired($input, ['id_pengguna', 'id_buku', 'jenis_transaksi']);

            if (!in_array($input['jenis_transaksi'], ['keluar', 'masuk'], true)) {
                respond(["status" => "error", "message" => "jenis_transaksi harus 'keluar' atau 'masuk'"], 422);
            }

            $stmt = $conn->prepare("UPDATE transaksi_buku SET id_pengguna = ?, id_buku = ?, jenis_transaksi = ? WHERE id = ?");
            $stmt->bind_param("iisi", $input['id_pengguna'], $input['id_buku'], $input['jenis_transaksi'], $id);

            if (!$stmt->execute()) {
                if ($conn->errno === 1452) {
                    respond(["status" => "error", "message" => "id_pengguna atau id_buku tidak valid"], 422);
                }
                respond(["status" => "error", "message" => "Gagal mengupdate transaksi: " . $stmt->error], 500);
            }
            if ($stmt->affected_rows === 0) {
                respond(["status" => "error", "message" => "Transaksi tidak ditemukan atau tidak ada perubahan"], 404);
            }
            respond(["status" => "success", "message" => "Transaksi berhasil diupdate"]);

        } elseif ($method === 'DELETE') {
            requireId($id);

            $stmt = $conn->prepare("DELETE FROM transaksi_buku WHERE id = ?");
            $stmt->bind_param("i", $id);

            if (!$stmt->execute()) {
                respond(["status" => "error", "message" => "Gagal menghapus transaksi: " . $stmt->error], 500);
            }
            if ($stmt->affected_rows === 0) {
                respond(["status" => "error", "message" => "Transaksi tidak ditemukan"], 404);
            }
            respond(["status" => "success", "message" => "Transaksi berhasil dihapus"]);

        } else {
            respond(["status" => "error", "message" => "Method tidak diizinkan"], 405);
        }
        break;

    default:
        respond([
            "status"  => "error",
            "message" => "Action tidak valid. Gunakan ?action=kategori, ?action=buku, ?action=pengguna, atau ?action=transaksi"
        ], 400);
}

$conn->close();
