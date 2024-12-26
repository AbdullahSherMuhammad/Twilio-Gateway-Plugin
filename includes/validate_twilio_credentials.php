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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountSid = $_POST['account_sid'] ?? '';
    $authToken = $_POST['auth_token'] ?? '';

    try {
        // Check if session credentials match
        if (isset($_SESSION['account_sid'], $_SESSION['auth_token']) &&
            $_SESSION['account_sid'] === $accountSid &&
            $_SESSION['auth_token'] === $authToken) {

            $table = $wpdb->prefix . "twilio_users"; // WordPress table with 'wp_' prefix
            $wpdb->update(
                $table,
                ['updated_at' => current_time('mysql')],
                ['account_sid' => $accountSid, 'auth_token' => $authToken]
            );

            echo json_encode(['status' => 'success', 'message' => 'Credentials updated successfully!']);
            exit;
        }

        // Authenticate with Twilio
        $twilio = new Client($accountSid, $authToken);
        $account = $twilio->api->v2010->accounts($accountSid)->fetch();

        $dateCreated = $account->dateCreated->format('Y-m-d H:i:s');
        $dateUpdated = $account->dateUpdated->format('Y-m-d H:i:s');

        $_SESSION['account_sid'] = $accountSid;
        $_SESSION['auth_token'] = $authToken;

        $table = $wpdb->prefix . "twilio_users";

        // Check if credentials already exist
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE account_sid = %s AND auth_token = %s",
                $accountSid, $authToken
            )
        );

        if ($exists > 0) {
            // Update existing record
            $wpdb->update(
                $table,
                [
                    'friendly_name' => $account->friendlyName,
                    'status'        => $account->status,
                    'date_created'  => $dateCreated,
                    'date_updated'  => $dateUpdated,
                    'updated_at'    => current_time('mysql'),
                ],
                ['account_sid' => $accountSid, 'auth_token' => $authToken]
            );

            echo json_encode(['status' => 'success', 'message' => 'Credentials updated successfully!']);
            exit;
        } else {
            // Insert new record
            $wpdb->insert(
                $table,
                [
                    'account_sid'   => $accountSid,
                    'auth_token'    => $authToken,
                    'friendly_name' => $account->friendlyName,
                    'status'        => $account->status,
                    'date_created'  => $dateCreated,
                    'date_updated'  => $dateUpdated,
                    'created_at'    => current_time('mysql'),
                ]
            );

            echo json_encode(['status' => 'success', 'message' => 'Credentials saved successfully!']);
            exit;
        }
    } catch (\Twilio\Exceptions\RestException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Authentication failed: ' . htmlspecialchars($e->getMessage()),
        ]);
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'An unexpected error occurred. Please try again later.',
        ]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Only POST requests are allowed.',
    ]);
    exit;
}
