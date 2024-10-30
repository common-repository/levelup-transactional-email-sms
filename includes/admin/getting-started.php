<?php

add_action('admin_menu', 'render_getting_started_page');

function render_getting_started_page() {
  add_submenu_page( 'lvltes', 'Getting started', 'Getting started', 'manage_options', 'getting-started', 'getting_started_page');
}

function getting_started_page() {
  ?>
    <div class="wrap">
      <h2><?php _e('Getting started', 'lvltes'); ?></h2>
      <p>This page contains information about how to get started sending SMS and Email with this plugin.</p>
      <p>Before you start using this plugin you must enter your settings, and have the products set up in the Levelup console.</p>

      <hr />

      <h3><?php _e('Sending SMS', 'lvltes'); ?></h3>
      <p>This plugin exposes a new function called <code>wp_sms($to, $message)</code> that you can use in your code.</p>
      <p>Be sure to only trigger the function from a place where it's safe, so your users don't end up sending a ton of messages without you knowing</p>

      <h3>Example code:</h3>

      <pre>
          $response = wp_sms($to, $message);

          if ($response == 'OK') {
            echo "Success";
          } else {
            echo "Failed";
          }
      </pre>


      <hr />

      <h3><?php _e('Sending Email', 'lvltes'); ?></h3>
      <p>This plugin overwrites the built in <code>wp_mail($to, $subject, $message, $headers = '', $attachments = array())</code> function.</p>

      <p>This means that you can use it in the same way you would normally send emails with wordpress!</p>

      <p>It also means that the emails WordPress sends out of the box will also get processed and sent with this Plugin.</p>

      <h3>Example code:</h3>

      <pre>
          $response = wp_mail($to, $subject, $message, $headers = '', $attachments = array());
          die($response);
      </pre>
    </div>
  <?php
}
