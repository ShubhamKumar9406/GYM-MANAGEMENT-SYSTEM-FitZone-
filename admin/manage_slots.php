<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $slot_name = sanitize($_POST['slot_name']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $max_members = intval($_POST['max_members']);

        $stmt = $db->prepare("INSERT INTO time_slots (slot_name, start_time, end_time, max_members, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssi", $slot_name, $start_time, $end_time, $max_members);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Time slot added successfully!');
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $slot_name = sanitize($_POST['slot_name']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $max_members = intval($_POST['max_members']);
        $status = $_POST['status'];

        $stmt = $db->prepare("UPDATE time_slots SET slot_name = ?, start_time = ?, end_time = ?, max_members = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssisi", $slot_name, $start_time, $end_time, $max_members, $status, $id);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Time slot updated successfully!');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($db->query("DELETE FROM time_slots WHERE id = $id")) {
            setFlashMessage('success', 'Time slot deleted successfully!');
        }
    }

    redirect('manage_slots.php');
}

$slots = $db->query("SELECT * FROM time_slots ORDER BY start_time ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Time Slots - <?php echo SITE_NAME; ?></title>
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

        <h1>Manage Time Slots</h1>

        <div class="card">
            <div class="card-header">
                <h3>All Time Slots</h3>
                <button onclick="openModal('addSlotModal')" class="btn btn-primary">+ Add New Slot</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Slot Name</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slots as $slot): ?>
                            <tr>
                                <td><?php echo $slot['id']; ?></td>
                                <td><?php echo htmlspecialchars($slot['slot_name']); ?></td>
                                <td><?php echo date('g:i A', strtotime($slot['start_time'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($slot['end_time'])); ?></td>
                                <td>
                                    <?php echo $slot['current_members']; ?> / <?php echo $slot['max_members']; ?>
                                    <?php if ($slot['current_members'] >= $slot['max_members']): ?>
                                        <span class="badge badge-danger">Full</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($slot['status'] === 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="editSlot(<?php echo htmlspecialchars(json_encode($slot)); ?>)"
                                        class="btn btn-sm btn-primary">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this slot?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $slot['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Slot Modal -->
    <div id="addSlotModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Time Slot</h3>
                <span class="close" onclick="closeModal('addSlotModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Slot Name *</label>
                    <input type="text" name="slot_name" required placeholder="e.g., Morning Batch">
                </div>
                <div class="form-group">
                    <label>Start Time *</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>End Time *</label>
                    <input type="time" name="end_time" required>
                </div>
                <div class="form-group">
                    <label>Max Members *</label>
                    <input type="number" name="max_members" required value="20" min="1">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Slot</button>
            </form>
        </div>
    </div>

    <!-- Edit Slot Modal -->
    <div id="editSlotModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Time Slot</h3>
                <span class="close" onclick="closeModal('editSlotModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Slot Name *</label>
                    <input type="text" name="slot_name" id="edit_slot_name" required>
                </div>
                <div class="form-group">
                    <label>Start Time *</label>
                    <input type="time" name="start_time" id="edit_start_time" required>
                </div>
                <div class="form-group">
                    <label>End Time *</label>
                    <input type="time" name="end_time" id="edit_end_time" required>
                </div>
                <div class="form-group">
                    <label>Max Members *</label>
                    <input type="number" name="max_members" id="edit_max_members" required min="1">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Slot</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function editSlot(slot) {
            document.getElementById('edit_id').value = slot.id;
            document.getElementById('edit_slot_name').value = slot.slot_name;
            document.getElementById('edit_start_time').value = slot.start_time;
            document.getElementById('edit_end_time').value = slot.end_time;
            document.getElementById('edit_max_members').value = slot.max_members;
            document.getElementById('edit_status').value = slot.status;
            openModal('editSlotModal');
        }
    </script>
</body>

</html>