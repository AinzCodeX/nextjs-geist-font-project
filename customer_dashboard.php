<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// Get customer profile
$stmt = $pdo->prepare('SELECT * FROM customers WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

if (!$customer) {
    // Create customer profile if it doesn't exist
    $stmt = $pdo->prepare('INSERT INTO customers (user_id, name, contact_info, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$_SESSION['user_id'], $_SESSION['username'], '']);
    
    // Get the newly created customer profile
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
}

$error = '';
$success = '';

// Handle rental form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rent'])) {
    $playstation_id = $_POST['playstation_id'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if (empty($playstation_id) || empty($start_time) || empty($end_time)) {
        $error = 'Harap isi semua kolom yang diperlukan.';
    } elseif (strtotime($end_time) <= strtotime($start_time)) {
        $error = 'Waktu selesai harus setelah waktu mulai.';
    } else {
        // Check if playstation is available
        $stmt = $pdo->prepare('SELECT status, price_per_hour FROM playstations WHERE id = ?');
        $stmt->execute([$playstation_id]);
        $playstation = $stmt->fetch();

        if (!$playstation || $playstation['status'] !== 'available') {
            $error = 'PlayStation yang dipilih tidak tersedia.';
        } else {
            // Calculate total price
            $hours = (strtotime($end_time) - strtotime($start_time)) / 3600;
            $total_price = $hours * $playstation['price_per_hour'];

            // Insert transaction with status unpaid
            $stmt = $pdo->prepare('INSERT INTO transactions (customer_id, playstation_id, start_time, end_time, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, "unpaid", NOW())');
            if ($stmt->execute([$customer['id'], $playstation_id, $start_time, $end_time, $total_price])) {
                $success = 'Penyewaan PlayStation berhasil. Silakan lakukan pembayaran di kasir.';
            } else {
            $error = 'Gagal membuat transaksi penyewaan. Silakan coba lagi.';
            }
        }
    }
}

// Fetch available playstations
$stmt = $pdo->query('SELECT * FROM playstations WHERE status = "available" ORDER BY name');
$available_playstations = $stmt->fetchAll();

// Fetch customer's transactions
$stmt = $pdo->prepare('
    SELECT t.*, p.name AS playstation_name
    FROM transactions t
    JOIN playstations p ON t.playstation_id = p.id
    WHERE t.customer_id = ?
    ORDER BY t.created_at DESC
');
$stmt->execute([$customer['id']]);
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Pelanggan - Rental PlayStation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
    <style>
        body {
            background: #4361ee;
            min-height: 100vh;
        }
        .content-wrapper {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            padding: 2rem;
            margin: 2rem auto;
        }
        .btn-primary {
            background: #4361ee;
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #3b4fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        .navbar {
            background: white !important;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            color: #4361ee !important;
            font-weight: 600;
        }
        .table {
            margin-top: 1rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            border-color: #4361ee;
        }
        .btn-outline-primary {
            color: #4361ee;
            border-color: #4361ee;
        }
        .btn-outline-primary:hover {
            background: #4361ee;
            border-color: #4361ee;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
        }
    </style>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Dashboard Pelanggan</a>
            <div class="d-flex">
                <span class="navbar-text me-3">Masuk sebagai: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-outline-primary btn-sm">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
        <h2>Sewa PlayStation</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="customer_dashboard.php" novalidate>
            <input type="hidden" name="rent" value="1" />
            <div class="mb-3">
                <label for="playstation_id" class="form-label">Pilih PlayStation</label>
                <select class="form-select" id="playstation_id" name="playstation_id" required>
                    <option value="" disabled selected>Pilih PlayStation</option>
                    <?php foreach ($available_playstations as $ps): ?>
                        <option value="<?php echo $ps['id']; ?>"><?php echo htmlspecialchars($ps['name']); ?> - Rp <?php echo number_format($ps['price_per_hour'], 0, ',', '.'); ?>/jam</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="start_time" class="form-label">Waktu Mulai</label>
                <input type="datetime-local" class="form-control" id="start_time" name="start_time" required />
            </div>
            <div class="mb-3">
                <label for="end_time" class="form-label">Waktu Selesai</label>
                <input type="datetime-local" class="form-control" id="end_time" name="end_time" required />
            </div>
            <button type="submit" class="btn btn-primary">Sewa PlayStation</button>
        </form>

        <hr />

        <h2>Riwayat Transaksi Anda</h2>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>PlayStation</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Dibuat Pada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) === 0): ?>
                    <tr><td colspan="6" class="text-center">Tidak ada transaksi ditemukan.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['playstation_name']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['start_time']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['end_time']); ?></td>
                            <td>Rp <?php echo number_format($transaction['total_price'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge <?php echo $transaction['status'] === 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $transaction['status'] === 'paid' ? 'Sudah Bayar' : 'Belum Bayar'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
