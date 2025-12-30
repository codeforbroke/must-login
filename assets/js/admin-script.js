(function($) {
    'use strict';
    
    /**
     * Initialize plugin
     */
    function init() {
        bindEvents();
        updateAdminBarClass();
    }
    
    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Toggle status on admin bar click
        $(document).on('click', '#wp-admin-bar-cfb-must-login > a', handleToggleClick);

        // Dismiss cache notice
        $(document).on('click', '.cfb-must-login-cache-notice .notice-dismiss', handleDismissCacheNotice);
    }
    
    /**
     * Update admin bar class based on status
     */
    function updateAdminBarClass() {
        var $adminBar = $('#wp-admin-bar-cfb-must-login');
        var $statusSpan = $('.cfb-must-login-status');

        if ($statusSpan.hasClass('cfb-must-login-status-on')) {
            $adminBar.addClass('cfb-must-login-active').removeClass('cfb-must-login-inactive');
        } else {
            $adminBar.addClass('cfb-must-login-inactive').removeClass('cfb-must-login-active');
        }
    }
    
    /**
     * Handle toggle click
     */
    function handleToggleClick(e) {
        e.preventDefault();

        // Don't toggle if clicking settings link
        if ($(this).closest('#wp-admin-bar-cfb-must-login-settings').length) {
            return;
        }

        toggleStatus();
    }
    
    /**
     * Toggle status via AJAX
     */
    function toggleStatus() {
        var $adminBar = $('#wp-admin-bar-cfb-must-login');

        $.ajax({
            url: cfbMustLoginData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cfb_must_login_toggle_status',
                nonce: cfbMustLoginData.nonce
            },
            beforeSend: function() {
                $adminBar.addClass('cfb-must-login-loading');
            },
            success: function(response) {
                if (response.success) {
                    updateStatusDisplay(response.data.enabled);
                }
            },
            error: function() {
                // Silently fail - user can see loading state ended
            },
            complete: function() {
                $adminBar.removeClass('cfb-must-login-loading');
            }
        });
    }
    
    /**
     * Update status display
     */
    function updateStatusDisplay(enabled) {
        var $statusSpan = $('.cfb-must-login-status');
        var $adminBar = $('#wp-admin-bar-cfb-must-login');

        // Update status
        if (enabled) {
            $statusSpan
                .text('ON')
                .removeClass('cfb-must-login-status-off')
                .addClass('cfb-must-login-status-on');
            $adminBar
                .addClass('cfb-must-login-active')
                .removeClass('cfb-must-login-inactive');
        } else {
            $statusSpan
                .text('OFF')
                .removeClass('cfb-must-login-status-on')
                .addClass('cfb-must-login-status-off');
            $adminBar
                .addClass('cfb-must-login-inactive')
                .removeClass('cfb-must-login-active');
        }
    }

    /**
     * Handle dismiss cache notice
     */
    function handleDismissCacheNotice(e) {
        var $notice = $(this).closest('.cfb-must-login-cache-notice');

        $.ajax({
            url: cfbMustLoginData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cfb_must_login_dismiss_cache_notice',
                nonce: cfbMustLoginData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $notice.fadeOut();
                }
            }
        });
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);