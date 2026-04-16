<?php
require_once 'db.php';

// Sanitize input
function sanitize($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect function
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

// Check user login status and redirect
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect(SITE_URL . 'user/login.php');
    }
}

// Check admin login status and redirect
function requireAdmin()
{
    if (!isAdmin()) {
        redirect(SITE_URL . 'admin/login.php');
    }
}

// Format date
function formatDate($date)
{
    return date('d M Y', strtotime($date));
}

// Calculate days remaining
function daysRemaining($endDate)
{
    $today = new DateTime();
    $end = new DateTime($endDate);
    $diff = $today->diff($end);

    if ($end < $today) {
        return 0;
    }

    return $diff->days;
}

// Get user by ID
function getUserById($userId)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get member info by user ID
function getMemberByUserId($userId)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT m.*, p.name as plan_name, p.price, p.duration_days, 
                          t.slot_name, t.start_time, t.end_time 
                          FROM members m 
                          LEFT JOIN plans p ON m.plan_id = p.id 
                          LEFT JOIN time_slots t ON m.time_slot_id = t.id 
                          WHERE m.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get all active plans
function getActivePlans()
{
    $db = getDB();
    $result = $db->query("SELECT * FROM plans ORDER BY price ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get all active time slots
function getActiveTimeSlots()
{
    $db = getDB();
    $result = $db->query("SELECT * FROM time_slots WHERE status = 'active' ORDER BY start_time ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Set flash message
function setFlashMessage($type, $message)
{
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

// Get and clear flash message
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Display flash message HTML
function displayFlashMessage()
{
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                {$flash['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// Check if email exists
function emailExists($email, $excludeUserId = null)
{
    $db = getDB();
    if ($excludeUserId) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $excludeUserId);
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Update member status based on expiry date
function updateMemberStatus($memberId)
{
    $db = getDB();
    $stmt = $db->prepare("UPDATE members SET status = 
                          CASE 
                              WHEN end_date < CURDATE() THEN 'expired'
                              WHEN end_date >= CURDATE() THEN 'active'
                              ELSE status
                          END 
                          WHERE id = ?");
    $stmt->bind_param("i", $memberId);
    return $stmt->execute();
}
