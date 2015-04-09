<?php
/**
 * Bananatag API PHP Library
 *
 * Requirements:
 *   1. PHP CURL library
 *   2. PHP >= 5.3
 *
 * @author Bananatag Systems <eric@bananatag.com>
 * @version 0.1.0
 * @license MIT
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
     * @var $authId
     * @access private
     */
    private $authId;

    /**
     * @var string $accessKey
     * @access private
     */
    private $accessKey;

    /**
     * @var string $baseUrl
     * @access private
     */
    private $baseUrl = "https://api.bananatag.com/";

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
     * @var integer $requests
     * @access private
     */
    private $requests = array();

    /**
     * @param $id
     * @param $key
     * @param array $options
     * @throws CurlException
     * @throws RequestException
     */
    public function __construct($id=null, $key=null, $options=array())
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new CurlException("The Bananatag-PHP API library requires cURL.", 400);
        }

        if (!$id || !$key) {
            throw new RequestException("You must provide both an authID and access key.", 401);
        }

        $this->authId	   = $id;
        $this->accessKey  = $key;
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
    public function __destruct()
    {
        curl_close($this->ch);
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return \stdClass $result
     */
    public function request($endpoint, $params)
    {
        $this->checkData($params);

        $post = $this->updateSession($endpoint, $params);

        if ($post) {
            $sig    = $this->generateSignature($post);
            $method = $this->getMethod($endpoint);

            return $this->makeRequest($endpoint, $method, $sig, $params, $post);
        } else {
            return false;
        }
    }

    /**
     * This method handles the cURL request using the provided method and parameters. It returns the response
     * as an object.
     *
     * @method makeRequest
     * @param string $endPoint
     * @param string $method
     * @param string $sig
     * @param array $params
     * @param $post
     * @throws CurlException
     * @throws RequestException
     * @return \stdClass $result
     */
    private function makeRequest($endPoint, $method = 'GET', $sig, $params = array(), $post)
    {
        $url = $this->baseUrl . $endPoint;

        // set url based on request type
        if ($method === 'GET') {
            $url = $url . '?' . http_build_query($post);
        } else {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('authorization: ' . base64_encode($this->authId . ":" . $sig)));
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($this->debug) {
            $start = microtime(true);
            $this->log('Call to ' . $method . $url . ': ' . http_build_query($post));
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

        if (isset($result->paging))
        {
            $this->updateSession($endPoint, $params, true);
        }

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
    private function checkData($data)
    {
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
     * @method updateSession
     * @param $endPoint
     * @param $params
     * @param bool $update
     * @return bool|array
     */
    private function updateSession($endPoint, $params, $update = false)
    {
        $session = md5($endPoint . http_build_query($params));

        if (!isset($params['rtn']))
        {
            $params['rtn'] = 'json';
        }

        if (!isset($this->requests[$session]))
        {
            $this->requests[$session] = array(
                'params' => $params,
                'next'   => 0,
                'prev'   => 0,
                'total'  => 1,
                'url'    => $this->baseUrl
            );
        } elseif ($update) {
            if (isset($update->cursors->total))
            {
                $this->requests[$session]['total'] = $update->cursors->total;
            }

            $this->requests[$session]['params'] = $params;
            $this->requests[$session]['next'] = $update->cursors->next;
            $this->requests[$session]['prev'] = $update->cursors->prev;

            return false;
        }

        // If the cursor is equal to
        if ($this->requests[$session]['next'] === $this->requests[$session]['total']) {
            return false;
        }

        // Manual override of cursor
        if (isset($params['page']))
        {
            if (is_numeric($params['page']))
            {
                $params['cursor'] = $params['page'] * 250;
            }

            unset($params['page']);

        }

        $params['cursor'] = $this->requests[$session]['next'];

        return $params;
    }

    /**
     * This method can be used to validate data strings are in the format yyyy-mm-dd
     * @method validateDate
     * @param $date
     * @return boolean
     * @throws RequestException
     */
    private function validateDate($date)
    {
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
    private function log($msg)
    {
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
        $signature = hash_hmac("sha1", urldecode(http_build_query($params)), $this->accessKey);
        return $signature;
    }

    /**
     * This method takes an API endpoint and returns the HTTP request method.
     *
     * @method getMethod
     * @param string $endpoint
     * @return string
     */
    private function getMethod($endpoint)
    {
        switch($endpoint) {
            case "": return "PUT";
            default: return "GET";
        }
    }

    public function buildMessage($params)
    {
        if (!isset($params['from']))
        {
            throw new RequestException('The From header must be specified', 400);
        }
        if (!isset($params['to']))
        {
            throw new RequestException('The To header must be specified', 400);
        }
        if (!isset($params['html']))
        {
            throw new RequestException('The HTML body must be specified', 400);
        }

        $message = Swift_Message::newInstance()
            ->setFrom($params['from'])
            ->setTo($params['to'])
            ->setBody($params['html'], 'text/html');

        if (isset($params['subject']))
        {
            $message->setSubject($params['subject']);
        }
        if (isset($params['cc']))
        {
            $message->setCc($params['cc']);
        }
        if (isset($params['text']))
        {
            $message->addPart($params['text'], 'text/plain');
        }
        if (isset($params['subject']))
        {
            $message->setSubject($params['subject']);
        }
        if (isset($params['headers']))
        {
            $headers = $message->getHeaders();
            foreach ($params['headers'] as $header)
            {
                $headers->addTextHeader($header['name'], $header['value']);
            }
        }

        if (isset($params['attachments']))
        {
            foreach ($params['attachments'] as $attachment)
            {
                if (!isset($attachment['filePath']))
                {
                    throw new RequestException('The attachment file path must be specified', 400);
                }
                $swift_attachment = Swift_Attachment::fromPath($attachment['filePath']);

                $filename = isset($attachment['filename']) ? isset($attachment['filename']) : end(explode("/", $attachment['filePath']));
                $swift_attachment->setFilename($filename);

                if (isset($attachment['content-type']))
                {
                    $swift_attachment->setContentType($attachment['content-type']);
                }

                if (isset($attachment['content-disposition']))
                {
                    $swift_attachment->setDisposition($attachment['content-disposition']);
                }
                else
                {
                    $swift_attachment->setDisposition('attachment');
                }
                $disposition = $swift_attachment->getHeaders()->get('Content-Disposition');
                $disposition->setParameter('filename', $filename);

                $message->attach($swift_attachment);
            }
        }

        return base64_encode($message->toString());
    }
}
