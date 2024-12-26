<?php
session_start();
// Load WordPress environment to use $wpdb
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}
require __DIR__ . '/../vendor/autoload.php';

use Twilio\Rest\Client;

header('Content-Type: application/json');

global $wpdb;
$twilio_call_table = $wpdb->prefix . 'twilio_call';

$accountSid = isset($_SESSION['account_sid']) ? $_SESSION['account_sid'] : '';
$authToken = isset($_SESSION['auth_token']) ? $_SESSION['auth_token'] : '';

if (empty($accountSid) || empty($authToken)) {
    echo json_encode(['error' => 'Twilio credentials are missing.']);
    exit;
}

$callSid = $_POST['callSid'] ?? null;
if (!$callSid) {
    echo json_encode(['status'=>'false','error' => 'Sid is required.']);
    exit;
}

$forceNoAnswer = isset($_POST['force_no_answer']) ? true : false;

$client = new Client($accountSid, $authToken);

try {
    if ($forceNoAnswer) {
        // Force update to no-answer
        $wpdb->update(
            $twilio_call_table,
            [
                'status'    => 'no-answer',
                'duration'  => 0,
                'endTime'   => current_time('mysql'),
                'success'   => 'false',
                'updated_at'=> current_time('mysql')
            ],
            [ 'callSid' => $callSid ]
        );

        echo json_encode([
            'success' => true,
            'callSid' => $callSid,
            'status'  => 'no-answer',
            'duration' => 0
        ]);
    } else {
        // Normal getCallStatus logic
        $call = $client->calls($callSid)->fetch();
        $status = $call->status;
        $duration = $call->duration;
        $startTime = $call->startTime ? $call->startTime->format('Y-m-d H:i:s') : null;
        $endTime = $call->endTime ? $call->endTime->format('Y-m-d H:i:s') : null;
        $fromNumber = $call->from;
        $toNumber = $call->to;
        $direction = $call->direction;

        $success = ($status === 'completed') ? 'true' : 'false';
        $endTimeValue = $endTime ?: '0000-00-00 00:00:00';

        $rows_affected = $wpdb->update(
            $twilio_call_table,
            [
                'status'    => $status,
                'duration'  => $duration,
                'startTime' => $startTime ?: current_time('mysql'),
                'endTime'   => $endTimeValue,
                'success'   => $success,
                'direction' => $direction,
                'updated_at'=> current_time('mysql')
            ],
            [ 'callSid' => $callSid ]
        );

        echo json_encode([
            'success' => true,
            'callSid' => $call->sid,
            'status' => $status,
            'duration' => $duration,
            'startTime' => $startTime,
            'endTime' => $endTimeValue,
            'from' => $fromNumber,
            'to' => $toNumber,
            'direction' => $direction
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
