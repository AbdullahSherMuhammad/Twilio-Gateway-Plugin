<?php
session_start();

if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}

require __DIR__ . '/../vendor/autoload.php';
use Twilio\Rest\Client;

header('Content-Type: application/json');

$accountSid = $_SESSION['account_sid'] ?? '';
$authToken = $_SESSION['auth_token'] ?? '';

if (empty($accountSid) || empty($authToken)) {
    echo json_encode(['error' => 'Twilio credentials are missing. Please authenticate first.']);
    exit;
}

$client = new Client($accountSid, $authToken);

$callFromNumber = $_POST['callFromNumber'] ?? null;
$connectBy = $_POST['connectBy'] ?? null;
$numberToCall = $_POST['numberToCall'] ?? null;

$conferenceName = 'MyConferenceRoom';

if (empty($callFromNumber) || empty($numberToCall)) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}
$applicationSid = 'AP7a482e0a36d9fb0f6ea4026d4d00ff1d';

try {
    // Create or join the initial call to the conference
    $call = $client->calls->create(
        $numberToCall,
        $callFromNumber,
        [
            // 'twiml' => '<Response><Dial><Conference>' . htmlspecialchars($conferenceName) . '</Conference></Dial></Response>'
            'twiml' => '<Response><Dial><Application>$applicationSid</Application></Dial></Response>'
        ]
    );

    echo json_encode([
        'success' => true,
        'callSid' => $call->sid,
        'status'  => $call->status
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error creating the call: ' . $e->getMessage()]);
    exit;
}
