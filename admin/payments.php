<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $payment_id = intval($_POST['payment_id']);
    $status = sanitize($_POST['status']);

    $stmt = $db->prepare("UPDATE payments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $payment_id);

    if ($stmt->execute()) {
        setFlashMessage('success', 'Payment status updated successfully!');
    }

    redirect('payments.php');
}

// Get all payments with user details
$payments = $db->query("SELECT p.*, u.name as user_name, u.email, pl.name as plan_name
                        FROM payments p
                        JOIN users u ON p.user_id = u.id
                        LEFT JOIN plans pl ON p.plan_id = pl.id
                        ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_paid = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$total_pending = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
$total_due = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'due'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - <?php echo SITE_NAME; ?></title>
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

        <h1>Payment Management</h1>

        <!-- Payment Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">✓</div>
                <div class="stat-info">
                    <h3>₹<?php echo number_format($total_paid, 2); ?></h3>
                    <p>Total Paid</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-info">
                    <h3>₹<?php echo number_format($total_pending, 2); ?></h3>
                    <p>Pending</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">!</div>
                <div class="stat-info">
                    <h3>₹<?php echo number_format($total_due, 2); ?></h3>
                    <p>Due</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">💰</div>
                <div class="stat-info">
                    <h3>₹<?php echo number_format($total_paid + $total_pending + $total_due, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header">
                <h3>All Payments</h3>
                <a href="export_payments.php" class="btn btn-sm btn-success">
                    📥 Export CSV
                </a>
            </div>
            <div class="table-responsive">
                <table id="paymentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['id']; ?></td>
                                <td><?php echo htmlspecialchars($payment['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['email']); ?></td>
                                <td><?php echo htmlspecialchars($payment['plan_name'] ?? 'N/A'); ?></td>
                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td>
                                    <?php if ($payment['status'] === 'paid'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($payment['status'] === 'pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Due</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></td>
                                <td>
                                    <button onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, '<?php echo $payment['status']; ?>')"
                                        class="btn btn-sm btn-primary">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Update Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Payment Status</h3>
                <span class="close" onclick="closeModal('paymentModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="payment_id" id="payment_id">
                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="status" id="payment_status" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="due">Due</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function updatePaymentStatus(paymentId, currentStatus) {
            document.getElementById('payment_id').value = paymentId;
            document.getElementById('payment_status').value = currentStatus;
            openModal('paymentModal');
        }
    </script>
</body>

</html>