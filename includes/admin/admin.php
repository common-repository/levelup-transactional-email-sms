<?php


class LVLTESAdmin extends LVLTES
{
  /**
   * @var    array    Array of "safe" option defaults.
   */
  private $defaults;

  /**
   * Setup backend functionality in WordPress.
   *
   * @return    void
   *
   * @since    0.1
   */
  public function __construct()
  {
    LVLTES::__construct();

    // Activation hook
    register_activation_hook($this->plugin_file, array(&$this, 'init'));

    // Hook into admin_init and register settings and potentially register an admin_notice
    add_action('admin_init', array(&$this, 'admin_init'));

    // Activate the options page
    add_action('admin_menu', array(&$this, 'admin_menu'));
  }

  /**
   * Initialize the default options during plugin activation.
   *
   * @return    void
   *
   * @since    0.1
   */
  public function init()
  {
    $this->defaults = array(
      'apiKey' => '',
      'accountId' => '',
      'smsFrom' => '',
      'emailDomain' => '',
      'emailFromName' => '',
      'emailFromEmail' => '',
    );

    if (!$this->options) {
      $this->options = $this->defaults;
      add_option('lvltes', $this->options);
    }
  }

  /**
   * Add the options page.
   *
   * @return    void
   *
   * @since    0.1
   */
  public function admin_menu()
  {
    if (current_user_can('manage_options')):
      $this->hook_suffix = add_menu_page(__('Levelup', 'lvltes'), __('Levelup', 'lvltes'), 'manage_options', 'lvltes', array(&$this, 'options_page'));

      add_filter("plugin_action_links_{$this->plugin_basename}", array(&$this, 'filter_plugin_actions'));
    endif;
  }


  /**
   * Output the options page.
   *
   * @return    void
   *
   * @since    0.1
   */
  public function options_page()
  {
    if (!@include 'options-page.php'):
      printf(__('<div id="message" class="updated fade"><p>The options page for the <strong>Levelup</strong> plugin cannot be displayed. The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div>',
        'lvltes'), dirname(__FILE__) . '/options-page.php');
    endif;
  }


  /**
   * Wrapper function hooked into admin_init to register settings
   * and potentially register an admin notice if the plugin hasn't
   * been configured yet.
   *
   * @return    void
   *
   * @since    0.1
   */
  public function admin_init()
  {
    $this->register_settings();
    $apiKey = $this->get_option('apiKey');
    $accountId = $this->get_option('accountId');
    $smsFrom = $this->get_option('smsFrom');
    $emailDomain = $this->get_option('emailDomain');

    add_action('admin_notices', array(&$this, 'admin_notices'));
  }

  /**
   * Whitelist the levelup options.
   *
   * @return    void
   *
   * @since    0.1
   */
  public function register_settings()
  {
    register_setting('lvltes', 'lvltes', array(&$this, 'validation'));
  }

  /**
   * Data validation callback function for options.
   *
   * @param    array $options An array of options posted from the options page
   *
   * @return    array
   *
   * @since    0.1
   */
  public function validation($options)
  {
    $apiKey = trim($options[ 'apiKey' ]);
    $accountId = trim($options[ 'accountId' ]);
    if (!empty($apiKey)):
      $options[ 'apiKey' ] = $apiKey;
    endif;

    if (!empty($accountId)):
      $options[ 'username' ] = $username;
    endif;

    foreach ($options as $key => $value) {
      $options[ $key ] = trim($value);
    }

    $this->options = $options;

    return $options;
  }

  /**
   * Function to output an admin notice
   * when plugin settings or constants need to be configured
   *
   * @return    void
   *
   * @since    0.1
   */
  public function admin_notices()
  {
    $screen = get_current_screen();
    if (!current_user_can('manage_options') || $screen->id == $this->hook_suffix):
      return;
    endif;

    $apiKeyUndefined = ( !$this->get_option('apiKey') && ( !defined('LVLTES_API_KEY') || !LVLTES_API_KEY ));
    $accountIdUndefined = ( !$this->get_option('accountId') && ( !defined('LVLTES_ACCOUNT_ID') || !LVLTES_ACCOUNT_ID ));

    if ($apiKeyUndefined || $accountIdUndefined):
?>
      <div id='levelup-warning' class='notice notice-warning is-dismissible'>
        <p>
          <?php
            printf(
              __('You have to save you API key and accountId to use this plugin. You can do it in your wp-config.php file or <a href="%1$s">here</a>',
                'lvltes'),
              menu_page_url('lvltes', false)
            );
          ?>
        </p>
      </div>
<?php
  endif;
  }

  /**
   * Add a settings link to the plugin actions.
   *
   * @param    array $links Array of the plugin action links
   *
   * @return    array
   *
   * @since    0.1
   */
  public function filter_plugin_actions($links)
  {
    $settings_link = '<a href="' . menu_page_url('lvltes', false) . '">' . __('Settings', 'lvltes') . '</a>';
    array_unshift($links, $settings_link);

    return $links;
  }
}
