<?php
/**
 * Plugin Name: Must Login
 * Plugin URI: https://github.com/codeforbroke/must-login
 * Description: Require users to log in before viewing your site with easy admin toggle controls
 * Author: Code For Broke, Inc.
 * Author URI: https://codeforbroke.com
 * Text Domain: must-login
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html 
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('MUST_LOGIN_VERSION', '1.0.0');
define('MUST_LOGIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MUST_LOGIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MUST_LOGIN_PLUGIN_BASENAME', plugin_basename(__FILE__));

class MustLogin {
  
  // Status constants
  const STATUS_DISABLED = '0';
  const STATUS_ENABLED = '1';
  
  // Cache group
  const CACHE_GROUP = 'must_login';
  const CACHE_KEY = 'must_login_status';
  
  // Capability
  const CAPABILITY = 'must_login_manage';
  
  private $option_name = 'must_login_require_login';
  private $is_enabled_cache = null;
  
  /**
   * Constructor
   */
  public function __construct() {
    // Activation/Deactivation hooks
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    
    // Initialize plugin
    add_action('init', array($this, 'init'));
    
    // Admin menu and settings
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
    
    // Admin bar
    add_action('admin_bar_menu', array($this, 'add_admin_bar_item'), 100);
    add_action('wp_ajax_must_login_toggle_status', array($this, 'ajax_toggle_status'));
    
    // Enqueue assets
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    
    // Enforce login requirement
    add_action('template_redirect', array($this, 'enforce_login'));
    add_action('admin_init', array($this, 'enforce_admin_login'));
    
    // Add settings link on plugins page
    add_filter('plugin_action_links_' . MUST_LOGIN_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    
    // Custom capability mapping
    add_filter('map_meta_cap', array($this, 'map_meta_cap'), 10, 4);
  }
  
  /**
   * Plugin activation
   */
  public function activate() {
    // Set default option (only on first install)
    if (get_option($this->option_name) === false) {
      add_option($this->option_name, self::STATUS_DISABLED);
    }
    
    // Version check for migrations
    $installed_version = get_option('must_login_version', '0.0.0');
    
    if (version_compare($installed_version, MUST_LOGIN_VERSION, '<')) {
      // Run any necessary migrations here
      $this->run_migrations($installed_version);
      
      // Update version number
      update_option('must_login_version', MUST_LOGIN_VERSION);
      
      // Clear caches
      wp_cache_flush();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
  }
  
  /**
   * Run database migrations between versions
   */
  private function run_migrations($from_version) {
    // Migrations logged here if needed in future versions
  }
  
  /**
   * Plugin deactivation
   */
  public function deactivate() {
    // Clear caches
    $this->clear_status_cache();
    
    // Flush rewrite rules
    flush_rewrite_rules();
  }
  
  /**
   * Initialize plugin
   */
  public function init() {
    // Load translations, future version
  }
  
  /**
   * Map custom capability
   */
  public function map_meta_cap($caps, $cap, $user_id, $args) {
    if (self::CAPABILITY === $cap) {
      $caps = array('manage_options');
    }
    return $caps;
  }
  
  /**
   * Check if login is required (with caching)
   */
  private function is_login_required() {
    // Check instance cache first
    if ($this->is_enabled_cache !== null) {
      return $this->is_enabled_cache;
    }
    
    // Check object cache
    $cached = wp_cache_get(self::CACHE_KEY, self::CACHE_GROUP);
    if ($cached !== false) {
      $this->is_enabled_cache = ($cached === self::STATUS_ENABLED);
      return $this->is_enabled_cache;
    }
    
    // Get from database
    $value = get_option($this->option_name, self::STATUS_DISABLED);
    
    // Store in cache
    wp_cache_set(self::CACHE_KEY, $value, self::CACHE_GROUP, 3600);
    
    $this->is_enabled_cache = ($value === self::STATUS_ENABLED);
    return $this->is_enabled_cache;
  }
  
  /**
   * Clear status cache
   */
  private function clear_status_cache() {
    wp_cache_delete(self::CACHE_KEY, self::CACHE_GROUP);
    $this->is_enabled_cache = null;
  }
  
  /**
   * Add admin menu
   */
  public function add_admin_menu() {
    add_options_page(
      __('Must Login', 'must-login'),
      __('Must Login', 'must-login'),
      self::CAPABILITY,
      'must-login',
      array($this, 'settings_page')
    );
  }
  
  /**
   * Register settings
   */
  public function register_settings() {
    register_setting(
      'must_login_settings_group',
      $this->option_name,
      array(
        'type' => 'string',
        'sanitize_callback' => array($this, 'sanitize_setting'),
        'default' => self::STATUS_DISABLED
      )
    );
    
    add_settings_section(
      'must_login_main_section',
      __('Access Control Settings', 'must-login'),
      array($this, 'settings_section_callback'),
      'must-login'
    );
    
    add_settings_field(
      'must_login_require_login_field',
      __('Require Login', 'must-login'),
      array($this, 'require_login_field_callback'),
      'must-login',
      'must_login_main_section'
    );
  }
  
  /**
   * Sanitize setting value
   */
  public function sanitize_setting($value) {
    // Clear cache when setting changes
    $this->clear_status_cache();
    
    // Only accept exact string '1' or boolean true, everything else becomes '0'
    if ($value === self::STATUS_ENABLED || $value === 1 || $value === true) {
      return self::STATUS_ENABLED;
    }
    return self::STATUS_DISABLED;
  }
  
  /**
   * Settings section description
   */
  public function settings_section_callback() {
    echo '<p>' . esc_html__('Control whether users must log in to view your site.', 'must-login') . '</p>';
  }
  
  /**
   * Require login field callback
   */
  public function require_login_field_callback() {
    $value = get_option($this->option_name, self::STATUS_DISABLED);
    ?>
    <label>
      <input type="checkbox" 
           name="<?php echo esc_attr($this->option_name); ?>" 
           value="1" 
           <?php checked($value, self::STATUS_ENABLED); ?>>
      <?php esc_html_e('Enable login requirement for all site pages', 'must-login'); ?>
    </label>
    <p class="description">
      <?php esc_html_e('When enabled, visitors must log in to view any page on your site. Administrators can always access the site.', 'must-login'); ?>
    </p>
    <?php
  }
  
  /**
   * Settings page
   */
  public function settings_page() {
    if (!current_user_can(self::CAPABILITY)) {
      return;
    }
    
    settings_errors('must_login_messages');
    ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      
      <div class="must-login-settings-container">
        <form action="options.php" method="post">
          <?php
          settings_fields('must_login_settings_group');
          do_settings_sections('must-login');
          submit_button(__('Save Settings', 'must-login'));
          ?>
        </form>
        
        <div class="must-login-info-box">
          <h2><?php esc_html_e('How It Works', 'must-login'); ?></h2>
          <ul>
            <li><?php esc_html_e('When enabled, all site pages require login to view', 'must-login'); ?></li>
            <li><?php esc_html_e('Administrators always have access', 'must-login'); ?></li>
            <li><?php esc_html_e('Non-logged-in users are redirected to the login page', 'must-login'); ?></li>
            <li><?php esc_html_e('Quick toggle available in the admin bar', 'must-login'); ?></li>
            <li><?php esc_html_e('RSS feeds and XML-RPC remain accessible', 'must-login'); ?></li>
          </ul>
          
          <h3><?php esc_html_e('Quick Access', 'must-login'); ?></h3>
          <p><?php esc_html_e('Use the lock icon in the admin bar to toggle site-wide login protection.', 'must-login'); ?></p>
        </div>
        
        <div class="must-login-info-box must-login-version-info">
          <p><strong><?php esc_html_e('Version:', 'must-login'); ?></strong> <?php echo esc_html(MUST_LOGIN_VERSION); ?></p>
        </div>
      </div>
    </div>
    <?php
  }
  
  /**
   * Add admin bar item
   */
  public function add_admin_bar_item($admin_bar) {
    // Check if user is logged in first (performance optimization)
    if (!is_user_logged_in() || !current_user_can(self::CAPABILITY)) {
      return;
    }
    
    $is_enabled = $this->is_login_required();
    $status_text = $is_enabled ? __('ON', 'must-login') : __('OFF', 'must-login');
    $status_class = $is_enabled ? 'must-login-status-on' : 'must-login-status-off';
    
    $admin_bar->add_node(array(
      'id' => 'must-login',
      'title' => '<span class="ab-icon dashicons dashicons-lock"></span>' .
             '<span class="ab-label">' . __('Must Login: ', 'must-login') . 
             '<span class="must-login-status ' . $status_class . '">' . $status_text . '</span></span>',
      'href' => '#',
      'meta' => array(
        'class' => 'must-login-admin-bar-item',
        'title' => __('Toggle login requirement', 'must-login')
      )
    ));
    
    $admin_bar->add_node(array(
      'id' => 'must-login-settings',
      'parent' => 'must-login',
      'title' => __('Settings', 'must-login'),
      'href' => admin_url('options-general.php?page=must-login')
    ));
  }
  
  /**
   * AJAX toggle status
   */
  public function ajax_toggle_status() {
    check_ajax_referer('must_login_toggle_nonce', 'nonce');
    
    if (!current_user_can(self::CAPABILITY)) {
      wp_send_json_error(array('message' => __('Unauthorized', 'must-login')));
    }
    
    $current_value = get_option($this->option_name, self::STATUS_DISABLED);
    $new_value = ($current_value === self::STATUS_ENABLED) ? self::STATUS_DISABLED : self::STATUS_ENABLED;
    
    update_option($this->option_name, $new_value);
    
    // Clear cache
    $this->clear_status_cache();
    
    wp_send_json_success(array(
      'enabled' => $new_value === self::STATUS_ENABLED,
      'message' => $new_value === self::STATUS_ENABLED 
        ? __('Login requirement enabled', 'must-login')
        : __('Login requirement disabled', 'must-login')
    ));
  }
  
  /**
   * Enqueue admin assets
   */
  public function enqueue_admin_assets($hook) {
    // Only load on settings page and when admin bar is showing
    $load_on_pages = array('settings_page_must-login');
    
    if (!in_array($hook, $load_on_pages, true) && !is_admin_bar_showing()) {
      return;
    }
    
    wp_enqueue_style(
      'must-login-admin-style',
      MUST_LOGIN_PLUGIN_URL . 'assets/css/admin-style.css',
      array(),
      MUST_LOGIN_VERSION
    );
    
    wp_enqueue_script(
      'must-login-admin-script',
      MUST_LOGIN_PLUGIN_URL . 'assets/js/admin-script.js',
      array('jquery'),
      MUST_LOGIN_VERSION,
      true
    );
    
    wp_localize_script('must-login-admin-script', 'mustLoginData', array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('must_login_toggle_nonce'),
      'strings' => array(
        'error' => __('Error toggling status. Please try again.', 'must-login')
      )
    ));
  }
  
  /**
   * Enqueue frontend assets
   */
  public function enqueue_frontend_assets() {
    if (is_admin_bar_showing() && current_user_can(self::CAPABILITY)) {
      wp_enqueue_style(
        'must-login-admin-style',
        MUST_LOGIN_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        MUST_LOGIN_VERSION
      );
      
      wp_enqueue_script(
        'must-login-admin-script',
        MUST_LOGIN_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery'),
        MUST_LOGIN_VERSION,
        true
      );
      
      wp_localize_script('must-login-admin-script', 'mustLoginData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('must_login_toggle_nonce'),
        'strings' => array(
          'error' => __('Error toggling status. Please try again.', 'must-login')
        )
      ));
    }
  }
  
  /**
   * Enforce login requirement on frontend
   */
  public function enforce_login() {
    // Skip if user is logged in
    if (is_user_logged_in()) {
      return;
    }
    
    // Skip if login requirement is disabled
    if (!$this->is_login_required()) {
      return;
    }
    
    // Skip for RSS feeds
    if (is_feed()) {
      return;
    }
    
    // Skip if on login page or related pages
    $allowed_pages = array('wp-login.php', 'wp-register.php');
    $current_page = isset($_SERVER['PHP_SELF']) ? basename(sanitize_text_field(wp_unslash($_SERVER['PHP_SELF']))) : '';
    
    if (in_array($current_page, $allowed_pages, true)) {
      return;
    }
    
    // Skip AJAX requests
    if (defined('DOING_AJAX') && DOING_AJAX) {
      return;
    }
    
    // Skip REST API requests
    if (defined('REST_REQUEST') && REST_REQUEST) {
      return;
    }
    
    // Skip cron requests
    if (defined('DOING_CRON') && DOING_CRON) {
      return;
    }
    
    // Skip XML-RPC requests
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
      return;
    }
    
    // Build safe redirect URL (prevent open redirect vulnerability)
    $request_uri = '';
    if (isset($_SERVER['REQUEST_URI'])) {
      $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
    }
    
    // Validate that redirect is to internal URL only
    $redirect_to = home_url($request_uri);
    $redirect_url = wp_login_url($redirect_to);
    
    // Apply filter to allow customization
    $redirect_url = apply_filters('must_login_redirect_url', $redirect_url, $redirect_to);
    
    wp_safe_redirect($redirect_url);
    exit;
  }
  
  /**
   * Enforce login requirement in wp-admin
   */
  public function enforce_admin_login() {
    // Skip if user is logged in
    if (is_user_logged_in()) {
      return;
    }
    
    // Skip if login requirement is disabled
    if (!$this->is_login_required()) {
      return;
    }
    
    // Allow AJAX requests (for login forms, etc.)
    global $pagenow;
    if ($pagenow === 'admin-ajax.php') {
      return;
    }
    
    // Redirect to login
    $redirect_url = wp_login_url(admin_url());
    wp_safe_redirect($redirect_url);
    exit;
  }
  
  /**
   * Add settings link on plugins page
   */
  public function add_settings_link($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=must-login')) . '">' . __('Settings', 'must-login') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }
}

// Initialize the plugin
new MustLogin();

/**
 * Uninstall hook - only runs when plugin is deleted (not deactivated)
 */
register_uninstall_hook(__FILE__, 'must_login_uninstall');

function must_login_uninstall() {
  // Delete all plugin data on uninstall:
  delete_option('must_login_require_login');
  delete_option('must_login_version');
}