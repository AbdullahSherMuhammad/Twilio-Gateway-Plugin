<div class="tab-pane fade" id="quick-sms" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Quick SMS</h5>
    </div>

    <!-- Add SMS Form -->
    <div class="" id="addSmsForm">
        <form id="sendSmsForm" class="mb-4">
            <div class="mb-3">
                <label for="from-sms-number" class="form-label">From</label>
                <select class="form-control" id="from-sms-number" required style="max-width: 75rem !important;min-height: 35px !important;">
                    <option value="" disabled selected>Select Your Twilio Number</option>
                   <?php
                    if (!session_id()) {
                        session_start();
                    }
                    global $wpdb;
                    $accountSid = isset($_SESSION['account_sid']) ? $_SESSION['account_sid'] : '';
                    $table_name = $wpdb->prefix . 'twilio_numbers';
                    if (!empty($accountSid)) {
                        $query = $wpdb->prepare(
                            "SELECT number FROM $table_name WHERE twilio_user_id = %s ORDER BY id DESC",
                            $accountSid
                        );
                        $twilioNumbers = $wpdb->get_results($query);
                    
                        if (!empty($twilioNumbers)) {
                            foreach ($twilioNumbers as $row) {
                                $twilioNumber = esc_html($row->number);
                                echo '<option value="' . esc_attr($twilioNumber) . '">' . esc_html($twilioNumber) . '</option>';
                            }
                        } else {
                            echo '<option disabled>No Twilio numbers found for this account</option>';
                        }
                    } else {
                        echo '<option disabled>Twilio credentials are missing or invalid. Please go to settings tab and validate the credentails.</option>';
                    }
                    ?>

                </select>
            </div>
            <div class="mb-3">
                <label for="to-sms-number" class="form-label">To</label>
                <input type="text" class="form-control" id="to-sms-number" placeholder="Enter Receiver's Number" required>
            </div>
            <div class="mb-3">
                <label for="sms-message" class="form-label">Message</label>
                <textarea class="form-control" id="sms-message" rows="3" placeholder="Enter your message" required></textarea>
            </div>
            <button type="submit" class="btn btn-success" id="sendSmsButton">Send SMS</button>
        </form>
    </div>
    
</div>
