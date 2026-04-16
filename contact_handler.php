<?php
require_once 'includes/config.php';

// This is a simple contact form handler
// In production, you would send emails or store messages in database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    // Validate
    $errors = [];

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }

    if (empty($message)) {
        $errors[] = 'Message is required';
    }

    if (empty($errors)) {
        // Here you can:
        // 1. Send email to gym admin
        // 2. Store in database
        // 3. Send SMS notification

        // For now, we'll just show a success message
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_message'] = 'Thank you for contacting us! We will get back to you soon.';

        // You can also log the message to a file
        $log_message = date('Y-m-d H:i:s') . " - Name: $name, Email: $email, Phone: $phone, Message: $message\n";
        file_put_contents('contact_messages.log', $log_message, FILE_APPEND);
    } else {
        $_SESSION['flash_type'] = 'danger';
        $_SESSION['flash_message'] = 'Please fix the errors: ' . implode(', ', $errors);
    }
} else {
    $_SESSION['flash_type'] = 'danger';
    $_SESSION['flash_message'] = 'Invalid request method';
}

// Redirect back to home page
header('Location: index.php#contact');
exit();
