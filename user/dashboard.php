<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$member = getMemberByUserId($user_id);

// Get payment information
$db = getDB();
$stmt = $db->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

// Get all active time slots
$slots = $db->query("SELECT * FROM time_slots WHERE status = 'active' ORDER BY start_time")->fetch_all(MYSQLI_ASSOC);

// Check if user has pending slot request
$stmt = $db->prepare("SELECT sr.*, ts.slot_name, ts.start_time, ts.end_time FROM slot_requests sr JOIN time_slots ts ON sr.requested_slot_id = ts.id WHERE sr.user_id = ? AND sr.status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$slot_request = $stmt->get_result()->fetch_assoc();

// Handle plan purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purchase_plan') {
    $plan_id = intval($_POST['plan_id']);
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    $transaction_id = sanitize($_POST['transaction_id'] ?? '');

    // Get plan details
    $stmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $plan = $stmt->get_result()->fetch_assoc();

    if ($plan && $payment_method) {
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));

        // Update member record (inactive until admin approves)
        if ($member) {
            $stmt = $db->prepare("UPDATE members SET plan_id = ?, status = 'inactive' WHERE user_id = ?");
            $stmt->bind_param("ii", $plan_id, $user_id);
        } else {
            $stmt = $db->prepare("INSERT INTO members (user_id, plan_id, status) VALUES (?, ?, 'inactive')");
            $stmt->bind_param("ii", $user_id, $plan_id);
        }
        $stmt->execute();
        $member_id = $member ? $member['id'] : $db->insert_id;

        // Create payment record
        $notes = "Method: " . $payment_method . ($transaction_id ? " | Transaction ID: " . $transaction_id : "");
        $stmt = $db->prepare("INSERT INTO payments (member_id, user_id, plan_id, amount, payment_date, status, payment_method, notes) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->bind_param("iiidsss", $member_id, $user_id, $plan_id, $plan['price'], $start_date, $payment_method, $notes);
        $stmt->execute();

        setFlashMessage('success', 'Plan purchased! Your payment is being verified by admin. You will be notified once approved.');
        redirect('dashboard.php');
    }
}

// Handle slot selection/request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'select_slot') {
    $slot_id = intval($_POST['slot_id'] ?? 0);

    if ($slot_id && $member) {
        // Get slot details
        $stmt = $db->prepare("SELECT * FROM time_slots WHERE id = ?");
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $slot = $stmt->get_result()->fetch_assoc();

        if ($slot) {
            $old_slot_id = $member['time_slot_id'];

            // Check if user is selecting their current slot
            if ($old_slot_id == $slot_id) {
                setFlashMessage('info', 'You are already assigned to this time slot.');
                redirect('dashboard.php');
            }

            $capacity_percent = ($slot['current_members'] / $slot['max_members']) * 100;

            if ($capacity_percent < 50) {
                // Auto-assign slot (less than 50% full)
                $stmt = $db->prepare("UPDATE members SET time_slot_id = ? WHERE user_id = ?");
                $stmt->bind_param("ii", $slot_id, $user_id);

                if ($stmt->execute()) {
                    // Update slot counts
                    if ($old_slot_id) {
                        $db->query("UPDATE time_slots SET current_members = current_members - 1 WHERE id = $old_slot_id");
                    }
                    $db->query("UPDATE time_slots SET current_members = current_members + 1 WHERE id = $slot_id");

                    setFlashMessage('success', 'Time slot assigned successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to assign time slot.');
                }
            } else {
                // Create slot change request (50% or more full)
                $request_notes = "User requested slot change to: {$slot['slot_name']} ({$slot['start_time']} - {$slot['end_time']})";
                $stmt = $db->prepare("INSERT INTO slot_requests (user_id, requested_slot_id, current_slot_id, status, notes, created_at) VALUES (?, ?, ?, 'pending', ?, NOW()) ON DUPLICATE KEY UPDATE requested_slot_id = ?, status = 'pending', notes = ?, created_at = NOW()");
                $current_slot = $member['time_slot_id'];
                $stmt->bind_param("iiiiss", $user_id, $slot_id, $current_slot, $request_notes, $slot_id, $request_notes);

                if ($stmt->execute()) {
                    setFlashMessage('info', 'Slot change request submitted! Admin will review and approve your request.');
                } else {
                    setFlashMessage('danger', 'Failed to submit slot request.');
                }
            }
        }
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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
    <!-- Navigation -->
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

    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active" onclick="closeSidebarOnMobile()">📊 Dashboard</a></li>
            <li><a href="profile.php" onclick="closeSidebarOnMobile()">👤 My Profile</a></li>
            <li><a href="attendance.php" onclick="closeSidebarOnMobile()">📅 Attendance</a></li>
            <li><a href="../index.php" onclick="closeSidebarOnMobile()">🏠 Home</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <?php displayFlashMessage(); ?>

        <h1>My Dashboard</h1>

        <?php if ((!$member || !$member['plan_id']) && (!$member || $member['status'] !== 'active')): ?>
            <div class="alert alert-info">
                <strong>🎯 Get Started:</strong> Purchase a membership plan below. After payment verification, admin will activate your membership and assign your preferred time slot.
            </div>
        <?php elseif ($member && $member['plan_id'] && $member['status'] === 'inactive'): ?>
            <div class="alert alert-warning">
                <strong>⏳ Payment Verification:</strong> Your payment is being verified by admin. You will be notified once your membership is activated and slot is assigned.
            </div>
        <?php elseif ($member && $member['status'] === 'active'): ?>
            <div class="alert alert-success">
                <strong>✅ Active Membership:</strong> Your membership is active! Enjoy full access to the gym facilities during your assigned time slot.
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon red">💪</div>
                <div class="stat-info">
                    <h3>
                        <?php if ($member && $member['status'] === 'active'): ?>
                            Active Member
                        <?php elseif ($user['status'] === 'pending'): ?>
                            Payment Pending
                        <?php else: ?>
                            Inactive
                        <?php endif; ?>
                    </h3>
                    <p>Membership Status</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-info">
                    <h3>
                        <?php
                        if ($member && $member['end_date']) {
                            echo daysRemaining($member['end_date']);
                        } else {
                            echo '0';
                        }
                        ?>
                    </h3>
                    <p>Days Remaining</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">⏰</div>
                <div class="stat-info">
                    <h3><?php echo $member && $member['slot_name'] ? htmlspecialchars($member['slot_name']) : 'Not Assigned'; ?></h3>
                    <p>Time Slot</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">💰</div>
                <div class="stat-info">
                    <h3><?php echo $payment ? htmlspecialchars($payment['status']) : 'No Payment'; ?></h3>
                    <p>Payment Status</p>
                </div>
            </div>
        </div>

        <!-- Current Plan -->
        <div class="card">
            <div class="card-header">
                <h3>Current Membership Plan</h3>
            </div>
            <?php if ($member && $member['plan_id']): ?>
                <table>
                    <tr>
                        <th>Plan Name</th>
                        <td><?php echo htmlspecialchars($member['plan_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <td>₹<?php echo number_format($member['price'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Duration</th>
                        <td><?php echo $member['duration_days']; ?> Days</td>
                    </tr>
                    <tr>
                        <th>Start Date</th>
                        <td><?php echo $member['start_date'] ? formatDate($member['start_date']) : '<span style="color: #999;">Pending Activation</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Expiry Date</th>
                        <td>
                            <?php
                            if ($member['end_date']) {
                                echo formatDate($member['end_date']);
                                $days = daysRemaining($member['end_date']);
                                if ($days <= 7 && $days > 0) {
                                    echo ' <span class="badge badge-warning">Expires Soon!</span>';
                                } elseif ($days == 0) {
                                    echo ' <span class="badge badge-danger">Expired</span>';
                                }
                            } else {
                                echo '<span style="color: #999;">Pending Activation</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ($member['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif ($member['status'] === 'expired'): ?>
                                <span class="badge badge-danger">Expired</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: #666;">
                    You don't have an active membership plan yet.
                    <br><br>
                    <a href="#" onclick="openModal('purchasePlanModal')" class="btn btn-primary">Purchase a Plan</a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Time Slot Information -->
        <div class="card">
            <div class="card-header">
                <h3>My Time Slot</h3>
                <?php if ($member && $member['status'] === 'active'): ?>
                    <button onclick="openModal('changeSlotModal')" class="btn btn-sm btn-primary">
                        🔄 Change Slot
                    </button>
                <?php endif; ?>
            </div>
            <?php if ($member && $member['time_slot_id']): ?>
                <table>
                    <tr>
                        <th>Slot Name</th>
                        <td><?php echo htmlspecialchars($member['slot_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Time</th>
                        <td>
                            <?php
                            echo date('g:i A', strtotime($member['start_time'])) . ' - ' .
                                date('g:i A', strtotime($member['end_time']));
                            ?>
                        </td>
                    </tr>
                </table>

                <?php if ($slot_request): ?>
                    <div class="alert alert-info" style="margin-top: 1rem;">
                        <strong>📝 Pending Slot Request:</strong> You have requested to change to <strong><?php echo htmlspecialchars($slot_request['slot_name']); ?></strong> (<?php echo date('g:i A', strtotime($slot_request['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot_request['end_time'])); ?>). Waiting for admin approval.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: #666;">
                    No time slot assigned yet.
                    <?php if ($member && $member['status'] === 'active'): ?>
                        <br><br>
                        <button onclick="openModal('changeSlotModal')" class="btn btn-primary">Select Your Time Slot</button>
                    <?php else: ?>
                        The admin will assign you a slot after plan activation.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Payment Status -->
        <?php if ($payment): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Latest Payment</h3>
                </div>
                <table>
                    <tr>
                        <th>Amount</th>
                        <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Payment Date</th>
                        <td><?php echo formatDate($payment['payment_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ($payment['status'] === 'paid'): ?>
                                <span class="badge badge-success">Paid</span>
                            <?php elseif ($payment['status'] === 'due'): ?>
                                <span class="badge badge-danger">Due</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($payment['notes']): ?>
                        <tr>
                            <th>Notes</th>
                            <td><?php echo htmlspecialchars($payment['notes']); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>

        <!-- Purchase Plan Button -->
        <?php if (!$member || $member['status'] !== 'active'): ?>
            <div style="text-align: center; margin: 2rem 0;">
                <button onclick="openModal('purchasePlanModal')" class="btn btn-primary btn-lg">
                    🛒 Purchase Membership Plan
                </button>
            </div>
        <?php endif; ?>
    </main>

    <!-- Purchase Plan Modal -->
    <div id="purchasePlanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Purchase Membership Plan</h3>
                <span class="close" onclick="closeModal('purchasePlanModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="purchase_plan">
                <div class="form-group">
                    <label for="plan_id">Select Plan *</label>
                    <select id="plan_id" name="plan_id" required onchange="updatePlanDetails()">
                        <option value="">-- Choose a Plan --</option>
                        <?php
                        $plans = getActivePlans();
                        foreach ($plans as $plan): ?>
                            <option value="<?php echo $plan['id']; ?>"
                                data-price="<?php echo $plan['price']; ?>"
                                data-duration="<?php echo $plan['duration_days']; ?>"
                                data-description="<?php echo htmlspecialchars($plan['description']); ?>">
                                <?php echo htmlspecialchars($plan['name']); ?> - ₹<?php echo number_format($plan['price'], 0); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="planDetails" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <p><strong>Price:</strong> ₹<span id="planPrice"></span></p>
                    <p><strong>Duration:</strong> <span id="planDuration"></span> Days</p>
                    <p><strong>Description:</strong> <span id="planDescription"></span></p>
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method *</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">-- Select Payment Method --</option>
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI / PhonePe / Google Pay</option>
                        <option value="Card">Debit/Credit Card</option>
                        <option value="Net Banking">Net Banking</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="transaction_id">Transaction ID / Reference Number</label>
                    <input type="text" id="transaction_id" name="transaction_id"
                        placeholder="Enter transaction ID, UPI ref, or cheque number">
                    <small style="color: #666; display: block; margin-top: 0.3rem;">
                        Optional: For online payments, cheque numbers, etc.
                    </small>
                </div>

                <div class="alert alert-info" style="margin-bottom: 1rem; font-size: 0.9rem;">
                    <strong>📋 Process:</strong><br>
                    1. Select your plan and payment method<br>
                    2. Click "Confirm Purchase" below<br>
                    3. Admin will verify your payment<br>
                    4. Once approved, your membership will be activated and slot assigned
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Confirm Purchase</button>
            </form>
        </div>
    </div>

    <!-- Change Slot Modal -->
    <div id="changeSlotModal" class="modal">
        <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px 10px 0 0; margin: -1.5rem -1.5rem 1.5rem -1.5rem;">
                <div>
                    <h2 style="margin: 0; font-size: 1.8rem;">⏰ Choose Your Time Slot</h2>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 0.95rem;">Select a time that works best for your schedule</p>
                </div>
                <span class="close" onclick="closeModal('changeSlotModal')" style="color: white; opacity: 0.9; font-size: 2rem; line-height: 1;">&times;</span>
            </div>

            <div style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #667eea;">
                <div style="display: flex; align-items: start; gap: 0.8rem;">
                    <span style="font-size: 1.5rem;">💡</span>
                    <div>
                        <strong style="color: #333; font-size: 1rem;">How it works:</strong>
                        <div style="margin-top: 0.5rem; color: #555; font-size: 0.9rem; line-height: 1.6;">
                            <div style="margin-bottom: 0.3rem;">✅ <strong>Available slots</strong> (less than 50% full) - Instant assignment</div>
                            <div>📝 <strong>Busy slots</strong> (50% or more full) - Request sent to admin</div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="select_slot">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 1.2rem;">
                    <?php foreach ($slots as $slot):
                        $capacity_percent = ($slot['current_members'] / $slot['max_members']) * 100;
                        $is_available = $capacity_percent < 50;
                        $is_current = ($member && $member['time_slot_id'] == $slot['id']);

                        // Determine card colors
                        if ($is_current) {
                            $border_color = '#28a745';
                            $bg_color = '#f0fff4';
                            $accent_color = '#28a745';
                        } elseif ($is_available) {
                            $border_color = '#667eea';
                            $bg_color = '#ffffff';
                            $accent_color = '#667eea';
                        } else {
                            $border_color = '#ffc107';
                            $bg_color = '#fffef7';
                            $accent_color = '#ffc107';
                        }
                    ?>
                        <label class="slot-card" onclick="this.querySelector('input').checked = true;" style="
                            display: block;
                            position: relative;
                            background: <?php echo $bg_color; ?>;
                            border: 3px solid <?php echo $border_color; ?>;
                            border-radius: 12px;
                            padding: 1.5rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                        " onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.15)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)';">

                            <input type="radio" name="slot_id" value="<?php echo $slot['id']; ?>"
                                <?php echo $is_current ? 'checked' : ''; ?>
                                style="position: absolute; top: 1rem; right: 1rem; width: 20px; height: 20px; cursor: pointer; accent-color: <?php echo $accent_color; ?>;" required>

                            <!-- Header -->
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(0,0,0,0.08);">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <span style="font-size: 1.5rem;">🕐</span>
                                    <strong style="font-size: 1.3rem; color: #333;"><?php echo htmlspecialchars($slot['slot_name']); ?></strong>
                                </div>
                                <div style="font-size: 1.1rem; color: <?php echo $accent_color; ?>; font-weight: 600;">
                                    <?php echo date('g:i A', strtotime($slot['start_time'])); ?> -
                                    <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div style="margin-bottom: 1rem;">
                                <?php if ($is_current): ?>
                                    <span style="display: inline-block; background: #28a745; color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        ✓ Your Current Slot
                                    </span>
                                <?php elseif ($is_available): ?>
                                    <span style="display: inline-block; background: #667eea; color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        ⚡ Instant Assignment
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; background: #ffc107; color: #333; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        📝 Needs Approval
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Capacity Info -->
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.9rem; color: #666; font-weight: 500;">Capacity</span>
                                    <strong style="font-size: 1rem; color: #333;">
                                        <?php echo $slot['current_members']; ?> / <?php echo $slot['max_members']; ?>
                                        <span style="color: #999; font-size: 0.85rem;">(<?php echo round($capacity_percent); ?>%)</span>
                                    </strong>
                                </div>

                                <!-- Progress Bar -->
                                <div style="
                                    width: 100%;
                                    height: 10px;
                                    background: #e0e0e0;
                                    border-radius: 10px;
                                    overflow: hidden;
                                    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
                                ">
                                    <div style="
                                        width: <?php echo $capacity_percent; ?>%;
                                        height: 100%;
                                        background: linear-gradient(90deg, <?php echo $accent_color; ?>, <?php echo $accent_color; ?>dd);
                                        border-radius: 10px;
                                        transition: width 0.3s ease;
                                    "></div>
                                </div>

                                <!-- Capacity Text -->
                                <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">
                                    <?php if ($capacity_percent < 30): ?>
                                        😊 Plenty of space available
                                    <?php elseif ($capacity_percent < 50): ?>
                                        👍 Good availability
                                    <?php elseif ($capacity_percent < 80): ?>
                                        ⚠️ Filling up fast
                                    <?php else: ?>
                                        🔥 Almost full
                                    <?php endif; ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary" style="
                    width: 100%; 
                    margin-top: 2rem; 
                    padding: 1rem; 
                    font-size: 1.1rem; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                ">
                    Confirm Selection →
                </button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function updatePlanDetails() {
            const select = document.getElementById('plan_id');
            const option = select.options[select.selectedIndex];
            const details = document.getElementById('planDetails');

            if (option.value) {
                document.getElementById('planPrice').textContent = '₹' + parseFloat(option.dataset.price).toLocaleString('en-IN');
                document.getElementById('planDuration').textContent = option.dataset.duration;
                document.getElementById('planDescription').textContent = option.dataset.description;
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }

        // Mobile sidebar toggle
        if (window.innerWidth <= 968) {
            document.querySelector('button[onclick="toggleSidebar()"]').style.display = 'block';
        }
    </script>
</body>

</html>