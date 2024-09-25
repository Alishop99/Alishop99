<?php 
session_start();
if ($_SESSION['role'] !== 'kepala') {
    header("Location: index.php");
    exit;
}

include 'connect.php';

if (isset($_GET['id'])) {
    $purchase_id = $_GET['id'];

    // Query untuk mendapatkan detail pembelian berdasarkan ID
    $query = "SELECT * FROM purchases WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $purchase_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $purchase = $result->fetch_assoc();
    } else {
        echo "Pembelian tidak ditemukan.";
        exit;
    }

    // Form untuk mengedit pembelian
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Simpan perubahan yang dilakukan
        $produk = $_POST['produk'];
        $qty = $_POST['qty'];
        $harga_satuan = $_POST['harga_satuan'];
        $potongan = $_POST['potongan'];

        $update_query = "UPDATE purchases SET produk = ?, qty = ?, harga_satuan = ?, potongan = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sidii", $produk, $qty, $harga_satuan, $potongan, $purchase_id);

        if ($stmt->execute()) {
            // Redirect to edit page with success message
            header("Location: edit_purchases.php?id=" . $purchase_id . "&success=1");
            exit;
        } else {
            echo "Terjadi kesalahan saat mengupdate pembelian.";
        }
    }
} else {
    echo "ID pembelian tidak ditemukan.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Pembelian</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        h2 {
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h2>Edit Pembelian</h2>
    <?php if (isset($_GET['success'])): ?>
        <p style="color: green;">Pembelian berhasil diupdate!</p>
    <?php endif; ?>
    <form action="" method="POST">
        <label for="produk">Produk:</label>
        <input type="text" name="produk" value="<?php echo htmlspecialchars($purchase['produk']); ?>" required>

        <label for="qty">Qty:</label>
        <input type="number" name="qty" value="<?php echo htmlspecialchars($purchase['qty']); ?>" required min="1">

        <label for="harga_satuan">Harga Satuan:</label>
        <input type="number" name="harga_satuan" value="<?php echo htmlspecialchars($purchase['harga_satuan']); ?>" required step="0.01">

        <label for="potongan">Potongan:</label>
        <input type="number" name="potongan" value="<?php echo htmlspecialchars($purchase['potongan']); ?>" required step="0.01">

        <button type="submit">Update</button>
    </form>
</body>
</html>
