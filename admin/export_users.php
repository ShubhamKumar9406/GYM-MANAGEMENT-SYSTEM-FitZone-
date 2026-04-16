<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Get all users with membership details
$users = $db->query("SELECT u.*, 
                            m.start_date, m.end_date, m.status as member_status,
                            pl.name as plan_name, pl.duration, pl.price,
                            ts.slot_name, ts.start_time, ts.end_time,
                            p.amount, p.payment_date, p.status as payment_status
                     FROM users u
                     LEFT JOIN members m ON u.id = m.user_id
                     LEFT JOIN plans pl ON m.plan_id = pl.id
                     LEFT JOIN time_slots ts ON m.time_slot_id = ts.id
                     LEFT JOIN payments p ON p.user_id = u.id AND p.plan_id = pl.id
                     ORDER BY u.id DESC")->fetch_all(MYSQLI_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="users_' . date('Y-m-d_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Headers
fputcsv($output, [
    'User ID',
    'Name',
    'Email',
    'Phone',
    'Gender',
    'User Role',
    'Account Status',
    'Plan Name',
    'Plan Duration (days)',
    'Plan Price (₹)',
    'Membership Status',
    'Start Date',
    'End Date',
    'Time Slot',
    'Slot Time',
    'Payment Amount (₹)',
    'Payment Date',
    'Payment Status',
    'Registered On'
]);

// CSV Rows
foreach ($users as $user) {
    $slot_time = '';
    if ($user['start_time'] && $user['end_time']) {
        $slot_time = date('g:i A', strtotime($user['start_time'])) . ' - ' . date('g:i A', strtotime($user['end_time']));
    }

    fputcsv($output, [
        $user['id'],
        $user['name'],
        $user['email'],
        $user['phone'] ?? '',
        ucfirst($user['gender'] ?? ''),
        ucfirst($user['role']),
        ucfirst($user['status']),
        $user['plan_name'] ?? 'N/A',
        $user['duration'] ?? '',
        $user['price'] ? number_format($user['price'], 2, '.', '') : '',
        $user['member_status'] ? ucfirst($user['member_status']) : '',
        $user['start_date'] ? date('d-M-Y', strtotime($user['start_date'])) : '',
        $user['end_date'] ? date('d-M-Y', strtotime($user['end_date'])) : '',
        $user['slot_name'] ?? '',
        $slot_time,
        $user['amount'] ? number_format($user['amount'], 2, '.', '') : '',
        $user['payment_date'] ? date('d-M-Y', strtotime($user['payment_date'])) : '',
        $user['payment_status'] ? ucfirst($user['payment_status']) : '',
        date('d-M-Y H:i:s', strtotime($user['created_at']))
    ]);
}

fclose($output);
exit;
