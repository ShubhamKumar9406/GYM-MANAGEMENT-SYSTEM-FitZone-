<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If already logged in, redirect
if (isLoggedIn() && !isAdmin()) {
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
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'user'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                if ($user['status'] !== 'blocked') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
                    redirect('dashboard.php');
                } else {
                    $errors[] = 'Your account has been blocked. Please contact admin.';
                }
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
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            position: relative;
            overflow: hidden;
        }

        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #1a1a1a 0%, #000 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 68, 68, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .auth-left-content {
            position: relative;
            z-index: 1;
            color: white;
            max-width: 500px;
        }

        .auth-left-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .auth-left-content .highlight {
            color: var(--primary-color);
        }

        .auth-left-content p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .gym-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .feature-item i {
            font-size: 1.5rem;
        }

        .auth-right {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }

        .auth-form-container {
            width: 100%;
            max-width: 450px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .auth-header .logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .auth-header h2 {
            color: var(--secondary-color);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #666;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.2rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: #1a1a1a;
            background: white;
        }

        .form-group input::placeholder {
            color: #999;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(255, 68, 68, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-color), #cc0000);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 68, 68, 0.3);
        }

        .divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            color: #999;
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
        }

        .auth-footer p {
            color: #666;
            margin-bottom: 1rem;
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .error-list {
            background: rgba(220, 53, 69, 0.1);
            border: 2px solid rgba(220, 53, 69, 0.3);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
        }

        .error-list ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--danger-color);
        }

        @media (max-width: 968px) {
            .auth-left {
                display: none;
            }

            .auth-right {
                flex: 1;
            }
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <!-- Left Side -->
        <div class="auth-left">
            <div class="auth-left-content">
                <h1>Welcome to <span class="highlight">FitZone</span></h1>
                <p>Your journey to a healthier, stronger you starts here. Join thousands of members transforming their lives.</p>

                <div class="gym-features">
                    <div class="feature-item">
                        <i>💪</i>
                        <div>
                            <strong>Expert Trainers</strong>
                            <p style="font-size: 0.9rem; opacity: 0.8; margin: 0;">Professional guidance</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i>🏋️</i>
                        <div>
                            <strong>Modern Equipment</strong>
                            <p style="font-size: 0.9rem; opacity: 0.8; margin: 0;">State-of-the-art facilities</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i>⏰</i>
                        <div>
                            <strong>Flexible Timings</strong>
                            <p style="font-size: 0.9rem; opacity: 0.8; margin: 0;">Choose your slot</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i>🎯</i>
                        <div>
                            <strong>Results Driven</strong>
                            <p style="font-size: 0.9rem; opacity: 0.8; margin: 0;">Achieve your goals</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side -->
        <div class="auth-right">
            <div class="auth-form-container">
                <div class="auth-header">
                    <div class="logo">🏋️</div>
                    <h2>Member Login</h2>
                    <p>Enter your credentials to access your account</p>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-list">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📧</span>
                            <input type="email" id="email" name="email" required
                                placeholder="your@email.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="password" name="password" required
                                placeholder="Enter your password">
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Login to Your Account</button>
                </form>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Create one now</a></p>
                    <p><a href="../index.php">← Back to Home</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>

</html>