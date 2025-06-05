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
    $status = $_POST['status'] ?? 'available';
    $price_per_hour = $_POST['price_per_hour'] ?? '';

    if (empty($name)) {
        $error = 'Name is required.';
    } elseif (!is_numeric($price_per_hour) || $price_per_hour <= 0) {
        $error = 'Price per hour must be a positive number.';
    } elseif (!in_array($status, ['available', 'rented'])) {
        $error = 'Invalid status selected.';
    } else {
        if ($_POST['action'] === 'add') {
            // Insert new playstation
            $stmt = $pdo->prepare('INSERT INTO playstations (name, status, price_per_hour, created_at) VALUES (?, ?, ?, NOW())');
            if ($stmt->execute([$name, $status, $price_per_hour])) {
                $success = 'PlayStation added successfully.';
            } else {
                $error = 'Failed to add PlayStation.';
            }
        } elseif ($_POST['action'] === 'edit' && $id > 0) {
            // Update playstation
            $stmt = $pdo->prepare('UPDATE playstations SET name = ?, status = ?, price_per_hour = ? WHERE id = ?');
            if ($stmt->execute([$name, $status, $price_per_hour, $id])) {
                $success = 'PlayStation updated successfully.';
            } else {
                $error = 'Failed to update PlayStation.';
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare('DELETE FROM playstations WHERE id = ?');
    if ($stmt->execute([$id])) {
        $success = 'PlayStation deleted successfully.';
        $action = '';
    } else {
        $error = 'Failed to delete PlayStation.';
    }
}

// Fetch playstations list
$stmt = $pdo->query('SELECT * FROM playstations ORDER BY created_at DESC');
$playstations = $stmt->fetchAll();

// If editing, fetch playstation data
$edit_playstation = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM playstations WHERE id = ?');
    $stmt->execute([$id]);
    $edit_playstation = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola PlayStation - Rental PlayStation</title>
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
        <h2>Kelola PlayStation</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <form method="POST" action="playstation_crud.php<?php echo $action === 'edit' ? '?action=edit&id=' . $id : '?action=add'; ?>" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>" />
                <div class="mb-3">
                    <label for="name" class="form-label">Nama PlayStation</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="name" 
                        name="name" 
                        required 
                        value="<?php echo htmlspecialchars($edit_playstation['name'] ?? ''); ?>"
                    />
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="available" <?php if (($edit_playstation['status'] ?? '') === 'available') echo 'selected'; ?>>Tersedia</option>
                        <option value="rented" <?php if (($edit_playstation['status'] ?? '') === 'rented') echo 'selected'; ?>>Disewa</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="price_per_hour" class="form-label">Harga Per Jam (USD)</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        class="form-control" 
                        id="price_per_hour" 
                        name="price_per_hour" 
                        required 
                        value="<?php echo htmlspecialchars($edit_playstation['price_per_hour'] ?? ''); ?>"
                    />
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Perbarui' : 'Tambah'; ?> PlayStation</button>
                <a href="playstation_crud.php" class="btn btn-secondary ms-2">Batal</a>
            </form>
        <?php else: ?>
            <a href="playstation_crud.php?action=add" class="btn btn-success mb-3">Tambah PlayStation Baru</a>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>Harga Per Jam</th>
                        <th>Dibuat Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($playstations) === 0): ?>
                        <tr><td colspan="5" class="text-center">Tidak ada PlayStation ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($playstations as $playstation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($playstation['name']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($playstation['status'])); ?></td>
                                <td>$<?php echo number_format($playstation['price_per_hour'], 2); ?></td>
                                <td><?php echo htmlspecialchars($playstation['created_at']); ?></td>
                                <td>
                                    <a href="playstation_crud.php?action=edit&id=<?php echo $playstation['id']; ?>" class="btn btn-sm btn-primary">Ubah</a>
                                    <a href="playstation_crud.php?action=delete&id=<?php echo $playstation['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus PlayStation ini?');">Hapus</a>
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
