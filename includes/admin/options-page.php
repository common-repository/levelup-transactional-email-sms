<?php

?>

<div class="wrap">
  <h2><?php _e('Levelup', 'lvltes'); ?></h2>

  <p>
    <?php
      $url = 'https://drift.levelup.no';
      $link = sprintf(
        wp_kses(
          __('You need a <a href="%1$s" target="%2$s">Levelup</a> account to use this plugin and the Levelup service.', 'lvltes'),
          array('a' => array(
              'href' => array(),
              'target' => array()
            )
          )
        ), esc_url($url), '_blank'
      );
      echo $link;
    ?>
  </p>

  <p>
    <?php
      $url = 'https://console.levelup.no';
      $link = sprintf(
        wp_kses(
          __('If you need to register for an account, you can do so at <a href="%1$s" target="%2$s">console.levelup.no</a>.', 'lvltes'),
          array('a' => array(
              'href' => array(),
              'target' => array()
            )
          )
        ), esc_url($url), '_blank'
      );
      echo $link;
    ?>
  </p>

  <h3><?php _e('Configuration', 'lvltes'); ?></h3>
  <form id="levelup-form" action="options.php" method="post">
    <?php settings_fields('lvltes'); ?>

    <table class="form-table">
      <tr valign="top">
        <th scope="row">
          <?php _e('Account Id', 'lvltes'); ?>
        </th>
        <td>
          <input type="text" class="regular-text"
               name="lvltes[accountId]"
               value="<?php esc_attr_e($this->get_option('accountId')); ?>"
               placeholder="cd227524-d2b2-4b58-91b2-c288de7880a1"
          />
          <p class="description">
            <?php _e('Your Levelup accountId.', 'lvltes'); ?>
          </p>
        </td>
      </tr>
      <tr valign="top" class="levelup-api">
        <th scope="row">
          <?php _e('API Key', 'Levelup'); ?>
        </th>
        <td>
          <input type="password" class="regular-text" name="lvltes[apiKey]"
               value="<?php esc_attr_e($this->get_option('apiKey')); ?>"
               placeholder="lvltes_sdfoij123idufh73h4iu23h7f"
          />
          <p class="description">
            <?php
              _e('Your Levelup API key.', 'lvltes');
            ?>
          </p>
        </td>
      </tr>
    </table>

    <h3><?php _e('SMS Settings', 'lvltes'); ?></h3>

    <table class="form-table">
      <tr valign="top">
        <th scope="row">
          <?php _e('From name', 'lvltes'); ?>
        </th>
        <td>
          <input type="text" class="regular-text"
               name="lvltes[smsFrom]"
               value="<?php esc_attr_e($this->get_option('smsFrom')); ?>"
               placeholder="Levelup"
          />
          <p class="description">
            <?php _e('The SMS From name you have been granted.', 'lvltes'); ?>
          </p>
        </td>
      </tr>
    </table>

    <h3><?php _e('Email settings', 'lvltes'); ?></h3>

    <table class="form-table">
      <tr valign="top">
        <th scope="row">
          <?php _e('Domain', 'lvltes'); ?>
        </th>
        <td>
          <input type="text" class="regular-text"
               name="lvltes[emailDomain]"
               value="<?php esc_attr_e($this->get_option('emailDomain')); ?>"
               placeholder="levelup.no"
          />
          <p class="description">
            <?php _e('The verified domain you are allowed to send email from.', 'lvltes'); ?>
          </p>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">
          <?php _e('From Name', 'lvltes'); ?>
        </th>
        <td>
          <input type="text" class="regular-text"
               name="lvltes[emailFromName]"
               value="<?php esc_attr_e($this->get_option('emailFromName')); ?>"
               placeholder="WordPress"
          />
          <p class="description">
            <?php _e('Enter name the email should be sent from', 'lvltes'); ?>
          </p>
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">
          <?php _e('From Eamil', 'lvltes'); ?>
        </th>
        <td>
          <input type="email" class="regular-text"
               name="lvltes[emailFromEmail]"
               value="<?php esc_attr_e($this->get_option('emailFromEmail')); ?>"
               placeholder="test@levelup.no"
          />
          <p class="description">
            <?php _e('Enter name email that should be shown as the sender', 'lvltes'); ?>
          </p>
        </td>
      </tr>
    </table>

    <p class="submit">
      <input type="submit"
           class="button-primary"
           value="<?php _e('Save Changes', 'lvltes'); ?>"
      />
    </p>
  </form>

</div>
