<?php
global $wpdb;

// Get account_sid from the session
$accountSid = isset($_SESSION['account_sid']) ? $_SESSION['account_sid'] : '';

// Fetch Twilio Numbers from the database
$twilioNumbers = [];
if (!empty($accountSid)) {
    $table_twilio_numbers = $wpdb->prefix . 'twilio_numbers';
    $query_twilio = $wpdb->prepare(
        "SELECT number, created_at FROM $table_twilio_numbers WHERE twilio_user_id = %s ORDER BY id DESC",
        $accountSid
    );
    $twilioNumbers = $wpdb->get_results($query_twilio);
}

// Fetch Other Numbers from the database
$otherNumbers = [];
if (!empty($accountSid)) {
    $table_other_numbers = $wpdb->prefix . 'other_numbers';
    $query_other = $wpdb->prepare(
        "SELECT number, created_at FROM $table_other_numbers WHERE twilio_user_id = %s ORDER BY id DESC",
        $accountSid
    );
    $otherNumbers = $wpdb->get_results($query_other);
}
?>

<div class="tab-pane fade" id="phone-book" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Phone Book</h5>
        <!-- Buttons to toggle between Twilio and Other numbers -->
        <div>
            <button class="btn btn-primary" id="showTwilioNumbersBtn" type="button">Twilio Numbers</button>
            <button class="btn btn-secondary" id="showOtherNumbersBtn" type="button">Other Numbers</button>
        </div>
    </div>

    <!-- Section to add a new number -->
    <div class="collapse" id="addNumberSection">
        <form id="addNumberForm" class="mb-4">
            <div class="row align-items-center mb-3">
                <div class="col">
                    <label for="add-number" class="form-label visually-hidden">Number</label>
                    <input type="text" class="form-control" id="add-number" placeholder="Enter a number to add" required>
                </div>
            </div>
            <button type="button" class="btn btn-success" id="addNumberButton">Submit</button>
        </form>
    </div>

    <div class="row">
        <!-- Twilio Numbers Table -->
        <div class="col-md-6 mb-3 w-100" id="twilioTableContainer" style="display: none;">
            <h5>Twilio Numbers</h5>
            <div class="data-table-container">
                <table class="table table-striped table-bordered w-100" id="myNumbersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Number</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (!empty($twilioNumbers)) {
                                $counter = 1;
                                foreach ($twilioNumbers as $row) {
                                    $number = esc_html($row->number);
                                    $created_at = esc_html($row->created_at);
                                    echo "<tr>
                                            <td>{$counter}</td>
                                            <td>{$number}</td>
                                            <td>{$created_at}</td>
                                        </tr>";
                                    $counter++;
                                }
                            } else {
                                echo "<tr><td colspan='3'>No Twilio numbers found.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Other Numbers Table -->
        <div class="col-md-6 mb-3 w-100" id="otherNumbersTableContainer" style="display: none;">
            <h5>Other Numbers</h5>
            <div class="data-table-container">
                <table class="table table-striped table-bordered w-100" id="otherNumbersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Number</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php
                            if (!empty($otherNumbers)) {
                                $counter = 1;
                                foreach ($otherNumbers as $row2) {
                                    $number2 = esc_html($row2->number);
                                    $created_at2 = esc_html($row2->created_at);
                                    echo "<tr>
                                            <td>{$counter}</td>
                                            <td>{$number2}</td>
                                            <td>{$created_at2}</td>
                                        </tr>";
                                    $counter++;
                                }
                            } else {
                                echo "<tr><td colspan='3'>No other numbers found.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        $(document).ready(function() {
            const showTwilioNumbers = () => {
                $('#twilioTableContainer').show();
                $('#otherNumbersTableContainer').hide();
                $('#addNumberSection').show();
                $('#showTwilioNumbersBtn').removeClass('btn-secondary').addClass('btn-primary');
                $('#showOtherNumbersBtn').removeClass('btn-primary').addClass('btn-secondary');
            };

            const showOtherNumbers = () => {
                $('#twilioTableContainer').hide();
                $('#otherNumbersTableContainer').show();
                $('#addNumberSection').show();
                $('#showOtherNumbersBtn').removeClass('btn-secondary').addClass('btn-primary');
                $('#showTwilioNumbersBtn').removeClass('btn-primary').addClass('btn-secondary');
            };

            $('#showTwilioNumbersBtn').on('click', showTwilioNumbers);
            $('#showOtherNumbersBtn').on('click', showOtherNumbers);

            $('#addNumberButton').on('click', function() {
                
                let number = $('#add-number').val().trim();
                if (!number.startsWith('+')) number = '+' + number;
                const type = $('#showOtherNumbersBtn').hasClass('btn-primary') ? 'other' : 'twilio';

                try {
                    const parsedNumber = libphonenumber.parsePhoneNumber(number);
                    if (!parsedNumber.isValid()) {
                        Swal.fire({ icon: 'error', title: 'Invalid Number', text: 'The phone number you entered is not valid.' });
                        return;
                    }
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Invalid Input', text: 'The input provided is not a valid phone number format.' });
                    return;
                }

                Swal.fire({ title: 'Processing...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                $.ajax({
                    url: twilioAjax.ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'add_number',
                        nonce: twilioAjax.nonce,
                        number: number,
                        type: type
                    },
                    success: function(response) {
                        Swal.close();
                           if (response.sid) {
                                Swal.fire({ icon: 'warning', title: response.sid, text: response.error });
                            } else if (response.exists) {
                                Swal.fire({ icon: 'info', title: 'Number Exists', text: 'This number already exists.' });
                            } else if (response.success) {
                                Swal.fire({ icon: 'success', title: 'Success', text: 'Number added successfully!' }).then(() => location.reload());
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to add the number.' });
                            }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        Swal.fire({ icon: 'error', title: 'Server Error', text: 'An error occurred: ' + error });
                    }
                });
            });

            showTwilioNumbers();
        });
    })(jQuery);
</script>
