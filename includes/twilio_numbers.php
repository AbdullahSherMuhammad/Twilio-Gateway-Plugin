<?php
if (!session_id()) {
    session_start();
}

if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}

require __DIR__ . '/../vendor/autoload.php';
use Twilio\Rest\Client;

global $wpdb;

header('Content-Type: application/json');

$accountSid = isset($_SESSION['account_sid']) ? $_SESSION['account_sid'] : '';
$authToken = isset($_SESSION['auth_token']) ? $_SESSION['auth_token'] : '';

try {
    $client = new Client($accountSid, $authToken);
    
    $incomingPhoneNumbers = $client->incomingPhoneNumbers->read();

    if (empty($incomingPhoneNumbers)) {
        echo json_encode(['success' => false, 'message' => 'No incoming phone numbers found from Twilio.']);
        exit;
    }
    
    foreach ($incomingPhoneNumbers as $number) {
        $phoneNumber = $number->phoneNumber;
        
        $sql_check = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}twilio_numbers WHERE number = %s AND twilio_user_id = %s", $phoneNumber, $accountSid);
        $result = $wpdb->get_results($sql_check);

        if (!empty($result)) {
            continue;
        }
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'twilio_numbers',
            [
                'twilio_user_id' => $accountSid,
                'number' => $phoneNumber,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
        if (!$insert_result) {
            // error_log("Failed to insert phone number: $phoneNumber");
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false
    ]);
}
