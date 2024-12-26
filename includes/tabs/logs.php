<div class="tab-pane fade" id="logs" role="tabpanel">
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Logs</h2>
        <!-- Buttons for SMS, Voice, and Error -->
        <div class="d-flex justify-content-start mb-3">
          <button class="btn btn-primary me-2" id="showSMSBtn" type="button">SMS</button>
          <button class="btn btn-secondary me-2" id="showVoiceBtn" type="button">Voice</button>
          <button class="btn btn-secondary" id="showErrorBtn" type="button">Error</button>
        </div>
    </div>

    <!-- SMS Container -->
    <div id="smsContainer">
      <!-- SMS Table -->
          <div class="data-table-container">
            <table class="table table-striped" id="smsDetailsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>To</th>
                        <th>From</th>
                        <th>Message</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <nav aria-label="SMS Pagination">
            <ul class="pagination justify-content-end"></ul>
        </nav>
    </div>

    <!-- Voice Container -->
    <div id="voiceContainer" style="display: none;">
       <!-- Latest Calls Section -->
        <h6>Latest Calls</h6>
        <table class="table table-striped" id="callDetailsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Status</th>
                    <th>Duration</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody>
                  <tr>
                    <td colspan="7">Loading...</td>
                </tr>
            </tbody>
        </table>
        <nav aria-label="Call Pagination">
        <ul class="pagination call-pagination justify-content-end"></ul>
    </nav>
    </div>

    <!-- Error Container -->
    <div id="errorContainer" style="display: none;">
      <h4>Error Logs</h4>
      <p>This is the Error logs content.</p>
    </div>
  </div>
</div>

<!-- script for SMS , Voice and Error -->
<script>
  function showLogs(activeButton, activeContainer, otherButtons, otherContainers) {
    otherContainers.forEach(function(container) {
      document.getElementById(container).style.display = 'none';
    });
    document.getElementById(activeContainer).style.display = 'block';
    
    otherButtons.forEach(function(button) {
      document.getElementById(button).classList.add('btn-secondary');
      document.getElementById(button).classList.remove('btn-primary');
    });
    
    document.getElementById(activeButton).classList.add('btn-primary');
    document.getElementById(activeButton).classList.remove('btn-secondary');
  }
  document.getElementById('showSMSBtn').addEventListener('click', function () {
    showLogs('showSMSBtn', 'smsContainer', ['showVoiceBtn', 'showErrorBtn'], ['voiceContainer', 'errorContainer']);
  });
  document.getElementById('showVoiceBtn').addEventListener('click', function () {
    showLogs('showVoiceBtn', 'voiceContainer', ['showSMSBtn', 'showErrorBtn'], ['smsContainer', 'errorContainer']);
  });
  document.getElementById('showErrorBtn').addEventListener('click', function () {
    showLogs('showErrorBtn', 'errorContainer', ['showSMSBtn', 'showVoiceBtn'], ['smsContainer', 'voiceContainer']);
  });
  showLogs('showSMSBtn', 'smsContainer', ['showVoiceBtn', 'showErrorBtn'], ['voiceContainer', 'errorContainer']);
</script>


<!--Script for fetching the SMS-->
<script>
    (function($) {
        function fetchSms(page = 1) {
            $.ajax({
                url: twilioAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fetch_sms',
                    nonce: twilioAjax.nonce,
                    page: page,
                },
                success: function(response) {
                    if (response.success) {
                        const smsData = response.data.sms_data;
                        const totalPages = response.data.total_pages;
                        const currentPage = response.data.current_page;
    
                        let smsRows = '';
                        if (smsData.length > 0) {
                            smsData.forEach(function(sms, index) {
                                smsRows += `
                                    <tr>
                                        <td>${(currentPage - 1) * 10 + index + 1}</td>
                                        <td>${sms.to_number}</td>
                                        <td>${sms.from_number}</td>
                                        <td>${sms.message}</td>
                                        <td>${sms.created_at}</td>
                                    </tr>`;
                            });
                        } else {
                            smsRows = '<tr><td colspan="5">No SMS data available</td></tr>';
                        }
    
                        $('#smsDetailsTable tbody').html(smsRows);
                        renderPagination(totalPages, currentPage);
                    } else {
                        $('#smsDetailsTable tbody').html('<tr><td colspan="5">Twilio credentials are missing or invalid. Please go to settings tab and validate the credentials.</td></tr>');
                    }
                },
                error: function() {
                    $('#smsDetailsTable tbody').html('<tr><td colspan="5">An error occurred while fetching SMS data.</td></tr>');
                },
            });
        }

        // Function to render pagination
        function renderPagination(totalPages, currentPage) {
            let paginationHtml = '';
    
            if (currentPage > 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
                    </li>`;
            }
    
            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
            }
    
            if (currentPage < totalPages) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
                    </li>`;
            }
    
            $('.pagination').html(paginationHtml);
        }
    
        // Event listener for pagination clicks
        $(document).on('click', '.pagination a', function(event) {
            event.preventDefault();
            const page = $(this).data('page');
            fetchSms(page);
        });
        $(document).on('click','#logs-tab',function(event){
            event.preventDefault();
            fetchSms(1)
        })
        $(document).on('click','#showSMSBtn',function(event){
            event.preventDefault();
            fetchSms(1);
        })
})(jQuery);

</script>


<script>
    (function($) {
       $('#sendSmsForm').submit(function(event) {
            event.preventDefault();
            
            var fromNumber = $('#from-sms-number').val();
            var toNumber = $('#to-sms-number').val();
            var message = $('#sms-message').val();
        
            Swal.fire({
                title: 'Sending SMS...',
                text: 'Please wait while your SMS is being sent.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        
            $.ajax({
               url: twilioAjax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'send_sms',
                    nonce: twilioAjax.nonce,
                    from: fromNumber,
                    to: toNumber,
                    body: message
                },
                success: function(response) {
                    Swal.close();
        
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'SMS Sent!',
                            text: 'Your SMS has been sent successfully.',
                            timer: 3000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        });
                        $('#to-sms-number').val('');
                        $('#sms-message').val('');
                    } else if (response.status === 'failed') {
                        if (response.message) {
                            Swal.fire({
                                icon: 'warning',
                                title: response.error,
                                text: response.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Send SMS!',
                                text: response.error || 'An error occurred while sending the SMS.'
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An internal server error occurred. Please try again.'
                    });
                }
            });
        });
    })(jQuery);
</script>

<!-- script of call webhook for display data of calls -->
<script>
    (function($) {
        function fetchCalls(page = 1) {
            $.ajax({
                url: twilioAjax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'fetch_calls',
                    nonce: twilioAjax.nonce,
                    page: page
                },
                success: function(response) {
                    if (response.success) {
                        const callData = response.data.call_data;
                        const totalPages = response.data.total_pages;
                        const currentPage = response.data.current_page;
    
                        let callRows = '';
                        if (callData.length > 0) {
                            callData.forEach(function(call, index) {
                                callRows += `
                                    <tr>
                                        <td>${(currentPage - 1) * 10 + index + 1}</td>
                                        <td>${call.from_number}</td>
                                        <td>${call.to_number}</td>
                                        <td>${call.status}</td>
                                        <td>${call.duration !== null ? call.duration : '-'}</td>
                                        <td>${call.startTime ? call.startTime : '-'}</td>
                                        <td>${call.endTime ? call.endTime : '-'}</td>
                                    </tr>`;
                            });
                        } else {
                            callRows = '<tr><td colspan="7">No Call data available</td></tr>';
                        }
    
                        $('#callDetailsTable tbody').html(callRows);
                        renderCallPagination(totalPages, currentPage);
                    } else {
                        $('#callDetailsTable tbody').html(`<tr><td colspan="7">${response.message || 'No call data found.'}</td></tr>`);
                    }
                },
                error: function() {
                    $('#callDetailsTable tbody').html('<tr><td colspan="7">An error occurred while fetching call data.</td></tr>');
                },
            });
        }
    
        // Function to render call pagination
        function renderCallPagination(totalPages, currentPage) {
            let paginationHtml = '';
    
            if (currentPage > 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-call-page="${currentPage - 1}">Previous</a>
                    </li>`;
            }
    
            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-call-page="${i}">${i}</a>
                    </li>`;
            }
    
            if (currentPage < totalPages) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-call-page="${currentPage + 1}">Next</a>
                    </li>`;
            }
    
            $('.call-pagination').html(paginationHtml);
        }
    
        // Event listener for call pagination clicks
        $(document).on('click', '.call-pagination a', function(event) {
            event.preventDefault();
            const page = $(this).data('call-page');
            fetchCalls(page);
        });
    
        // When the Voice tab is shown, fetchCalls initially
        // Assuming you show Voice logs by clicking the showVoiceBtn
        $('#showVoiceBtn').on('click', function() {
            fetchCalls();
        });
    })(jQuery);
</script>
