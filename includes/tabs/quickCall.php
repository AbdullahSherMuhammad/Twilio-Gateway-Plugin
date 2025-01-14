<div class="tab-pane fade" id="quick-calls" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Quick Call</h5>
    </div>
    <div id="" class="">
        <div class="selectContainer" style="display:flex  !important; justify-content:center  !important;">
            <div class="" style="width:323px !important">
                <div id="callButtonsContainer" class="mb-3 selectInput">
                    <label for="callByDropdown"><strong>Call From:</strong></label>
                    <select id="callByDropdown" required class="form-select">
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
                <div id="connectedByContainer" class="mb-3 selectInput">
                    <label for="connectedByDropdown"><strong>Connect By:</strong></label>
                    <select id="connectedByDropdown" class="form-select" required>
                        <option value="" disabled selected>Select connection...</option>
                        <option value="Web Browser">Web Browser</option>
                        <option value="Admin">Cell Phone</option>
                    </select>
                </div>

                <div id="BrowserNumbersSection" class="d-none">
                    <label for="secondNumberToCallTo"><strong>Enter a Number to Call:</strong></label>
                    <input type="text" id="secondNumberToCallTo" class="form-control" placeholder="Enter a number to call">
                    <div class="error-message text-danger" style="display: none;"></div>
                </div>
                <div id="callByBrowserStatus" class="d-none" style="font-size: 18px; margin-left: 10px; margin-top: 10px;">
                    <!-- Content will be injected here dynamically by jQuery -->
                </div>


                <div id="HostNumbersSection" class="d-none">
                    <label for="HostNumbersDropdown"><strong>Call the Host:</strong></label>
                    <select id="HostNumbersDropdown" class="form-select">
                        <option value="" disabled selected>Select an option...</option>
                        <?php
                        if (!session_id()) {
                            session_start();
                        }
                        global $wpdb;

                        // Get account_sid from the session
                        $accountSid = isset($_SESSION['account_sid']) ? $_SESSION['account_sid'] : '';

                        // Fetch data from the wp_twilio_numbers table for the specific account_sid
                        $table_name = $wpdb->prefix . 'other_numbers';
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
                                echo '<option disabled>No other numbers found for this account</option>';
                            }
                        } else {
                            echo '<option disabled>Twilio credentials are missing or invalid. Please go to settings tab and validate the credentails.</option>';
                        }
                        ?>
                    </select>
                    <div id="hostTickMark" class="d-none" style="color: green; font-size: 18px; margin-left: 10px; margin-top: 10px;">
                        <!-- Content will be injected here dynamically by jQuery -->
                    </div>
                    <div id="secondDropdownSection" class="d-none mt-3">
                        <label for="numberToCallTo"><strong>Enter a Number to Call:</strong></label>
                        <input type="text" id="numberToCallTo" class="form-control" placeholder="Enter a number to call">
                        <div class="error-message text-danger" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="dial_pad">
            <?php include_once plugin_dir_path(__FILE__) . '../components/dial_pad.php'; ?>
        </div>
        <div id="dial_pad_for_conf" class="d-none">
            <?php include_once plugin_dir_path(__FILE__) . '../components/dialPadConferenceCall.php'; ?>
        </div>
    </div>
</div>

<script>
    (function($) {
        var callSid = null;
        var checkCount = 0;
        var maxChecks = 5;

        var statusData = {
            'queued': {
                icon: '<i class="fas fa-hourglass-start"></i>',
                text: 'Queued',
                color: 'orange'
            },
            'ringing': {
                icon: '<i class="fas fa-bell"></i>',
                text: 'Ringing',
                color: 'orange'
            },
            'in-progress': {
                icon: '<i class="fas fa-phone-volume"></i>',
                text: 'In Progress',
                color: 'green'
            },
            'completed': {
                icon: '<i class="fas fa-check-circle"></i>',
                text: 'Completed',
                color: 'green'
            },
            'busy': {
                icon: '<i class="fas fa-times-circle"></i>',
                text: 'Busy',
                color: 'red'
            },
            'failed': {
                icon: '<i class="fas fa-exclamation-triangle"></i>',
                text: 'Failed',
                color: 'red'
            },
            'no-answer': {
                icon: '<i class="fas fa-question-circle"></i>',
                text: 'No Answer',
                color: 'red'
            },
            'canceled': {
                icon: '<i class="fas fa-ban"></i>',
                text: 'Canceled',
                color: 'red'
            },
            'default': {
                icon: '<i class="fas fa-info-circle"></i>',
                text: 'Unknown',
                color: 'grey'
            }
        };

        function resetCallUI() {
            localStorage.removeItem('callFromNumber');
            localStorage.removeItem('callToNumber');

            $('#callByDropdown').val('');
            $('#connectedByDropdown').val('');
            $('#secondNumberToCallTo').val('');
            $('#numberToCallTo').val('');

            $('#BrowserNumbersSection').addClass('d-none');
            $('#HostNumbersSection').addClass('d-none');
            $('#hostTickMark').addClass('d-none');
            $('#secondDropdownSection').addClass('d-none');

            $('#number-display').text('');
            $('#callByBrowserStatus').addClass('d-none').html('');
            callSid = null;
            checkCount = 0;
        }

        function displayCallStatusInSwal(status) {
            var data = statusData[status] || statusData['default'];
            Swal.fire({
                icon: data.color === 'green' ? 'success' : 'error',
                title: `Call Status: ${data.text}`,
                // text: `${data.icon} <br> ${data.text}`,
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
            });
        }

        $(document).ready(function() {
            localStorage.removeItem('callFromNumber');
            localStorage.removeItem('callToNumber');
            let numberChosen = false;

            $('#makeCallButton').on('click', function() {
                $('#callSection').removeClass('d-none');
                $('#callByButton').prop('disabled', false);
                $('#connectedByButton').prop('disabled', true);
                $('#callButtonsContainer').show();
                $('.selected-from-number, .selected-to-number').remove();
                $('#numberDropdown').prop('disabled', false);
                $('#manualNumberInput').prop('disabled', false).val('');
                numberChosen = false;
            });

            $('#callByDropdown').on('change', function() {
                const selectedCallFrom = $(this).val();
                if (selectedCallFrom) {
                    $('#number-display').text('');
                    localStorage.setItem('callFromNumber', selectedCallFrom);
                }
            });

            $('#connectedByDropdown').on('change', function() {
                const selectedConnectTo = $(this).val();
                if (selectedConnectTo === "Web Browser" || selectedConnectTo === "Admin") {
                    // Open empty dial pad and allow custom number input
                    $('#dial_pad').removeClass('d-none');
                    $('#number-display').text(''); // Clear number display
                    $('#manualNumberInput').prop('disabled', false).val('');
                    localStorage.setItem('callToNumber', selectedConnectTo);
                } else {
                    $('#manualNumberInput').prop('disabled', true);
                }

                // Reset the tick mark when changing the connection type
                $('#hostTickMark').addClass('d-none'); // Hide tick mark
            });

            $('#HostNumbersDropdown').on('change', function() {
                const selectedHostNumber = $(this).val();
                if (selectedHostNumber) {
                    // $('#dial_pad').removeClass('d-none');
                    $('#number-display').text(selectedHostNumber);
                    localStorage.setItem('callToNumber', selectedHostNumber);
                    $('#hostTickMark').removeClass('d-none');
                    $('#secondDropdownSection').removeClass('d-none');
                }
            });

            $('#numberToCallTo, #secondNumberToCallTo').on('input', function() {
                let input = $(this).val();
                const errorMessage = $(this).siblings('.error-message');

                // Check for non-numeric characters
                if (/[^0-9]/.test(input)) {
                    // Show error message for invalid input
                    errorMessage.text('Please enter numbers only.').show();
                    // Remove non-numeric characters
                    input = input.replace(/[^0-9]/g, '');
                    $(this).val(input); // Update input field with the cleaned value
                }
                // else if (input.length > 11) {
                //     // Show error message for exceeding the max length
                //     errorMessage.text('You can only enter up to 11 digits.').show();
                //     // Trim input to 11 characters
                //     input = input.slice(0, 11);
                //     $(this).val(input); // Update input field
                // } 
                else {
                    errorMessage.hide();
                }

                // Update dial pad display
                if (input) {
                    $('#dial_pad').addClass('d-none');
                    $('#number-display').text(input);
                    localStorage.setItem('callToNumber', input);
                } else {
                    $('#number-display').text('');
                    localStorage.removeItem('callToNumber');
                }
            });

            $('#saveNumberButton').on('click', function() {
                const selectedDropdownNumber = $('#numberDropdown').val();
                const enteredNumber = $('#manualNumberInput').val().trim();
                const finalToNumber = enteredNumber || selectedDropdownNumber;

                if (!finalToNumber) {
                    alert('Please select or enter a number.');
                    return;
                }

                localStorage.setItem('callToNumber', finalToNumber);
                $('#dummyOtherNumbersSection').addClass('d-none');
                $('#callButtonsContainer').addClass('d-none');
                $('#dial_pad').removeClass('d-none');

                if (!$('.selected-to-number').length) {
                    $('<div class="selected-to-number mb-2"><strong>Connected To: </strong>' + finalToNumber + '</div>').insertAfter('#connectedByButton');
                } else {
                    $('.selected-to-number').text('Connected To: ' + finalToNumber);
                }

                $('#number-display').text(finalToNumber); // Display the number in #number-display
                numberChosen = true;
            });

            $('#connectedByDropdown').on('change', function() {
                const selectedConnectTo = $(this).val();
                $('#dial_pad').addClass('d-none');
                $('#dial_pad_for_conf').addClass('d-none');
                $('#BrowserNumbersSection, #HostNumbersSection').addClass('d-none');

                if (selectedConnectTo === 'Web Browser') {
                    $('#BrowserNumbersSection').removeClass('d-none');
                    $('#dial_pad').removeClass('d-none');
                } else if (selectedConnectTo === 'Admin') {
                    $('#HostNumbersSection').removeClass('d-none');
                    $('#dial_pad_for_conf').removeClass('d-none');
                }
            });

            function updateCallStatusUI(status) {
                var data = statusData[status] || statusData['default'];
                var html = data.icon + ' Call Status: ' + data.text;
                $('#callByBrowserStatus')
                    .removeClass('d-none')
                    .css('color', data.color)
                    .html(html);
            }

            function enableMakeCallButton() {
                $('#makeCallButton')
                    .prop('disabled', false)
                    .css({
                        'pointer-events': '',
                        'background-color': '',
                        'opacity': '',
                        'cursor': ''
                    });
            }

            function checkCallStatus() {
                if (!callSid) return;

                $.ajax({
                    url: twilioAjax.ajaxUrl,
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        action: 'get_call_status',
                        nonce: twilioAjax.nonce,
                        callSid: callSid,
                    },
                    success: function(response) {
                        if (response.success) {
                            var status = response.status;
                            updateCallStatusUI(status);
                            var terminalStates = ['completed', 'busy', 'failed', 'no-answer', 'canceled'];
                            if (terminalStates.includes(status)) {
                                setTimeout(() => {
                                    resetCallUI();
                                    displayCallStatusInSwal(status);
                                    enableMakeCallButton();
                                }, 1000);
                                return;
                            }
                            if (checkCount < maxChecks) {
                                checkCount++;
                                setTimeout(checkCallStatus, 6000);
                            } else {
                                if (status === 'ringing') {
                                    forceNoAnswerStatus();
                                } else {
                                    setTimeout(() => {
                                        enableMakeCallButton();
                                        resetCallUI();
                                        displayCallStatusInSwal(status);
                                    }, 1000);
                                }
                            }
                        } else {
                            enableMakeCallButton();
                            console.error('Error fetching call status:', response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        enableMakeCallButton();
                        resetCallUI();
                        console.error('Ajax error:', error);
                    }
                });
            }

            function forceNoAnswerStatus() {
                $.ajax({
                    url: twilioAjax.ajaxUrl,
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        action: 'get_call_status',
                        nonce: twilioAjax.nonce,
                        callSid: callSid,
                        force_no_answer: true
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            setTimeout(() => {
                                updateCallStatusUI(response.status);
                                enableMakeCallButton();
                                resetCallUI();
                                displayCallStatusInSwal(response);
                            }, 1000);
                        }
                        enableMakeCallButton();
                    },
                    error: function(xhr, status, error) {
                        enableMakeCallButton();
                        resetCallUI();
                        console.error('Ajax error:', error);
                    }
                });
            }

            $('#makeCallButton').on('click', function() {
                const callFromNumber = localStorage.getItem('callFromNumber');
                const connectBy = $('#connectedByDropdown').val();
                const numberToCall = $('#secondNumberToCallTo').val() || $('#numberToCallTo').val();

                if (!callFromNumber || !connectBy || !numberToCall) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please fill all required fields!'
                    });
                    return;
                }

                $('#makeCallButton')
                    .prop('disabled', true)
                    .css({
                        'pointer-events': 'none',
                        'background-color': 'grey',
                        'opacity': '0.6',
                        'cursor': 'not-allowed'
                    });

                $.ajax({
                    url: twilioAjax.ajaxUrl,
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        action: 'make_call_webHook',
                        nonce: twilioAjax.nonce,
                        callFromNumber: callFromNumber,
                        connectBy: connectBy,
                        numberToCall: numberToCall
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            callSid = response.callSid;
                            updateCallStatusUI(response.status);
                            setTimeout(checkCallStatus, 2000);
                            Swal.fire({
                                icon: 'success',
                                title: 'Call Connecting...',
                                text: 'Your call has been connected successfully.',
                                timer: 3000,
                                showConfirmButton: false,
                                timerProgressBar: true
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error initiating call',
                                text: response.error
                            });
                            enableMakeCallButton();
                            resetCallUI();
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error initiating call',
                            text: error
                        });
                        enableMakeCallButton();
                        resetCallUI();
                    }
                });
            });

            function checkCallStatusForHost(targetElementId) {
                if (!callSid) return;

                $.ajax({
                    url: twilioAjax.ajaxUrl,
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        action: 'get_call_status',
                        nonce: twilioAjax.nonce,
                        callSid: callSid
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var status = response.status;
                            var data = statusData[status] || statusData['default'];
                            $('#' + targetElementId)
                                .removeClass('d-none')
                                .css('color', data.color)
                                .html(data.icon + ' Call Status: ' + data.text);

                            var terminalStates = ['completed', 'busy', 'failed', 'no-answer', 'canceled'];
                            if (terminalStates.includes(status)) {
                                enableMakeCallButton();
                                resetCallUI();
                                return;
                            }
                            if (checkCount < maxChecks) {
                                checkCount++;
                                setTimeout(function() {
                                    checkCallStatusForHost(targetElementId);
                                }, 6000);
                            } else {
                                if (status === 'ringing') {
                                    forceNoAnswerStatusForHost(targetElementId);
                                } else {
                                    enableMakeCallButton();
                                    resetCallUI();
                                }
                            }
                        } else {
                            enableMakeCallButton();
                            console.error('Error fetching call status:', response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        enableMakeCallButton();
                        resetCallUI();
                        console.error('Ajax error:', error);
                    }
                });
            }

            function forceNoAnswerStatusForHost(targetElementId) {
                $.ajax({
                    url: twilioAjax.ajaxUrl,
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        action: 'get_call_status',
                        nonce: twilioAjax.nonce,
                        callSid: callSid,
                        force_no_answer: true
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var status = response.status;
                            var data = statusData[status] || statusData['default'];
                            $('#' + targetElementId)
                                .removeClass('d-none')
                                .css('color', data.color)
                                .html(data.icon + ' Call Status: ' + data.text);

                            enableMakeCallButton();
                            resetCallUI();
                        }
                    },
                    error: function(xhr, status, error) {
                        enableMakeCallButton();
                        resetCallUI();
                        console.error('Ajax error:', error);
                    }
                });
            }

            // $('#HostNumbersDropdown').on('change', function() {
            //     const selectedHostNumber = $(this).val();
            //     const connectBy = $('#connectedByDropdown').val();
            //     if (selectedHostNumber) {
            //         const callFromNumber = localStorage.getItem('callFromNumber');
            //         if (!callFromNumber) {
            //             Swal.fire({
            //                 icon: 'warning',
            //                 title: 'Warning',
            //                 text: 'Please select the "Call From" number first!'
            //             });
            //             $(this).val('');
            //             return;
            //         }
            //         $('#number-display').text(selectedHostNumber);
            //         setTimeout(() => {
            //             $('#number-display').text('');
            //         }, 1000);
            //         localStorage.setItem('callToNumber', selectedHostNumber);

            //         $('#hostTickMark').removeClass('d-none').html('<i class="fas fa-hourglass-start"></i> Call Status: Initializing...').css('color', 'orange');
            //         $('#secondDropdownSection').removeClass('d-none');

            //         $.ajax({
            //             url: twilioAjax.ajaxUrl,
            //             dataType: 'json',
            //             method: 'POST',
            //             data: {
            //                 action: 'host_call_handler',
            //                 nonce: twilioAjax.nonce,
            //                 callFromNumber: callFromNumber,
            //                 numberToCall: selectedHostNumber,
            //                 connectBy: connectBy
            //             },
            //             success: function(response) {
            //                 if (response.success) {
            //                     callSid = response.callSid;
            //                     checkCallStatusForHost('hostTickMark');
            //                     Swal.fire({
            //                         icon: 'success',
            //                         title: 'Call Connecting...',
            //                         text: 'Your host call has been connected successfully. Now you can add another participant.',
            //                         timer: 3000,
            //                         showConfirmButton: false,
            //                         timerProgressBar: true
            //                     });
            //                     $('#addParticipantSection').removeClass('d-none');
            //                 } else {
            //                     Swal.fire({
            //                         icon: 'error',
            //                         title: 'Error initiating call',
            //                         text: response.error
            //                     });
            //                     enableMakeCallButton();
            //                     resetCallUI();
            //                 }
            //             },
            //             error: function(xhr, status, error) {
            //                 Swal.fire({
            //                     icon: 'error',
            //                     title: 'AJAX Error',
            //                     text: 'Could not contact the server for host call initiation.'
            //                 });
            //                 enableMakeCallButton();
            //                 resetCallUI();
            //             }
            //         });
            //     }
            // });

            let confCallCount = $("#conf-number-display").text().length;

            $(".quick-call-digit").on("click", function() {
                if (confCallCount < 11) {
                    const num = $(this).text().trim();
                    $("#conf-number-display").append(num);
                    confCallCount++;
                }
            });

            $(".delete-icon").on("click", function() {
                const currentDisplay = $("#conf-number-display").text();
                if (currentDisplay.length > 0) {
                    $("#conf-number-display").text(currentDisplay.slice(0, -1));
                    confCallCount--;
                }
            });

            // $('#makeConfCallButton').on('click', function() {
            //     const callFromNumber = $('#callByDropdown').val();
            //     const connectedByDropdown = $('#connectedByDropdown').val().trim();
            //     const additionalNumber = $('#numberToCallTo');

            //     if (!additionalNumber || !callFromNumber || connectedByDropdown) {
            //         Swal.fire({
            //             icon: 'warning',
            //             title: 'Warning',
            //             text: 'Please fill all required fields!'
            //         });
            //         return;
            //     }

            //     $.ajax({
            //         url: twilioAjax.ajaxUrl,
            //         dataType: 'json',
            //         method: 'POST',
            //         data: {
            //             action: 'add_participant',
            //             nonce: twilioAjax.nonce,
            //             callFromNumber: callFromNumber,
            //             additionalNumber: additionalNumber,
            //             conferenceName: 'MyConferenceRoom'
            //         },
            //         success: function(response) {
            //             if (response.success) {
            //                 Swal.fire({
            //                     icon: 'success',
            //                     title: 'Participant Added',
            //                     text: 'The participant has been successfully added to the conference!'
            //                 });
            //             } else {
            //                 Swal.fire({
            //                     icon: 'error',
            //                     title: 'Error adding participant',
            //                     text: response.error || 'An error occurred.'
            //                 });
            //             }
            //         },
            //         error: function(xhr, status, error) {
            //             Swal.fire({
            //                 icon: 'error',
            //                 title: 'AJAX Error',
            //                 text: 'Could not contact the server for adding a participant.'
            //             });
            //         }
            //     });
            // });

            $('#makeConfCallButton').on('click', function() {
                const hostNumber = $('#HostNumbersDropdown').val(); // Host number
                const recipientNumber = $('#numberToCallTo').val().trim(); // Recipient number
                const connectBy = $('#connectedByDropdown').val(); // Connection method

                // Error handling: Check if both host and recipient numbers are provided
                if (!hostNumber) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Host Number',
                        text: 'Please select a host number before initiating the call.',
                    });
                    return;
                }

                if (!recipientNumber) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Recipient Number',
                        text: 'Please enter a recipient number before initiating the call.',
                    });
                    return;
                }

                // Disable the call button during the process
                $('#makeCallButton')
                    .prop('disabled', true)
                    .css({
                        'pointer-events': 'none',
                        'background-color': 'grey',
                        opacity: '0.6',
                        cursor: 'not-allowed',
                    });

                // Display the first message: Connecting to the host
                Swal.fire({
                    icon: 'info',
                    title: 'Connecting to Host',
                    text: 'Please wait while we connect the call to the host.',
                    timer: 3000,
                    showConfirmButton: false,
                    timerProgressBar: true,
                }).then(() => {
                    // Simulate a delay for connecting to the host
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Connected to Host',
                            text: 'The call has been connected to the host. Now connecting to the recipient.',
                            timer: 3000,
                            showConfirmButton: false,
                            timerProgressBar: true,
                        }).then(() => {
                            // Simulate a delay for connecting to the recipient
                            setTimeout(() => {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Connecting to Recipient',
                                    text: 'Please wait while we connect the call to the recipient.',
                                    timer: 3000,
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                }).then(() => {
                                    // Simulate a delay for connecting the call
                                    setTimeout(() => {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Call Connected',
                                            text: 'The host and recipient are now on a call.',
                                            timer: 3000,
                                            showConfirmButton: false,
                                            timerProgressBar: true,
                                        }).then(() => {
                                            // Simulate a delay for ending the call
                                            setTimeout(() => {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Call Finished',
                                                    text: 'The call between the host and recipient has ended.',
                                                    timer: 3000,
                                                    showConfirmButton: false,
                                                    timerProgressBar: true,
                                                }).then(() => {
                                                    window.location.href = window.location.href;
                                                })

                                                // Re-enable the call button after the process
                                                $('#makeCallButton')
                                                    .prop('disabled', false)
                                                    .css({
                                                        'pointer-events': '',
                                                        'background-color': '',
                                                        opacity: '',
                                                        cursor: '',
                                                    });
                                            }, 3000);
                                        });
                                    }, 3000);
                                });
                            }, 3000);
                        });
                    }, 3000);
                });
            });

        });
    })(jQuery);
</script>