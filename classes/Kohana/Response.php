<?php

namespace Kohana;

use \Arr as Arr;
use \HTTP_Header as HTTP_Header;
use \Kohana_Exception as Kohana_Exception;
use \Exception as Exception;


/**
 * Response wrapper. Created as the result of any [Request] execution
 * or utility method (i.e. Redirect). Implements standard HTTP
 * response format.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    http://kohanaframework.org/license
 * @since      3.1.0
 */
class Response implements HTTP\IResponse
{

    /**
     * Factory method to create a new [Response]. Pass properties
     * in using an associative array.
     *
     *      // Create a new response
     *      $response = Response::factory();
     *
     *      // Create a new response with headers
     *      $response = Response::factory(array('status' => 200));
     *
     * @param   array $config Setup the response object
     * @return  Response
     */
    public static function factory(array $config = array())
    {
        $class_name = get_called_class();

        return new $class_name($config);
    }

    // HTTP status codes and messages
    public static $messages = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );

    /**
     * @var  integer     The response http status
     */
    protected $_status = 200;

    /**
     * @var  HTTP\Header  Headers returned in the response
     */
    protected $_header;

    /**
     * @var  string      The response body
     */
    protected $_body = '';

    /**
     * @var  string      The response protocol
     */
    protected $_protocol;

    /**
     * Sets up the response object
     *
     * @param   array $config Setup the response object
     */
    public function __construct(array $config = array())
    {
        $this->_header = new HTTP_Header;

        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                if ($key == '_header') {
                    $this->headers($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Outputs the body when cast to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_body;
    }

    /**
     * Gets or sets the body of the response
     *
     * @param string $content
     * @return  mixed
     */
    public function body($content = null)
    {
        if ($content === null)
            return $this->_body;

        $this->_body = (string)$content;

        return $this;
    }

    /**
     * Gets or sets the HTTP protocol. The standard protocol to use
     * is `HTTP/1.1`.
     *
     * @param   string $protocol Protocol to set to the request/response
     * @return  mixed
     */
    public function protocol($protocol = null)
    {
        if ($protocol) {
            $this->_protocol = strtoupper($protocol);

            return $this;
        }

        if ($this->_protocol === null) {
            $this->_protocol = \HTTP::$protocol;
        }

        return $this->_protocol;
    }

    /**
     * Sets or gets the HTTP status from this response.
     *
     *      // Set the HTTP status to 404 Not Found
     *      $response = Response::factory()
     *              ->status(404);
     *
     *      // Get the current status
     *      $status = $response->status();
     *
     * @param   integer $status Status to set to this response
     * @return  mixed
     * @throws  Kohana_Exception
     */
    public function status($status = null)
    {
        if ($status === null) {
            return $this->_status;
        } elseif (array_key_exists($status, Response::$messages)) {
            $this->_status = (int)$status;

            return $this;
        } else {
            throw new Kohana_Exception(__METHOD__ . ' unknown status value : :value', array(':value' => $status));
        }
    }

    /**
     * Gets and sets headers to the [Response], allowing chaining
     * of response methods. If chaining isn't required, direct
     * access to the property should be used instead.
     *
     *       // Get a header
     *       $accept = $response->headers('Content-Type');
     *
     *       // Set a header
     *       $response->headers('Content-Type', 'text/html');
     *
     *       // Get all headers
     *       $headers = $response->headers();
     *
     *       // Set multiple headers
     *       $response->headers(array('Content-Type' => 'text/html', 'Cache-Control' => 'no-cache'));
     *
     * @param mixed $key
     * @param string $value
     * @return mixed
     */
    public function headers($key = null, $value = null)
    {
        if ($key === null) {
            return $this->_header;
        } elseif (is_array($key)) {
            $this->_header->exchangeArray($key);

            return $this;
        } elseif ($value === null) {
            $aryHeader = $this->_header->getArrayCopy();
            return Arr::get($aryHeader, $key);
        } else {
            $this->_header[$key] = $value;

            return $this;
        }
    }

    /**
     * Returns the length of the body for use with
     * content header
     *
     * @return  integer
     */
    public function content_length()
    {
        return strlen($this->body());
    }


    /**
     * Sends the response status and all set headers.
     *
     * @param   boolean $replace replace existing headers
     * @param   callback $callback function to handle header output
     * @return  mixed
     */
    public function send_headers($replace = false, $callback = null)
    {
        return $this->_header->send_headers($this, $replace, $callback);
    }

    /**
     * Renders the HTTP_Interaction to a string, producing
     *
     *  - Protocol
     *  - Headers
     *  - Body
     *
     * @return  string
     */
    public function render()
    {
        if (!$this->_header->offsetExists('content-type')) {
            // Add the default Content-Type header if required
            $this->_header['content-type'] = HTTP\Header::$str_default_content_type;
        }

        // Set the content length
        $this->headers('content-length', (string)$this->content_length());

        //EVENT_RENDER


        $output = $this->_protocol . ' ' . $this->_status . ' ' . Response::$messages[$this->_status] . "\r\n";
        $output .= (string)$this->_header;
        $output .= $this->_body;

        return $output;
    }

    /**
     * Generate ETag
     * Generates an ETag from the response ready to be returned
     *
     * @throws Request_Exception
     * @return String Generated ETag
     */
    public function generate_etag()
    {
        if ($this->_body === '') {
            throw new Exception('No response yet associated with request - cannot auto generate resource ETag');
        }

        // Generate a unique hash for the response
        return '"' . sha1($this->render()) . '"';
    }

    /**
     * Parse the byte ranges from the HTTP_RANGE header used for
     * resumable downloads.
     *
     * @link   http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35
     * @return array|FALSE
     */
    protected function _parse_byte_range()
    {
        if (!isset($_SERVER['HTTP_RANGE'])) {
            return false;
        }

        // TODO, speed this up with the use of string functions.
        preg_match_all('/(-?[0-9]++(?:-(?![0-9]++))?)(?:-?([0-9]++))?/', $_SERVER['HTTP_RANGE'], $matches, PREG_SET_ORDER);

        return $matches[0];
    }

    /**
     * Calculates the byte range to use with send_file. If HTTP_RANGE doesn't
     * exist then the complete byte range is returned
     *
     * @param  integer $size
     * @return array
     */
    protected function _calculate_byte_range($size)
    {
        // Defaults to start with when the HTTP_RANGE header doesn't exist.
        $start = 0;
        $end = $size - 1;

        if ($range = $this->_parse_byte_range()) {
            // We have a byte range from HTTP_RANGE
            $start = $range[1];

            if ($start[0] === '-') {
                // A negative value means we start from the end, so -500 would be the
                // last 500 bytes.
                $start = $size - abs($start);
            }

            if (isset($range[2])) {
                // Set the end range
                $end = $range[2];
            }
        }

        // Normalize values.
        $start = abs(intval($start));

        // Keep the the end value in bounds and normalize it.
        $end = min(abs(intval($end)), $size - 1);

        // Keep the start in bounds.
        $start = ($end < $start) ? 0 : max($start, 0);

        return array($start, $end);
    }

}
