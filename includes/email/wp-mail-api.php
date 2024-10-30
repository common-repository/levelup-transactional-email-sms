<?php

safeRequireFile('/includes/email/lvl-filter.php');

/**
 * lvltes_api_last_error is a compound getter/setter for the last error that was
 * encountered during a Mailgun API call.
 *
 * @param string  $error  OPTIONAL
 *
 * @return  string  Last error that occurred.
 *
 * @since 1.5.0
 */
function lvltes_api_last_error($error = null)
{
    static $last_error;

    if (null === $error) {
        return $last_error;
    } else {
        $tmp = $last_error;
        $last_error = $error;

        return $tmp;
    }
}

/*
 * Wordpress filter to mutate a `To` header to use recipient variables.
 * Uses the `lvltes_use_recipient_vars_syntax` filter to apply the actual
 * change. Otherwise, just a list of `To` addresses will be returned.
 *
 * @param string|array $to_addrs Array or comma-separated list of email addresses to mutate.
 *
 * @return array Array containing list of `To` addresses and recipient vars array
 *
 * @since 1.5.7
 */
add_filter('lvltes_mutate_to_rcpt_vars', 'lvltes_mutate_to_rcpt_vars_cb');
function lvltes_mutate_to_rcpt_vars_cb($to_addrs)
{
    if (is_string($to_addrs)) {
        $to_addrs = explode(',', $to_addrs);
    }

    if (has_filter('lvltes_use_recipient_vars_syntax')) {
        $use_rcpt_vars = apply_filters('lvltes_use_recipient_vars_syntax', null);
        if ($use_rcpt_vars) {
            $vars = array();

            $idx = 0;
            foreach ($to_addrs as $addr) {
                $rcpt_vars[$addr] = array('batch_msg_id' => $idx);
                $idx++;
            }

            // TODO: Also add folding to prevent hitting the 998 char limit on headers.
            return array(
                'to'        => '%recipient%',
                'rcpt_vars' => json_encode($rcpt_vars),
            );
        }
    }

    return array(
        'to'        => $to_addrs,
        'rcpt_vars' => null,
    );
}

/**
 * wp_mail function to be loaded in to override the core wp_mail function
 * from wp-includes/pluggable.php.
 *
 * @param string|array  $to       Array or comma-separated list of email addresses to send message.
 * @param string      $subject    Email subject
 * @param string      $message    Message contents
 * @param string|array  $headers    Optional. Additional headers.
 * @param string|array  $attachments  Optional. Files to attach.
 *
 * @return  bool  Whether the email contents were sent successfully.
 *
 * @since 0.1
 */
function wp_mail($to, $subject, $message, $headers = '', $attachments = array())
{
    // Compact the input, apply the filters, and extract them back out
    extract(apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments')));


    $lvltes = get_option('lvltes');
    $apiKey = $lvltes['apiKey'];
    $accountId = $lvltes['accountId'];
    $emailDomain = $lvltes['emailDomain'];

    if (empty($apiKey) || empty($accountId) || empty($emailDomain)) {
        return false;
    }

    if (!is_array($attachments)) {
        $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
    }

    // Headers
    if (empty($headers)) {
        $headers = array();
    } else {
        if (!is_array($headers)) {
            // Explode the headers out, so this function can take both
            // string headers and an array of headers.
            $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
        } else {
            $tempheaders = $headers;
        }
        $headers = array();
        $cc = array();
        $bcc = array();

        // If it's actually got contents
        if (!empty($tempheaders)) {
            // Iterate through the raw headers
            foreach ((array) $tempheaders as $header) {
                if (strpos($header, ':') === false) {
                    if (false !== stripos($header, 'boundary=')) {
                        $parts = preg_split('/boundary=/i', trim($header));
                        $boundary = trim(str_replace(array("'", '"'), '', $parts[1]));
                    }
                    continue;
                }
                // Explode them out
                list($name, $content) = explode(':', trim($header), 2);

                // Cleanup crew
                $name = trim($name);
                $content = trim($content);

                switch (strtolower($name)) {
                    // Mainly for legacy -- process a From: header if it's there
                case 'from':
                    if (strpos($content, '<') !== false) {
                        // So... making my life hard again?
                        $from_name = substr($content, 0, strpos($content, '<') - 1);
                        $from_name = str_replace('"', '', $from_name);
                        $from_name = trim($from_name);

                        $from_email = substr($content, strpos($content, '<') + 1);
                        $from_email = str_replace('>', '', $from_email);
                        $from_email = trim($from_email);
                    } else {
                        $from_email = trim($content);
                    }
                    break;
                case 'content-type':
                    if (strpos($content, ';') !== false) {
                        list($type, $charset) = explode(';', $content);
                        $content_type = trim($type);
                        if (false !== stripos($charset, 'charset=')) {
                            $charset = trim(str_replace(array('charset=', '"'), '', $charset));
                        } elseif (false !== stripos($charset, 'boundary=')) {
                            $boundary = trim(str_replace(array('BOUNDARY=', 'boundary=', '"'), '', $charset));
                            $charset = '';
                        }
                    } else {
                        $content_type = trim($content);
                    }
                    break;
                case 'cc':
                    $cc = array_merge((array) $cc, explode(',', $content));
                    break;
                case 'bcc':
                    $bcc = array_merge((array) $bcc, explode(',', $content));
                    break;
                default:
                    // Add it to our grand headers array
                    $headers[trim($name)] = trim($content);
                    break;
                }
            }
        }
    }

    if (!isset($from_name)) {
        $from_name = null;
    }

    if (!isset($from_email)) {
        $from_email = null;
    }

    $from_name = lvltes_detect_from_name($from_name);
    $from_email = lvltes_detect_from_address($from_email);

    $body = array(
        'from'    => "{$from_name} <{$from_email}>",
        'to'      => $to,
        'subject' => $subject,
    );


    $rcpt_data = apply_filters('lvltes_mutate_to_rcpt_vars', $to);
    if (!is_null($rcpt_data['rcpt_vars'])) {
        $body['recipient-variables'] = $rcpt_data['rcpt_vars'];
    }


    if (!empty($cc) && is_array($cc)) {
        $body['cc'] = implode(', ', $cc);
    }

    if (!empty($bcc) && is_array($bcc)) {
        $body['bcc'] = implode(', ', $bcc);
    }

    // If we are not given a Content-Type in the supplied headers,
    // write the message body to a file and try to determine the mimetype
    // using get_mime_content_type.
    if (!isset($content_type)) {
        $tmppath = tempnam(sys_get_temp_dir(), 'mg');
        $tmp = fopen($tmppath, 'w+');

        fwrite($tmp, $message);
        fclose($tmp);

        $content_type = get_mime_content_type($tmppath, 'text/plain');

        unlink($tmppath);
    }

    // Allow external content type filter to function normally
    if (has_filter('wp_mail_content_type')) {
        $content_type = apply_filters(
            'wp_mail_content_type',
            $content_type
        );
    }

    if ('text/plain' === $content_type) {
        $body['text'] = $message;
    } else if ('text/html' === $content_type) {
        $body['html'] = $message;
    } else {
        // Unknown Content-Type??
        error_log('[mailgun] Got unknown Content-Type: ' . $content_type);
        $body['text'] = $message;
        $body['html'] = $message;
    }

    // If we don't have a charset from the input headers
    if (!isset($charset)) {
        $charset = get_bloginfo('charset');
    }

    // Set the content-type and charset
    $charset = apply_filters('wp_mail_charset', $charset);
    if (isset($headers['Content-Type'])) {
        if (!strstr($headers['Content-Type'], 'charset')) {
            $headers['Content-Type'] = rtrim($headers['Content-Type'], '; ')."; charset={$charset}";
        }
    }

    // Set custom headers
    if (!empty($headers)) {
        foreach ((array) $headers as $name => $content) {
            $body["h:{$name}"] = $content;
        }

        // TODO: Can we handle this?
        //if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
        //  $phpmailer->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
    }

    /*
     * Deconstruct post array and create POST payload.
     * This entire routine is because wp_remote_post does
     * not support files directly.
     */

    $payload = '';

    // First, generate a boundary for the multipart message.
    $boundary = base_convert(uniqid('boundary', true), 10, 36);

    // Allow other plugins to apply body changes before creating the payload.
    $body = apply_filters('lvltes_mutate_message_body', $body);
    if ( ($body_payload = lvltes_build_payload_from_body($body, $boundary)) != null ) {
        $payload .= $body_payload;
    }

    // TODO: Special handling for multipart/alternative mail
    // if ('multipart/alternative' === $content_type) {
    //     // Build payload from mime
    //     // error_log(sprintf('building message payload from multipart/alternative'));
    //     // error_log($body['message']);
    //     // error_log('Attachments:');
    //     // foreach ($attachments as $attachment) {
    //     //     error_log($attachment);
    //     // }
    // }

    // Allow other plugins to apply attachment changes before writing to the payload.
    $attachments = apply_filters('lvltes_mutate_attachments', $attachments);
    if ( ($attachment_payload = lvltes_build_attachments_payload($attachments, $boundary)) != null ) {
        $payload .= $attachment_payload;
    }

    $payload .= '--'.$boundary.'--';

    $data = array(
        'body'    => $payload,
        'headers' => array(
            'Authorization' => 'Bearer ' . $apiKey,
            'AccountId' => $accountId,
            'Content-Type'  => 'multipart/form-data; boundary='.$boundary,
        ),
    );

    $url = 'https://levelupapis.no/v1/emails/' . $emailDomain;


    // TODO: Mailgun only supports 1000 recipients per request, since we are
    // overriding this function, let's add looping here to handle that
    $response = wp_remote_post($url, $data);
    if (is_wp_error($response)) {
        // Store WP error in last error.
        lvltes_api_last_error($response->get_error_message());

        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response));

    // Mailgun API should *always* return a `message` field, even when
    // $response_code != 200, so a lack of `message` indicates something
    // is broken.
    if ((int) $response_code != 200 && !isset($response_body->message)) {
        // Store response code and HTTP response message in last error.
        $response_message = wp_remote_retrieve_response_message($response);
        $errmsg = "$response_code - $response_message";
        lvltes_api_last_error($errmsg);

        return false;
    }

    // Not sure there is any additional checking that needs to be done here, but why not?
    if ($response_body->message != 'Queued. Thank you.') {
        lvltes_api_last_error($response_body->message);

        return false;
    }

    return true;
}

function lvltes_build_payload_from_body($body, $boundary) {
    $payload = '';

    // Iterate through pre-built params and build payload:
    foreach ($body as $key => $value) {
        if (is_array($value)) {
            $parent_key = $key;
            foreach ($value as $key => $value) {
                $payload .= '--'.$boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="'.$parent_key."\"\r\n\r\n";
                $payload .= $value;
                $payload .= "\r\n";
            }
        } else {
            $payload .= '--'.$boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="'.$key.'"'."\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }
    }

    return $payload;
}

function lvltes_build_payload_from_mime($body, $boundary) {
}

function lvltes_build_attachments_payload($attachments, $boundary) {
    $payload = '';

    // If we have attachments, add them to the payload.
    if (!empty($attachments)) {
        $i = 0;
        foreach ($attachments as $attachment) {
            if (!empty($attachment)) {
                $payload .= '--'.$boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="attachment['.$i.']"; filename="'.basename($attachment).'"'."\r\n\r\n";
                $payload .= file_get_contents($attachment);
                $payload .= "\r\n";
                $i++;
            }
        }
    } else {
        return null;
    }

    return $payload;
}
