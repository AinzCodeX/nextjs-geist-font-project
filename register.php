<?php
session_start();
if (isset($_SESSION['user_id'])) {
    // Redirect logged in users to their dashboard
    switch ($_SESSION['role']) {
        case 'owner':
            header('Location: owner_dashboard.php');
            exit;
        case 'employee':
            header('Location: employee_dashboard.php');
            exit;
        case 'customer':
            header('Location: customer_dashboard.php');
            exit;
    }
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Remove role from POST data as all registered users are customers
    $role = 'customer';

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Harap isi semua kolom.';
    } elseif ($password !== $confirm_password) {
        $error = 'Kata sandi tidak cocok.';
    } else {
        // Check if username exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Nama pengguna sudah digunakan.';
        } else {
            // Start transaction
            $pdo->beginTransaction();
            try {
                // Insert new user with role customer
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role, created_at) VALUES (?, ?, "customer", NOW())');
                $stmt->execute([$username, $password_hash]);
                
                // Get the inserted user ID
                $user_id = $pdo->lastInsertId();
                
                // Create customer profile
                $stmt = $pdo->prepare('INSERT INTO customers (user_id, name, contact_info, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$user_id, $username, '']); // Using username as initial name, empty contact info
                
                $pdo->commit();
                $success = 'Pendaftaran berhasil. Anda dapat <a href="index.php">masuk</a> sekarang.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Pendaftaran gagal. Silakan coba lagi.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Daftar - Rental PlayStation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <style>
        body {
            background: #4361ee;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.5s ease forwards;
        }
        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            border-color: #4361ee;
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
        .links a {
            color: #4361ee;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .links a:hover {
            color: #3b4fd8;
            text-decoration: underline;
        }
    </style>
    <div class="login-card">
            <h3 class="mb-4 text-center">Daftar Akun Baru</h3>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" action="register.php" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Nama Pengguna</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username" 
                        required 
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    />
                </div>
            <!-- Removed role selection as all registered users are customers -->
                <div class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            required 
                        />
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', 'togglePassword1')">
                            <i class="bi bi-eye-slash" id="togglePassword1"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                        />
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password', 'togglePassword2')">
                            <i class="bi bi-eye-slash" id="togglePassword2"></i>
                        </button>
                    </div>
                </div>
                <script>
                    function togglePassword(inputId, iconId) {
                        const passwordInput = document.getElementById(inputId);
                        const toggleIcon = document.getElementById(iconId);
                        
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            toggleIcon.classList.remove('bi-eye-slash');
                            toggleIcon.classList.add('bi-eye');
                        } else {
                            passwordInput.type = 'password';
                            toggleIcon.classList.remove('bi-eye');
                            toggleIcon.classList.add('bi-eye-slash');
                        }
                    }
                </script>
                <button type="submit" class="btn btn-primary w-100">Daftar</button>
            </form>
            <div class="mt-3 text-center links">
                <a href="index.php">Sudah punya akun? Masuk di sini</a>
            </div>
    </div>
</body>
</html>
