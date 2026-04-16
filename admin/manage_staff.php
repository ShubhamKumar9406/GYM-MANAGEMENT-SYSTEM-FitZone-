<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $role = sanitize($_POST['role']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $salary = floatval($_POST['salary']);
        $join_date = $_POST['join_date'];

        $stmt = $db->prepare("INSERT INTO staff (name, role, phone, email, salary, join_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssds", $name, $role, $phone, $email, $salary, $join_date);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Staff added successfully!');
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $role = sanitize($_POST['role']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $salary = floatval($_POST['salary']);
        $status = $_POST['status'];

        $stmt = $db->prepare("UPDATE staff SET name = ?, role = ?, phone = ?, email = ?, salary = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssdsi", $name, $role, $phone, $email, $salary, $status, $id);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Staff updated successfully!');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($db->query("DELETE FROM staff WHERE id = $id")) {
            setFlashMessage('success', 'Staff deleted successfully!');
        }
    }

    redirect('manage_staff.php');
}

$staff = $db->query("SELECT * FROM staff ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - <?php echo SITE_NAME; ?></title>
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

        <h1>Manage Staff</h1>

        <div class="card">
            <div class="card-header">
                <h3>All Staff Members</h3>
                <button onclick="openModal('addStaffModal')" class="btn btn-primary">+ Add New Staff</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Salary</th>
                            <th>Join Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $member): ?>
                            <tr>
                                <td><?php echo $member['id']; ?></td>
                                <td><?php echo htmlspecialchars($member['name']); ?></td>
                                <td><?php echo htmlspecialchars($member['role']); ?></td>
                                <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td>₹<?php echo number_format($member['salary'], 2); ?></td>
                                <td><?php echo formatDate($member['join_date']); ?></td>
                                <td>
                                    <?php if ($member['status'] === 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick='editStaff(<?php echo json_encode($member); ?>)'
                                        class="btn btn-sm btn-primary">Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this staff?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
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

    <!-- Add Staff Modal -->
    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Staff</h3>
                <span class="close" onclick="closeModal('addStaffModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <input type="text" name="role" required placeholder="e.g., Personal Trainer">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Salary (₹)</label>
                    <input type="number" name="salary" step="0.01">
                </div>
                <div class="form-group">
                    <label>Join Date</label>
                    <input type="date" name="join_date">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Staff</button>
            </form>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div id="editStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Staff</h3>
                <span class="close" onclick="closeModal('editStaffModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <input type="text" name="role" id="edit_role" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" id="edit_phone">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email">
                </div>
                <div class="form-group">
                    <label>Salary (₹)</label>
                    <input type="number" name="salary" id="edit_salary" step="0.01">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Staff</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function editStaff(staff) {
            document.getElementById('edit_id').value = staff.id;
            document.getElementById('edit_name').value = staff.name;
            document.getElementById('edit_role').value = staff.role;
            document.getElementById('edit_phone').value = staff.phone;
            document.getElementById('edit_email').value = staff.email;
            document.getElementById('edit_salary').value = staff.salary;
            document.getElementById('edit_status').value = staff.status;
            openModal('editStaffModal');
        }
    </script>
</body>

</html>