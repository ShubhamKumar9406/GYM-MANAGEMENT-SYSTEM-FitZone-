<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } elseif (emailExists($email)) {
        $errors[] = 'Email already registered';
    }

    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be 10 digits';
    }

    if (empty($gender)) {
        $errors[] = 'Gender is required';
    } elseif (!in_array($gender, ['male', 'female', 'other'])) {
        $errors[] = 'Invalid gender selection';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    // If no errors, register user
    if (empty($errors)) {
        $db = getDB();
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("INSERT INTO users (name, email, phone, gender, password, role, status) VALUES (?, ?, ?, ?, ?, 'user', 'pending')");
        $stmt->bind_param("sssss", $name, $email, $phone, $gender, $hashed_password);

        if ($stmt->execute()) {
            $user_id = $db->insert_id;

            // Create member record (inactive until admin activates)
            $stmt2 = $db->prepare("INSERT INTO members (user_id, status) VALUES (?, 'inactive')");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();

            setFlashMessage('success', 'Registration successful! Please wait for admin approval.');
            redirect('login.php');
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
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
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -200px;
            right: -200px;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
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

        .auth-left-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }

        .benefits-list li {
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.1rem;
        }

        .benefits-list li i {
            font-size: 1.8rem;
        }

        .auth-right {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            overflow-y: auto;
        }

        .auth-form-container {
            width: 100%;
            max-width: 500px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
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
            font-size: 0.95rem;
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
            padding: 14px 14px 14px 45px;
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

        .form-group small {
            display: block;
            margin-top: 0.3rem;
            color: #999;
            font-size: 0.85rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-register {
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
            margin-top: 1rem;
        }

        .btn-register:hover {
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
            font-size: 0.95rem;
        }

        @media (max-width: 968px) {
            .auth-left {
                display: none;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <!-- Left Side -->
        <div class="auth-left">
            <div class="auth-left-content">
                <h1>Start Your Fitness Journey Today!</h1>
                <p>Join FitZone and get access to world-class facilities, expert trainers, and a community that motivates you to achieve your goals.</p>

                <ul class="benefits-list">
                    <li>
                        <i>✓</i>
                        <div>
                            <strong>Premium Membership</strong>
                            <p style="font-size: 0.9rem; opacity: 0.9; margin: 0;">Access to all equipment and facilities</p>
                        </div>
                    </li>
                    <li>
                        <i>✓</i>
                        <div>
                            <strong>Expert Guidance</strong>
                            <p style="font-size: 0.9rem; opacity: 0.9; margin: 0;">Professional trainers to guide you</p>
                        </div>
                    </li>
                    <li>
                        <i>✓</i>
                        <div>
                            <strong>Flexible Plans</strong>
                            <p style="font-size: 0.9rem; opacity: 0.9; margin: 0;">Choose what works best for you</p>
                        </div>
                    </li>
                    <li>
                        <i>✓</i>
                        <div>
                            <strong>Results Guaranteed</strong>
                            <p style="font-size: 0.9rem; opacity: 0.9; margin: 0;">Achieve your fitness goals faster</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Side -->
        <div class="auth-right">
            <div class="auth-form-container">
                <div class="auth-header">
                    <div class="logo">🏋️</div>
                    <h2>Create Account</h2>
                    <p>Fill in your details to get started</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-list">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" onsubmit="return validateForm('registerForm');" id="registerForm">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" id="name" name="name" required
                                placeholder="John Doe"
                                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <div class="input-wrapper">
                                <span class="input-icon">📧</span>
                                <input type="email" id="email" name="email" required
                                    placeholder="your@email.com"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <div class="input-wrapper">
                                <span class="input-icon">📱</span>
                                <input type="tel" id="phone" name="phone" required
                                    placeholder="9876543210" maxlength="10" pattern="[0-9]{10}"
                                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <div class="input-wrapper">
                            <span class="input-icon">⚧</span>
                            <select id="gender" name="gender" required style="padding: 14px 14px 14px 45px; width: 100%; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; color: #1a1a1a; background: white;">
                                <option value="">-- Select Gender --</option>
                                <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <div class="input-wrapper">
                                <span class="input-icon">🔒</span>
                                <input type="password" id="password" name="password" required minlength="6"
                                    placeholder="Min 6 characters">
                                <button type="button" class="toggle-password" onclick="togglePassword('password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; color: #999;">👁️</button>
                            </div>
                            <small>Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <div class="input-wrapper">
                                <span class="input-icon">🔒</span>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                    placeholder="Re-enter password">
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; color: #999;">👁️</button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-register">Create My Account</button>
                </form>

                <div class="divider">
                    <span>or</span>
                </div>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign in instead</a></p>
                    <p><a href="../index.php">← Back to Home</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = '🙈';
            } else {
                field.type = 'password';
                button.textContent = '👁️';
            }
        }
    </script>
</body>

</html>