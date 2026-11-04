
<?php
session_start();
require_once "../config/database.php";

// Cek login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$pesan = "";
$error = "";
$role = $_SESSION["role"] ?? "Staff";

// Token keamanan form
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Proses transaksi stok
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";
    } else {
        $product_id = filter_var(
            $_POST["product_id"] ?? "",
            FILTER_VALIDATE_INT
        );

        $tipe = $_POST["tipe"] ?? "";

        $jumlah = filter_var(
            $_POST["jumlah"] ?? "",
            FILTER_VALIDATE_INT
        );

        $keterangan = trim($_POST["keterangan"] ?? "");

        if ($product_id === false || $product_id === null || $product_id <= 0) {
            $error = "Silakan pilih produk.";
        } elseif (!in_array($tipe, ["IN", "OUT"], true)) {
            $error = "Jenis transaksi tidak valid.";
        } elseif ($jumlah === false || $jumlah === null || $jumlah <= 0) {
            $error = "Jumlah stok harus berupa bilangan bulat lebih dari 0.";
        } else {
            try {
                $pdo->beginTransaction();

                // Ambil dan kunci stok produk
                $stmt = $pdo->prepare(
                    "SELECT stock FROM products
                     WHERE id = ? FOR UPDATE"
                );
                $stmt->execute([$product_id]);
                $produkDipilih = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$produkDipilih) {
                    throw new RuntimeException("Produk tidak ditemukan.");
                }

                $stokSekarang = (int) $produkDipilih["stock"];

                if ($tipe === "OUT" && $jumlah > $stokSekarang) {
                    throw new RuntimeException("Stok tidak mencukupi.");
                }

                if ($tipe === "IN") {
                    $stokBaru = $stokSekarang + $jumlah;
                } else {
                    $stokBaru = $stokSekarang - $jumlah;
                }

                // Perbarui stok produk
                $stmt = $pdo->prepare(
                    "UPDATE products SET stock = ? WHERE id = ?"
                );
                $stmt->execute([$stokBaru, $product_id]);

                // Simpan riwayat transaksi
                $stmt = $pdo->prepare(
                    "INSERT INTO stock_movements
                    (product_id, user_id, type, quantity, note, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())"
                );

                $stmt->execute([
                    $product_id,
                    $_SESSION["user_id"],
                    $tipe,
                    $jumlah,
                    $keterangan
                ]);

                $pdo->commit();

                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                $_SESSION["stok_pesan"] = "Transaksi stok berhasil disimpan.";

                header("Location: stok.php");
                exit;

            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Transaksi gagal disimpan. Periksa kembali database.";
            }
        }
    }
}

// Pesan setelah transaksi berhasil
if (isset($_SESSION["stok_pesan"])) {
    $pesan = $_SESSION["stok_pesan"];
    unset($_SESSION["stok_pesan"]);
}

// Ambil daftar produk
$produk = $pdo->query(
    "SELECT id, name, stock
     FROM products
     ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Ambil riwayat transaksi terbaru
$riwayat = $pdo->query(
    "SELECT sm.*, p.name AS product_name, u.name AS user_name
     FROM stock_movements sm
     JOIN products p ON sm.product_id = p.id
     JOIN users u ON sm.user_id = u.id
     ORDER BY sm.id DESC
     LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

// Ringkasan transaksi
$totalMasuk = 0;
$totalKeluar = 0;

foreach ($riwayat as $r) {
    if ($r["type"] === "IN") {
        $totalMasuk += (int) $r["quantity"];
    } elseif ($r["type"] === "OUT") {
        $totalKeluar += (int) $r["quantity"];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Masuk dan Keluar</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f8f5;
            color: #34483d;
            font-family: Arial, sans-serif;
        }

        .stock-page {
            width: min(1200px, 100%);
            margin: 0 auto;
            padding: 34px 26px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 27px;
        }

        .page-heading h1 {
            margin: 0 0 8px;
            color: #304b3b;
            font-size: 28px;
        }

        .page-heading p {
            margin: 0;
            color: #7b8c80;
            font-size: 14px;
            line-height: 1.6;
        }

        .user-label {
            padding: 9px 15px;
            background: #e5efe6;
            color: #42674d;
            border-radius: 20px;
            font-size: 13px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .summary-card {
            padding: 21px 23px;
            background: #fff;
            border: 1px solid #e4ece4;
            border-radius: 15px;
        }

        .summary-card span {
            color: #7b8c80;
            font-size: 13px;
        }

        .summary-card h2 {
            margin: 12px 0 0;
            font-size: 28px;
            color: #4c7955;
        }

        .summary-card.out h2 {
            color: #b17a48;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(280px, 0.8fr) minmax(0, 1.5fr);
            gap: 22px;
            align-items: start;
        }

        .panel {
            min-width: 0;
            padding: 23px;
            background: #fff;
            border: 1px solid #e4ece4;
            border-radius: 16px;
        }

        .panel-heading {
            margin: 0 0 7px;
            color: #304b3b;
            font-size: 19px;
        }

        .panel-description {
            margin: 0 0 22px;
            color: #89968c;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4b6050;
            font-size: 13px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            min-height: 42px;
            padding: 11px 12px;
            border: 1px solid #dce6dc;
            border-radius: 9px;
            background: #fff;
            color: #34483d;
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }

        .form-control:focus {
            border-color: #8eae94;
            box-shadow: 0 0 0 3px #edf4ed;
        }

        textarea.form-control {
            min-height: 95px;
            resize: vertical;
        }

        .form-help {
            margin-top: 7px;
            color: #89968c;
            font-size: 11px;
            line-height: 1.5;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 11px 15px;
            border: none;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary {
            background: #527c5a;
            color: #fff;
        }

        .btn-primary:hover {
            background: #416849;
        }

        .btn-light {
            background: #e5efe6;
            color: #42674d;
        }

        .btn-light:hover {
            background: #d5e6d7;
        }

        .btn-full {
            width: 100%;
        }

        .message {
            padding: 13px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.6;
        }

        .message-success {
            background: #e6f2e7;
            color: #3d7047;
            border: 1px solid #d2e8d5;
        }

        .message-error {
            background: #fbe9e7;
            color: #a94d49;
            border: 1px solid #f3d7d4;
        }

        .history-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .history-top .panel-heading {
            margin-bottom: 0;
        }

        .history-count {
            padding: 6px 10px;
            border-radius: 20px;
            background: #edf4ed;
            color: #55745b;
            font-size: 11px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 580px;
        }

        th {
            padding: 12px 10px;
            background: #f2f6f1;
            color: #647768;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
        }

        td {
            padding: 14px 10px;
            border-bottom: 1px solid #edf1ec;
            font-size: 12px;
            line-height: 1.5;
            vertical-align: top;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .product-name {
            color: #354d3b;
            font-weight: 600;
        }

        .type-badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 20px;
            white-space: nowrap;
            font-size: 11px;
            font-weight: 600;
        }

        .type-in {
            background: #e3f1e4;
            color: #47734e;
        }

        .type-out {
            background: #fff0d9;
            color: #9b6c2f;
        }

        .quantity-in {
            color: #47734e;
            font-weight: 700;
        }

        .quantity-out {
            color: #b17a36;
            font-weight: 700;
        }

        .empty-state {
            padding: 30px 15px;
            text-align: center;
            color: #89968c;
        }

        .back-link {
            display: inline-block;
            margin-top: 22px;
            color: #55745b;
            font-size: 13px;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .stock-page {
                padding: 23px 14px;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .summary-grid {
                gap: 10px;
            }

            .summary-card {
                padding: 16px;
            }

            .summary-card h2 {
                font-size: 24px;
            }

            .panel {
                padding: 17px;
            }
        }
    </style>
</head>

<body>
<?php include __DIR__ . "/includes/sidebar.php"; ?>

<main class="stock-page">

    <header class="page-heading">
        <div>
            <h1>Stok Masuk & Keluar</h1>
            <p>Catat pergerakan stok dan pantau riwayat persediaan produk.</p>
        </div>

        <div class="user-label">
            <?= htmlspecialchars($_SESSION["name"] ?? "Pengguna", ENT_QUOTES, "UTF-8") ?>
            · <?= htmlspecialchars($role, ENT_QUOTES, "UTF-8") ?>
        </div>
    </header>

    <?php if ($pesan !== ""): ?>
        <div class="message message-success">
            <?= htmlspecialchars($pesan, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="message message-error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="summary-grid">
        <article class="summary-card">
            <span>Total Unit Stok Masuk (20 transaksi terakhir)</span>
            <h2>+<?= number_format($totalMasuk) ?></h2>
        </article>

        <article class="summary-card out">
            <span>Total Unit Stok Keluar (20 transaksi terakhir)</span>
            <h2>−<?= number_format($totalKeluar) ?></h2>
        </article>
    </section>

    <section class="content-grid">

        <div class="panel">
            <h2 class="panel-heading">Catat Transaksi</h2>
            <p class="panel-description">
                Pilih produk dan masukkan jumlah stok yang ingin ditambah atau dikurangi.
            </p>

            <form method="POST" action="stok.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"
                >

                <div class="form-group">
                    <label for="product_id">Pilih Produk</label>
                    <select
                        class="form-control"
                        name="product_id"
                        id="product_id"
                        required
                    >
                        <option value="">-- Pilih Produk --</option>

                        <?php foreach ($produk as $p): ?>
                            <option value="<?= (int) $p["id"] ?>">
                                <?= htmlspecialchars($p["name"], ENT_QUOTES, "UTF-8") ?>
                                (Stok: <?= (int) $p["stock"] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tipe">Jenis Transaksi</label>
                    <select class="form-control" name="tipe" id="tipe" required>
                        <option value="IN">Stok Masuk</option>
                        <option value="OUT">Stok Keluar</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah">Jumlah Stok</label>
                    <input
                        class="form-control"
                        type="number"
                        name="jumlah"
                        id="jumlah"
                        min="1"
                        step="1"
                        placeholder="Masukkan jumlah stok"
                        required
                    >
                    <div class="form-help">
                        Jumlah harus berupa bilangan bulat lebih dari nol.
                    </div>
                </div>

                <div class="form-group">
                    <label for="keterangan">Keterangan (Opsional)</label>
                    <textarea
                        class="form-control"
                        name="keterangan"
                        id="keterangan"
                        rows="3"
                        maxlength="1000"
                        placeholder="Contoh: Pembelian barang atau penjualan"
                    ></textarea>
                </div>

                <button class="btn btn-primary btn-full" type="submit">
                    Simpan Transaksi
                </button>
            </form>
        </div>

        <div class="panel">
            <div class="history-top">
                <h2 class="panel-heading">Riwayat Stok</h2>
                <span class="history-count">20 transaksi terbaru</span>
            </div>

            <p class="panel-description">
                Catatan perubahan stok yang sudah tersimpan di database.
            </p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                            <th>Pengguna</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($riwayat)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                Belum ada riwayat transaksi.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($riwayat as $r): ?>
                            <?php $isMasuk = ($r["type"] === "IN"); ?>
                            <tr>
                                <td class="product-name">
                                    <?= htmlspecialchars($r["product_name"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <span class="type-badge <?= $isMasuk ? "type-in" : "type-out" ?>">
                                        <?= $isMasuk ? "Masuk" : "Keluar" ?>
                                    </span>
                                </td>

                                <td class="<?= $isMasuk ? "quantity-in" : "quantity-out" ?>">
                                    <?= $isMasuk ? "+" : "−" ?><?= number_format((int) $r["quantity"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r["note"] ?? "-", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r["user_name"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r["created_at"], ENT_QUOTES, "UTF-8") ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

    <a class="back-link" href="dashboard.php">← Kembali ke Dashboard</a>

</main>
</body>
</html>