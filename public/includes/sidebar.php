<?php
// Navigasi bersama untuk seluruh halaman setelah login.
$activePage = basename($_SERVER["PHP_SELF"]);
?>
<nav class="app-nav" aria-label="Navigasi utama">
  <a class="app-brand" href="dashboard.php"><span class="app-brand-icon">▦</span><span>Stockroom<small>Inventory Management</small></span></a>
  <div class="app-nav-links">
    <a class="<?= $activePage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
    <a class="<?= in_array($activePage, ['products.php','tambah_produk.php','edit_produk.php','hapus_produk.php'], true) ? 'active' : '' ?>" href="products.php">Produk</a>
    <a class="<?= $activePage === 'categories.php' ? 'active' : '' ?>" href="categories.php">Kategori</a>
    <a class="<?= $activePage === 'suppliers.php' ? 'active' : '' ?>" href="suppliers.php">Supplier</a>
    <a class="<?= $activePage === 'stok.php' ? 'active' : '' ?>" href="stok.php">Stok</a>
  </div>
  <span class="app-nav-user"><?= htmlspecialchars($_SESSION["name"] ?? "Pengguna", ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars(ucfirst($_SESSION["role"] ?? "staff"), ENT_QUOTES, "UTF-8") ?></span>
</nav>
