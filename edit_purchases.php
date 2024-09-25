<?php
session_start();
if ($_SESSION['role'] !== 'kepala') {
    header("Location: index.php");
    exit;
}

// Disable cut, copy, paste
echo '<script type="text/javascript">
    document.oncopy = function(){alert("Copy option disabled"); return false;}
    document.oncut = function(){alert("Cut option disabled"); return false;}
    document.onpaste = function(){alert("Paste option disabled"); return false;}
    document.onmousedown = function(e) {
        if (e.button == 2) {
            alert("Right Click Disabled");
            return false;
        }
    }
</script>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Tagihan Supplier</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #0047ab;
            color: white;
        }
        .total {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .section-header {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .sub-header {
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
        }
        input {
            width: 100px;
            padding: 5px;
        }
        button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .logout-button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        .logout-button:hover {
            background-color: #c9302c; /* Darker red on hover */
        }
    </style>
</head>
<body>
<div>
    <a href="logout.php" class="button logout-button">Logout</a>
</div>
<div class="container">
    <div class="section-header">RINGKASAN TAGIHAN SUPPLIER</div>
    <div class="sub-header">Yayasan Al Ashriyyah Nurul Iman Islamic Boarding School</div>

    <?php
    // Koneksi ke database
    include 'connect.php';
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    // Query untuk mengambil data pembelian dan pembayaran
    $query = "SELECT p.*, s.nama AS supplier_nama, s.no_rekening, s.nama_bank, s.Nama_Rekening,
                     IFNULL(SUM(pay.jumlah_terbayar), 0) AS total_terbayar
              FROM purchases p
              LEFT JOIN suppliers s ON p.supplier_id = s.id
              LEFT JOIN payments pay ON p.id = pay.purchase_id
              GROUP BY p.id
              ORDER BY s.nama, p.tanggal_pengiriman";

    $result = $conn->query($query);

    if (!$result) {
        die("Query error: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        echo 'Jumlah data ditemukan: ' . $result->num_rows . '<br>';

        // Initialize grand total for remaining bills
        $grand_total = 0;

        echo '<form action="update_payment.php" method="post">';
        echo '<table>';
        echo '<tr><th>Nama Supplier</th><th>Jumlah Tagihan</th><th>Telah Dibayar</th><th>Sisa Tagihan</th><th>Bank</th><th>Nomor Rekening</th><th>Atas Nama</th><th>Tanggal Jatuh Tempo Terdekat</th><th>Keterangan</th><th>Update Pembayaran</th><th>Edit</th></tr>';

        // Loop through the data
        while ($row = $result->fetch_assoc()) {
            // Calculate total per purchase
            $total_harga = ($row['qty'] * $row['harga_satuan']) - $row['potongan'];
            $tanggal_jatuh_tempo = date('Y-m-d', strtotime('+7 days', strtotime($row['tanggal_pengiriman']))); 
            $sisa_tagihan = $total_harga - $row['total_terbayar'];

            // Add to grand total only for unpaid amounts
            if ($sisa_tagihan > 0) {
                $grand_total += $sisa_tagihan;
            }

            // Dynamically generate 'keterangan'
            $keterangan = $row['produk'] . ' - ' . $row['qty'] . ' ' . $row['satuan'] . ' @Rp ' . number_format($row['harga_satuan'], 2);

            // Display the row
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['supplier_nama']) . '</td>';
            echo '<td>Rp ' . htmlspecialchars(number_format($total_harga, 2)) . '</td>';
            echo '<td>Rp ' . htmlspecialchars(number_format($row['total_terbayar'], 2)) . '</td>';
            echo '<td>Rp ' . htmlspecialchars(number_format($sisa_tagihan, 2)) . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_bank']) . '</td>';
            echo '<td>' . htmlspecialchars($row['no_rekening']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Nama_Rekening']) . '</td>';
            echo '<td>' . htmlspecialchars($tanggal_jatuh_tempo) . '</td>';
            echo '<td>' . htmlspecialchars($keterangan) . '</td>';
            echo '<td>';
            if ($sisa_tagihan > 0) {
                echo '<input type="hidden" name="purchase_ids[]" value="' . $row['id'] . '">';
                echo '<input type="number" name="pembayaran[' . $row['id'] . ']" placeholder="Jumlah" min="0" max="' . $sisa_tagihan . '" step="0.01">';
            } else {
                echo 'Lunas';
            }
            echo '</td>';
            echo '<td><a href="edit_pur.php?id=' . $row['id'] . '">Edit</a></td>'; // Link ke halaman edit
            echo '</tr>';
        }

        // Display grand total
        echo '<tr class="total">';
        echo '<td colspan="9">TOTAL SISA TAGIHAN (Rp/USD)</td>';
        echo '<td>Rp ' . htmlspecialchars(number_format($grand_total, 2)) . '</td>';
        echo '</tr>';

        echo '</table>';
        echo '<button type="submit">Update Pembayaran</button>';
        echo '</form>';
    } else {
        echo '<p class="message">Tidak ada data pembelian ditemukan.</p>';
    }

    // Tutup koneksi
    $conn->close();
    ?>

    <button onclick="window.print()">Cetak Tagihan</button>
</div>

</body>
</html>
