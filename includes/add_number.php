<?php
session_start();

// Load WordPress environment to use $wpdb
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}

global $wpdb;

header('Content-Type: application/json');

// Check for Twilio credentials in session
if (!isset($_SESSION['account_sid'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Twilio credentials are missing or invalid.',
        'error' => 'Please go to settings tab and validate the credentials.'
    ]);
    exit;
}

$accountSid = $_SESSION['account_sid'];

// Get input values
$number = isset($_POST['number']) ? trim($_POST['number']) : '';
$type = isset($_POST['type']) ? $_POST['type'] : '';

if (empty($number) || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// Determine which table to use
$table = $type === 'twilio' ? $wpdb->prefix . 'twilio_numbers' : $wpdb->prefix . 'other_numbers';

// Check if the number already exists
$sql_check = $wpdb->prepare("SELECT id FROM $table WHERE number = %s AND twilio_user_id = %s", $number, $accountSid);
$result = $wpdb->get_results($sql_check);

if (!empty($result)) {
    echo json_encode(['success' => false, 'exists' => true, 'message' => 'Number already exists.']);
    exit;
}

// Insert the new number into the table
$insert_result = $wpdb->insert(
    $table,
    [
        'twilio_user_id' => $accountSid,
        'number' => $number,
        'created_at' => current_time('mysql') // Use WordPress's current time
    ],
    ['%s', '%s', '%s']
);

// Check if insertion was successful
if ($insert_result) {
    echo json_encode(['success' => true, 'exists' => false, 'message' => 'Number added successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add number.']);
}
