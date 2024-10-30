<?php

class LVLTES
{
    public function __construct()
    {
        $this->options = get_option('lvltes');
        $this->plugin_file = __FILE__;
        $this->plugin_basename = plugin_basename($this->plugin_file);

        if ($this->get_option('emailDomain')) {
            if (!function_exists('wp_mail')) {
                safeRequireFile('/includes/email/wp-mail-api.php');
            }
        }
    }

    /**
     * Get specific option from the options table.
     *
     * @param    string $option  Name of option to be used as array key for retrieving the specific value
     * @param    array  $options Array to iterate over for specific values
     * @param    bool   $default False if no options are set
     *
     * @return    mixed
     *
     * @since    0.1
     */
    public function get_option($option, $options = null, $default = false)
    {
        if (is_null($options)):
            $options = &$this->options;
        endif;
        if (isset($options[ $option ])):
            return $options[ $option ];
        else:
            return $default;
        endif;
    }

    /**
     * Make a Levelup api call.
     *
     * @param    string $uri    The endpoint for the Levelup API
     * @param    array  $params Array of parameters passed to the API
     * @param    string $method The form request type
     *
     * @return    array
     *
     * @since    0.1
     */
    public function api_call($uri, $params = array(), $method = 'POST')
    {
        $options = get_option('lvltes');
        $apiKey = (defined('LVLTES_API_KEY') && LVLTES_API_KEY) ? LVLTES_API_KEY : $options[ 'apiKey' ];
        $accountId = (defined('LVLTES_ACCOUNT_ID') && LVLTES_ACCOUNT_ID) ? LVLTES_ACCOUNT_ID : $options[ 'accountId' ];

        $this->api_endpoint = 'https://levelupapis.no/v1/';

        $time = time();
        $url = $this->api_endpoint . $uri;
        $headers = array(
            'Authorization' => 'Bearer ' . $apiKey,
            'AccountId'=> $accountId
        );

        switch ($method) {
            case 'GET':
                $querystring = http_build_query($params);
                $url = $url . '?' . $querystring;
                $params = '';
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                break;
        }

        // make the request
        $args = array(
            'method' => $method,
            'body' => $params,
            'headers' => $headers,
            'sslverify' => true,
        );

        // make the remote request
        $result = wp_remote_request($url, $args);
        if (!is_wp_error($result)):
            return $result[ 'body' ];
        else:
            return $result->get_error_message();
        endif;
    }
}
