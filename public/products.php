
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$role = $_SESSION["role"] ?? "staff";
$isAdmin = ($role === "admin");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$cari = trim($_GET["cari"] ?? "");
$urut = $_GET["urut"] ?? "terbaru";

$urutan = [
    "terbaru" => "p.id DESC",
    "nama" => "p.name ASC",
    "harga_asc" => "p.price ASC",
    "harga_desc" => "p.price DESC",
    "stok" => "p.stock ASC"
];

$orderBy = $urutan[$urut] ?? $urutan["terbaru"];

$perHalaman = 5;
$halaman = max(1, (int)($_GET["halaman"] ?? 1));

$sqlJumlah = "SELECT COUNT(*)
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.name LIKE ? OR c.name LIKE ?";

$stmt = $pdo->prepare($sqlJumlah);
$stmt->execute(["%$cari%", "%$cari%"]);
$totalProduk = (int)$stmt->fetchColumn();

$totalHalaman = max(1, (int)ceil($totalProduk / $perHalaman));
$halaman = min($halaman, $totalHalaman);
$offset = ($halaman - 1) * $perHalaman;

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.name LIKE ? OR c.name LIKE ?
        ORDER BY $orderBy
        LIMIT $perHalaman OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$cari%", "%$cari%"]);
$produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pesan = $_SESSION["pesan"] ?? "";
unset($_SESSION["pesan"]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Produk</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        :root {
            --sage: #7c9a83;
            --sage-dark: #587461;
            --mint: #edf5ef;
            --mint-light: #f6faf7;
            --white: #ffffff;
            --text: #34483a;
            --muted: #829087;
            --border: #e2ebe4;
            --danger: #b95c5c;
        }

        * {
            box-sizing: border-box;
        }

        body.products-page {
            margin: 0;
            background: var(--mint-light);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        .products-main {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 34px;
        }

        .products-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 25px;
        }

        .products-eyebrow {
            margin: 0 0 8px;
            color: var(--sage-dark);
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .products-header h1 {
            margin: 0;
            font-size: clamp(25px, 4vw, 32px);
            letter-spacing: -0.7px;
        }

        .products-subtitle {
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            padding: 11px 16px;
            border: 0;
            border-radius: 10px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, transform .2s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--sage);
            color: white;
        }

        .btn-primary:hover {
            background: var(--sage-dark);
        }

        .products-alert {
            padding: 13px 16px;
            margin-bottom: 20px;
            background: #e4f1e7;
            color: #41694b;
            border: 1px solid #cfe3d3;
            border-radius: 10px;
            font-size: 14px;
        }

        .products-summary {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            margin-bottom: 22px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
        }

        .summary-icon {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 13px;
            background: var(--mint);
            color: var(--sage-dark);
            font-size: 23px;
        }

        .summary-label {
            margin: 0 0 5px;
            color: var(--muted);
            font-size: 13px;
        }

        .summary-number {
            margin: 0;
            font-size: 25px;
            font-weight: bold;
        }

        .products-panel {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(64, 91, 70, .04);
        }

        .products-panel-header {
            padding: 22px 22px 18px;
            border-bottom: 1px solid var(--border);
        }

        .products-panel-header h2 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        .products-panel-header p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .products-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 18px 22px;
            background: #fbfdfb;
            border-bottom: 1px solid var(--border);
        }

        .products-filter input,
        .products-filter select {
            min-width: 0;
            padding: 11px 12px;
            border: 1px solid #dce6de;
            border-radius: 9px;
            background: white;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            outline: none;
        }

        .products-filter input {
            flex: 1 1 230px;
        }

        .products-filter select {
            flex: 0 1 180px;
        }

        .products-filter input:focus,
        .products-filter select:focus {
            border-color: var(--sage);
            box-shadow: 0 0 0 3px rgba(124, 154, 131, .13);
        }

        .btn-filter {
            background: var(--mint);
            color: var(--sage-dark);
        }

        .btn-filter:hover {
            background: #dcebe0;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        .products-table th {
            padding: 14px 18px;
            background: #f5f9f5;
            color: #728277;
            font-size: 11px;
            letter-spacing: .6px;
            text-transform: uppercase;
        }

        .products-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #edf2ee;
            font-size: 13px;
        }

        .products-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .products-table tbody tr:hover {
            background: #fbfdfb;
        }

        .product-number {
            color: var(--muted);
        }

        .product-name {
            color: var(--text);
            font-weight: bold;
        }

        .category-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 7px;
            background: var(--mint);
            color: var(--sage-dark);
            font-size: 11px;
        }

        .product-price {
            font-weight: bold;
        }

        .stock-badge {
            display: inline-block;
            min-width: 32px;
            padding: 5px 8px;
            border-radius: 7px;
            background: #edf5ef;
            color: #456b4f;
            text-align: center;
            font-weight: bold;
        }

        .product-actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .btn-small {
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 12px;
        }

        .btn-edit {
            background: #edf3ee;
            color: #4d6c55;
        }

        .btn-delete {
            background: #fff0ef;
            color: var(--danger);
        }

        .empty-state {
            padding: 42px 20px !important;
            color: var(--muted);
            text-align: center;
            white-space: normal;
        }

        .products-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 18px 22px;
            border-top: 1px solid var(--border);
        }

        .pagination-info {
            color: var(--muted);
            font-size: 12px;
        }

        .pagination-actions {
            display: flex;
            gap: 8px;
        }

        .btn-page {
            background: white;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-page.disabled {
            opacity: .45;
            pointer-events: none;
        }

        @media (max-width: 650px) {
            .products-main {
                padding: 22px 14px;
            }

            .products-header {
                align-items: flex-start;
            }

            .products-header .btn {
                width: 100%;
            }

            .products-filter > * {
                width: 100%;
                flex: 1 1 100%;
            }

            .products-table th,
            .products-table td {
                padding: 13px 14px;
            }

            .products-pagination {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body class="products-page">
<?php include __DIR__ . "/includes/sidebar.php"; ?>
<div class="page-back-wrap"><a class="back-link" href="dashboard.php">← Kembali ke Dashboard</a></div>


<main class="products-main">

    <header class="products-header">
        <div>
            <p class="products-eyebrow">Manajemen Inventaris</p>
            <h1>Data Produk</h1>
            <p class="products-subtitle">
                Kelola daftar produk dan pantau stok barang kamu.
            </p>
        </div>

        <?php if ($isAdmin): ?>
            <a class="btn btn-primary" href="tambah_produk.php">
                + Tambah Produk
            </a>
        <?php endif; ?>
    </header>

    <?php if ($pesan !== ""): ?>
        <div class="products-alert">
            <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <section class="products-summary">
        <div class="summary-icon">▦</div>
        <div>
            <p class="summary-label">Total produk ditemukan</p>
            <p class="summary-number"><?= $totalProduk ?></p>
        </div>
    </section>

    <section class="products-panel">
        <div class="products-panel-header">
            <h2>Daftar Produk</h2>
            <p>Gunakan pencarian atau pengurutan untuk menemukan produk.</p>
        </div>

        <form method="GET" class="products-filter">
            <input
                type="text"
                name="cari"
                placeholder="Cari nama atau kategori..."
                value="<?= e($cari) ?>"
            >

            <select name="urut">
                <option value="terbaru" <?= $urut === "terbaru" ? "selected" : "" ?>>Terbaru</option>
                <option value="nama" <?= $urut === "nama" ? "selected" : "" ?>>Nama A-Z</option>
                <option value="harga_asc" <?= $urut === "harga_asc" ? "selected" : "" ?>>Harga termurah</option>
                <option value="harga_desc" <?= $urut === "harga_desc" ? "selected" : "" ?>>Harga termahal</option>
                <option value="stok" <?= $urut === "stok" ? "selected" : "" ?>>Stok terendah</option>
            </select>

            <button type="submit" class="btn btn-filter">
                Cari / Urutkan
            </button>
        </form>

        <div class="table-scroll">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <?php if ($isAdmin): ?>
                            <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$produk): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 6 : 5 ?>" class="empty-state">
                            Belum ada produk yang sesuai dengan pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produk as $i => $p): ?>
                        <tr>
                            <td class="product-number">
                                <?= $offset + $i + 1 ?>
                            </td>

                            <td class="product-name">
                                <?= e($p["name"]) ?>
                            </td>

                            <td>
                                <span class="category-badge">
                                    <?= e($p["category_name"] ?? "Belum berkategori") ?>
                                </span>
                            </td>

                            <td class="product-price">
                                Rp <?= number_format((float)$p["price"], 0, ",", ".") ?>
                            </td>

                            <td>
                                <span class="stock-badge">
                                    <?= (int)$p["stock"] ?>
                                </span>
                            </td>

                            <?php if ($isAdmin): ?>
                                <td>
                                    <div class="product-actions">
                                        <a
                                            class="btn btn-small btn-edit"
                                            href="edit_produk.php?id=<?= (int)$p["id"] ?>"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="hapus_produk.php"
                                            onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$p["id"] ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION["csrf_token"]) ?>"
                                            >
                                            <button
                                                type="submit"
                                                class="btn btn-small btn-delete"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="products-pagination">
            <span class="pagination-info">
                Halaman <?= $halaman ?> dari <?= $totalHalaman ?>
                · <?= $totalProduk ?> produk
            </span>

            <div class="pagination-actions">
                <?php
                $queryDasar = [
                    "cari" => $cari,
                    "urut" => $urut
                ];
                ?>

                <?php if ($halaman > 1): ?>
                    <a
                        class="btn btn-small btn-page"
                        href="?<?= e(http_build_query($queryDasar + ["halaman" => $halaman - 1])) ?>"
                    >
                        ← Sebelumnya
                    </a>
                <?php endif; ?>

                <?php if ($halaman < $totalHalaman): ?>
                    <a
                        class="btn btn-small btn-page"
                        href="?<?= e(http_build_query($queryDasar + ["halaman" => $halaman + 1])) ?>"
                    >
                        Berikutnya →
                    </a>
                <?php endif; ?>
            </div>
        </footer>
    </section>

</main>

</body>
</html>