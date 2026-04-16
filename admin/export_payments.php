<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Get all payments with user details
$payments = $db->query("SELECT p.*, u.name as user_name, u.email, pl.name as plan_name
                        FROM payments p
                        JOIN users u ON p.user_id = u.id
                        LEFT JOIN plans pl ON p.plan_id = pl.id
                        ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="payments_' . date('Y-m-d_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Headers
fputcsv($output, [
    'Payment ID',
    'User Name',
    'Email',
    'Plan Name',
    'Amount (₹)',
    'Payment Date',
    'Status',
    'Transaction ID',
    'Payment Method',
    'Notes',
    'Created At'
]);

// CSV Rows
foreach ($payments as $payment) {
    fputcsv($output, [
        $payment['id'],
        $payment['user_name'],
        $payment['email'],
        $payment['plan_name'] ?? 'N/A',
        number_format($payment['amount'], 2, '.', ''),
        date('d-M-Y', strtotime($payment['payment_date'])),
        ucfirst($payment['status']),
        $payment['transaction_id'] ?? '',
        $payment['payment_method'] ?? '',
        $payment['notes'] ?? '',
        date('d-M-Y H:i:s', strtotime($payment['created_at']))
    ]);
}

fclose($output);
exit;
