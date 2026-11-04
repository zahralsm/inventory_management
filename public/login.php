
<?php
session_start();
require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, name, username, password, role
             FROM users
             WHERE username = ?"
        );

        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            header("Location: dashboard.php");
            exit;
        }

        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login Admin | Inventory Management</title>

    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">

    <main class="login-wrapper">
        <section class="login-card">

            <div class="login-brand">
                <div class="brand-icon">▦</div>
                <div>
                    <h1>Inventory<span>Management</span></h1>
                    <p>Sistem Informasi Inventaris</p>
                </div>
            </div>

            <div class="login-heading">
                <h2>Selamat datang 👋</h2>
                <p>Silakan masuk menggunakan akun admin kamu.</p>
            </div>

            <?php if ($error !== ""): ?>
                <div class="login-error" role="alert">
                    <span>!</span>
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="login-button">
                    Masuk ke akun
                    <span aria-hidden="true">→</span>
                </button>
            </form>

            <div class="login-footer">
                <span class="footer-line"></span>
                <p>Akses khusus pengguna terdaftar</p>
                <span class="footer-line"></span>
            </div>

        </section>

        <p class="copyright">
            © <?= date("Y") ?> Inventory Management
        </p>
    </main>

</body>
</html>