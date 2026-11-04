
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Hanya Admin yang boleh menghapus produk
if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Akses ditolak! Hanya Admin yang boleh menghapus produk.");
}

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit;
}

// Periksa token keamanan
$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    $_SESSION["pesan"] = "Permintaan tidak valid.";
    header("Location: products.php");
    exit;
}

// Periksa ID produk
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION["pesan"] = "ID produk tidak valid.";
    header("Location: products.php");
    exit;
}

try {
    $stmt = $pdo->prepare(
        "DELETE FROM products WHERE id = ?"
    );

    $stmt->execute([$id]);

    $_SESSION["pesan"] = $stmt->rowCount() > 0
        ? "Produk berhasil dihapus."
        : "Produk tidak ditemukan.";

} catch (PDOException $e) {
    $_SESSION["pesan"] =
        "Produk tidak dapat dihapus karena masih digunakan oleh data lain.";
}

header("Location: products.php");
exit;
