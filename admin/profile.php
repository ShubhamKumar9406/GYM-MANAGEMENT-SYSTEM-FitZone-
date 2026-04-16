<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);

    $errors = [];
    $profile_image = $user['profile_image']; // Keep existing image by default

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    } elseif (emailExists($email, $user_id)) {
        $errors[] = 'Email already in use';
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file_type = $_FILES['profile_image']['type'];
        $file_size = $_FILES['profile_image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Only JPG, PNG, and GIF images are allowed';
        } elseif ($file_size > $max_size) {
            $errors[] = 'Image size must be less than 5MB';
        } else {
            // Create unique filename
            $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($user['name']));
            $filename = 'user_' . $user_id . '_' . $clean_name . '.' . $extension;
            $upload_path = __DIR__ . '/../user/user_uploads/' . $filename;

            // Delete old profile image if exists
            if ($user['profile_image'] && file_exists(__DIR__ . '/../user/user_uploads/' . $user['profile_image'])) {
                unlink(__DIR__ . '/../user/user_uploads/' . $user['profile_image']);
            }

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = $filename;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $profile_image, $user_id);

        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            setFlashMessage('success', 'Profile updated successfully!');
            redirect('profile.php');
        } else {
            $errors[] = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    if (!password_verify($current_password, $user['password'])) {
        $errors[] = 'Current password is incorrect';
    }

    if (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters';
    }

    if ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    if (empty($errors)) {
        $db = getDB();
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user_id);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Password changed successfully!');
            redirect('profile.php');
        } else {
            $errors[] = 'Failed to change password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-group input,
        .form-group select,
        .form-group textarea {
            background: white !important;
            color: #1a1a1a !important;
            border: 2px solid #e0e0e0 !important;
        }

        .form-group input::placeholder {
            color: #999 !important;
        }
    </style>
</head>

<body class="dashboard">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <?php displayFlashMessage(); ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h1>My Profile</h1>

        <!-- Profile Information -->
        <div class="card">
            <div class="card-header">
                <h3>Profile Information</h3>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Profile Image Section -->
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div style="display: inline-block; position: relative;">
                        <?php if ($user['profile_image'] && file_exists(__DIR__ . '/../user/user_uploads/' . $user['profile_image'])): ?>
                            <img src="../user/user_uploads/<?php echo htmlspecialchars($user['profile_image']); ?>"
                                alt="Profile"
                                id="profilePreview"
                                style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color); box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                        <?php else: ?>
                            <div id="profilePreview" style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 4rem; color: white; border: 4px solid var(--primary-color); box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 1rem;">
                        <label for="profile_image" class="btn btn-primary" style="cursor: pointer; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 0.6rem 1.5rem; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                            📷 Change Photo
                        </label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;" onchange="previewImage(event)">
                        <p style="font-size: 0.85rem; color: #666; margin-top: 0.5rem;">JPG, PNG, or GIF (Max 5MB)</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+91 1234567890">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="Administrator" disabled style="background: #f5f5f5 !important; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Member Since</label>
                    <input type="text" value="<?php echo formatDate($user['created_at']); ?>" disabled style="background: #f5f5f5 !important; cursor: not-allowed;">
                </div>

                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h3>Change Password</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="change_password" value="1">

                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                    <small style="color: #666; display: block; margin-top: 0.3rem;">
                        Password must be at least 6 characters long
                    </small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" class="btn btn-warning">Change Password</button>
            </form>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        preview.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
                    }
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>

</html>