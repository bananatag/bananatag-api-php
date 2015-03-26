<?php
/**
 * Bananatag API PHP Library
 *
 * Requirements:
 *   1. PHP CURL library
 *   2. PHP >= 5
 *
 * How to use:
 *   1. create BtagApi instance using your AuthID and Access Key:
 *      $btag = new BtagApi('your AuthID', 'your Access Key');
 *   2. create your request params object -
 *      Example:
 *      $params = ['start'=>'2013-01-01', 'end'=>'2014-03-30', 'rtn'=>'json'];
 *   3. Send API request:
 *      $btag->send($endpoint, $params);
 *
 * Other commands:
 *    1. Generate request signature:
 *       $btag->generateSignature($params);
 *
 * @author Bananatag Systems <eric@bananatag.com>
 * @version 1.0.0
 **/

namespace Bananatag;

require_once "BTException.php";
class CurlException extends BTException{}
class RequestException extends BTException{}

/**
 * Class BtagApi
 */
class Api
{
    /**
     * @var $auth_id
     * @access private
     */
    private $auth_id;

    /**
     * @var string $access_key
     * @access private
     */
    private $access_key;

    /**
     * @var string $base_url
     * @access private
     */
    private $base_url = "https://api.bananatag.com/";

    /**
     * Specifies whether debugging is enabled
     *
     * @var bool $debug
     * @access private
     */
    private $debug = false;

    /**
     * cURL request timeout specified (seconds).
     *
     * @var integer $timeout
     * @access private
     */
    private $timeout = 500;

    /**
     * @var integer $ch
     * @access private
     */
    private $ch;

    /**
     * @param $id
     * @param $key
     * @param array $options
     * @throws CurlException
     * @throws RequestException
     */
    public function __construct($id=null, $key=null, $options = array()) {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new CurlException("The Bananatag-PHP API library requires cURL.", 400);
        }

        if (!$id || !$key) {
            throw new RequestException("You must provide both an authID and access key.", 401);
        }

        $this->auth_id	   = $id;
        $this->access_key  = $key;
        $this->ch          = curl_init();

        if (isset($options['debug'])) {
            $this->debug = true;
        }

        if (isset($options['timeout']) && is_int($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Bananatag-PHP/1.0.0');
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
    }

    /**
     * Close cURL on destruct
     */
    public function __destruct() {
        curl_close($this->ch);
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return \stdClass $result
     */
    public function send($endpoint, $params) {
        $this->checkData($params);

        $url    = $this->base_url . $endpoint;
        $sig    = $this->generateSignature($params);
        $method = $this->getMethod($endpoint);

        return $this->makeRequest($url, $method, $sig, $params);
    }

    /**
     * This method handles the cURL request using the provided method and parameters. It returns the response
     * as an object.
     *
     * @method makeRequest
     * @param string $url
     * @param string $method
     * @param string $sig
     * @param array $data
     * @throws CurlException
     * @throws RequestException
     * @return \stdClass $result
     */
    private function makeRequest($url, $method = 'GET', $sig, $data = array()) {
        // set url based on request type
        ($method === 'GET') ? ($url = $url . '?' . http_build_query($data)) : curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('authorization: ' . base64_encode($this->auth_id . ":" . $sig)));
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($this->debug) {
            $start = microtime(true);
            $this->log('Call to ' . $method . $url . ': ' . http_build_query($data));
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($this->ch, CURLOPT_STDERR, $curl_buffer);
        }

        $output = curl_exec($this->ch);
        $info = curl_getinfo($this->ch);

        if ($this->debug) {
            $time = microtime(true) - $start;
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
            $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
            $this->log('Got response: ' . $output);
        }

        if (curl_error($this->ch)) {
            throw new CurlException("API call to $url failed, " . curl_error($this->ch), 400);
        }

        $result = json_decode($output, true);

        // If the status code is 4XX or above, parse node error and throw new custom exception
        if (floor($info['http_code'] / 100) >= 4) {
            throw new RequestException($result["error"] . ", " . $result["message"], $result["statusCode"]);
        }

        return $result;
    }

    /**
     * This method can be used to sanitize data before it hits the API
     *
     * @method checkData
     * @param $data
     * @throws RequestException
     */
    private function checkData($data) {
        // check date strings are in correct format
        if (isset($data['start'])) {
            $this->validateDate($data['start']);
        }

        if (isset($data['end'])) {
            $this->validateDate($data['end']);
        }

        // Check if start date is less than end date.
        if (isset($data['start']) && isset($data['end'])) {
            if (strtotime($data['start']) > strtotime($data['end'])) {
                throw new RequestException("Error with provided parameters: Start date is greater than end date.", 400);
            }
        }

        // check if aggregateData is either true or false
        if (isset($data['aggregateData']) && $data['aggregateData'] != "true" && $data['aggregateData'] != "false") {
            throw new RequestException("Error with provided parameters: aggregateData must either be 'true' or 'false'.", 400);
        }

        return;
    }

    /**
     * This method can be used to validate data strings are in the format yyyy-mm-dd
     * @method validateDate
     * @param $date
     * @throws RequestException
     */
    private function validateDate($date) {
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date)) {
            return true;
        }
        else {
            throw new RequestException("Error with provided parameters: Date string must be in format yyyy-mm-dd.", 400);
        }
    }

    /**
     * @method log
     * @param $msg
     */
    private function log($msg) {
        if($this->debug) {
            error_log($msg);
        }
    }

    /**
     * @method generateSignature
     * @param array $params
     * @return string
     */
    private function generateSignature($params)
    {
        $signature = hash_hmac("sha1", urldecode(http_build_query($params)), $this->access_key);
        return $signature;
    }

    /**
     * This method takes an API endpoint and returns the HTTP request method.
     *
     * @method getMethod
     * @param string $endpoint
     * @return string
     */
    private function getMethod($endpoint) {
        switch($endpoint) {
            case "": return "PUT";
            default: return "GET";
        }
    }
}
