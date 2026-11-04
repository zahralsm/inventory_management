
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
$contact = "";
$phone = "";
$email = "";
$address = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$isAdmin) {
        http_response_code(403);
        exit("Akses ditolak! Hanya Admin yang boleh menambahkan supplier.");
    }

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        $errors[] = "Permintaan tidak valid.";
    }

    $name = trim($_POST["name"] ?? "");
    $contact = trim($_POST["contact"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");

    if ($name === "" || strlen($name) > 150) {
        $errors[] = "Nama supplier wajib diisi, maksimal 150 karakter.";
    }

    if (strlen($contact) > 100) {
        $errors[] = "Nama kontak maksimal 100 karakter.";
    }

    if (strlen($phone) > 30) {
        $errors[] = "Nomor telepon maksimal 30 karakter.";
    }

    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    if (strlen($email) > 150 || strlen($address) > 1000) {
        $errors[] = "Email atau alamat terlalu panjang.";
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO suppliers
                (name, contact_person, phone, email, address)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $contact ?: null,
                $phone ?: null,
                $email ?: null,
                $address ?: null
            ]);

            $_SESSION["pesan"] = "Supplier berhasil ditambahkan.";
            header("Location: suppliers.php");
            exit;

        } catch (PDOException $error) {
            $errors[] = "Supplier gagal ditambahkan. Periksa kembali data dan database.";
        }
    }
}

$pesan = $_SESSION["pesan"] ?? "";
unset($_SESSION["pesan"]);

$suppliers = $pdo->query(
    "SELECT * FROM suppliers ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Supplier</title>
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

        body.suppliers-page {
            margin: 0;
            background: var(--mint-light);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        .suppliers-main {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 34px;
        }

        .suppliers-header {
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

        .suppliers-header h1 {
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

        .suppliers-grid {
            display: grid;
            grid-template-columns: minmax(270px, .8fr) minmax(0, 1.5fr);
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

        .supplier-form {
            padding: 21px;
        }

        .form-field {
            margin-bottom: 16px;
        }

        .form-field label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        .form-field input,
        .form-field textarea {
            display: block;
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #dce6de;
            border-radius: 9px;
            background: white;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            outline: none;
        }

        .form-field textarea {
            min-height: 90px;
            resize: vertical;
        }

        .form-field input:focus,
        .form-field textarea:focus {
            border-color: var(--sage);
            box-shadow: 0 0 0 3px rgba(124, 154, 131, .13);
        }

        .required {
            color: #b85c5c;
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

        .supplier-summary {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 18px 21px;
            border-bottom: 1px solid var(--border);
        }

        .summary-icon {
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

        .summary-label {
            margin: 0 0 4px;
            color: var(--muted);
            font-size: 12px;
        }

        .summary-number {
            margin: 0;
            font-size: 21px;
            font-weight: bold;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .suppliers-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .suppliers-table th {
            padding: 13px 15px;
            background: #f5f9f5;
            color: #728277;
            font-size: 11px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .suppliers-table td {
            padding: 15px;
            border-bottom: 1px solid #edf2ee;
            font-size: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .suppliers-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .suppliers-table tbody tr:hover {
            background: #fbfdfb;
        }

        .supplier-name {
            min-width: 130px;
            font-weight: bold;
        }

        .supplier-contact {
            min-width: 120px;
        }

        .supplier-address {
            min-width: 150px;
            color: var(--muted);
        }

        .empty-state {
            padding: 32px 16px !important;
            color: var(--muted);
            text-align: center;
        }

        @media (max-width: 850px) {
            .suppliers-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .suppliers-main {
                padding: 22px 14px;
            }

            .suppliers-header {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body class="suppliers-page">
<?php include __DIR__ . "/includes/sidebar.php"; ?>
<div class="page-back-wrap"><a class="back-link" href="dashboard.php">← Kembali ke Dashboard</a></div>


<main class="suppliers-main">

    <header class="suppliers-header">
        <div>
            <p class="eyebrow">Manajemen Inventaris</p>
            <h1>Data Supplier</h1>
            <p class="subtitle">
                Kelola informasi pemasok yang bekerja sama dengan usahamu.
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

    <div class="suppliers-grid">

        <section class="panel">
            <div class="panel-header">
                <h2>Tambah Supplier</h2>
                <p>
                    <?= $isAdmin
                        ? "Lengkapi informasi pemasok pada formulir berikut."
                        : "Akun Staff hanya dapat melihat data supplier." ?>
                </p>
            </div>

            <?php if ($isAdmin): ?>
                <form method="POST" class="supplier-form">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["csrf_token"]) ?>"
                    >

                    <div class="form-field">
                        <label for="name">
                            Nama Supplier <span class="required">*</span>
                        </label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="<?= e($name) ?>"
                            maxlength="150"
                            placeholder="Masukkan nama supplier"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="contact">Nama Kontak</label>
                        <input
                            id="contact"
                            type="text"
                            name="contact"
                            value="<?= e($contact) ?>"
                            maxlength="100"
                            placeholder="Nama orang yang bisa dihubungi"
                        >
                    </div>

                    <div class="form-field">
                        <label for="phone">Nomor Telepon</label>
                        <input
                            id="phone"
                            type="tel"
                            name="phone"
                            value="<?= e($phone) ?>"
                            maxlength="30"
                            placeholder="Contoh: 08xxxxxxxxxx"
                        >
                    </div>

                    <div class="form-field">
                        <label for="email">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="<?= e($email) ?>"
                            maxlength="150"
                            placeholder="supplier@email.com"
                        >
                    </div>

                    <div class="form-field">
                        <label for="address">Alamat</label>
                        <textarea
                            id="address"
                            name="address"
                            maxlength="1000"
                            placeholder="Masukkan alamat supplier"
                        ><?= e($address) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        + Simpan Supplier
                    </button>
                </form>
            <?php else: ?>
                <div class="staff-note">
                    Kamu login sebagai Staff. Fitur menambahkan supplier hanya tersedia untuk Admin.
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Daftar Supplier</h2>
                <p>Informasi supplier yang tersimpan di dalam sistem.</p>
            </div>

            <div class="supplier-summary">
                <div class="summary-icon">♧</div>
                <div>
                    <p class="summary-label">Total supplier</p>
                    <p class="summary-number"><?= count($suppliers) ?></p>
                </div>
            </div>

            <div class="table-scroll">
                <table class="suppliers-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Supplier</th>
                            <th>Kontak</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Alamat</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!$suppliers): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                Belum ada supplier yang terdaftar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $i => $supplier): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="supplier-name">
                                    <?= e($supplier["name"]) ?>
                                </td>
                                <td class="supplier-contact">
                                    <?= e($supplier["contact_person"] ?? "-") ?>
                                </td>
                                <td>
                                    <?= e($supplier["phone"] ?? "-") ?>
                                </td>
                                <td>
                                    <?= e($supplier["email"] ?? "-") ?>
                                </td>
                                <td class="supplier-address">
                                    <?= e($supplier["address"] ?? "-") ?>
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
