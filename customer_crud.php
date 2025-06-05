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
    $name = trim($_POST['name'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');

    if (empty($name)) {
        $error = 'Name is required.';
    } else {
        if ($_POST['action'] === 'add') {
            // Insert new customer
            $stmt = $pdo->prepare('INSERT INTO customers (user_id, name, contact_info, created_at) VALUES (?, ?, ?, NOW())');
            // For simplicity, user_id is set to 0 (no linked user account)
            if ($stmt->execute([0, $name, $contact_info])) {
                $success = 'Customer added successfully.';
            } else {
                $error = 'Failed to add customer.';
            }
        } elseif ($_POST['action'] === 'edit' && $id > 0) {
            // Update customer
            $stmt = $pdo->prepare('UPDATE customers SET name = ?, contact_info = ? WHERE id = ?');
            if ($stmt->execute([$name, $contact_info, $id])) {
                $success = 'Customer updated successfully.';
            } else {
                $error = 'Failed to update customer.';
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
    if ($stmt->execute([$id])) {
        $success = 'Customer deleted successfully.';
        $action = '';
    } else {
        $error = 'Failed to delete customer.';
    }
}

// Fetch customers list
$stmt = $pdo->query('SELECT * FROM customers ORDER BY created_at DESC');
$customers = $stmt->fetchAll();

// If editing, fetch customer data
$edit_customer = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $edit_customer = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Pelanggan - Rental PlayStation</title>
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
        <h2>Kelola Pelanggan</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <form method="POST" action="customer_crud.php<?php echo $action === 'edit' ? '?action=edit&id=' . $id : '?action=add'; ?>" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>" />
                <div class="mb-3">
                    <label for="name" class="form-label">Nama Pelanggan</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="name" 
                        name="name" 
                        required 
                        value="<?php echo htmlspecialchars($edit_customer['name'] ?? ''); ?>"
                    />
                </div>
                <div class="mb-3">
                    <label for="contact_info" class="form-label">Informasi Kontak</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="contact_info" 
                        name="contact_info" 
                        value="<?php echo htmlspecialchars($edit_customer['contact_info'] ?? ''); ?>"
                    />
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Perbarui' : 'Tambah'; ?> Pelanggan</button>
                <a href="customer_crud.php" class="btn btn-secondary ms-2">Batal</a>
            </form>
        <?php else: ?>
            <a href="customer_crud.php?action=add" class="btn btn-success mb-3">Tambah Pelanggan Baru</a>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Informasi Kontak</th>
                        <th>Dibuat Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customers) === 0): ?>
                        <tr><td colspan="4" class="text-center">Tidak ada pelanggan ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['contact_info']); ?></td>
                                <td><?php echo htmlspecialchars($customer['created_at']); ?></td>
                                <td>
                                    <a href="customer_crud.php?action=edit&id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">Ubah</a>
                                    <a href="customer_crud.php?action=delete&id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini?');">Hapus</a>
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
