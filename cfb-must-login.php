<?php
/**
 * Plugin Name: CFB Must Login
 * Plugin URI: https://github.com/codeforbroke/must-login
 * Description: Require users to log in before viewing your site with easy admin toggle controls
 * Version: 1.0.0
 * Author: Code For Broke, Inc.
 * Author URI: https://codeforbroke.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cfb-must-login
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('CFB_MUST_LOGIN_VERSION', '1.0.0');
define('CFB_MUST_LOGIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFB_MUST_LOGIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFB_MUST_LOGIN_PLUGIN_BASENAME', plugin_basename(__FILE__));

class CFB_Must_Login {
  
  // Status constants
  const STATUS_DISABLED = '0';
  const STATUS_ENABLED = '1';
  
  // Cache group
  const CACHE_GROUP = 'cfb_must_login';
  const CACHE_KEY = 'cfb_must_login_status';
  
  // Capability
  const CAPABILITY = 'cfb_must_login_manage';

  private $option_name = 'cfb_must_login_require_login';
  private $rest_api_option_name = 'cfb_must_login_protect_rest_api';
  private $is_enabled_cache = null;
  private $rest_api_enabled_cache = null;
  
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
    add_action('wp_ajax_cfb_must_login_toggle_status', array($this, 'ajax_toggle_status'));

    // Admin notices
    add_action('admin_notices', array($this, 'display_cache_warning'));
    add_action('wp_ajax_cfb_must_login_dismiss_cache_notice', array($this, 'dismiss_cache_notice'));

    // Enqueue assets
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    
    // Enforce login requirement
    add_action('template_redirect', array($this, 'enforce_login'));
    add_action('admin_init', array($this, 'enforce_admin_login'));

    // Enforce REST API authentication
    add_filter('rest_authentication_errors', array($this, 'enforce_rest_api_authentication'), 99);

    // Add settings link on plugins page
    add_filter('plugin_action_links_' . CFB_MUST_LOGIN_PLUGIN_BASENAME, array($this, 'add_settings_link'));

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

    // Set default REST API protection (only on first install)
    if (get_option($this->rest_api_option_name) === false) {
      add_option($this->rest_api_option_name, self::STATUS_ENABLED); // Default to protected
    }
    
    // Version check for migrations
    $installed_version = get_option('cfb_must_login_version', '0.0.0');
    
    if (version_compare($installed_version, CFB_MUST_LOGIN_VERSION, '<')) {
      // Run any necessary migrations here
      $this->run_migrations($installed_version);
      
      // Update version number
      update_option('cfb_must_login_version', CFB_MUST_LOGIN_VERSION);
      
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
    // WordPress automatically loads translations for WordPress.org hosted plugins since 4.6
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
   * Check if REST API protection is enabled
   */
  private function is_rest_api_protection_enabled() {
    // Check instance cache first
    if ($this->rest_api_enabled_cache !== null) {
      return $this->rest_api_enabled_cache;
    }

    // Get from database
    $value = get_option($this->rest_api_option_name, self::STATUS_ENABLED);

    $this->rest_api_enabled_cache = ($value === self::STATUS_ENABLED);
    return $this->rest_api_enabled_cache;
  }

  /**
   * Clear status cache
   */
  private function clear_status_cache() {
    wp_cache_delete(self::CACHE_KEY, self::CACHE_GROUP);
    $this->is_enabled_cache = null;

    // Also clear page caches
    $this->clear_page_caches();
  }

  /**
   * Clear page caches from popular caching plugins
   */
  private function clear_page_caches() {
    // WP Super Cache
    if (function_exists('wp_cache_clean_cache')) {
      global $file_prefix;
      wp_cache_clean_cache($file_prefix, true);
    }

    // W3 Total Cache
    if (function_exists('w3tc_flush_all')) {
      w3tc_flush_all();
    }

    // WP Rocket
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }

    // LiteSpeed Cache
    if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
      LiteSpeed_Cache_API::purge_all();
    }

    // WP Fastest Cache
    if (function_exists('wpfc_clear_all_cache')) {
      wpfc_clear_all_cache(true);
    }

    // Autoptimize
    if (class_exists('autoptimizeCache')) {
      autoptimizeCache::clearall();
    }

    // Cache Enabler
    if (class_exists('Cache_Enabler')) {
      Cache_Enabler::clear_complete_cache();
    }

    // Comet Cache
    if (class_exists('comet_cache') && method_exists('comet_cache', 'clear')) {
      comet_cache::clear();
    }

    // SG Optimizer (SiteGround)
    if (function_exists('sg_cachepress_purge_cache')) {
      sg_cachepress_purge_cache();
    }

    // WP Optimize
    if (class_exists('WP_Optimize') && method_exists('WP_Optimize', 'get_page_cache')) {
      $wp_optimize = WP_Optimize::instance();
      if ($cache = $wp_optimize->get_page_cache()) {
        $cache->purge();
      }
    }

    // Fire action for other caching plugins
    do_action('cfb_must_login_clear_cache');
  }
  
  /**
   * Add admin menu
   */
  public function add_admin_menu() {
    add_options_page(
      __('CFB Must Login', 'cfb-must-login'),
      __('CFB Must Login', 'cfb-must-login'),
      self::CAPABILITY,
      'cfb-must-login',
      array($this, 'settings_page')
    );
  }
  
  /**
   * Register settings
   */
  public function register_settings() {
    register_setting(
      'cfb_must_login_settings_group',
      $this->option_name,
      array(
        'type' => 'string',
        'sanitize_callback' => array($this, 'sanitize_setting'),
        'default' => self::STATUS_DISABLED
      )
    );

    register_setting(
      'cfb_must_login_settings_group',
      $this->rest_api_option_name,
      array(
        'type' => 'string',
        'sanitize_callback' => array($this, 'sanitize_rest_api_setting'),
        'default' => self::STATUS_ENABLED
      )
    );

    add_settings_section(
      'cfb_must_login_main_section',
      __('Access Control Settings', 'cfb-must-login'),
      array($this, 'settings_section_callback'),
      'cfb-must-login'
    );

    add_settings_field(
      'cfb_must_login_require_login_field',
      __('Require Login', 'cfb-must-login'),
      array($this, 'require_login_field_callback'),
      'cfb-must-login',
      'cfb_must_login_main_section'
    );

    add_settings_field(
      'cfb_must_login_protect_rest_api_field',
      __('Protect REST API', 'cfb-must-login'),
      array($this, 'protect_rest_api_field_callback'),
      'cfb-must-login',
      'cfb_must_login_main_section'
    );
  }
  
  /**
   * Sanitize setting value
   */
  public function sanitize_setting($value) {
    // Clear cache when setting changes
    $this->clear_status_cache();

    // Clear dismiss flag so notice shows again if re-enabled
    $user_id = get_current_user_id();
    delete_user_meta($user_id, 'cfb_must_login_cache_notice_dismissed');

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
    echo '<p>' . esc_html__('Control whether users must log in to view your site.', 'cfb-must-login') . '</p>';
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
      <?php esc_html_e('Enable login requirement for all site pages', 'cfb-must-login'); ?>
    </label>
    <p class="description">
      <?php esc_html_e('When enabled, visitors must log in to view any page on your site. Administrators can always access the site.', 'cfb-must-login'); ?>
    </p>
    <?php
  }

  /**
   * Protect REST API field callback
   */
  public function protect_rest_api_field_callback() {
    $value = get_option($this->rest_api_option_name, self::STATUS_ENABLED);
    ?>
    <label>
      <input type="checkbox"
           name="<?php echo esc_attr($this->rest_api_option_name); ?>"
           value="1"
           <?php checked($value, self::STATUS_ENABLED); ?>>
      <?php esc_html_e('Require authentication for REST API endpoints', 'cfb-must-login'); ?>
    </label>
    <p class="description">
      <?php esc_html_e('When enabled, REST API access requires authentication (when login requirement is active). Some endpoints like authentication and public forms are always allowed.', 'cfb-must-login'); ?>
    </p>
    <?php
  }

  /**
   * Sanitize REST API setting value
   */
  public function sanitize_rest_api_setting($value) {
    // Clear cache when setting changes
    $this->rest_api_enabled_cache = null;

    // Only accept exact string '1' or boolean true, everything else becomes '0'
    if ($value === self::STATUS_ENABLED || $value === 1 || $value === true) {
      return self::STATUS_ENABLED;
    }
    return self::STATUS_DISABLED;
  }
  
  /**
   * Settings page
   */
  public function settings_page() {
    if (!current_user_can(self::CAPABILITY)) {
      return;
    }
    
    settings_errors('cfb_must_login_messages');
    ?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      
      <div class="cfb-must-login-settings-container">
        <form action="options.php" method="post">
          <?php
          settings_fields('cfb_must_login_settings_group');
          do_settings_sections('cfb-must-login');
          submit_button(__('Save Settings', 'cfb-must-login'));
          ?>
        </form>
        
        <div class="cfb-must-login-info-box">
          <h2><?php esc_html_e('How It Works', 'cfb-must-login'); ?></h2>
          <ul>
            <li><?php esc_html_e('When enabled, all site pages require login to view', 'cfb-must-login'); ?></li>
            <li><?php esc_html_e('Administrators always have access', 'cfb-must-login'); ?></li>
            <li><?php esc_html_e('Non-logged-in users are redirected to the login page', 'cfb-must-login'); ?></li>
            <li><?php esc_html_e('Quick toggle available in the admin bar', 'cfb-must-login'); ?></li>
            <li><?php esc_html_e('REST API protection can be enabled/disabled separately', 'cfb-must-login'); ?></li>
            <li><?php esc_html_e('RSS feeds and XML-RPC remain accessible', 'cfb-must-login'); ?></li>
          </ul>

          <h3><?php esc_html_e('REST API Protection', 'cfb-must-login'); ?></h3>
          <p><?php esc_html_e('When REST API protection is enabled, most REST API endpoints require authentication. The following are always allowed:', 'cfb-must-login'); ?></p>
          <ul>
            <li><?php esc_html_e('Authentication endpoints (JWT, Simple JWT Login)', 'cfb-must-login'); ?></li>
            <li><?php esc_html_e('Contact form endpoints (Contact Form 7, WPForms, Gravity Forms)', 'cfb-must-login'); ?></li>
            <li><?php esc_html_e('oEmbed endpoints', 'cfb-must-login'); ?></li>
          </ul>
          <p><?php esc_html_e('Developers can use the "cfb_must_login_allowed_rest_routes" filter to allow additional endpoints.', 'cfb-must-login'); ?></p>

          <h3><?php esc_html_e('Quick Access', 'cfb-must-login'); ?></h3>
          <p><?php esc_html_e('Use the lock icon in the admin bar to toggle site-wide login protection.', 'cfb-must-login'); ?></p>
        </div>

        <div class="cfb-must-login-info-box cfb-must-login-version-info">
          <p><strong><?php esc_html_e('Version:', 'cfb-must-login'); ?></strong> <?php echo esc_html(CFB_MUST_LOGIN_VERSION); ?></p>
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
    $status_text = $is_enabled ? __('ON', 'cfb-must-login') : __('OFF', 'cfb-must-login');
    $status_class = $is_enabled ? 'cfb-must-login-status-on' : 'cfb-must-login-status-off';

    $admin_bar->add_node(array(
      'id' => 'cfb-must-login',
      'title' => '<span class="ab-icon dashicons dashicons-lock"></span>' .
             '<span class="ab-label">' . __('CFB Must Login: ', 'cfb-must-login') .
             '<span class="cfb-must-login-status ' . $status_class . '">' . $status_text . '</span></span>',
      'href' => '#',
      'meta' => array(
        'class' => 'cfb-must-login-admin-bar-item',
        'title' => __('Toggle login requirement', 'cfb-must-login')
      )
    ));

    $admin_bar->add_node(array(
      'id' => 'cfb-must-login-settings',
      'parent' => 'cfb-must-login',
      'title' => __('Settings', 'cfb-must-login'),
      'href' => admin_url('options-general.php?page=cfb-must-login')
    ));
  }
  
  /**
   * AJAX toggle status
   */
  public function ajax_toggle_status() {
    check_ajax_referer('cfb_must_login_toggle_nonce', 'nonce');

    if (!current_user_can(self::CAPABILITY)) {
      wp_send_json_error(array('message' => __('Unauthorized', 'cfb-must-login')));
    }

    $current_value = get_option($this->option_name, self::STATUS_DISABLED);
    $new_value = ($current_value === self::STATUS_ENABLED) ? self::STATUS_DISABLED : self::STATUS_ENABLED;

    update_option($this->option_name, $new_value);

    // Clear cache
    $this->clear_status_cache();

    // Clear dismiss flag so notice shows again if re-enabled
    $user_id = get_current_user_id();
    delete_user_meta($user_id, 'cfb_must_login_cache_notice_dismissed');

    wp_send_json_success(array(
      'enabled' => $new_value === self::STATUS_ENABLED,
      'message' => $new_value === self::STATUS_ENABLED
        ? __('Login requirement enabled', 'cfb-must-login')
        : __('Login requirement disabled', 'cfb-must-login')
    ));
  }
  
  /**
   * Enqueue admin assets
   */
  public function enqueue_admin_assets($hook) {
    // Load on settings page, when admin bar is showing, or when user can manage the plugin (for notices)
    $load_on_pages = array('settings_page_cfb-must-login');

    if (!in_array($hook, $load_on_pages, true) && !is_admin_bar_showing() && !current_user_can(self::CAPABILITY)) {
      return;
    }
    
    wp_enqueue_style(
      'cfb-must-login-admin-style',
      CFB_MUST_LOGIN_PLUGIN_URL . 'assets/css/admin-style.css',
      array(),
      CFB_MUST_LOGIN_VERSION
    );
    
    wp_enqueue_script(
      'cfb-must-login-admin-script',
      CFB_MUST_LOGIN_PLUGIN_URL . 'assets/js/admin-script.js',
      array('jquery'),
      CFB_MUST_LOGIN_VERSION,
      true
    );

    wp_localize_script('cfb-must-login-admin-script', 'cfbMustLoginData', array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('cfb_must_login_toggle_nonce'),
      'strings' => array(
        'error' => __('Error toggling status. Please try again.', 'cfb-must-login')
      )
    ));
  }
  
  /**
   * Enqueue frontend assets
   */
  public function enqueue_frontend_assets() {
    if (is_admin_bar_showing() && current_user_can(self::CAPABILITY)) {
      wp_enqueue_style(
        'cfb-must-login-admin-style',
        CFB_MUST_LOGIN_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        CFB_MUST_LOGIN_VERSION
      );
      
      wp_enqueue_script(
        'cfb-must-login-admin-script',
        CFB_MUST_LOGIN_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery'),
        CFB_MUST_LOGIN_VERSION,
        true
      );

      wp_localize_script('cfb-must-login-admin-script', 'cfbMustLoginData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cfb_must_login_toggle_nonce'),
        'strings' => array(
          'error' => __('Error toggling status. Please try again.', 'cfb-must-login')
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
    // Use wp_validate_redirect to ensure the URL is internal
    $redirect_to = wp_validate_redirect($redirect_to, home_url());
    $redirect_url = wp_login_url($redirect_to);

    // Apply filter to allow customization
    $redirect_url = apply_filters('cfb_must_login_redirect_url', $redirect_url, $redirect_to);

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
   * Enforce REST API authentication
   */
  public function enforce_rest_api_authentication($result) {
    // If already an error, return it
    if (is_wp_error($result)) {
      return $result;
    }

    // Skip if user is already logged in
    if (is_user_logged_in()) {
      return $result;
    }

    // Skip if login requirement is disabled
    if (!$this->is_login_required()) {
      return $result;
    }

    // Skip if REST API protection is disabled
    if (!$this->is_rest_api_protection_enabled()) {
      return $result;
    }

    // Get the current REST route
    $route = '';
    if (isset($GLOBALS['wp']->query_vars['rest_route'])) {
      $route = $GLOBALS['wp']->query_vars['rest_route'];
    } elseif (!empty($_SERVER['REQUEST_URI'])) {
      $route = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
      // Remove query string
      $route = strtok($route, '?');
      // Remove the rest_route prefix if present
      $route = preg_replace('#^/wp-json#', '', $route);
    }

    // Allow specific public endpoints
    $allowed_routes = apply_filters('cfb_must_login_allowed_rest_routes', array(
      // Authentication endpoints
      '/wp/v2/users/me',
      '/jwt-auth/v1/token',
      '/simple-jwt-login/v1/auth',
      '/wp/v2/users/register',

      // Contact Form 7
      '/contact-form-7/',

      // WPForms
      '/wpforms/',

      // Gravity Forms
      '/gf/',

      // oEmbed (for embedding content)
      '/oembed/',
    ));

    // Check if current route is allowed
    foreach ($allowed_routes as $allowed_route) {
      if (strpos($route, $allowed_route) !== false) {
        return $result;
      }
    }

    // Return authentication error
    return new WP_Error(
      'rest_authentication_required',
      __('Authentication required to access the REST API.', 'cfb-must-login'),
      array('status' => 401)
    );
  }

  /**
   * Add settings link on plugins page
   */
  public function add_settings_link($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=cfb-must-login')) . '">' . __('Settings', 'cfb-must-login') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  /**
   * Detect active caching plugins
   */
  private function get_active_caching_plugins() {
    $caching_plugins = array();

    // WP Super Cache
    if (function_exists('wp_cache_clean_cache')) {
      $caching_plugins[] = 'WP Super Cache';
    }

    // W3 Total Cache
    if (function_exists('w3tc_flush_all')) {
      $caching_plugins[] = 'W3 Total Cache';
    }

    // WP Rocket
    if (function_exists('rocket_clean_domain')) {
      $caching_plugins[] = 'WP Rocket';
    }

    // LiteSpeed Cache
    if (class_exists('LiteSpeed_Cache_API')) {
      $caching_plugins[] = 'LiteSpeed Cache';
    }

    // WP Fastest Cache
    if (function_exists('wpfc_clear_all_cache')) {
      $caching_plugins[] = 'WP Fastest Cache';
    }

    // Autoptimize
    if (class_exists('autoptimizeCache')) {
      $caching_plugins[] = 'Autoptimize';
    }

    // Cache Enabler
    if (class_exists('Cache_Enabler')) {
      $caching_plugins[] = 'Cache Enabler';
    }

    // Comet Cache
    if (class_exists('comet_cache')) {
      $caching_plugins[] = 'Comet Cache';
    }

    // SG Optimizer
    if (function_exists('sg_cachepress_purge_cache')) {
      $caching_plugins[] = 'SG Optimizer';
    }

    // WP Optimize
    if (class_exists('WP_Optimize')) {
      $caching_plugins[] = 'WP Optimize';
    }

    return $caching_plugins;
  }

  /**
   * Display cache warning notice
   */
  public function display_cache_warning() {
    // Only show to users who can manage the plugin
    if (!current_user_can(self::CAPABILITY)) {
      return;
    }

    // Only show if login requirement is enabled
    if (!$this->is_login_required()) {
      return;
    }

    // Check if user has dismissed the notice
    $user_id = get_current_user_id();
    if (get_user_meta($user_id, 'cfb_must_login_cache_notice_dismissed', true)) {
      return;
    }

    // Check for active caching plugins
    $caching_plugins = $this->get_active_caching_plugins();
    if (empty($caching_plugins)) {
      return;
    }

    $plugin_list = implode(', ', $caching_plugins);
    ?>
    <div class="notice notice-warning is-dismissible cfb-must-login-cache-notice" data-notice="cache-warning">
      <p>
        <strong><?php esc_html_e('CFB Must Login - Cache Notice:', 'cfb-must-login'); ?></strong>
        <?php
        printf(
          /* translators: %s: Comma-separated list of caching plugin names */
          esc_html__('Login requirement is enabled. We automatically cleared the cache for: %s. If you experience issues with users accessing the site without logging in, try manually clearing your cache.', 'cfb-must-login'),
          '<strong>' . esc_html($plugin_list) . '</strong>'
        );
        ?>
      </p>
    </div>
    <?php
  }

  /**
   * AJAX handler to dismiss cache notice
   */
  public function dismiss_cache_notice() {
    check_ajax_referer('cfb_must_login_toggle_nonce', 'nonce');

    if (!current_user_can(self::CAPABILITY)) {
      wp_send_json_error(array('message' => __('Unauthorized', 'cfb-must-login')));
    }

    $user_id = get_current_user_id();
    update_user_meta($user_id, 'cfb_must_login_cache_notice_dismissed', true);

    wp_send_json_success();
  }
}

// Initialize the plugin
new CFB_Must_Login();

/**
 * Uninstall hook - only runs when plugin is deleted (not deactivated)
 */
register_uninstall_hook(__FILE__, 'cfb_must_login_uninstall');

function cfb_must_login_uninstall() {
  // Delete all plugin data on uninstall:
  delete_option('cfb_must_login_require_login');
  delete_option('cfb_must_login_protect_rest_api');
  delete_option('cfb_must_login_version');
}