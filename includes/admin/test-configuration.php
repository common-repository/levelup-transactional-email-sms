<?php

add_action('admin_menu', 'render_test_sub_page');

function render_test_sub_page() {
  add_submenu_page( 'lvltes', 'Test configuration', 'Test configuration', 'manage_options', 'test', 'test_page');
}

add_action('wp_ajax_levelup-test-sms', 'ajax_send_test_sms');
function ajax_send_test_sms()
{
  nocache_headers();
  header('Content-Type: application/json');

  $response = wp_sms(filter_var($_POST['to'], FILTER_SANITIZE_NUMBER_INT), 'This is a test message from my WordPress site!');

  if ($response == 'OK') {
    die(json_encode(array('success' => 'true')));
  } else {
    die(json_encode(array('error' => 'true')));
  }
}

add_action('wp_ajax_levelup-test-email', 'ajax_send_test_email');
function ajax_send_test_email()
{
  nocache_headers();
  header('Content-Type: application/json');

  $response = wp_mail(filter_var($_POST['to'], FILTER_SANITIZE_EMAIL), 'Subject line for test email!', 'This is a test message from my WordPress site!');
  die($response);

}

function test_page() {
  ?>
    <div class="wrap">
    <h2><?php _e('Test configuration', 'lvltes'); ?></h2>

    <h3><?php _e("Connected Levelup Account", 'lvltes')?></h3>

    <?php 
      $billingAccount = lvltes_get_billing_account();

      if (!$billingAccount) {
        echo "No billing account connected";
      } else {
        $ba = json_decode($billingAccount);
        unset($ba->id);
        unset($ba->stripeCustomerId)
        ?>
          <table>
            <?php
              foreach ($ba as $key => $value) {
                ?>
                <tr>
                  <th align="left"><?php _e($key, 'lvltes')?>: </th>
                  <td><?php echo $value; ?></td>
                </tr>
                <?php
              }
            ?>
          </table>
        <?php
      } 
    ?>

    <hr />

    <h3><?php _e('Send test SMS', 'lvltes') ?></h3>

    <form id="levelup-sms-test-form">
      <table class="form-table">
        <tr valign="top">
          <th scope="row">
            <?php _e('Phone number', 'lvltes'); ?>
          </th>
          <td>
            <input type="text" class="regular-text"
                 name="phone"
                 id="sms-phone"
                 value=""
                 placeholder="4745454545"
            />
            <p class="description">
              <?php _e('Enter a valid phone number, including extension', 'lvltes'); ?>
            </p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">
            <?php _e('Message', 'lvltes'); ?>
          </th>
          <td>
            <input type="text" class="regular-text"
                 name="message"
                 id="sms-message"
                 readonly
                 value="This is a test message from my WordPress site!"
                 placeholder=""
            />
            <p class="description">
              <?php _e('Write a short and sweet message :)', 'lvltes'); ?>
            </p>
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" 
               class="button-primary" 
               value="<?php _e('Send a test sms', 'lvltes'); ?>"
               id="levelup-sms-test-button" />
      </p>
    </form>

    <script type="text/javascript">
      jQuery().ready(function () {

        jQuery('#levelup-sms-test-button').click(function (e) {
          console.log('Clicked!')
          e.preventDefault()

          jQuery(this).val('<?php _e('Testing...', 'mailgun'); ?>')

          jQuery.post(
            ajaxurl,
            {
              action: 'levelup-test-sms',
              _wpnonce: '<?php echo wp_create_nonce(); ?>',
              to: jQuery("#sms-phone").val(),
              message: jQuery("#sms-message").val()
            }
          )
            .complete(function () {
              jQuery('#levelup-sms-test').val('<?php _e('Test Configuration', 'lvltes'); ?>')
            })
            .success(function (data) {
              console.log('data', data)
              jQuery('#levelup-sms-test-button').val('<?php _e('Send a test sms', 'lvltes'); ?>')
              alert('Success!')
            })
            .error(function (error) {
              jQuery('#levelup-sms-test-button').val('<?php _e('Send a test sms', 'lvltes'); ?>')
              console.log('error', error)
              alert('Levelup Test <?php _e('Failure', 'lvltes'); ?>')
            })
        })
      })
  </script>

  <hr />

    <h3><?php _e('Send test Email', 'lvltes') ?></h3>

    <form id="levelup-email-test-form">
      <table class="form-table">

        <tr valign="top">
          <th scope="row">
            <?php _e('Email address', 'lvltes'); ?>
          </th>
          <td>
            <input type="email" class="regular-text"
                 name="email"
                 id="to-email"
                 value=""
                 placeholder="test@levelup.no"
            />
            <p class="description">
              <?php _e('Enter a valid email address', 'lvltes'); ?>
            </p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">
            <?php _e('Subject', 'lvltes'); ?>
          </th>
          <td>
            <input type="text" class="regular-text"
                 name="subject"
                 id="subject"
                 readonly
                 value="Subject line for test email!"
                 placeholder=""
            />
            <p class="description">
              <?php _e('Write a subject line', 'lvltes'); ?>
            </p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">
            <?php _e('Message', 'lvltes'); ?>
          </th>
          <td>
            <input type="text" class="regular-text"
                 name="message"
                 id="message"
                 readonly
                 value="This is a test message from my WordPress site!"
                 placeholder=""
            />
            <p class="description">
              <?php _e('Write a short and sweet message :)', 'lvltes'); ?>
            </p>
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" 
               class="button-primary" 
               value="<?php _e('Send a test email', 'lvltes'); ?>"
               id="levelup-email-test-button" />
      </p>
    </form>

    <script type="text/javascript">
      jQuery().ready(function () {

        jQuery('#levelup-email-test-button').click(function (e) {
          console.log('Clicked!')
          e.preventDefault()

          jQuery(this).val('<?php _e('Testing...', 'mailgun'); ?>')

          jQuery.post(
            ajaxurl,
            {
              action: 'levelup-test-email',
              _wpnonce: '<?php echo wp_create_nonce(); ?>',
              to: jQuery("#to-email").val(),
              email: jQuery("#email").val(),
              subject: jQuery("#subject").val(),
              message: jQuery("#message").val(),
            }
          )
            .complete(function () {
              jQuery('#levelup-email-test').val('<?php _e('Test Configuration', 'Levelup'); ?>')
            })
            .success(function (data) {
              console.log('data', data)
              jQuery('#levelup-sms-email-button').val('<?php _e('Send a test sms', 'lvltes'); ?>')
              alert('Success!')
            })
            .error(function (error) {
              jQuery('#levelup-sms-email-button').val('<?php _e('Send a test sms', 'lvltes'); ?>')
              console.log('error', error)
              alert('Levelup Test <?php _e('Failure', 'lvltes'); ?>')
            })
        })
      })
  </script>

  <?php
}

