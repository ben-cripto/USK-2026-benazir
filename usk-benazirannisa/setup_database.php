<?php

require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database '" . DB_NAME . "' berhasil dibuat atau sudah ada.<br>";
} else {
    echo "Error membuat database: " . $conn->error . "<br>";
}

$conn->select_db(DB_NAME);

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'users' berhasil dibuat.<br>";
} else {
    echo "Error membuat tabel users: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'products' berhasil dibuat.<br>";
} else {
    echo "Error membuat tabel products: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'purchases' berhasil dibuat.<br>";
} else {
    echo "Error membuat tabel purchases: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel 'purchase_items' berhasil dibuat.<br>";
} else {
    echo "Error membuat tabel purchase_items: " . $conn->error . "<br>";
}

$admin_password = hashPassword('admin123');
$sql = "INSERT IGNORE INTO users (username, password, role) VALUES ('admin', '$admin_password', 'admin')";

if ($conn->query($sql) === TRUE) {
    echo "User admin berhasil dibuat (username: admin, password: admin123).<br>";
} else {
    echo "Error membuat user admin: " . $conn->error . "<br>";
}

$products = [
    ['name' => 'Beras 5kg', 'price' => 65000, 'stock' => 50, 'description' => 'Beras premium'],
    ['name' => 'Minyak Goreng 1L', 'price' => 15000, 'stock' => 30, 'description' => 'Minyak goreng kemasan'],
    ['name' => 'Gula Pasir 1kg', 'price' => 12000, 'stock' => 40, 'description' => 'Gula pasir putih'],
    ['name' => 'Telur 1kg', 'price' => 25000, 'stock' => 20, 'description' => 'Telur ayam kampung']
];

foreach ($products as $product) {
    $name = $conn->real_escape_string($product['name']);
    $price = $product['price'];
    $stock = $product['stock'];
    $desc = $conn->real_escape_string($product['description']);
    
    $sql = "INSERT IGNORE INTO products (name, price, stock, description) VALUES ('$name', $price, $stock, '$desc')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Produk '{$name}' berhasil ditambahkan.<br>";
    } else {
        echo "Error menambah produk: " . $conn->error . "<br>";
    }
}

$conn->close();

echo "<br>Setup database selesai! <a href='login.php'>Login</a>";
?>