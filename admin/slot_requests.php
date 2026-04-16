<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle approve request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_request') {
    $request_id = intval($_POST['request_id']);
    $admin_response = sanitize($_POST['admin_response'] ?? '');

    // Get request details
    $stmt = $db->prepare("SELECT * FROM slot_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if ($request) {
        // Get member's current actual slot
        $stmt = $db->prepare("SELECT time_slot_id FROM members WHERE user_id = ?");
        $stmt->bind_param("i", $request['user_id']);
        $stmt->execute();
        $member_data = $stmt->get_result()->fetch_assoc();
        $actual_current_slot = $member_data['time_slot_id'];

        // Check if requested slot has capacity
        $stmt = $db->prepare("SELECT * FROM time_slots WHERE id = ?");
        $stmt->bind_param("i", $request['requested_slot_id']);
        $stmt->execute();
        $new_slot = $stmt->get_result()->fetch_assoc();

        if ($new_slot && $new_slot['current_members'] < $new_slot['max_members']) {
            // Update member's slot
            $stmt = $db->prepare("UPDATE members SET time_slot_id = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $request['requested_slot_id'], $request['user_id']);

            if ($stmt->execute()) {
                // Update slot counts based on ACTUAL current slot (not request's stored slot)
                if ($actual_current_slot && $actual_current_slot != $request['requested_slot_id']) {
                    $db->query("UPDATE time_slots SET current_members = current_members - 1 WHERE id = " . $actual_current_slot);
                }
                if ($actual_current_slot != $request['requested_slot_id']) {
                    $db->query("UPDATE time_slots SET current_members = current_members + 1 WHERE id = " . $request['requested_slot_id']);
                }

                // Mark request as approved
                $stmt = $db->prepare("UPDATE slot_requests SET status = 'approved', admin_response = ? WHERE id = ?");
                $stmt->bind_param("si", $admin_response, $request_id);
                $stmt->execute();

                setFlashMessage('success', 'Slot change request approved successfully!');
            }
        } else {
            setFlashMessage('danger', 'Requested slot is full. Cannot approve request.');
        }
    }
    redirect('slot_requests.php');
}

// Handle reject request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_request') {
    $request_id = intval($_POST['request_id']);
    $admin_response = sanitize($_POST['admin_response'] ?? 'Request rejected');

    $stmt = $db->prepare("UPDATE slot_requests SET status = 'rejected', admin_response = ? WHERE id = ?");
    $stmt->bind_param("si", $admin_response, $request_id);

    if ($stmt->execute()) {
        setFlashMessage('info', 'Slot change request rejected.');
    }
    redirect('slot_requests.php');
}

// Get all pending requests
$pending_requests = $db->query("
    SELECT sr.*, u.name as user_name, u.email,
           ts_current.slot_name as current_slot_name, ts_current.start_time as current_start, ts_current.end_time as current_end,
           ts_new.slot_name as requested_slot_name, ts_new.start_time as requested_start, ts_new.end_time as requested_end,
           ts_new.current_members, ts_new.max_members
    FROM slot_requests sr
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN time_slots ts_current ON sr.current_slot_id = ts_current.id
    JOIN time_slots ts_new ON sr.requested_slot_id = ts_new.id
    WHERE sr.status = 'pending'
    ORDER BY sr.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

// Get all historical requests
$all_requests = $db->query("
    SELECT sr.*, u.name as user_name, u.email,
           ts_current.slot_name as current_slot_name,
           ts_new.slot_name as requested_slot_name
    FROM slot_requests sr
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN time_slots ts_current ON sr.current_slot_id = ts_current.id
    JOIN time_slots ts_new ON sr.requested_slot_id = ts_new.id
    ORDER BY sr.created_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Change Requests - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-group input,
        .form-group select,
        .form-group textarea {
            background: white !important;
            color: #1a1a1a !important;
            border: 2px solid #e0e0e0 !important;
        }

        .request-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .slot-comparison {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 1rem;
            align-items: center;
            margin: 1rem 0;
        }

        .slot-box {
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }

        .slot-box.current {
            background: #f8f9fa;
            border: 2px solid #6c757d;
        }

        .slot-box.requested {
            background: #e3f2fd;
            border: 2px solid #2196f3;
        }
    </style>
</head>

<body class="dashboard">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <?php displayFlashMessage(); ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>🔄 Slot Change Requests</h1>
            <span class="badge badge-warning" style="font-size: 1.2rem; padding: 0.5rem 1rem;">
                <?php echo count($pending_requests); ?> Pending
            </span>
        </div>

        <?php if (count($pending_requests) > 0): ?>
            <h2>Pending Requests</h2>
            <?php foreach ($pending_requests as $request):
                $capacity_percent = ($request['current_members'] / $request['max_members']) * 100;
                $is_full = $request['current_members'] >= $request['max_members'];
            ?>
                <div class="request-card">
                    <div class="request-header">
                        <div>
                            <h3 style="margin: 0;">👤 <?php echo htmlspecialchars($request['user_name']); ?></h3>
                            <small style="color: #666;"><?php echo htmlspecialchars($request['email']); ?></small>
                        </div>
                        <div style="text-align: right;">
                            <small style="color: #666;">Requested</small><br>
                            <strong><?php echo formatDate($request['created_at']); ?></strong>
                        </div>
                    </div>

                    <div class="slot-comparison">
                        <div class="slot-box current">
                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Current Slot</div>
                            <strong style="font-size: 1.1rem;">
                                <?php echo $request['current_slot_name'] ? htmlspecialchars($request['current_slot_name']) : 'No Slot'; ?>
                            </strong>
                            <?php if ($request['current_slot_name']): ?>
                                <div style="margin-top: 0.5rem; color: #666;">
                                    <?php echo date('g:i A', strtotime($request['current_start'])); ?> -
                                    <?php echo date('g:i A', strtotime($request['current_end'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="font-size: 2rem; color: #666;">→</div>

                        <div class="slot-box requested">
                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Requested Slot</div>
                            <strong style="font-size: 1.1rem; color: #2196f3;">
                                <?php echo htmlspecialchars($request['requested_slot_name']); ?>
                            </strong>
                            <div style="margin-top: 0.5rem; color: #666;">
                                <?php echo date('g:i A', strtotime($request['requested_start'])); ?> -
                                <?php echo date('g:i A', strtotime($request['requested_end'])); ?>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <span class="badge <?php echo $is_full ? 'badge-danger' : 'badge-success'; ?>">
                                    <?php echo $request['current_members']; ?> / <?php echo $request['max_members']; ?>
                                    (<?php echo round($capacity_percent); ?>%)
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($request['notes']): ?>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                            <strong>Notes:</strong> <?php echo htmlspecialchars($request['notes']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button onclick="openApproveModal(<?php echo $request['id']; ?>, '<?php echo addslashes($request['user_name']); ?>', '<?php echo addslashes($request['requested_slot_name']); ?>')"
                            class="btn btn-success" <?php echo $is_full ? 'disabled' : ''; ?>>
                            ✅ Approve
                        </button>
                        <button onclick="openRejectModal(<?php echo $request['id']; ?>, '<?php echo addslashes($request['user_name']); ?>')"
                            class="btn btn-danger">
                            ❌ Reject
                        </button>
                    </div>

                    <?php if ($is_full): ?>
                        <div class="alert alert-warning" style="margin-top: 1rem; margin-bottom: 0;">
                            ⚠️ Cannot approve: Requested slot is currently full
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>✅ All Clear!</strong> No pending slot change requests at the moment.
            </div>
        <?php endif; ?>

        <h2 style="margin-top: 3rem;">Request History</h2>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>From Slot</th>
                        <th>To Slot</th>
                        <th>Status</th>
                        <th>Admin Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_requests as $request): ?>
                        <tr>
                            <td><?php echo formatDate($request['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                            <td><?php echo $request['current_slot_name'] ? htmlspecialchars($request['current_slot_name']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($request['requested_slot_name']); ?></td>
                            <td>
                                <?php if ($request['status'] === 'approved'): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php elseif ($request['status'] === 'rejected'): ?>
                                    <span class="badge badge-danger">Rejected</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $request['admin_response'] ? htmlspecialchars($request['admin_response']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Approve Slot Change Request</h3>
                <span class="close" onclick="closeModal('approveModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="approve_request">
                <input type="hidden" name="request_id" id="approve_request_id">

                <p>Approve slot change for <strong id="approve_user_name"></strong> to <strong id="approve_slot_name"></strong>?</p>

                <div class="form-group">
                    <label for="admin_response">Admin Message (Optional)</label>
                    <textarea id="admin_response" name="admin_response" rows="3"
                        placeholder="Add any message for the user..."></textarea>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Approve Request</button>
                    <button type="button" onclick="closeModal('approveModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Slot Change Request</h3>
                <span class="close" onclick="closeModal('rejectModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reject_request">
                <input type="hidden" name="request_id" id="reject_request_id">

                <p>Reject slot change request for <strong id="reject_user_name"></strong>?</p>

                <div class="form-group">
                    <label for="reject_response">Reason for Rejection *</label>
                    <textarea id="reject_response" name="admin_response" rows="3" required
                        placeholder="Please explain why this request is being rejected..."></textarea>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-danger" style="flex: 1;">Reject Request</button>
                    <button type="button" onclick="closeModal('rejectModal')" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function openApproveModal(id, userName, slotName) {
            document.getElementById('approve_request_id').value = id;
            document.getElementById('approve_user_name').textContent = userName;
            document.getElementById('approve_slot_name').textContent = slotName;
            openModal('approveModal');
        }

        function openRejectModal(id, userName) {
            document.getElementById('reject_request_id').value = id;
            document.getElementById('reject_user_name').textContent = userName;
            openModal('rejectModal');
        }
    </script>
</body>

</html>