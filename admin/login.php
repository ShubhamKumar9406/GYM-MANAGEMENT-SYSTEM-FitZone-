<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If already logged in as admin, redirect
if (isAdmin()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Please enter both email and password';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                setFlashMessage('success', 'Welcome back, Admin!');
                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid email or password';
            }
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a1a 0%, #000 100%);
            padding: 2rem;
        }

        .auth-box {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(255, 68, 68, 0.3);
            max-width: 450px;
            width: 100%;
            animation: fadeInUp 0.5s ease;
        }

        .auth-box h2 {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 2rem;
        }

        .admin-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .error-list {
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid var(--danger-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }

        .error-list ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--danger-color);
        }

        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .form-group input {
            color: #1a1a1a;
            background: white;
        }

        .form-group input::placeholder {
            color: #999;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-box">
            <div style="text-align: center;">
                <span class="admin-badge">🔐 ADMIN ACCESS</span>
            </div>
            <h2>Admin Login</h2>

            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Login as Admin</button>
            </form>

            <div class="auth-links">
                <p><a href="../index.php">← Back to Home</a></p>
                <p style="margin-top: 1rem; font-size: 0.85rem; color: #666;">
                    <!-- Default credentials: admin@gym.com / admin123 -->
                </p>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>

</html>