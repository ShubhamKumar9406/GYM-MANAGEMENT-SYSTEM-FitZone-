<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Get statistics
$totalUsers = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$pendingUsers = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND status = 'pending'")->fetch_assoc()['count'];
$activeMembers = $db->query("SELECT COUNT(*) as count FROM members WHERE status = 'active'")->fetch_assoc()['count'];
$totalRevenue = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$pendingPayments = $db->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")->fetch_assoc()['count'];
$totalStaff = $db->query("SELECT COUNT(*) as count FROM staff WHERE status = 'active'")->fetch_assoc()['count'];
$pendingSlotRequests = $db->query("SELECT COUNT(*) as count FROM slot_requests WHERE status = 'pending'")->fetch_assoc()['count'];

// Get recent registrations
$recentUsers = $db->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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

        <h1>Admin Dashboard</h1>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div class="stat-info">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-info">
                    <h3><?php echo $pendingUsers; ?></h3>
                    <p>Pending Approvals</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">✓</div>
                <div class="stat-info">
                    <h3><?php echo $activeMembers; ?></h3>
                    <p>Active Members</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">💰</div>
                <div class="stat-info">
                    <h3>₹<?php echo number_format($totalRevenue, 0); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">💳</div>
                <div class="stat-info">
                    <h3><?php echo $pendingPayments; ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">👨‍💼</div>
                <div class="stat-info">
                    <h3><?php echo $totalStaff; ?></h3>
                    <p>Active Staff</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">🔄</div>
                <div class="stat-info">
                    <h3><?php echo $pendingSlotRequests; ?></h3>
                    <p>Slot Requests</p>
                    <?php if ($pendingSlotRequests > 0): ?>
                        <a href="slot_requests.php" style="font-size: 0.8rem; color: #007bff;">View Requests →</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="quick-actions-grid">
                <a href="manage_users.php" class="btn btn-primary">Manage Users</a>
                <a href="manage_plans.php" class="btn btn-primary">Manage Plans</a>
                <a href="manage_slots.php" class="btn btn-primary">Manage Time Slots</a>
                <a href="manage_staff.php" class="btn btn-primary">Manage Staff</a>
                <a href="manage_equipment.php" class="btn btn-primary">Manage Equipment</a>
                <a href="payments.php" class="btn btn-success">View Payments</a>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div class="card">
            <div class="card-header">
                <h3>Recent User Registrations</h3>
                <a href="manage_users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td>
                                        <?php if ($user['status'] === 'approved'): ?>
                                            <span class="badge badge-success">Approved</span>
                                        <?php elseif ($user['status'] === 'pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Blocked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No recent registrations</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="../assets/js/main.js"></script>
</body>

</html>