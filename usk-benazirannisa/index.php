<?php

require_once 'config.php';
requireLogin();

startSession();
$conn = getConnection();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_to_cart') {
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            
            $stmt = $conn->prepare("SELECT name, price, stock FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $product = $result->fetch_assoc();
                
                if ($product['stock'] >= $quantity) {
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = [];
                    }
                    
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                    } else {
                        $_SESSION['cart'][$product_id] = [
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'quantity' => $quantity
                        ];
                    }
                    
                    $message = 'Produk berhasil ditambahkan ke keranjang!';
                    $message_type = 'success';
                } else {
                    $message = 'Stok tidak mencukupi!';
                    $message_type = 'error';
                }
            }
            
            $stmt->close();
            
        } elseif ($action === 'update_cart') {
            $product_id = intval($_POST['product_id']);
            $type = $_POST['type'];
            
            if (isset($_SESSION['cart'][$product_id])) {
                if ($type === 'plus') {
                    $_SESSION['cart'][$product_id]['quantity']++;
                } elseif ($type === 'minus') {
                    $_SESSION['cart'][$product_id]['quantity']--;
                    if ($_SESSION['cart'][$product_id]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$product_id]);
                    }
                }
            }
            
        } elseif ($action === 'remove_from_cart') {
            $product_id = intval($_POST['product_id']);
            unset($_SESSION['cart'][$product_id]);
            
        } elseif ($action === 'checkout') {
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                $total = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $total += $item['price'] * $item['quantity'];
                }
                
                $stmt = $conn->prepare("INSERT INTO purchases (user_id, total) VALUES (?, ?)");
                $stmt->bind_param("id", $_SESSION['user_id'], $total);
                $stmt->execute();
                $purchase_id = $conn->insert_id;
                
                foreach ($_SESSION['cart'] as $product_id => $item) {
                    $stmt = $conn->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiid", $purchase_id, $product_id, $item['quantity'], $item['price']);
                    $stmt->execute();
                    
                    $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->bind_param("ii", $item['quantity'], $product_id);
                    $stmt->execute();
                }
                
            
                unset($_SESSION['cart']);
                
                $message = 'Pembelian berhasil! Total: Rp ' . number_format($total, 0, ',', '.');
                $message_type = 'success';
                
                $stmt->close();
            }
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

$total_cart = 0;
$item_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_cart += $item['price'] * $item['quantity'];
        $item_count += $item['quantity'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Kasir - <?php echo $_SESSION['username']; ?></title>
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
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
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .products-section, .cart-section {
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
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .product-name {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .product-price {
            color: #667eea;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .product-stock {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .add-to-cart-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
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
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: bold;
        }
        
        .cart-item-details {
            color: #666;
            font-size: 0.9rem;
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cart-total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #667eea;
            text-align: center;
        }
        
        .cart-total h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .empty-cart {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
        
        .admin-link {
            margin-top: 1rem;
            text-align: center;
        }
        
        .admin-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .admin-link a:hover {
            text-decoration: underline;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛒 Aplikasi Kasir</h1>
        <div class="user-info">
            <span>Selamat datang, <?php echo $_SESSION['username']; ?>!</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid">
            <div class="products-section">
                <h2 class="section-title">📦 Produk Tersedia</h2>
                
                <?php if (empty($products)): ?>
                    <p>Belum ada produk tersedia.</p>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-name"><?php echo $product['name']; ?></div>
                                <div class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
                                <div class="product-stock">Stok: <?php echo $product['stock']; ?></div>
                                
                                <?php if ($product['stock'] > 0): ?>
                                    <form method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                                        <button type="submit" class="btn btn-primary">Tambah</button>
                                    </form>
                                <?php else: ?>
                                    <p style="color: #dc3545; font-weight: bold;">Stok Habis</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="cart-section">
                <h2 class="section-title">🛒 Keranjang Belanja (<?php echo $item_count; ?> item)</h2>
                
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="empty-cart">
                        <p>Keranjang kosong</p>
                        <p>Tambahkan produk untuk memulai belanja</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <div class="cart-item-name"><?php echo $item['name']; ?></div>
                                <div class="cart-item-details">
                                    Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> × <?php echo $item['quantity']; ?> = 
                                    Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                                </div>
                            </div>
                            <div class="cart-item-controls">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="hidden" name="type" value="minus">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem;">-</button>
                                </form>
                                
                                <span><?php echo $item['quantity']; ?></span>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="hidden" name="type" value="plus">
                                    <button type="submit" class="btn btn-success" style="padding: 0.25rem 0.5rem;">+</button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_from_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem;" onclick="return confirm('Hapus item ini?')">🗑️</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-total">
                        <h3>Total: Rp <?php echo number_format($total_cart, 0, ',', '.'); ?></h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 1rem;" onclick="return confirm('Konfirmasi pembelian?')">Checkout</button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="admin-link">
                        <a href="admin.php">🏢 Panel Admin</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>