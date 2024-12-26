<?php
if (!session_id()) {
    session_start();
}

if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}

global $wpdb;

if (!isset($_SESSION['account_sid']) || !isset($_SESSION['auth_token'])) {
    echo json_encode(['status' => 'failed', 'error' => 'Twilio credentials are missing or invalid', 'message' => 'Please go to settings tab and validate the credentails.']);
    exit;
}

$accountSid = $_SESSION['account_sid'];
$authToken = $_SESSION['auth_token'];

require __DIR__ . '/../vendor/autoload.php';

use Twilio\Rest\Client;

$client = new Client($accountSid, $authToken);

// Get POST data from AJAX request
$from = $_POST['from'];
$to = $_POST['to'];
$body = $_POST['body'];

try {
    $message = $client->messages->create(
        $to, // To number
        [
            'from' => $from,
            'body' => $body
        ]
    );
    $twilio_sms_table = $wpdb->prefix . 'twilio_sms';

    $data = [
        'to_number'  => $to,
        'from_number' => $from,
        'message'    => $body,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ];
    $inserted = $wpdb->insert($twilio_sms_table, $data);

    if ($inserted !== false) {
        echo json_encode([
            'status' => 'success',
            'sid' => $message->sid,
            'message' => 'Message sent and logged successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'failure',
            'error' => 'Database Error',
            'message' => 'Message sent but failed to log in database.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'failed',
        'error' => $e->getMessage(),
        'message' => 'Failed to send the message.'
    ]);
}
