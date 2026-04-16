<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$member = getMemberByUserId($user_id);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="dashboard">
    <nav class="dashboard-nav">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button onclick="toggleSidebar()" id="hamburgerBtn" class="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a href="../index.php" style="color: white; text-decoration: none; font-size: 1.5rem; font-weight: bold;">
                <span class="nav-full-text">💪 FitZone</span>
                <span class="nav-short-text" style="display: none;">💪 FitZone</span>
            </a>
        </div>
        <div class="nav-right-section" style="display: flex; align-items: center; gap: 1rem;">
            <span class="user-name-text" style="color: white;">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </nav>

    <aside class="dashboard-sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" onclick="closeSidebarOnMobile()">📊 Dashboard</a></li>
            <li><a href="profile.php" onclick="closeSidebarOnMobile()">👤 My Profile</a></li>
            <li><a href="attendance.php" class="active" onclick="closeSidebarOnMobile()">📅 Attendance</a></li>
            <li><a href="../index.php" onclick="closeSidebarOnMobile()">🏠 Home</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <h1>My Attendance</h1>

        <!-- Membership Status -->
        <div class="card">
            <div class="card-header">
                <h3>Membership Status</h3>
            </div>
            <?php if ($member && $member['status'] === 'active'): ?>
                <div style="padding: 1.5rem;">
                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div>
                            <strong>Plan:</strong><br>
                            <span style="color: var(--primary-color); font-size: 1.2rem;">
                                <?php echo htmlspecialchars($member['plan_name']); ?>
                            </span>
                        </div>
                        <div>
                            <strong>Time Slot:</strong><br>
                            <span style="color: var(--primary-color); font-size: 1.2rem;">
                                <?php echo $member['slot_name'] ? htmlspecialchars($member['slot_name']) : 'Not Assigned'; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Valid Until:</strong><br>
                            <span style="color: var(--primary-color); font-size: 1.2rem;">
                                <?php echo formatDate($member['end_date']); ?>
                            </span>
                        </div>
                        <div>
                            <strong>Days Left:</strong><br>
                            <span style="color: var(--primary-color); font-size: 1.2rem;">
                                <?php echo daysRemaining($member['end_date']); ?> days
                            </span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: #666;">
                    <p>You don't have an active membership.</p>
                    <a href="dashboard.php" class="btn btn-primary">Purchase a Plan</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Attendance Information -->
        <div class="card">
            <div class="card-header">
                <h3>About Attendance</h3>
            </div>
            <div style="padding: 1.5rem;">
                <div class="alert alert-info" style="background: rgba(0, 123, 255, 0.1); border-left: 4px solid #007bff; padding: 1rem; margin-bottom: 1rem;">
                    <strong>Note:</strong> Attendance tracking is managed at the gym reception. Please check in when you arrive at the gym during your assigned time slot.
                </div>

                <h4 style="margin: 1.5rem 0 1rem;">Your Assigned Slot</h4>
                <?php if ($member && $member['time_slot_id']): ?>
                    <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 10px; border-left: 4px solid var(--primary-color);">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <strong>Slot Name:</strong><br>
                                <span style="font-size: 1.2rem;"><?php echo htmlspecialchars($member['slot_name']); ?></span>
                            </div>
                            <div>
                                <strong>Time:</strong><br>
                                <span style="font-size: 1.2rem;">
                                    <?php echo date('g:i A', strtotime($member['start_time'])); ?> -
                                    <?php echo date('g:i A', strtotime($member['end_time'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="color: #666;">No time slot assigned yet. Please contact the admin.</p>
                <?php endif; ?>

                <h4 style="margin: 2rem 0 1rem;">Gym Timings</h4>
                <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 10px;">
                    <p><strong>Monday - Saturday:</strong> 6:00 AM - 10:00 PM</p>
                    <p><strong>Sunday:</strong> 7:00 AM - 8:00 PM</p>
                    <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                        <em>Please arrive during your assigned time slot for the best experience.</em>
                    </p>
                </div>

                <h4 style="margin: 2rem 0 1rem;">Guidelines</h4>
                <ul style="line-height: 2;">
                    <li>✓ Check in at reception when you arrive</li>
                    <li>✓ Arrive during your assigned time slot</li>
                    <li>✓ Carry your membership card or ID</li>
                    <li>✓ Follow gym rules and safety protocols</li>
                    <li>✓ Contact admin if you need to change your time slot</li>
                </ul>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="card">
            <div class="card-header">
                <h3>Need Help?</h3>
            </div>
            <div style="padding: 1.5rem;">
                <p>If you have any questions about attendance or need to change your time slot, please contact us:</p>
                <div style="margin-top: 1rem;">
                    <p><strong>📞 Phone:</strong> +91 98765 43210</p>
                    <p><strong>📧 Email:</strong> info@fitzonegym.com</p>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>

</html>