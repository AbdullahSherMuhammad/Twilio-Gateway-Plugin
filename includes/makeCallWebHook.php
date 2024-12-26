<?php
session_start();
// Load WordPress environment to use $wpdb
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}
require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

use Twilio\Rest\Client;

global $wpdb;
$twilio_call_table = $wpdb->prefix . 'twilio_call';

$accountSid = isset($_SESSION['account_sid']) ? $_SESSION['account_sid'] : '';
$authToken = isset($_SESSION['auth_token']) ? $_SESSION['auth_token'] : '';

if (empty($accountSid) || empty($authToken)) {
   echo json_encode(['error' => 'Twilio credentials are missing. Please authenticate first.']);
    exit;
}

$client = new Client($accountSid, $authToken);

$callFromNumber = $_POST['callFromNumber'] ?? null;
$connectBy = $_POST['connectBy'] ?? null;
$numberToCall = $_POST['numberToCall'] ?? null;

if (empty($callFromNumber) || empty($connectBy) || empty($numberToCall)) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;

}

$messageToSay = "Hello, this is a test call from twilio gateway plugin!";

$twiml = '<Response><Say voice="alice">' . htmlspecialchars($messageToSay) . '</Say></Response>';
try {
    $call = $client->calls->create(
        $numberToCall,
        $callFromNumber,
        [
            'twiml' => $twiml
        ]
    );
    
    $wpdb->insert($twilio_call_table, [
        'callSid'     => $call->sid,
        'direction'   => 'outbound-api',
        'success'     => 'false',
        'to_number'   => $numberToCall,
        'from_number' => $callFromNumber,
        'status'      => $call->status,
        'duration'    => null,
        'startTime'   => current_time('mysql'),
        'endTime'     => '0000-00-00 00:00:00',
        'created_at' => current_time('mysql')
    ]);
    
   echo json_encode([
        'success' => true,
        'callSid' => $call->sid,
        'status'  => $call->status
    ]);

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
