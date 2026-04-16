<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($action === 'approve' && $user_id) {
        $slot_id = intval($_POST['slot_id'] ?? 0);

        if ($slot_id) {
            // Check slot capacity
            $stmt = $db->prepare("SELECT max_members, current_members FROM time_slots WHERE id = ?");
            $stmt->bind_param("i", $slot_id);
            $stmt->execute();
            $slot = $stmt->get_result()->fetch_assoc();

            if ($slot && $slot['current_members'] < $slot['max_members']) {
                // Get member and payment info
                $stmt = $db->prepare("SELECT m.id as member_id, m.plan_id, p.duration_days, pay.id as payment_id 
                                      FROM members m 
                                      LEFT JOIN plans p ON m.plan_id = p.id
                                      LEFT JOIN payments pay ON pay.user_id = m.user_id AND pay.plan_id = m.plan_id
                                      WHERE m.user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();

                if ($result && $result['plan_id']) {
                    // Calculate dates
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d', strtotime("+{$result['duration_days']} days"));

                    // Update user status to approved
                    $stmt = $db->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    // Activate membership with slot and dates
                    $stmt = $db->prepare("UPDATE members SET time_slot_id = ?, start_date = ?, end_date = ?, status = 'active' WHERE user_id = ?");
                    $stmt->bind_param("issi", $slot_id, $start_date, $end_date, $user_id);
                    $stmt->execute();

                    // Update slot count
                    $db->query("UPDATE time_slots SET current_members = current_members + 1 WHERE id = $slot_id");

                    // Mark payment as paid
                    if ($result['payment_id']) {
                        $stmt = $db->prepare("UPDATE payments SET status = 'paid' WHERE id = ?");
                        $stmt->bind_param("i", $result['payment_id']);
                        $stmt->execute();
                    }

                    setFlashMessage('success', 'User approved! Membership activated and slot assigned.');
                    redirect('manage_users.php'); // Immediate redirect to refresh data
                } else {
                    setFlashMessage('danger', 'User has not purchased any plan yet.');
                }
            } else {
                setFlashMessage('danger', 'Selected time slot is full.');
            }
        } else {
            setFlashMessage('danger', 'Please select a time slot for approval.');
        }
    } elseif ($action === 'block' && $user_id) {
        $stmt = $db->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            setFlashMessage('success', 'User blocked successfully!');
        }
    } elseif ($action === 'assign_slot') {
        $slot_id = intval($_POST['slot_id'] ?? 0);

        if ($user_id && $slot_id) {
            // Check if user has an active membership plan
            $stmt = $db->prepare("SELECT id, status FROM members WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();

            if (!$member || $member['status'] !== 'active') {
                setFlashMessage('danger', 'User must have an active membership plan before assigning time slot.');
            } else {
                // Check slot capacity
                $stmt = $db->prepare("SELECT max_members, current_members FROM time_slots WHERE id = ?");
                $stmt->bind_param("i", $slot_id);
                $stmt->execute();
                $slot = $stmt->get_result()->fetch_assoc();

                if ($slot && $slot['current_members'] < $slot['max_members']) {
                    // Get current slot of user
                    $stmt = $db->prepare("SELECT time_slot_id FROM members WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $member_data = $stmt->get_result()->fetch_assoc();
                    $old_slot_id = $member_data['time_slot_id'] ?? null;

                    // Check if user already has this slot
                    if ($old_slot_id == $slot_id) {
                        setFlashMessage('info', 'User is already assigned to this time slot.');
                    } else {
                        // Update member's time slot
                        $stmt = $db->prepare("UPDATE members SET time_slot_id = ? WHERE user_id = ?");
                        $stmt->bind_param("ii", $slot_id, $user_id);

                        if ($stmt->execute()) {
                            // Update slot counts only if changing to a different slot
                            if ($old_slot_id) {
                                $db->query("UPDATE time_slots SET current_members = current_members - 1 WHERE id = $old_slot_id");
                            }
                            $db->query("UPDATE time_slots SET current_members = current_members + 1 WHERE id = $slot_id");

                            setFlashMessage('success', 'Time slot assigned successfully!');
                        } else {
                            setFlashMessage('danger', 'Failed to assign time slot.');
                        }
                    }
                } else {
                    setFlashMessage('danger', 'Time slot is full or invalid.');
                }
            }
        }
    } elseif ($action === 'activate_plan') {
        $plan_id = intval($_POST['plan_id'] ?? 0);

        if ($user_id && $plan_id) {
            // Get plan details
            $stmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
            $stmt->bind_param("i", $plan_id);
            $stmt->execute();
            $plan = $stmt->get_result()->fetch_assoc();

            // Check if user has paid for this exact plan amount
            $stmt = $db->prepare("SELECT id, amount, status FROM payments WHERE user_id = ? AND status = 'paid' AND amount = ?");
            $stmt->bind_param("id", $user_id, $plan['price']);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();

            if (!$payment) {
                setFlashMessage('danger', 'Cannot activate plan! User must pay ₹' . number_format($plan['price']) . ' first.');
            } elseif ($plan) {
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));

                // Check if member exists
                $stmt = $db->prepare("SELECT id FROM members WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Update existing member
                    $stmt = $db->prepare("UPDATE members SET plan_id = ?, start_date = ?, end_date = ?, status = 'active' WHERE user_id = ?");
                    $stmt->bind_param("issi", $plan_id, $start_date, $end_date, $user_id);
                } else {
                    // Create new member
                    $stmt = $db->prepare("INSERT INTO members (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt->bind_param("iiss", $user_id, $plan_id, $start_date, $end_date);
                }

                if ($stmt->execute()) {
                    // Link payment to this plan
                    $stmt = $db->prepare("UPDATE payments SET plan_id = ? WHERE id = ?");
                    $stmt->bind_param("ii", $plan_id, $payment['id']);
                    $stmt->execute();

                    // Also update user status to approved when activating plan
                    $stmt = $db->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    setFlashMessage('success', 'Membership plan activated successfully!');
                } else {
                    setFlashMessage('danger', 'Failed to activate plan.');
                }
            }
        }
    } elseif ($action === 'update_payment') {
        $payment_id = intval($_POST['payment_id'] ?? 0);
        $payment_status = sanitize($_POST['payment_status'] ?? '');

        if ($payment_id && $payment_status) {
            $stmt = $db->prepare("UPDATE payments SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $payment_status, $payment_id);
            if ($stmt->execute()) {
                setFlashMessage('success', 'Payment status updated!');
            }
        }
    }

    redirect('manage_users.php');
}

// Get all users with member info and payment status
$users = $db->query("SELECT u.*, m.plan_id, m.time_slot_id, m.status as member_status, m.end_date,
                      p.name as plan_name, p.price as plan_price, t.slot_name,
                      pay.status as payment_status, pay.amount as payment_amount, pay.payment_method, pay.notes as payment_notes,
                      pay.payment_date, pay.id as payment_id
                      FROM users u
                      LEFT JOIN members m ON u.id = m.user_id
                      LEFT JOIN plans p ON m.plan_id = p.id
                      LEFT JOIN time_slots t ON m.time_slot_id = t.id
                      LEFT JOIN payments pay ON pay.user_id = u.id AND pay.plan_id = m.plan_id
                      WHERE u.role = 'user'
                      ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$plans = getActivePlans();
$timeSlots = getActiveTimeSlots();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo SITE_NAME; ?></title>
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

        /* Fixed column widths for users table */
        #usersTable {
            table-layout: fixed;
            width: 100%;
            font-size: 0.875rem;
        }

        #usersTable th:nth-child(1) {
            width: 40px;
        }

        /* ID */
        #usersTable th:nth-child(2) {
            width: 120px;
        }

        /* Name */
        #usersTable th:nth-child(3) {
            width: 150px;
        }

        /* Email */
        #usersTable th:nth-child(4) {
            width: 100px;
        }

        /* Phone */
        #usersTable th:nth-child(5) {
            width: 60px;
        }

        /* Gender */
        #usersTable th:nth-child(6) {
            width: 85px;
        }

        /* Status */
        #usersTable th:nth-child(7) {
            width: 110px;
        }

        /* Plan */
        #usersTable th:nth-child(8) {
            width: 110px;
        }

        /* Payment */
        #usersTable th:nth-child(9) {
            width: 100px;
        }

        /* Time Slot */
        #usersTable th:nth-child(10) {
            width: 95px;
        }

        /* Expiry */
        #usersTable th:nth-child(11) {
            width: 130px;
        }

        /* Actions */

        #usersTable td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            padding: 8px 6px;
        }

        #usersTable th {
            padding: 10px 6px;
        }

        /* Allow actions column to wrap */
        #usersTable td:nth-child(11) {
            white-space: normal;
        }

        /* Ensure badges don't break layout */
        #usersTable .badge {
            display: inline-block;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            white-space: nowrap;
        }

        /* Compact button styling */
        #usersTable .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
    </style>
</head>

<body class="dashboard">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <?php displayFlashMessage(); ?>

        <h1>Manage Users</h1>

        <!-- Search Box -->
        <div class="card">
            <input type="text" id="searchInput" onkeyup="searchTable('searchInput', 'usersTable')"
                placeholder="Search by name, email, or phone..."
                style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px;">
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h3>All Users (<?php echo count($users); ?>)</h3>
                <a href="export_users.php" class="btn btn-sm btn-success">
                    📥 Export CSV
                </a>
            </div>
            <div class="table-responsive">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Status</th>
                            <th>Plan</th>
                            <th>Payment</th>
                            <th>Time Slot</th>
                            <th>Expiry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo $user['gender'] ? ucfirst(htmlspecialchars($user['gender'])) : '<span style="color: #999;">-</span>'; ?></td>
                                <td>
                                    <?php if ($user['status'] === 'approved'): ?>
                                        <span class="badge badge-success">Approved</span>
                                    <?php elseif ($user['status'] === 'pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Blocked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['plan_name']): ?>
                                        <?php echo htmlspecialchars($user['plan_name']); ?>
                                        <?php if ($user['member_status'] === 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php elseif ($user['member_status'] === 'expired'): ?>
                                            <span class="badge badge-danger">Expired</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">No Plan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['payment_status']): ?>
                                        <?php if ($user['payment_status'] === 'paid'): ?>
                                            <span class="badge badge-success">Paid ₹<?php echo number_format($user['payment_amount']); ?></span>
                                        <?php elseif ($user['payment_status'] === 'pending'): ?>
                                            <span class="badge badge-warning">Pending ₹<?php echo number_format($user['payment_amount']); ?></span>
                                            <br><small><?php echo htmlspecialchars($user['payment_method']); ?></small>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Due</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['slot_name'] ? htmlspecialchars($user['slot_name']) : '<span style="color: #999;">Not Assigned</span>'; ?></td>
                                <td>
                                    <?php if ($user['end_date']): ?>
                                        <?php
                                        echo formatDate($user['end_date']);
                                        $days = daysRemaining($user['end_date']);
                                        if ($days > 0 && $days <= 7) {
                                            echo '<br><small style="color: orange;">(' . $days . ' days left)</small>';
                                        }
                                        ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="openUserModal(<?php echo $user['id']; ?>)" class="btn btn-sm btn-primary" title="Manage User">
                                        ⚙️ Manage
                                    </button>
                                    <?php if ($user['status'] === 'pending' && $user['payment_status'] === 'pending'): ?>
                                        <button onclick="openUserModal(<?php echo $user['id']; ?>)" class="btn btn-sm btn-success" title="Approve & Activate">
                                            ✓ Approve
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Manage User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Manage User</h3>
                <span class="close" onclick="closeModal('userModal')">&times;</span>
            </div>
            <div id="userModalContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function openUserModal(userId) {
            const users = <?php echo json_encode($users); ?>;
            const user = users.find(u => u.id == userId);
            const plans = <?php echo json_encode($plans); ?>;
            const slots = <?php echo json_encode($timeSlots); ?>;

            let content = `
                <div class="stats-grid" style="margin-bottom: 1.5rem;">
                    <div><strong>Name:</strong> ${user.name}</div>
                    <div><strong>Email:</strong> ${user.email}</div>
                    <div><strong>Phone:</strong> ${user.phone}</div>
                    <div><strong>Gender:</strong> ${user.gender ? user.gender.charAt(0).toUpperCase() + user.gender.slice(1) : '-'}</div>
                    <div><strong>User Status:</strong> 
                        <span class="badge badge-${user.status === 'approved' ? 'success' : user.status === 'pending' ? 'warning' : 'danger'}">
                            ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                        </span>
                    </div>
                </div>
                ${user.plan_name ? `
                    <div class="stats-grid" style="margin-bottom: 1.5rem; background: #f0f8ff; padding: 1rem; border-radius: 5px;">
                        <div><strong>Plan:</strong> ${user.plan_name}</div>
                        <div><strong>Member Status:</strong> 
                            <span class="badge badge-${user.member_status === 'active' ? 'success' : user.member_status === 'expired' ? 'danger' : 'secondary'}">
                                ${user.member_status ? user.member_status.charAt(0).toUpperCase() + user.member_status.slice(1) : 'Inactive'}
                            </span>
                        </div>
                        <div><strong>Current Slot:</strong> ${user.slot_name || 'Not Assigned'}</div>
                        <div><strong>Expiry Date:</strong> ${user.end_date || '-'}</div>
                    </div>
                ` : ''}
                
                <!-- Payment Information -->
                ${user.payment_status ? `
                    <div class="card" style="margin-bottom: 1rem; background: #f8f9fa;">
                        <h4>💳 Payment Details</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div><strong>Amount:</strong> ₹${user.payment_amount}</div>
                            <div><strong>Method:</strong> ${user.payment_method}</div>
                            <div><strong>Date:</strong> ${user.payment_date}</div>
                            <div><strong>Status:</strong> 
                                <span class="badge badge-${user.payment_status === 'paid' ? 'success' : user.payment_status === 'pending' ? 'warning' : 'danger'}">
                                    ${user.payment_status.toUpperCase()}
                                </span>
                            </div>
                            ${user.payment_notes ? `<div style="grid-column: 1 / -1;"><strong>Notes:</strong> ${user.payment_notes}</div>` : ''}
                        </div>
                    </div>
                ` : ''}
                
                <!-- Approve User with Slot Assignment -->
                ${user.status === 'pending' && user.plan_id && user.payment_status === 'pending' ? `
                    <div class="card" style="margin-bottom: 1rem; border: 2px solid #28a745;">
                        <h4>✅ Approve Membership</h4>
                        <p style="color: #666; margin-bottom: 1rem;">Verify payment and assign time slot to activate membership.</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="user_id" value="${user.id}">
                            <div class="form-group">
                                <label>Select Time Slot *</label>
                                <select name="slot_id" required class="form-control" style="padding: 10px; border: 2px solid #ddd; border-radius: 5px; width: 100%;">
                                    <option value="">-- Select Time Slot --</option>
                                    ${slots.map(slot => `
                                        <option value="${slot.id}">
                                            ${slot.slot_name} (${slot.start_time} - ${slot.end_time}) 
                                            [Available: ${slot.max_members - slot.current_members}/${slot.max_members}]
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">Approve & Activate Membership</button>
                        </form>
                    </div>
                ` : ''}
                
                <!-- Block User -->
                <div class="card" style="margin-bottom: 1rem;">
                    <h4>User Status Management</h4>
                    <form method="POST" style="display: flex; gap: 1rem;">
                        <input type="hidden" name="user_id" value="${user.id}">
                        ${user.status !== 'blocked' ? `
                            <button type="submit" name="action" value="block" class="btn btn-danger" 
                                    onclick="return confirm('Block this user?')">Block User</button>
                        ` : ''}
                    </form>
                </div>
                
                <!-- Activate Plan -->
                <div class="card" style="margin-bottom: 1rem;">
                    <h4>Activate Membership Plan</h4>
                    ${user.payment_status === 'paid' ? `
                        <p style="color: #28a745; margin-bottom: 1rem;">✅ Payment verified: ₹${user.payment_amount}</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="activate_plan">
                            <input type="hidden" name="user_id" value="${user.id}">
                            <div class="form-group">
                                <select name="plan_id" required class="form-control" style="padding: 10px; border: 2px solid #ddd; border-radius: 5px; width: 100%;">
                                    <option value="">-- Select Plan --</option>
                                    ${plans.filter(plan => parseFloat(plan.price) === parseFloat(user.payment_amount)).map(plan => `
                                        <option value="${plan.id}" ${user.plan_id == plan.id ? 'selected' : ''}>
                                            ${plan.name} - ₹${plan.price} (${plan.duration_days} days)
                                        </option>
                                    `).join('')}
                                    ${plans.filter(plan => parseFloat(plan.price) === parseFloat(user.payment_amount)).length === 0 ? 
                                        '<option value="" disabled>No matching plan for paid amount</option>' : ''}
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success" ${plans.filter(plan => parseFloat(plan.price) === parseFloat(user.payment_amount)).length === 0 ? 'disabled' : ''}>
                                Activate Plan
                            </button>
                        </form>
                    ` : `
                        <p style="color: #dc3545; margin-bottom: 1rem;">❌ Payment required to activate plan</p>
                        <p style="color: #666;">User must complete payment before activating membership plan.</p>
                    `}
                </div>
                
                <!-- Assign Time Slot -->
                <div class="card">
                    <h4>Assign Time Slot</h4>
                    ${user.plan_id && user.member_status === 'active' ? `
                        <p style="color: #28a745; margin-bottom: 1rem;">✅ Membership active: ${user.plan_name}</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="assign_slot">
                            <input type="hidden" name="user_id" value="${user.id}">
                            <div class="form-group">
                                <select name="slot_id" required class="form-control" style="padding: 10px; border: 2px solid #ddd; border-radius: 5px; width: 100%;">
                                    <option value="">-- Select Time Slot --</option>
                                    ${slots.map(slot => `
                                        <option value="${slot.id}" ${user.time_slot_id == slot.id ? 'selected' : ''}>
                                            ${slot.slot_name} (${slot.start_time} - ${slot.end_time}) 
                                            [${slot.current_members}/${slot.max_members}]
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Assign Slot</button>
                        </form>
                    ` : `
                        <p style="color: #dc3545; margin-bottom: 1rem;">❌ Activate membership plan first</p>
                        <p style="color: #666;">User must have an active membership plan before assigning a time slot.</p>
                    `}
                </div>
            `;

            document.getElementById('userModalContent').innerHTML = content;
            openModal('userModal');
        }
    </script>
</body>

</html>