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
        $(document).on('click', '#wp-admin-bar-must-login > a', handleToggleClick);

        // Dismiss cache notice
        $(document).on('click', '.must-login-cache-notice .notice-dismiss', handleDismissCacheNotice);
    }
    
    /**
     * Update admin bar class based on status
     */
    function updateAdminBarClass() {
        var $adminBar = $('#wp-admin-bar-must-login');
        var $statusSpan = $('.must-login-status');
        
        if ($statusSpan.hasClass('must-login-status-on')) {
            $adminBar.addClass('must-login-active').removeClass('must-login-inactive');
        } else {
            $adminBar.addClass('must-login-inactive').removeClass('must-login-active');
        }
    }
    
    /**
     * Handle toggle click
     */
    function handleToggleClick(e) {
        e.preventDefault();
        
        // Don't toggle if clicking settings link
        if ($(this).closest('#wp-admin-bar-must-login-settings').length) {
            return;
        }
        
        toggleStatus();
    }
    
    /**
     * Toggle status via AJAX
     */
    function toggleStatus() {
        var $adminBar = $('#wp-admin-bar-must-login');
        
        $.ajax({
            url: mustLoginData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'must_login_toggle_status',
                nonce: mustLoginData.nonce
            },
            beforeSend: function() {
                $adminBar.addClass('must-login-loading');
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
                $adminBar.removeClass('must-login-loading');
            }
        });
    }
    
    /**
     * Update status display
     */
    function updateStatusDisplay(enabled) {
        var $statusSpan = $('.must-login-status');
        var $adminBar = $('#wp-admin-bar-must-login');

        // Update status
        if (enabled) {
            $statusSpan
                .text('ON')
                .removeClass('must-login-status-off')
                .addClass('must-login-status-on');
            $adminBar
                .addClass('must-login-active')
                .removeClass('must-login-inactive');
        } else {
            $statusSpan
                .text('OFF')
                .removeClass('must-login-status-on')
                .addClass('must-login-status-off');
            $adminBar
                .addClass('must-login-inactive')
                .removeClass('must-login-active');
        }
    }

    /**
     * Handle dismiss cache notice
     */
    function handleDismissCacheNotice(e) {
        var $notice = $(this).closest('.must-login-cache-notice');

        $.ajax({
            url: mustLoginData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'must_login_dismiss_cache_notice',
                nonce: mustLoginData.nonce
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