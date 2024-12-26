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
$additionalNumber = $_POST['additionalNumber'] ?? null;
$conferenceName = $_POST['conferenceName'] ?? null;

if (empty($callFromNumber) || empty($additionalNumber) || empty($conferenceName)) {
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

try {
    // Add the new participant to the conference
    $participant = $client->conferences($conferenceName)
                          ->participants
                          ->create($additionalNumber, ['from' => $callFromNumber]);

    echo json_encode([
        'success' => true,
        'participantSid' => $participant->sid
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error adding participant: ' . $e->getMessage()]);
    exit;
}
