
<?php
require_once "../config/database.php";

$nama = "Staff";
$username = "staff";
$password = password_hash("staff123", PASSWORD_DEFAULT);
$role = "staff";

$stmt = $pdo->prepare(
    "SELECT id FROM users WHERE username = ?"
);
$stmt->execute([$username]);

if ($stmt->fetch()) {
    exit("Akun Staff sudah tersedia.");
}

$stmt = $pdo->prepare(
    "INSERT INTO users (name, username, password, role)
     VALUES (?, ?, ?, ?)"
);

$stmt->execute([$nama, $username, $password, $role]);

echo "Akun Staff berhasil dibuat.";
?>

