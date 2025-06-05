<?php
session_start();
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard based on role
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Harap isi semua kolom.';
    } else {
        // Check fixed owner and employee accounts
        if ($username === 'owner' && $password === 'owner123') {
            $_SESSION['user_id'] = 0;
            $_SESSION['username'] = 'owner';
            $_SESSION['role'] = 'owner';
            header('Location: owner_dashboard.php');
            exit;
        } elseif ($username === 'pegawai' && $password === 'pegawai123') {
            $_SESSION['user_id'] = 0;
            $_SESSION['username'] = 'pegawai';
            $_SESSION['role'] = 'employee';
            header('Location: employee_dashboard.php');
            exit;
        } else {
            // Check in database for customers
            require_once 'db.php';
            $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash']) && $user['role'] === 'customer') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'customer';
                
                if ($remember) {
                    // Set cookie for 30 days
                    setcookie('remember_user', $username, time() + (86400 * 30), "/");
                }
                
                header('Location: customer_dashboard.php');
                exit;
            } else {
                $error = 'Username atau password salah.';
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
    <title>Login - Rental PlayStation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
        .btn-outline-secondary {
            border-color: #e0e0e0;
            color: #4361ee;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background: #4361ee;
            border-color: #4361ee;
            color: white;
        }
        .input-group .form-control {
            border-right: none;
        }
        .input-group .btn {
            border-left: none;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .form-check-input:checked {
            background-color: #4361ee;
            border-color: #4361ee;
        }
        .links {
            font-size: 0.9rem;
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
        .welcome-text {
            color: #2d3748;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="text-center welcome-text">Selamat Datang Kembali!</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php" novalidate>
            <div class="mb-3">
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="Nama Pengguna"
                    required 
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                />
            </div>
            <div class="mb-3">
                <div class="input-group">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Kata Sandi"
                        required 
                    />
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                        <i class="bi bi-eye-slash" id="togglePassword"></i>
                    </button>
                </div>
            </div>
            <script>
                function togglePassword() {
                    const passwordInput = document.getElementById('password');
                    const toggleIcon = document.getElementById('togglePassword');
                    
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        class="form-check-input" 
                        id="remember" 
                        name="remember"
                    />
                    <label class="form-check-label" for="remember">Ingat Saya</label>
                </div>
                <div class="links">
                    <a href="#" onclick="alert('Fitur lupa password akan segera hadir!')">Lupa Password?</a>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Masuk</button>
            <div class="text-center links">
                <a href="register.php">Belum punya akun? Daftar di sini</a>
            </div>
        </form>
    </div>
</body>
</html>
