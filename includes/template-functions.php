<?php

// Get a billing account
function lvltes_get_billing_account() {
    global $lvltes;

    return $lvltes->api_call('billing-account', array(), 'GET');
}


// send SMS
function wp_sms($to, $message) {
    global $lvltes;

    if (!$lvltes->options || !$lvltes->options['smsFrom']) {
      return json_encode(array('error' => 'Missing smsFrom'));
    }


    return $lvltes->api_call('sms', array('from' => $lvltes->options['smsFrom'], 'to' => $to, 'message' => $message), 'POST');
}
