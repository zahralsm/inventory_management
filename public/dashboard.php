
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$nama = $_SESSION["name"] ?? "Pengguna";
$role = $_SESSION["role"] ?? "staff";

$totalProduk = $pdo->query(
    "SELECT COUNT(*) FROM products"
)->fetchColumn();

$totalKategori = $pdo->query(
    "SELECT COUNT(*) FROM categories"
)->fetchColumn();

$totalPemasok = $pdo->query(
    "SELECT COUNT(*) FROM suppliers"
)->fetchColumn();

$totalStok = $pdo->query(
    "SELECT COALESCE(SUM(stock), 0) FROM products"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Management</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        body {
            margin: 0;
            background: #f7f9f6;
            font-family: "Inter", Arial, sans-serif;
            color: #344b39;
        }

        .dashboard-page {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
            box-sizing: border-box;
        }

        /* Header */
        .dashboard-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 25px;
        }

        .dashboard-topbar h1 {
            margin: 0;
            color: #304638;
            font-size: 27px;
            font-weight: 700;
        }

        .dashboard-topbar p {
            margin: 7px 0 0;
            color: #849184;
            font-size: 13px;
            line-height: 1.6;
        }

        .dashboard-topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .dashboard-role {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            background: #edf4ec;
            border: 1px solid #dce8d9;
            border-radius: 10px;
            color: #56745b;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .dashboard-role-dot {
            width: 7px;
            height: 7px;
            background: #789780;
            border-radius: 50%;
        }

        /* Tombol logout */
        .btn-logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 14px;
            background: #fffafa;
            border: 1px solid #e4d8d5;
            border-radius: 10px;
            color: #98665c;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-logout:hover {
            background: #f8eeeb;
        }

        /* Welcome panel */
        .dashboard-welcome {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            padding: 30px;
            margin-bottom: 30px;
            background: linear-gradient(115deg, #e5efe3, #f2f7f0);
            border: 1px solid #dce8d9;
            border-radius: 18px;
        }

        .dashboard-welcome-content {
            min-width: 0;
        }

        .welcome-eyebrow {
            margin-bottom: 11px;
            color: #6d8b70;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.3px;
        }

        .dashboard-welcome h2 {
            margin: 0;
            color: #304638;
            font-size: 25px;
            line-height: 1.4;
        }

        .dashboard-welcome p {
            max-width: 440px;
            margin: 9px 0 0;
            color: #718273;
            font-size: 13px;
            line-height: 1.8;
        }

        .welcome-side {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.65);
            border: 1px solid #dce8d9;
            border-radius: 20px;
            color: #789780;
            font-size: 38px;
        }

        /* Bagian dashboard */
        .dashboard-section {
            margin-bottom: 30px;
        }

        .dashboard-section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 17px;
        }

        .dashboard-section-heading h3 {
            margin: 0;
            color: #344b39;
            font-size: 17px;
        }

        .dashboard-section-heading p {
            margin: 5px 0 0;
            color: #89958a;
            font-size: 12px;
        }

        .section-count {
            padding: 6px 10px;
            background: #f0f5ee;
            border-radius: 8px;
            color: #6d866f;
            font-size: 11px;
        }

        /* Kartu statistik */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .dashboard-stat {
            min-width: 0;
            padding: 21px;
            background: #ffffff;
            border: 1px solid #e3eae1;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(47, 69, 53, 0.035);
            transition: transform 0.2s ease;
        }

        .dashboard-stat:hover {
            transform: translateY(-3px);
            border-color: #c9d9c6;
        }

        .stat-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 21px;
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #edf4ec;
            color: #66856b;
            font-size: 21px;
        }

        .stat-index {
            color: #a1ada1;
            font-size: 10px;
        }

        .stat-label {
            margin-bottom: 8px;
            color: #7f8d80;
            font-size: 12px;
        }

        .stat-number {
            color: #344b39;
            font-size: 30px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .stat-description {
            margin-top: 9px;
            color: #9aa59a;
            font-size: 11px;
        }

        /* Akses cepat */
        .dashboard-quick-links {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .dashboard-quick-link {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
            padding: 19px;
            background: #ffffff;
            border: 1px solid #e3eae1;
            border-radius: 14px;
            color: inherit;
            text-decoration: none;
            transition: background 0.2s ease,
                        border-color 0.2s ease;
        }

        .dashboard-quick-link:hover {
            background: #f5f8f3;
            border-color: #c8d9c5;
        }

        .quick-link-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #edf4ec;
            color: #64836a;
            font-size: 21px;
        }

        .quick-link-content {
            flex: 1;
            min-width: 0;
        }

        .quick-link-content strong {
            display: block;
            margin-bottom: 5px;
            color: #405a45;
            font-size: 13px;
        }

        .quick-link-content span {
            display: block;
            color: #899589;
            font-size: 11px;
            line-height: 1.6;
        }

        .quick-link-arrow {
            color: #7e9a81;
            font-size: 19px;
        }

        /* Catatan */
        .dashboard-note {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 17px;
            background: #f4f7f2;
            border: 1px solid #e6ece3;
            border-radius: 11px;
            color: #7d8a7e;
            font-size: 11px;
            line-height: 1.7;
        }

        .dashboard-note-icon {
            color: #789780;
            font-size: 17px;
        }

        /* Responsif */
        @media (max-width: 1100px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .dashboard-page {
                padding: 20px 14px;
            }

            .dashboard-topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .dashboard-topbar h1 {
                font-size: 24px;
            }

            .dashboard-topbar-actions {
                width: 100%;
                justify-content: space-between;
            }

            .dashboard-welcome {
                padding: 22px;
            }

            .dashboard-welcome h2 {
                font-size: 21px;
            }

            .welcome-side {
                display: none;
            }

            .dashboard-stats {
                gap: 11px;
            }

            .dashboard-stat {
                padding: 15px;
            }

            .stat-card-top {
                margin-bottom: 16px;
            }

            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }

            .stat-number {
                font-size: 25px;
            }

            .dashboard-quick-links {
                grid-template-columns: 1fr;
                gap: 11px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .dashboard-stat,
            .dashboard-quick-link,
            .btn-logout {
                transition: none;
            }
        }
    </style>
</head>

<body>
<?php include __DIR__ . "/includes/sidebar.php"; ?>


<main class="dashboard-page">

    <!-- Header -->
    <header class="dashboard-topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Ringkasan dan informasi inventaris kamu.</p>
        </div>

        <div class="dashboard-topbar-actions">
            <div class="dashboard-role">
                <span class="dashboard-role-dot"></span>
                <?= e(ucfirst($role)) ?> Account
            </div>

            <a href="logout.php"
               class="btn-logout"
               onclick="return confirm('Yakin ingin logout?')">
                ↪ Logout
            </a>
        </div>
    </header>

    <!-- Welcome -->
    <section class="dashboard-welcome">
        <div class="dashboard-welcome-content">
            <div class="welcome-eyebrow">INVENTORY MANAGEMENT</div>

            <h2>Selamat datang, <?= e($nama) ?>!</h2>

            <p>
                Pantau data produk, kategori, pemasok, dan stok
                inventaris kamu melalui satu halaman.
            </p>
        </div>

        <div class="welcome-side" aria-hidden="true">▦</div>
    </section>

    <!-- Statistik -->
    <section class="dashboard-section">
        <div class="dashboard-section-heading">
            <div>
                <h3>Ringkasan Inventaris</h3>
                <p>Informasi terbaru dari database.</p>
            </div>

            <span class="section-count">4 Ringkasan</span>
        </div>

        <div class="dashboard-stats">

            <article class="dashboard-stat">
                <div class="stat-card-top">
                    <div class="stat-icon">▦</div>
                    <span class="stat-index">01</span>
                </div>
                <div class="stat-label">Total Produk</div>
                <div class="stat-number"><?= (int)$totalProduk ?></div>
                <div class="stat-description">Produk terdaftar</div>
            </article>

            <article class="dashboard-stat">
                <div class="stat-card-top">
                    <div class="stat-icon">▤</div>
                    <span class="stat-index">02</span>
                </div>
                <div class="stat-label">Total Kategori</div>
                <div class="stat-number"><?= (int)$totalKategori ?></div>
                <div class="stat-description">Kategori barang</div>
            </article>

            <article class="dashboard-stat">
                <div class="stat-card-top">
                    <div class="stat-icon">♧</div>
                    <span class="stat-index">03</span>
                </div>
                <div class="stat-label">Total Supplier</div>
                <div class="stat-number"><?= (int)$totalPemasok ?></div>
                <div class="stat-description">Pemasok terdaftar</div>
            </article>

            <article class="dashboard-stat">
                <div class="stat-card-top">
                    <div class="stat-icon">▣</div>
                    <span class="stat-index">04</span>
                </div>
                <div class="stat-label">Total Stok</div>
                <div class="stat-number"><?= (int)$totalStok ?></div>
                <div class="stat-description">Jumlah seluruh stok</div>
            </article>

        </div>
    </section>

    <!-- Akses cepat -->
    <section class="dashboard-section">
        <div class="dashboard-section-heading">
            <div>
                <h3>Akses Cepat</h3>
                <p>Pilih menu untuk mengelola inventaris.</p>
            </div>
        </div>

        <div class="dashboard-quick-links">

            <a class="dashboard-quick-link" href="products.php">
                <span class="quick-link-icon">▦</span>
                <span class="quick-link-content">
                    <strong>Data Produk</strong>
                    <span>Lihat, cari, dan kelola produk.</span>
                </span>
                <span class="quick-link-arrow">→</span>
            </a>

            <a class="dashboard-quick-link" href="categories.php">
                <span class="quick-link-icon">▤</span>
                <span class="quick-link-content">
                    <strong>Kategori</strong>
                    <span>Atur kelompok kategori barang.</span>
                </span>
                <span class="quick-link-arrow">→</span>
            </a>

            <a class="dashboard-quick-link" href="suppliers.php">
                <span class="quick-link-icon">♧</span>
                <span class="quick-link-content">
                    <strong>Supplier</strong>
                    <span>Kelola informasi data pemasok.</span>
                </span>
                <span class="quick-link-arrow">→</span>
            </a>

            <a class="dashboard-quick-link" href="stok.php">
                <span class="quick-link-icon">↕</span>
                <span class="quick-link-content">
                    <strong>Mutasi Stok</strong>
                    <span>Catat barang masuk dan keluar.</span>
                </span>
                <span class="quick-link-arrow">→</span>
            </a>

        </div>
    </section>

    <!-- Catatan -->
    <div class="dashboard-note">
        <span class="dashboard-note-icon">ⓘ</span>
        <span>
            Ringkasan inventaris ditampilkan berdasarkan data
            yang tersimpan di database.
        </span>
    </div>

</main>

</body>
</html>
