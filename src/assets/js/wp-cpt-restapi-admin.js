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
            
            // Check if the field is exactly 20 characters long
            if (baseSegment.length !== 20) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // Check if the field contains at least one lowercase letter
            if (!/[a-z]/.test(baseSegment)) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // Check if the field contains at least one uppercase letter
            if (!/[A-Z]/.test(baseSegment)) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // Check if the field contains at least one digit
            if (!/\d/.test(baseSegment)) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // Check if the field contains at least one underscore
            if (!/_/.test(baseSegment)) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // Check if the field contains at least one dash
            if (!/-/.test(baseSegment)) {
                e.preventDefault();
                baseSegmentField.addClass('error');
                return false;
            }
            
            // Check if the field contains only allowed characters
            if (!/^[a-zA-Z0-9_-]+$/.test(baseSegment)) {
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
    });
})(jQuery);