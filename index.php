<?php

/**
 * Plugin Name: Twilio Gateway Plugin
 * Description: A WordPress plugin for Twilio integration (calls, SMS, and phone logs).
 * Version: 1.1
 * Author: Devs Leader
 * License: GPLv2 or later
 */

if (!session_id()) {
    session_start();
}

// Include required files (e.g., Twilio SDK)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    wp_die('Twilio SDK not found. Please install the dependencies.');
}

global $wpdb;

// Register activation hook to create necessary tables
register_activation_hook(__FILE__, 'twilio_gateway_install');
function twilio_gateway_install()
{
    global $wpdb;

    // Check if WordPress is connected to the database
    if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}options'") === null) {
        // wp_die('Database connection failed. Please check your wp-config.php file.');
    }

    // Table names with the WordPress table prefix
    $twilio_numbers_table = $wpdb->prefix . 'twilio_numbers';
    $other_numbers_table = $wpdb->prefix . 'other_numbers';
    $twilio_users_table = $wpdb->prefix . 'twilio_users';
    $twilio_sms_table = $wpdb->prefix . 'twilio_sms';
    $twilio_call_table = $wpdb->prefix . 'twilio_call';

    // SQL to create twilio_numbers table
    $sql_twilio_numbers = "CREATE TABLE IF NOT EXISTS $twilio_numbers_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        twilio_user_id VARCHAR(255) NOT NULL,
        number VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    // SQL to create other_numbers table
    $sql_other_numbers = "CREATE TABLE IF NOT EXISTS $other_numbers_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        twilio_user_id VARCHAR(255) NOT NULL,
        number VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    // SQL to create twilio_users table
    $sql_twilio_users = "CREATE TABLE IF NOT EXISTS $twilio_users_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        account_sid VARCHAR(1000) NOT NULL,
        auth_token VARCHAR(1000) NOT NULL,
        friendly_name VARCHAR(255) NOT NULL,
        status ENUM('active', 'suspended') NOT NULL,
        date_created DATETIME NOT NULL,
        date_updated DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL,
        updated_at TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $sql_twilio_call = "CREATE TABLE IF NOT EXISTS $twilio_call_table (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `callSid` varchar(255) NOT NULL,
          `direction` varchar(255) NOT NULL,
          `success` enum('true','false','','') NOT NULL,
          `to_number` varchar(20) NOT NULL,
          `from_number` varchar(20) NOT NULL,
          `status` enum('queued','ringing','in-progress','failed','completed','busy','canceled','no-answer') NOT NULL,
          `duration` int(11) DEFAULT NULL,
          `startTime` timestamp NOT NULL,
          `endTime` timestamp NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $sql_twilio_sms = "CREATE TABLE IF NOT EXISTS $twilio_sms_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        to_number VARCHAR(20) NOT NULL,
        from_number VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('queued', 'busy', 'completed', 'failed', 'in-progress', 'canceled') NOT NULL,
        duration INT(11) DEFAULT NULL,  -- Call duration in seconds (optional)
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    // Execute table creation queries and check for errors
    $tables_created = true;

    $queries = [
        'twilio_numbers' => $sql_twilio_numbers,
        'other_numbers'  => $sql_other_numbers,
        'twilio_users'   => $sql_twilio_users,
        'twilio_sms'   => $sql_twilio_sms,
        'twilio_call'   => $sql_twilio_call,
    ];

    foreach ($queries as $table_name => $sql) {
        $wpdb->query($sql);
        if ($wpdb->last_error) {
            // error_log("Error creating table $table_name: " . $wpdb->last_error);
            $tables_created = false;
        }
    }

    if ($tables_created) {
        add_option('twilio_gateway_db_status', 'success');
        // error_log("Twilio Gateway Plugin: All tables created successfully.");
    } else {
        add_option('twilio_gateway_db_status', 'failure');
        // error_log("Twilio Gateway Plugin: Error creating tables. Check database permissions and logs.");
    }
}

// Register uninstall hook to remove session data
register_uninstall_hook(__FILE__, 'twilio_gateway_uninstall');

function twilio_gateway_uninstall()
{
    global $wpdb;

    // Table names with the WordPress table prefix
    $twilio_numbers_table = $wpdb->prefix . 'twilio_numbers';
    $other_numbers_table = $wpdb->prefix . 'other_numbers';
    $twilio_users_table = $wpdb->prefix . 'twilio_users';
    $twilio_sms_table = $wpdb->prefix . 'twilio_sms';
    $twilio_call_table = $wpdb->prefix . 'twilio_call';

    // Array of tables to drop
    $tables = [
        $twilio_numbers_table,
        $other_numbers_table,
        $twilio_users_table,
        $twilio_sms_table,
        $twilio_call_table,
    ];

    // Drop each table
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }

    // Remove the plugin-specific options
    delete_option('twilio_gateway_db_status');

    // Unset session data if exists
    if (isset($_SESSION['account_sid'])) {
        unset($_SESSION['account_sid']);
    }

    if (isset($_SESSION['auth_token'])) {
        unset($_SESSION['auth_token']);
    }
}


// Add admin menu
function twilio_gateway_add_menu()
{
    add_menu_page(
        'Twilio Gateway',
        'Twilio Gateway',
        'manage_options',
        'twilio-gateway-plugin',
        'twilio_gateway_render_page',
        'dashicons-phone',
        20
    );
}

add_action('admin_menu', 'twilio_gateway_add_menu');

add_action('send_headers', 'twilio_gateway_add_cors_headers');

function twilio_gateway_add_cors_headers()
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
}

// Ensure scripts are only enqueued on the Twilio Gateway plugin admin page
function twilio_gateway_enqueue_scripts($hook)
{
    if ($hook !== 'toplevel_page_twilio-gateway-plugin') {
        return;
    }
    wp_enqueue_script('jquery');

    wp_enqueue_style('twilio-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css');
    wp_enqueue_script('twilio-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);

    wp_enqueue_script('twilio-sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
    wp_enqueue_script('twilio-libphonenumber', 'https://cdn.jsdelivr.net/npm/libphonenumber-js@1.10.36/bundle/libphonenumber-js.min.js', array(), null, true);

    $custom_script = "
        jQuery(document).ready(function($) {
            const tabKey = 'activeTab';
            const storedTab = localStorage.getItem(tabKey);
            if (storedTab) {
                const tabTrigger = document.querySelector(`[data-bs-target='\${storedTab}']`);
                if (tabTrigger) {
                    const bootstrapTab = new bootstrap.Tab(tabTrigger);
                    bootstrapTab.show();
                }
            }
            $('.nav-link').on('click', function() {
                const activeTab = $(this).data('bs-target');
                localStorage.setItem(tabKey, activeTab);
            });
        });
    ";

    wp_add_inline_script('jquery', $custom_script);
}

add_action('admin_enqueue_scripts', 'twilio_gateway_enqueue_scripts');

// Register AJAX actions
add_action('wp_ajax_get_twilio_numbers', 'handle_get_twilio_numbers');
add_action('wp_ajax_nopriv_get_twilio_numbers', 'handle_get_twilio_numbers');

function handle_get_twilio_numbers()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }
    $file_path = plugin_dir_path(__FILE__) . 'includes/twilio_numbers.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Twilio numbers loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }

    wp_die();
}

add_action('wp_ajax_fetch_sms', 'handle_fetch_sms');
add_action('wp_ajax_nopriv_fetch_sms', 'handle_fetch_sms');

function handle_fetch_sms()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/sms.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Sms loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }

    wp_die();
}

add_action('wp_ajax_send_sms', 'handle_send_sms');
add_action('wp_ajax_nopriv_send_sms', 'handle_send_sms');

function handle_send_sms()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/sendSms.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'SendSms loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }
    wp_die();
}

add_action('wp_ajax_fetch_calls', 'handle_fetch_calls');
add_action('wp_ajax_nopriv_fetch_calls', 'handle_fetch_calls');

function handle_fetch_calls()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/call.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Call loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }
    wp_die();
}

add_action('wp_ajax_add_number', 'handle_add_number');
add_action('wp_ajax_nopriv_add_number', 'handle_add_number');

function handle_add_number()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/add_number.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Number loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }
    wp_die();
}

add_action('wp_ajax_validate_user_credentails', 'handle_validate_user_credentails');
add_action('wp_ajax_nopriv_validate_user_credentails', 'handle_validate_user_credentails');

function handle_validate_user_credentails()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/validate_twilio_credentials.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Validate loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }
    wp_die();
}

add_action('wp_ajax_make_call_webHook', 'handle_make_call_webHook');
add_action('wp_ajax_nopriv_make_call_webHook', 'handle_make_call_webHook');

function handle_make_call_webHook()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/makeCallWebHook.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Validate loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }
    wp_die();
}

add_action('wp_ajax_get_call_status', 'handle_get_call_status');
add_action('wp_ajax_nopriv_get_call_status', 'handle_get_call_status');

function handle_get_call_status()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/getCallStatus.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Validate loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }
    wp_die();
}

add_action('wp_ajax_host_call_handler', 'handle_host_call_handler');
add_action('wp_ajax_nopriv_host_call_handler', 'handle_host_call_handler');

function handle_host_call_handler()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/hostCallHandler.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Validate loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }
    wp_die();
}

add_action('wp_ajax_add_participant', 'handle_add_participant');
add_action('wp_ajax_nopriv_add_participant', 'handle_add_participant');

function handle_add_participant()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'twilio-ajax-nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        wp_die();
    }

    $file_path = plugin_dir_path(__FILE__) . 'includes/addParticipant.php';
    if (file_exists($file_path)) {
        include_once $file_path;
        // wp_send_json_success(['message' => 'Validate loaded successfully']);
    } else {
        wp_send_json_error(['message' => 'File not found']);
    }
    wp_die();
}

function twilio_gateway_enqueue_ajax_script($hook)
{
    if ($hook !== 'toplevel_page_twilio-gateway-plugin') {
        return;
    }
    wp_enqueue_script('jquery');

    wp_localize_script('jquery', 'twilioAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('twilio-ajax-nonce'),
    ]);
}
add_action('admin_enqueue_scripts', 'twilio_gateway_enqueue_ajax_script');

// Render the plugin page
function twilio_gateway_render_page()
{ ?>
    <div class="container mt-5">
        <div class="card shadow" style="max-width: 100% !important;">
            <div class="card-body">
                <h1 class="text-center">Twilio Gateway Plugin</h1>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">Dashboard</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="quick-sms-tab" data-bs-toggle="tab" data-bs-target="#quick-sms" type="button" role="tab">Quick SMS</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="quick-calls-tab" data-bs-toggle="tab" data-bs-target="#quick-calls" type="button" role="tab">Quick Call</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">Logs</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="phone-book-tab" data-bs-toggle="tab" data-bs-target="#phone-book" type="button" role="tab">Phone Book</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">Settings</button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="myTabContent">
                    <!-- Dashboard Tab -->
                    <?php
                    // Include Dashboard Tab
                    include plugin_dir_path(__FILE__) . 'includes/tabs/dashboard.php';

                    // Quick SMS Tab
                    include plugin_dir_path(__FILE__) . 'includes/tabs/quickSms.php';

                    // Quick Calls Tab
                    include plugin_dir_path(__FILE__) . 'includes/tabs/quickCall.php';

                    // Logs Tab
                    include plugin_dir_path(__FILE__) . 'includes/tabs/logs.php';

                    // Phone Book Tab
                    include plugin_dir_path(__FILE__) . 'includes/tabs/phoneBook.php';

                    // Settings Tab
                    include plugin_dir_path(__FILE__) . 'includes/tabs/settings.php';
                    ?>
                    <!-- Other tabs (Quick SMS, Quick Calls, Logs, Phone Book, Settings) will go here -->
                </div>
            </div>
        </div>
    </div>
<?php }
