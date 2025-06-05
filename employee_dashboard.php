<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Pegawai - Rental PlayStation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
        .list-group-item {
            border: none;
            border-radius: 10px !important;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .list-group-item:hover {
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
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
            <a class="navbar-brand" href="#">Dashboard Pegawai</a>
            <div class="d-flex">
                <span class="navbar-text me-3">Masuk sebagai: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-outline-primary btn-sm">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
            <h2 class="mb-4">Menu Pegawai</h2>
            <div class="list-group">
                <a href="customer_crud.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-people me-3"></i>
                    Kelola Pelanggan
                </a>
                <a href="playstation_crud.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-controller me-3"></i>
                    Kelola PlayStation
                </a>
                <a href="transaction_crud.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-receipt me-3"></i>
                    Kelola Transaksi
                </a>
            </div>
    </div>
</body>
</html>
