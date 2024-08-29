jQuery(document).ready(function($) {
    $('#multi-step-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: myAjax.ajaxurl,
            type: 'POST',
            security:myAjax.nonce,
            data: {
            action: 'submit_form', // Action parameter as a separate key-value pair
            formData: formData // Include your form data as an object
        },
            //dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#show-message').html('<p>' + response.data.message + '</p>');
                    console.log(response.data.form_data); // For debugging
                } else {
                    $('#show-message').html('<p>Error: ' + response.data.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#show-message').html('<p>An error occurred: ' + error + '</p>');
                console.log('AJAX error:', error); // For debugging
            }
        });
    });
});


