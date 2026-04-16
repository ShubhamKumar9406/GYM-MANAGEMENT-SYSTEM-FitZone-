<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="dashboard-sidebar">
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">📊 Dashboard</a></li>
        <li><a href="profile.php" class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">👤 My Profile</a></li>
        <li><a href="manage_users.php" class="<?php echo $current_page === 'manage_users.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">👥 Manage Users</a></li>
        <li><a href="manage_plans.php" class="<?php echo $current_page === 'manage_plans.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">📋 Manage Plans</a></li>
        <li><a href="manage_slots.php" class="<?php echo $current_page === 'manage_slots.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">⏰ Time Slots</a></li>
        <li><a href="slot_requests.php" class="<?php echo $current_page === 'slot_requests.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">🔄 Slot Requests</a></li>
        <li><a href="manage_staff.php" class="<?php echo $current_page === 'manage_staff.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">👨‍💼 Manage Staff</a></li>
        <li><a href="manage_equipment.php" class="<?php echo $current_page === 'manage_equipment.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">🏋️ Equipment</a></li>
        <li><a href="payments.php" class="<?php echo $current_page === 'payments.php' ? 'active' : ''; ?>" onclick="closeSidebarOnMobile()">💳 Payments</a></li>
    </ul>
</aside>