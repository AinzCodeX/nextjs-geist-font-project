<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// Fetch income data grouped by month for the last 6 months
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, 
           SUM(total_price) AS income
    FROM transactions
    WHERE status = 'paid'
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
$stmt->execute();
$income_data = $stmt->fetchAll();

$months = [];
$incomes = [];
foreach (array_reverse($income_data) as $row) {
    $months[] = $row['month'];
    $incomes[] = (float)$row['income'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Owner - Rental PlayStation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        #incomeChart {
            margin-top: 1.5rem;
        }
        .btn-secondary {
            background: #4361ee;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: #3b4fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
    </style>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Dashboard Owner</a>
            <div class="d-flex">
                <span class="navbar-text me-3">Masuk sebagai: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-outline-primary btn-sm">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
            <h2 class="mb-4">Ringkasan Pendapatan</h2>
            <div class="chart-container" style="position: relative; height:400px;">
                <canvas id="incomeChart"></canvas>
            </div>

            <div class="mt-5">
                <h3 class="mb-3">Laporan Keuangan</h3>
                <button class="btn btn-secondary d-flex align-items-center gap-2" onclick="window.print()">
                    <i class="bi bi-printer"></i>
                    Cetak Laporan
                </button>
            </div>
    </div>

    <script>
        const ctx = document.getElementById('incomeChart').getContext('2d');
        const incomeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode($incomes); ?>,
                    backgroundColor: 'rgba(67, 97, 238, 0.7)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
