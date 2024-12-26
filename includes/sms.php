<?php

if (!session_id()) {
    session_start();
}

if (!isset($_SESSION['account_sid'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Twilio credentials are missing or invalid.',
        'error' => 'Please go to settings tab and validate the credentials.'
    ]);
    exit;
}

define('SHORTINIT', true);
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$twilio_sms_table = $wpdb->prefix . 'twilio_sms';

$sms_data = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $twilio_sms_table ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ),
    ARRAY_A
);

$total_sms = $wpdb->get_var("SELECT COUNT(*) FROM $twilio_sms_table");
$total_pages = ceil($total_sms / $per_page);

$response = [
    'success' => true,
    'data' => [
        'sms_data' => $sms_data,
        'total_pages' => $total_pages,
        'current_page' => $page,
    ],
];

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
