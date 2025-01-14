<?php

if (!session_id()) {
    session_start();
}
// Fetch SID and Auth Token from session
$accountSid = isset($_SESSION['account_sid']) ? $_SESSION['account_sid'] : '';
$authToken = isset($_SESSION['auth_token']) ? $_SESSION['auth_token'] : '';

$siteUrl = "https://" . $_SERVER['HTTP_HOST'];
$logsUrl = $siteUrl . "/wp-content/plugins/Twilio-Gateway-Plugin/includes/webHook.php";
?>

<div class="tab-pane fade" id="settings" role="tabpanel">
    <h5>Settings</h5>
    <form id="twilioForm">
        <div class="mb-3">
            <label for="accountSid" class="form-label">Account SID</label>
            <input type="text" id="accountSid" name="account_sid" class="form-control" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" value="<?php echo htmlspecialchars($accountSid); ?>" required>
        </div>
        <div class="mb-3">
            <label for="authToken" class="form-label">Auth Token</label>
            <input type="text" id="authToken" name="auth_token" class="form-control" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" value="<?php echo htmlspecialchars($authToken); ?>" required>
        </div>
        <?php if (!empty($accountSid) && !empty($authToken)) : ?>
            <div class="alert alert-success" role="alert">
                <strong>Webhook URL:</strong> <code><?php echo $logsUrl; ?></code>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary w-100">Validate Credentials</button>
    </form>
    <div id="responseMessage" class="mt-3"></div>
</div>

<script>
    jQuery(document).ready(function($) {
        $.post(twilioAjax.ajaxUrl, {
                action: 'get_twilio_numbers',
                nonce: twilioAjax.nonce,
            })
            .done(function(response) {
                if (response.success) {} else {}
            })
            .fail(function(jqXHR, textStatus, errorThrown) {});
    });
</script>

<script>
    jQuery(document).ready(function($) {
        $('#twilioForm').submit(function(event) {
            event.preventDefault();

            var formDataArray = $(this).serializeArray();
            var formData = {};
            $.each(formDataArray, function(index, field) {
                formData[field.name] = field.value;
            });

            Swal.fire({
                title: 'Validating...',
                text: 'Please wait while we validate your credentials.',
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
                    action: 'validate_user_credentails',
                    nonce: twilioAjax.nonce,
                    ...formData
                },
                success: function(response) {
                    Swal.close();

                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Validated!',
                            text: `${response.message}`,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else if (response.status === 'error') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Credentials',
                            text: response.message || 'The credentials provided are incorrect. Please check and try again.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();

                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: `An error occurred: ${error}. Please try again later.`
                    });

                    console.error('AJAX error:', error);
                }
            });
        });
    });
</script>