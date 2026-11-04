
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Khusus Admin yang boleh menambah produk
if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Akses ditolak! Halaman ini hanya untuk Admin.");
}

require_once "../config/database.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$errors = [];
$name = "";
$category_id = "";
$price = "";
$stock = "";

$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        $errors[] = "Permintaan tidak valid. Silakan muat ulang halaman.";
    }

    $name = trim($_POST["name"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $price = trim($_POST["price"] ?? "");
    $stock = trim($_POST["stock"] ?? "");

    if (strlen($name) < 3 || strlen($name) > 150) {
        $errors[] = "Nama produk harus 3–150 karakter.";
    }

    if ($category_id !== "") {
        $validCategory = false;

        foreach ($categories as $category) {
            if ((string)$category["id"] === $category_id) {
                $validCategory = true;
                break;
            }
        }

        if (!$validCategory) {
            $errors[] = "Kategori tidak valid.";
        }
    }

    if ($price === "" || !is_numeric($price) || (float)$price < 0) {
        $errors[] = "Harga harus berupa angka dan tidak boleh negatif.";
    }

    if (
        $stock === "" ||
        filter_var($stock, FILTER_VALIDATE_INT) === false ||
        (int)$stock < 0
    ) {
        $errors[] = "Stok harus berupa bilangan bulat dan tidak boleh negatif.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO products (name, category_id, price, stock)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $name,
            $category_id === "" ? null : (int)$category_id,
            (float)$price,
            (int)$stock
        ]);

        $_SESSION["pesan"] = "Produk berhasil ditambahkan.";
        header("Location: products.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . "/includes/sidebar.php"; ?>

<main class="form-page">
    <a class="back-link" href="products.php">← Kembali ke Daftar Produk</a>
    <section class="form-card">
        <div class="form-heading">
            <span class="form-eyebrow">MANAJEMEN INVENTARIS</span>
            <h1>Tambah Produk</h1>
            <p>Lengkapi informasi produk yang akan ditambahkan ke inventaris.</p>
        </div>

    <?php foreach ($errors as $error): ?>
        <p style="color:red"><?= e($error) ?></p>
    <?php endforeach; ?>

    <form method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION["csrf_token"]) ?>"
        >

        <label>Nama Produk</label><br>
        <input
            type="text"
            name="name"
            value="<?= e($name) ?>"
            required
        >
        <br><br>

        <label>Kategori</label><br>
        <select name="category_id">
            <option value="">-- Tanpa kategori --</option>

            <?php foreach ($categories as $category): ?>
                <option
                    value="<?= (int)$category["id"] ?>"
                    <?= $category_id === (string)$category["id"] ? "selected" : "" ?>
                >
                    <?= e($category["name"]) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label>Harga</label><br>
        <input
            type="number"
            name="price"
            min="0"
            step="0.01"
            value="<?= e($price) ?>"
            required
        >
        <br><br>

        <label>Stok Awal</label><br>
        <input
            type="number"
            name="stock"
            min="0"
            step="1"
            value="<?= e($stock) ?>"
            required
        >
        <br><br>

        <button type="submit">Simpan Produk</button>
    </form>
    </section>
</main>

</body>
</html>


