<?php

require_once 'config.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

startSession();
$conn = getConnection();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_product') {
            $name = sanitize($_POST['name']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            $description = sanitize($_POST['description']);
            
            if (empty($name) || $price <= 0 || $stock < 0) {
                $message = 'Data produk tidak valid!';
                $message_type = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO products (name, price, stock, description) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sdss", $name, $price, $stock, $description);
                
                if ($stmt->execute()) {
                    $message = 'Produk berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan produk!';
                    $message_type = 'error';
                }
                
                $stmt->close();
            }
            
        } elseif ($action === 'update_product') {
            $id = intval($_POST['id']);
            $name = sanitize($_POST['name']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            $description = sanitize($_POST['description']);
            
            if (empty($name) || $price <= 0 || $stock < 0) {
                $message = 'Data produk tidak valid!';
                $message_type = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ? WHERE id = ?");
                $stmt->bind_param("sdsis", $name, $price, $stock, $description, $id);
                
                if ($stmt->execute()) {
                    $message = 'Produk berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal memperbarui produk!';
                    $message_type = 'error';
                }
                
                $stmt->close();
            }
            
        } elseif ($action === 'delete_product') {
            $id = intval($_POST['id']);
            
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = 'Produk berhasil dihapus!';
                $message_type = 'success';
            } else {
                $message = 'Gagal menghapus produk!';
                $message_type = 'error';
            }
            
            $stmt->close();
        }
    }
}


$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Aplikasi Kasir</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .form-section, .products-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .products-table th, .products-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .products-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: 1rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .products-table {
                font-size: 0.9rem;
            }
            
            .product-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏢 Panel Admin</h1>
        <div class="nav-links">
            <a href="index.php" class="nav-link">🏠 Kasir</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid">
            <div class="form-section">
                <h2 class="section-title">➕ Tambah/Edit Produk</h2>
                
                <form method="POST" id="productForm">
                    <input type="hidden" name="action" value="add_product" id="formAction">
                    <input type="hidden" name="id" id="productId">
                    
                    <div class="form-group">
                        <label for="name">Nama Produk:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Harga (Rp):</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stok:</label>
                        <input type="number" id="stock" name="stock" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi:</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">Tambah Produk</button>
                    <button type="button" class="btn" onclick="resetForm()" style="margin-left: 0.5rem;">Reset</button>
                </form>
            </div>
            
            <div class="products-section">
                <h2 class="section-title">📦 Daftar Produk</h2>
                
                <?php if (empty($products)): ?>
                    <p>Belum ada produk.</p>
                <?php else: ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['name']; ?></td>
                                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td class="product-actions">
                                        <button class="btn btn-primary" onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['stock']; ?>, '<?php echo addslashes($product['description']); ?>')">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Hapus produk ini?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function editProduct(id, name, price, stock, description) {
            document.getElementById('formAction').value = 'update_product';
            document.getElementById('productId').value = id;
            document.getElementById('name').value = name;
            document.getElementById('price').value = price;
            document.getElementById('stock').value = stock;
            document.getElementById('description').value = description;
            document.getElementById('submitBtn').textContent = 'Update Produk';
            
            // Scroll to form
            document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('productForm').reset();
            document.getElementById('formAction').value = 'add_product';
            document.getElementById('productId').value = '';
            document.getElementById('submitBtn').textContent = 'Tambah Produk';
        }
    </script>
</body>
</html>