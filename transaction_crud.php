<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? '';
    $playstation_id = $_POST['playstation_id'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $status = $_POST['status'] ?? 'unpaid';

    if (empty($customer_id) || empty($playstation_id) || empty($start_time) || empty($end_time)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($end_time) <= strtotime($start_time)) {
        $error = 'End time must be after start time.';
    } else {
        // Calculate total price
        $stmt = $pdo->prepare('SELECT price_per_hour FROM playstations WHERE id = ?');
        $stmt->execute([$playstation_id]);
        $playstation = $stmt->fetch();
        if (!$playstation) {
            $error = 'Invalid PlayStation selected.';
        } else {
            $hours = (strtotime($end_time) - strtotime($start_time)) / 3600;
            $total_price = $hours * $playstation['price_per_hour'];

            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare('INSERT INTO transactions (customer_id, playstation_id, start_time, end_time, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                if ($stmt->execute([$customer_id, $playstation_id, $start_time, $end_time, $total_price, $status])) {
                    $success = 'Transaction added successfully.';
                } else {
                    $error = 'Failed to add transaction.';
                }
            } elseif ($_POST['action'] === 'edit' && $id > 0) {
                $stmt = $pdo->prepare('UPDATE transactions SET customer_id = ?, playstation_id = ?, start_time = ?, end_time = ?, total_price = ?, status = ? WHERE id = ?');
                if ($stmt->execute([$customer_id, $playstation_id, $start_time, $end_time, $total_price, $status, $id])) {
                    $success = 'Transaction updated successfully.';
                } else {
                    $error = 'Failed to update transaction.';
                }
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = ?');
    if ($stmt->execute([$id])) {
        $success = 'Transaction deleted successfully.';
        $action = '';
    } else {
        $error = 'Failed to delete transaction.';
    }
}

// Fetch transactions list with joins
$stmt = $pdo->query('
    SELECT t.*, c.name AS customer_name, p.name AS playstation_name
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    JOIN playstations p ON t.playstation_id = p.id
    ORDER BY t.created_at DESC
');
$transactions = $stmt->fetchAll();

// Fetch customers and playstations for form selects
$customers = $pdo->query('SELECT id, name FROM customers ORDER BY name')->fetchAll();
$playstations = $pdo->query('SELECT id, name FROM playstations ORDER BY name')->fetchAll();

// If editing, fetch transaction data
$edit_transaction = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
    $stmt->execute([$id]);
    $edit_transaction = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Transaksi - Rental PlayStation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="employee_dashboard.php">Dashboard Pegawai</a>
            <div class="d-flex">
                <span class="navbar-text me-3">Masuk sebagai: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2>Kelola Transaksi</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <form method="POST" action="transaction_crud.php<?php echo $action === 'edit' ? '?action=edit&id=' . $id : '?action=add'; ?>" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>" />
                <div class="mb-3">
                    <label for="customer_id" class="form-label">Pelanggan</label>
                    <select class="form-select" id="customer_id" name="customer_id" required>
                        <option value="" disabled selected>Pilih pelanggan</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>" <?php if (($edit_transaction['customer_id'] ?? '') == $customer['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="playstation_id" class="form-label">PlayStation</label>
                    <select class="form-select" id="playstation_id" name="playstation_id" required>
                        <option value="" disabled selected>Pilih PlayStation</option>
                        <?php foreach ($playstations as $playstation): ?>
                            <option value="<?php echo $playstation['id']; ?>" <?php if (($edit_transaction['playstation_id'] ?? '') == $playstation['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($playstation['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="start_time" class="form-label">Waktu Mulai</label>
                    <input 
                        type="datetime-local" 
                        class="form-control" 
                        id="start_time" 
                        name="start_time" 
                        required 
                        value="<?php echo isset($edit_transaction['start_time']) ? date('Y-m-d\TH:i', strtotime($edit_transaction['start_time'])) : ''; ?>"
                    />
                </div>
                <div class="mb-3">
                    <label for="end_time" class="form-label">Waktu Selesai</label>
                    <input 
                        type="datetime-local" 
                        class="form-control" 
                        id="end_time" 
                        name="end_time" 
                        required 
                        value="<?php echo isset($edit_transaction['end_time']) ? date('Y-m-d\TH:i', strtotime($edit_transaction['end_time'])) : ''; ?>"
                    />
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="unpaid" <?php if (($edit_transaction['status'] ?? '') === 'unpaid') echo 'selected'; ?>>Belum Bayar</option>
                        <option value="paid" <?php if (($edit_transaction['status'] ?? '') === 'paid') echo 'selected'; ?>>Sudah Bayar</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Perbarui' : 'Tambah'; ?> Transaksi</button>
                <a href="transaction_crud.php" class="btn btn-secondary ms-2">Batal</a>
            </form>
        <?php else: ?>
            <a href="transaction_crud.php?action=add" class="btn btn-success mb-3">Tambah Transaksi Baru</a>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>PlayStation</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Dibuat Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) === 0): ?>
                        <tr><td colspan="8" class="text-center">Tidak ada transaksi ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['playstation_name']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['start_time']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['end_time']); ?></td>
                                <td>$<?php echo number_format($transaction['total_price'], 2); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($transaction['status'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                                <td>
                                    <a href="transaction_crud.php?action=edit&id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-primary">Ubah</a>
                                    <a href="transaction_crud.php?action=delete&id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">Hapus</a>
                                    <?php if ($transaction['status'] === 'paid'): ?>
                                        <a href="generate_pdf.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info" target="_blank">Cetak Bukti</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
