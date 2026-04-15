# Aplikasi Kasir PHP

Aplikasi kasir sederhana berbasis web menggunakan PHP dan MySQL.

## Fitur

- ✅ **Login & Logout**: Sistem autentikasi pengguna
- ✅ **Registrasi**: Pendaftaran akun baru
- ✅ **Pendataan Barang**: Manajemen produk (CRUD)
- ✅ **Pembelian**: Sistem keranjang belanja dan checkout
- ✅ **Stok Barang**: Tracking dan update stok otomatis
- ✅ **Panel Admin**: Interface khusus untuk admin

## Persyaratan

- PHP 7.4 atau lebih baru
- MySQL/MariaDB
- Web server (Apache/Nginx) - direkomendasikan XAMPP

## Instalasi

1. **Clone atau download** file ke folder web server Anda (misalnya `htdocs` di XAMPP)

2. **Jalankan setup database**:
   - Pastikan XAMPP/MySQL sudah berjalan
   - Akses `http://localhost/nama-folder/setup_database.php`
   - Script akan membuat database `kasir_app` dan tabel yang diperlukan

3. **Login**:
   - Username: `admin`
   - Password: `admin123`
   - Atau daftar akun baru melalui `register.php`

## Struktur File

```
usk-benazirannisa/
├── config.php          # Konfigurasi database dan fungsi umum
├── setup_database.php  # Setup database dan tabel
├── login.php           # Halaman login
├── register.php        # Halaman registrasi
├── logout.php          # Script logout
├── index.php           # Halaman utama kasir
├── admin.php           # Panel admin untuk manajemen produk
└── README.md           # Dokumentasi ini
```

## Struktur Database

### Tabel `users`
- `id` (INT, Primary Key)
- `username` (VARCHAR, Unique)
- `password` (VARCHAR, Hashed)
- `role` (ENUM: 'admin', 'user')
- `created_at` (TIMESTAMP)

### Tabel `products`
- `id` (INT, Primary Key)
- `name` (VARCHAR)
- `price` (DECIMAL)
- `stock` (INT)
- `description` (TEXT)
- `created_at` (TIMESTAMP)

### Tabel `purchases`
- `id` (INT, Primary Key)
- `user_id` (INT, Foreign Key)
- `total` (DECIMAL)
- `created_at` (TIMESTAMP)

### Tabel `purchase_items`
- `id` (INT, Primary Key)
- `purchase_id` (INT, Foreign Key)
- `product_id` (INT, Foreign Key)
- `quantity` (INT)
- `price` (DECIMAL)

## Cara Penggunaan

### Untuk User Biasa:
1. Login dengan akun Anda
2. Pilih produk dari daftar
3. Tambahkan ke keranjang
4. Sesuaikan jumlah jika perlu
5. Klik "Checkout" untuk menyelesaikan pembelian

### Untuk Admin:
1. Login dengan akun admin
2. Akses "Panel Admin" dari halaman kasir
3. Tambah produk baru
4. Edit atau hapus produk existing
5. Monitor stok produk

## Keamanan

- Password di-hash menggunakan `password_hash()`
- Input di-sanitize untuk mencegah SQL injection
- Session-based authentication
- Role-based access control

## Pengembangan

Untuk mengembangkan lebih lanjut:
- Tambahkan fitur laporan penjualan
- Implementasi barcode scanning
- Multiple payment methods
- Export data ke Excel/PDF
- Backup database otomatis

## Lisensi

Proyek ini dibuat untuk tujuan edukasi.