# Stockroom — Inventory Management

Aplikasi inventaris berbasis PHP dan MySQL/MariaDB untuk mengelola produk, kategori, supplier, serta transaksi stok masuk dan keluar.

## Menjalankan aplikasi
1. Salin folder `inventory_management` ke `C:\xampp\htdocs\`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Buka phpMyAdmin, lalu impor `schema.sql`.
4. Periksa konfigurasi database di `config/database.php`.
5. Buat akun admin pada tabel `users` dengan password yang sudah di-hash menggunakan `password_hash()` PHP.
6. Buka `http://localhost/inventory_management/public/login.php`.

## Struktur utama
- `config/database.php` — koneksi PDO.
- `schema.sql` — struktur database.
- `public/login.php` — login dan sesi.
- `public/dashboard.php` — ringkasan inventaris dan tombol logout.
- `public/products.php`, `tambah_produk.php`, `edit_produk.php` — pengelolaan produk.
- `public/categories.php` — pengelolaan kategori.
- `public/suppliers.php` — pengelolaan supplier.
- `public/stok.php` — transaksi stok masuk/keluar.
- `public/assets/style.css` — stylesheet bersama.
- `public/includes/sidebar.php` — navigasi bersama.

## Business rules
- Hanya pengguna yang sudah login dapat membuka halaman internal.
- Role `admin` dapat menambah/mengubah/menghapus produk dan mengelola data yang dibatasi admin.
- Role `staff` tidak boleh menjalankan aksi khusus admin; server memeriksa role, bukan hanya menyembunyikan tombol.
- Harga dan stok tidak boleh negatif; jumlah transaksi stok harus bilangan bulat positif.
- Stok keluar tidak boleh melebihi stok tersedia.
- Setiap perubahan stok dicatat di `stock_movements` dengan produk, pengguna, jenis, jumlah, catatan, dan waktu.
- Form yang mengubah data menggunakan token CSRF.
