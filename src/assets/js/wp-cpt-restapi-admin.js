/**
 * Admin JavaScript for the WP CPT REST API plugin
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Get the base segment input field
        const baseSegmentField = $('#cpt_rest_api_base_segment');
        
        // Get the REST API URL preview element
        const restApiPreview = $('#rest-api-preview');
        
        // Get the current site URL
        const siteUrl = window.location.origin;
        
        // Update the REST API URL preview when the user types
        baseSegmentField.on('input', function() {
            const baseSegment = $(this).val();
            const restApiUrl = siteUrl + '/wp-json/' + baseSegment + '/v1/';
            restApiPreview.text(restApiUrl);
        });
        
        // Validate the input field on form submission
        $('form').on('submit', function(e) {
            const baseSegment = baseSegmentField.val();
            
            // Check if the field is empty
            if (!baseSegment) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // Check if the field length is between 1 and 120 characters
            if (baseSegment.length < 1 || baseSegment.length > 120) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // Check if the field contains only allowed characters (lowercase letters, digits, and hyphens)
            if (!/^[a-z0-9-]+$/.test(baseSegment)) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // If all validations pass, remove the error class
            baseSegmentField.removeClass('error');
        });
        
        // Remove the error class when the user starts typing
        baseSegmentField.on('input', function() {
            $(this).removeClass('error');
        });
        // API Keys Management
        
        // Generate API Key
        $('.cpt-rest-api-generate-key').on('click', function() {
            const label = $('#cpt_rest_api_key_label').val();
            
            if (!label) {
                alert(cptRestApiAdmin.i18n.emptyLabel);
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpt_rest_api_add_key',
                    nonce: cptRestApiAdmin.nonce,
                    label: label
                },
                beforeSend: function() {
                    $('.cpt-rest-api-generate-key').prop('disabled', true).text(cptRestApiAdmin.i18n.generating);
                },
                success: function(response) {
                    if (response.success) {
                        // Display the new key
                        $('#cpt_rest_api_new_key').text(response.data.key.key);
                        $('.cpt-rest-api-key-generated').show();

                        // Auto-scroll to the generated key display
                        $('html, body').animate({
                            scrollTop: $('.cpt-rest-api-key-generated').offset().top - 100
                        }, 500);

                        // Clear the label field
                        $('#cpt_rest_api_key_label').val('');

                        // Note: Key remains visible until page is manually refreshed
                        // This gives users time to copy and store the key securely
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(cptRestApiAdmin.i18n.ajaxError);
                },
                complete: function() {
                    $('.cpt-rest-api-generate-key').prop('disabled', false).text(cptRestApiAdmin.i18n.generateKey);
                }
            });
        });
        
        // Copy API Key to Clipboard
        $('.cpt-rest-api-copy-key').on('click', function() {
            const $button = $(this);
            const keyText = $('#cpt_rest_api_new_key').text();

            // Create a temporary textarea element to copy from
            const textarea = document.createElement('textarea');
            textarea.value = keyText;
            document.body.appendChild(textarea);
            textarea.select();

            try {
                // Execute the copy command
                document.execCommand('copy');

                // Update button with success feedback
                $button.html('<span class="dashicons dashicons-yes"></span> ' + cptRestApiAdmin.i18n.copied);
                $button.addClass('button-success');

                // Reset the button after a delay
                setTimeout(() => {
                    $button.html('<span class="dashicons dashicons-clipboard"></span> ' + cptRestApiAdmin.i18n.copy);
                    $button.removeClass('button-success');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy text: ', err);
                alert(cptRestApiAdmin.i18n.copyFailed);
            }

            // Remove the temporary textarea
            document.body.removeChild(textarea);
        });
        
        // Delete API Key
        $('.cpt-rest-api-delete-key').on('click', function() {
            const keyId = $(this).data('id');
            const confirmMessage = $(this).data('confirm');
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpt_rest_api_delete_key',
                    nonce: cptRestApiAdmin.nonce,
                    id: keyId
                },
                beforeSend: function() {
                    // Disable the button
                    $('.cpt-rest-api-delete-key[data-id="' + keyId + '"]').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show the updated list
                        location.reload();
                    } else {
                        alert(response.data.message);
                        // Re-enable the button
                        $('.cpt-rest-api-delete-key[data-id="' + keyId + '"]').prop('disabled', false);
                    }
                },
                error: function() {
                    alert(cptRestApiAdmin.i18n.ajaxError);
                    // Re-enable the button
                    $('.cpt-rest-api-delete-key[data-id="' + keyId + '"]').prop('disabled', false);
                }
            });
        });

        // CPT Management
        
        // Reset All CPTs
        $('.cpt-rest-api-reset-cpts').on('click', function() {
            const confirmMessage = 'Are you sure you want to deactivate all Custom Post Types? This action will uncheck all toggle switches.';
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpt_rest_api_reset_cpts',
                    nonce: cptRestApiAdmin.nonce
                },
                beforeSend: function() {
                    $('.cpt-rest-api-reset-cpts').prop('disabled', true).text('Resetting...');
                },
                success: function(response) {
                    if (response.success) {
                        // Uncheck all CPT toggles
                        $('.cpt-rest-api-toggle-switch input[type="checkbox"]').prop('checked', false);
                        
                        // Show success message
                        alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(cptRestApiAdmin.i18n.ajaxError);
                },
                complete: function() {
                    $('.cpt-rest-api-reset-cpts').prop('disabled', false).text('Reset All');
                }
            });
        });

        // Toggle switch accessibility - handle keyboard navigation
        $('.cpt-rest-api-toggle-switch input[type="checkbox"]').on('keydown', function(e) {
            // Allow space bar to toggle the checkbox
            if (e.which === 32) { // Space bar
                e.preventDefault();
                $(this).prop('checked', !$(this).prop('checked')).trigger('change');
            }
        });

        // Toggle switch visual feedback
        $('.cpt-rest-api-toggle-switch input[type="checkbox"]').on('focus', function() {
            $(this).next('.cpt-rest-api-toggle-slider').addClass('focused');
        }).on('blur', function() {
            $(this).next('.cpt-rest-api-toggle-slider').removeClass('focused');
        });
    });
})(jQuery);