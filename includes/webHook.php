<?php
if (!session_id()) {
    session_start();
}
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}

global $wpdb;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_sid = $_POST['MessageSid'] ?? null;
    $from_number = $_POST['From'] ?? null;
    $to_number = $_POST['To'] ?? null;
    $body = $_POST['Body'] ?? null;

    if ($message_sid && $from_number && $to_number && $body) {
        $twilio_sms_table = $wpdb->prefix . 'twilio_sms';

        $data = [
            'to_number'  => $to_number,
            'from_number' => $from_number,
            'message'    => $body,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        $inserted = $wpdb->insert($twilio_sms_table, $data);

        if ($inserted !== false) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'failure', 'message' => 'Failed to insert data']);
        }
    } else {
        echo json_encode(['status' => 'failure', 'message' => 'Missing required data']);
    }
    exit;
}
?>
