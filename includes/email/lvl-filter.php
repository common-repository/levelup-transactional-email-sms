<?php


/**
 * Tries several methods to get the MIME Content-Type of a file.
 *
 * @param string  $filepath
 * @param string  $default_type If all methods fail, fallback to $default_type
 *
 * @return  string  Content-Type
 *
 * @since 1.5.4
 */
function get_mime_content_type($filepath, $default_type = 'text/plain')
{
    if (function_exists('mime_content_type')) {
        return mime_content_type($filepath);
    } elseif (function_exists('finfo_file')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $ret = finfo_file($fi, $filepath);
        finfo_close($fi);

        return $ret;
    } else {
        return $default_type;
    }
}

/**
 * Find the sending "From Name" with a similar process used in `wp_mail`.
 * This operates as a filter for the from name. If the override is set,
 * a given name will clobbered except in ONE case.
 * If the override is not enabled this is the from name resolution order:
 *  1. From name given by headers - {@param $from_name_header}
 *  2. From name set in Mailgun settings
 *  3. From `MAILGUN_FROM_NAME` constant
 *  4. From name constructed as `<your_site_title>` or "WordPress"
 *
 * If the `wp_mail_from` filter is available, it is applied to the resulting
 * `$from_addr` before being returned. The filtered result is null-tested
 * before being returned.
 *
 * @return  string
 *
 * @since 1.5.8
 */
function lvltes_detect_from_name($from_name_header = null)
{
    // Get options to avoid strict mode problems
    $lvltes_opts = get_option('lvltes');
    $lvltes_override_from = (isset($lvltes_opts['emailFromName'])) ? $lvltes_opts['emailFromName'] : null;

    $from_name = null;

    if ($lvltes_override_from) {
        $from_name = $lvltes_override_from;
    } elseif (!is_null($from_name_header)) {
        $from_name = $from_name_header;
    } else {
        if (is_null($lvltes_from_name) || empty($lvltes_from_name)) {
            if (function_exists('get_current_site')) {
                $from_name = get_current_site()->site_name;
            } else {
                $from_name = 'WordPress';
            }
        } else {
            $from_name = $lvltes_from_name;
        }
    }

    $filter_from_name = null;
    if (has_filter('wp_mail_from_name')) {
        $filter_from_name = apply_filters(
            'wp_mail_from_name',
            $from_name
        );
        if (!is_null($filter_from_name) && !empty($filter_from_name)) {
            $from_name = $filter_from_name;
        }
    }

    return $from_name;
}

/**
 * Find the sending "From Address" with a similar process used in `wp_mail`.
 * This operates as a filter for the from address. If the override is set,
 * a given address will except in ONE case.
 * If the override is not enabled this is the from address resolution order:
 *  1. From address given by headers - {@param $from_addr_header}
 *  2. From address set in Mailgun settings
 *  4. From address constructed as `wordpress@<your_site_domain>`
 *
 * If the `wp_mail_from` filter is available, it is applied to the resulting
 * `$from_addr` before being returned. The filtered result is null-tested
 * before being returned.
 *
 * If we don't have `From` input headers, use wordpress@$sitedomain
 * Some hosts will block outgoing mail from this address if it doesn't
 * exist but there's no easy alternative. Defaulting to admin_email
 * might appear to be another option but some hosts may refuse to
 * relay mail from an unknown domain.
 *
 * @link  http://trac.wordpress.org/ticket/5007.
 *
 * @return  string
 *
 * @since 1.5.8
 */
function lvltes_detect_from_address($from_addr_header = null)
{
    // Get options to avoid strict mode problems
    $lvltes_opts = get_option('lvltes');
    $lvltes_from_addr = (isset($lvltes_opts['emailFromEmail'])) ? $lvltes_opts['emailFromEmail'] : null;
    $from_addr = null;

    if ($lvltes_from_addr) {
        $from_addr = $lvltes_from_addr;
    } elseif (!is_null($from_addr_header)) {
        $from_addr = $from_addr_header;
    } else {
        if (is_null($lvltes_from_addr) || empty($lvltes_from_addr)) {
            if (function_exists('get_current_site')) {
                $sitedomain = get_current_site()->domain;
            } else {
                $sitedomain = strtolower($_SERVER['SERVER_NAME']);
                if (substr($sitedomain, 0, 4) === 'www.') {
                    $sitedomain = substr($sitedomain, 4);
                }
            }

            $from_addr = 'wordpress@'.$sitedomain;
        } else {
            $from_addr = $lvltes_from_addr;
        }
    }

    $filter_from_addr = null;
    if (has_filter('wp_mail_from')) {
        $filter_from_addr = apply_filters(
            'wp_mail_from',
            $from_addr
        );
        if (!is_null($filter_from_addr) || !empty($filter_from_addr)) {
            $from_addr = $filter_from_addr;
        }
    }

    return $from_addr;
}

/**
 * Parses mail headers into an array of arrays so they can be easily modified.
 * We have to deal with headers that may have boundaries or parts, so a single
 * header like:
 *
 *  From: Excited User <user@samples.mailgun.com>
 *
 * Will look like this in array format:
 *
 *  array(
 *      'from' => array(
 *          'value'    => 'Excited User <user@samples.mailgun.com>',
 *          'boundary' => null,
 *          'parts'    => null,
 *      )
 *  )
 *
 * @param string|array  $headers
 *
 * @return  array
 *
 * @since 1.5.8
 */
function lvltes_parse_headers($headers = array())
{
    if (empty($headers)) {
        return array();
    }

    $tmp = array();
    if (!is_array($headers)) {
        $tmp = explode("\n", str_replace("\r\n", "\n", $headers));
    } else {
        $tmp = $headers;
    }

    $new_headers = array();
    if (!empty($tmp)) {
        $name = null;
        $value = null;
        $boundary = null;
        $parts = null;

        foreach ((array) $tmp as $header) {
            // If this header does not contain a ':', is it a fold?
            if (false === strpos($header, ':')) {
                // Does this header have a boundary?
                if (false !== stripos($header, 'boundary=')) {
                    $parts = preg_split('/boundary=/i', trim($header));
                    $boundary = trim(str_replace(array('"', '\''), '', $parts[1]));
                }
                $value .= $header;

                continue;
            }

            // Explode the header
            list($name, $value) = explode(':', trim($header), 2);

            // Clean up the values
            $name = trim($name);
            $value = trim($value);

            if ( !isset($new_headers[$name]) ) {
                $new_headers[$name] = array();
            }

            array_push($new_headers[$name], array(
                'value'     => $value,
                'boundary'  => $boundary,
                'parts'     => $parts,
            ));
        }
    }

    return $new_headers;
}

/**
 * Takes a header array in the format produced by lvltes_parse_headers and
 * dumps them down in to a submittable header format.
 *
 * @param array $headers  Headers to dump
 *
 * @return  string  String of \r\n separated headers
 *
 * @since 1.5.8
 */
function lvltes_dump_headers($headers = null)
{
    if (is_null($headers) || !is_array($headers)) {
        return '';
    }

    $header_string = '';
    foreach ($headers as $name => $values) {
        $header_string .= sprintf("%s: ", $name);
        $header_values = array();

        foreach ($values as $content) {
            // XXX - Is it actually okay to discard `parts` and `boundary`?
            array_push($header_values, $content['value']);
        }

        $header_string .= sprintf("%s\r\n", implode(", ", $header_values));
    }

    return $header_string;
}

/**
 * Set the API endpoint based on the region selected.
 * Value can be "0" if not selected, "us" or "eu"
 *
 * @param string  $getRegion  Region value set either in config or Mailgun plugin settings.
 *
 * @return  bool|string
 *
 * @since 1.5.12
 */
function lvltes_api_get_region($getRegion)
{
    switch ($getRegion) {
        case 'us': return 'https://api.mailgun.net/v3/';
        case 'eu': return 'https://api.eu.mailgun.net/v3/';
        default: return false;
    }
}

/**
 * Set the SMTP endpoint based on the region selected.
 * Value can be "0" if not selected, "us" or "eu"
 *
 * @param string  $getRegion  Region value set either in config or Mailgun plugin settings.
 *
 * @return  bool|string
 *
 * @since 1.5.12
 */
function lvltes_smtp_get_region($getRegion)
{
    switch ($getRegion) {
        case 'us': return 'smtp.mailgun.org';
        case 'eu': return 'smtp.eu.mailgun.org'; 
        default: return false;
    }
}
