
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

$role = $_SESSION["role"] ?? "staff";
$isAdmin = ($role === "admin");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$errors = [];
$name = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$isAdmin) {
        http_response_code(403);
        exit("Akses ditolak! Hanya Admin yang boleh menambahkan kategori.");
    }

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        $errors[] = "Permintaan tidak valid.";
    }

    $name = trim($_POST["name"] ?? "");

    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = "Nama kategori harus 2–100 karakter.";
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO categories (name) VALUES (?)"
            );
            $stmt->execute([$name]);

            $_SESSION["pesan"] = "Kategori berhasil ditambahkan.";
            header("Location: categories.php");
            exit;
        } catch (PDOException $error) {
            $errors[] = "Kategori gagal ditambahkan. Mungkin nama kategori sudah ada.";
        }
    }
}

$pesan = $_SESSION["pesan"] ?? "";
unset($_SESSION["pesan"]);

$categories = $pdo->query(
    "SELECT c.id, c.name, COUNT(p.id) AS total_produk
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY c.name ASC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Kategori</title>
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
            --red-bg: #fff0ef;
            --red-text: #a94f4f;
        }

        * {
            box-sizing: border-box;
        }

        body.categories-page {
            margin: 0;
            background: var(--mint-light);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        .categories-main {
            width: min(1100px, 100%);
            margin: 0 auto;
            padding: 34px;
        }

        .categories-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 26px;
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--sage-dark);
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .categories-header h1 {
            margin: 0;
            font-size: clamp(25px, 4vw, 32px);
            letter-spacing: -.5px;
        }

        .subtitle {
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .role-label {
            padding: 9px 13px;
            border-radius: 9px;
            background: var(--mint);
            color: var(--sage-dark);
            font-size: 12px;
            font-weight: bold;
        }

        .notice {
            padding: 13px 16px;
            margin-bottom: 18px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.5;
        }

        .notice-success {
            background: #e4f1e7;
            border: 1px solid #cfe3d3;
            color: #41694b;
        }

        .notice-error {
            background: var(--red-bg);
            border: 1px solid #f1d7d5;
            color: var(--red-text);
        }

        .categories-grid {
            display: grid;
            grid-template-columns: minmax(250px, .85fr) minmax(0, 1.5fr);
            gap: 20px;
            align-items: start;
        }

        .panel {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            box-shadow: 0 5px 18px rgba(64, 91, 70, .04);
        }

        .panel-header {
            padding: 21px;
            border-bottom: 1px solid var(--border);
        }

        .panel-header h2 {
            margin: 0 0 7px;
            font-size: 17px;
        }

        .panel-header p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .category-form {
            padding: 21px;
        }

        .category-form label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .category-form input[type="text"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #dce6de;
            border-radius: 9px;
            background: white;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            outline: none;
        }

        .category-form input[type="text"]:focus {
            border-color: var(--sage);
            box-shadow: 0 0 0 3px rgba(124, 154, 131, .13);
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 11px 15px;
            border: 0;
            border-radius: 9px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s;
        }

        .btn-primary {
            width: 100%;
            background: var(--sage);
            color: white;
        }

        .btn-primary:hover {
            background: var(--sage-dark);
        }

        .staff-note {
            margin: 20px;
            padding: 13px;
            border-radius: 9px;
            background: var(--mint);
            color: var(--sage-dark);
            font-size: 13px;
            line-height: 1.6;
        }

        .category-count {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 18px 21px;
            border-bottom: 1px solid var(--border);
        }

        .count-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 11px;
            background: var(--mint);
            color: var(--sage-dark);
            font-size: 20px;
        }

        .count-label {
            margin: 0 0 4px;
            color: var(--muted);
            font-size: 12px;
        }

        .count-number {
            margin: 0;
            font-size: 21px;
            font-weight: bold;
        }

        .table-scroll {
            overflow-x: auto;
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .categories-table th {
            padding: 13px 18px;
            background: #f5f9f5;
            color: #728277;
            font-size: 11px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .categories-table td {
            padding: 15px 18px;
            border-bottom: 1px solid #edf2ee;
            font-size: 13px;
        }

        .categories-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .categories-table tbody tr:hover {
            background: #fbfdfb;
        }

        .category-name {
            font-weight: bold;
        }

        .product-count-badge {
            display: inline-block;
            min-width: 30px;
            padding: 5px 9px;
            border-radius: 7px;
            background: var(--mint);
            color: var(--sage-dark);
            text-align: center;
            font-weight: bold;
        }

        .empty-state {
            padding: 30px 15px !important;
            color: var(--muted);
            text-align: center;
        }

        @media (max-width: 760px) {
            .categories-main {
                padding: 22px 14px;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .categories-header {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body class="categories-page">
<?php include __DIR__ . "/includes/sidebar.php"; ?>
<div class="page-back-wrap"><a class="back-link" href="dashboard.php">← Kembali ke Dashboard</a></div>


<main class="categories-main">

    <header class="categories-header">
        <div>
            <p class="eyebrow">Manajemen Inventaris</p>
            <h1>Data Kategori</h1>
            <p class="subtitle">
                Kelola kategori dan lihat jumlah produk pada setiap kategori.
            </p>
        </div>

        <span class="role-label">
            <?= e(ucfirst($role)) ?>
        </span>
    </header>

    <?php if ($pesan !== ""): ?>
        <div class="notice notice-success">
            <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="notice notice-error">
            <?= e($error) ?>
        </div>
    <?php endforeach; ?>

    <div class="categories-grid">

        <section class="panel">
            <div class="panel-header">
                <h2>Tambah Kategori</h2>
                <p>
                    <?= $isAdmin
                        ? "Isi nama kategori untuk menambah data baru."
                        : "Akun Staff hanya dapat melihat daftar kategori." ?>
                </p>
            </div>

            <?php if ($isAdmin): ?>
                <form method="POST" class="category-form">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["csrf_token"]) ?>"
                    >

                    <label for="name">Nama Kategori</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="<?= e($name) ?>"
                        placeholder="Contoh: Elektronik"
                        required
                        minlength="2"
                        maxlength="100"
                    >

                    <button type="submit" class="btn btn-primary">
                        + Simpan Kategori
                    </button>
                </form>
            <?php else: ?>
                <div class="staff-note">
                    Kamu login sebagai Staff. Fitur menambahkan kategori hanya tersedia untuk Admin.
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Daftar Kategori</h2>
                <p>Daftar kategori yang tersimpan dalam sistem.</p>
            </div>

            <div class="category-count">
                <div class="count-icon">▤</div>
                <div>
                    <p class="count-label">Total kategori</p>
                    <p class="count-number"><?= count($categories) ?></p>
                </div>
            </div>

            <div class="table-scroll">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Kategori</th>
                            <th>Jumlah Produk</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!$categories): ?>
                        <tr>
                            <td colspan="3" class="empty-state">
                                Belum ada kategori.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $i => $category): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="category-name">
                                    <?= e($category["name"]) ?>
                                </td>
                                <td>
                                    <span class="product-count-badge">
                                        <?= (int)$category["total_produk"] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

</main>

</body>
</html>
