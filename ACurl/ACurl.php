<?php
/**
 * Copyright 2013, Kerem Gunes <http://qeremy.com/>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
namespace ACurl;

/**
 * ACurl
 *
 * Creates a fresh cURL request and response.
 *
 * @package ACurl
 * @object  ACurl\ACurl
 * @version 0.2
 * @author  Kerem Güneş <qeremy@gmail>
 */
class ACurl
{
    /**
     * Version.
     * @const double
     */
    const VERSION = 0.2;

    /**
     * Request methods.
     * @const string
     */
    const METHOD_GET     = 'GET',
          METHOD_PUT     = 'PUT',
          METHOD_POST    = 'POST',
          METHOD_PATCH   = 'PATCH',
          METHOD_DELETE  = 'DELETE',
          METHOD_HEAD    = 'HEAD',
          METHOD_TRACE   = 'TRACE',
          METHOD_OPTIONS = 'OPTIONS',
          METHOD_CONNECT = 'CONNECT';

    /**
     * cURL handle.
     * @var resource|null
     */
    protected $_ch = null;

    /**
     * Request URL.
     * @var string
     */
    protected $_url;

    /**
     * Request URL params.
     * @var array
     */
    protected $_urlParams = [];

    /**
     * Request method.
     * @var string
     */
    protected $_method = self::METHOD_GET;

    /**
     * cURL info.
     * @var array
     */
    protected $_info = [];

    /**
     * cURL error no, error text.
     * @var int
     * @var string
     */
    protected $_failCode = 0,
              $_failText = '';

    /**
     * Auto-close directive for cURL session.
     * @var bool
     */
    protected $_autoClose = true;

    /**
     * cURL options.
     * @var array
     */
    protected $_options = [];

    /**
     * cURL default options.
     * @var array
     */
    protected $_optionsDefault = [
        CURLOPT_CUSTOMREQUEST  => self::METHOD_GET,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER         => 1,
        // required for a proper response body and headers
        CURLOPT_HTTPHEADER     => ['Expect:'],
        CURLINFO_HEADER_OUT    => 1
    ];

    /**
     * Request.
     * @var null|string
     */
    protected $_request = null;

    /**
     * Request body - not set if method GET/HEAD etc.
     * @var string
     */
    protected $_requestBody = '';

    /**
     * Request headers.
     * @var array
     */
    protected $_requestHeaders = [];

    /**
     * Request raw headers.
     * @var string
     */
    protected $_requestHeadersRaw = '';

    /**
     * Response.
     * @var null|string
     */
    protected $_response = null;

    /**
     * Response body.
     * @var string
     */
    protected $_responseBody = '';

    /**
     * Response headers.
     * @var array
     */
    protected $_responseHeaders = [];

    /**
     * Response raw headers.
     * @var array
     */
    protected $_responseHeadersRaw = '';

    /**
     * Should store response body and headers?
     * @var bool
     */
    protected $_storeResponseBody = true,
              $_storeResponseHeaders = true;

    /**
     * Set a new ACurl instance.
     *
     * @param  string $url  - could be set later but before run()
     * @param  array  options
     * @throws ACurlException
     */
    public function __construct($url = '', array $options = []) {
        // check php-curl extension
        if (!extension_loaded('curl')) {
            throw new ACurlException('cURL extension not found!');
        }

        // init cURL session
        $this->_ch =@ curl_init();
        if (!is_resource($this->_ch)) {
            throw new ACurlException('Could not initialize cURL session!');
        }

        // set URL if provided
        if ($url != '') {
            $this->setUrl($url);
        }

        // set options if provided
        $this->setOptions($options);

        // set user agent
        if (isset($options[CURLOPT_USERAGENT])) {
            $this->_optionsDefault[CURLOPT_USERAGENT] = $options[CURLOPT_USERAGENT];
        } elseif (isset($options['useragent'])) {
            $this->_optionsDefault[CURLOPT_USERAGENT] = $options['useragent'];
        } else {
            $this->_optionsDefault[CURLOPT_USERAGENT] = sprintf(
                'ACurl/v%s (+http://github.com/qeremy/acurl)', self::VERSION);
        }
    }

    /**
     * Close cURL session.
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Actually, only for set/get operations for CURLOPT_*.
     *
     * @param  string $name
     * @param  array  $args
     * @return mixed
     * @throws ACurlException - if method does not exists
     * @note - see available constants: php.net/curl_setopt
     * @usage
     * - CURLOPT_URL           -> setUrl('php.net')
     * - CURLOPT_FRESH_CONNECT -> setFreshConnect(1)
     */
    public function __call($name, $args) {
        if (!method_exists($this, $name)) {
            // easy wat to set-get option handle
            $cmd = substr($name, 0, 3);
            if ($cmd == 'set' || $cmd == 'get') {
                // upper chars to underscore
                $opt = preg_replace_callback('~([A-Z])~', function($m) {
                    return strtoupper('_'. $m[1]);
                }, substr($name, 3));
                // set action
                if ($cmd == 'set') {
                    return $this->setOption($opt, $args[0]);
                }
                // get action
                if ($cmd == 'get') {
                    return $this->getOption($opt);
                }
            }
        }
        throw new ACurlException('Method does not exists!');
    }

    /**
     * Perform cURL request.
     *
     * @throws ACurlException
     */
    public function run() {
        try {
            // set all cURL options
            // note: Be sure already set all needed options before run()
            $this->_setOptionArray();

            // check URL
            if (empty($this->_url)) {
                throw new ACurlException('I need an URL! :(');
            }

            // prevent output whole reponse if CURLOPT_RETURNTRANSFER=0
            ob_start();
            $curlResult =@ curl_exec($this->_ch);
            $this->_response = ob_get_clean();
            if (is_string($curlResult)) {
                $this->_response = $curlResult;
            }

            // set cURL info
            $this->_setInfo();

            // we have error?
            if ($curlResult === false) {
                $this->_failCode = curl_errno($this->_ch);
                $this->_failText = curl_error($this->_ch);
            } else {
                // correction for `explode`
                if (!$this->getOption('header')) {
                    $this->_response = "\r\n\r\n". $this->_response;
                }

                // parse response, set headers and body
                @list($headers, $body) = explode("\r\n\r\n", $this->_response, 2);
                $this->_setResponseBody($body);
                $this->_setResponseHeaders($headers);
            }

            // close curl if autoClose=true
            if ($this->_autoClose) {
                $this->close();
            }
        } catch (ACurlException $e) {
            // set error stuff
            $this->_failCode = null;
            $this->_failText = $e->getMessage();
        }
    }

    /**
     * Close cURL.
     */
    public function close() {
        if (is_resource($this->_ch)) {
            curl_close($this->_ch);
            $this->_ch = null;
        }
    }

    /**
     * Set request URL.
     *
     * @param string $url
     */
    public function setUrl($url) {
        $this->_url = trim($url);
        $this->_options[CURLOPT_URL] = $this->_url;
    }

    /**
     * Get request URL.
     */
    public function getUrl() {
        return $this->_url;
    }

    /**
     * Set request method.
     *
     * @param string $method
     */
    public function setMethod($method) {
        $this->_method = strtoupper($method);
        $this->_options[CURLOPT_CUSTOMREQUEST] = $this->_method;
        // See: http://tr.php.net/manual/en/function.curl-setopt.php#109634 :)
        if ($this->_method == self::METHOD_PUT) {
            $this->_optionsDefault[CURLOPT_HTTPHEADER][] = 'X-HTTP-Method-Override: PUT';
        }
    }

    /**
     * Get request method.
     */
    public function getMethod() {
        return $this->_method;
    }

    /**
     * Set request URL param.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setUrlParam($key, $value = null) {
        $this->_setUrlParams([$key => $value]);
    }

    /**
     * Set request URL params.
     *
     * @param array $params
     */
    public function setUrlParams(array $params) {
        $this->_setUrlParams($params);
    }

    /**
     * Get request URL param.
     *
     * @param  string $key
     * @param  string $default
     * @return mixed|null
     */
    public function getUrlParam($key, $default = null) {
        if (isset($this->_urlParams[$key])) {
            $default = $this->_urlParams[$key];
        }
        return $default;
    }

    /**
     * Get request URL params.
     *
     * @return array
     */
    public function getUrlParams() {
        return $this->_urlParams;
    }

    /**
     * Set cURL option.
     *
     * @param string $key
     * @param mixed  $value
     * @note - see available constants: php.net/curl_setopt
     * @usage
     * - setOption('fresh_connect', 1)
     * - setOption(CURLOPT_FRESH_CONNECT, 1)
     */
    public function setOption($key, $value = null) {
        $this->_options[$this->_prepareOptionKey($key)] = $value;
    }

    /**
     * Set cURL options.
     *
     * @param array $options
     */
    public function setOptions(array $options) {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    /**
     * Get cURL option.
     *
     * @param  string     $key
     * @param  mixed|null $default
     * @return mixed|null
     * @note - see available constants: php.net/curl_setopt
     */
    public function getOption($key, $default = null) {
        $key = $this->_prepareOptionKey($key);
        if (array_key_exists($key, $this->_options)) {
            $default = $this->_options[$key];
        }
        return $default;
    }

    /**
     * Get cURL options.
     *
     * @return array
     */
    public function getOptions() {
        return $this->_options;
    }

    /**
     * Set auto-close.
     *
     * @param $option
     * @note - if false, needs a manual call for close() after run()
     */
    public function autoClose($option) {
        $this->_autoClose = (bool) $option;
    }

    /**
     * Get request info by key.
     *
     * @return null|mixed [description]
     * @note - see available keys: php.net/curl_getinfo
     */
    public function getInfo($key, $default = null) {
        if (isset($this->_info[$key])) {
            $default = $this->_info[$key];
        }
        return $default;
    }

    /**
     * Get all request info.
     *
     * @return array
     */
    public function getInfoAll() {
        return $this->_info;
    }

    /**
     * Check error.
     *
     * @return bool
     */
    public function isFail() {
        return (bool) ($this->_failText !== '');
    }

    /**
     * Get error code.
     *
     * @return int
     */
    public function getFailCode() {
        return $this->_failCode;
    }

    /**
     * Get error text.
     *
     * @return string
     */
    public function getFailText() {
        return $this->_failText;
    }

    /**
     * Set request header.
     *
     * @param mixed $key
     * @param mixed $value
     * @return self
     */
    public function setRequestHeader($key, $value = null) {
        if (is_null($value)) {
            @list($key, $value) = explode(':', $key, 2);
        }
        if ($key) {
            $this->_optionsDefault[CURLOPT_HTTPHEADER][] = sprintf(
                '%s: %s', $key, trim($value));
        }
        return $this;
    }

    /**
     * Set request headers.
     *
     * @param array $headers
     */
    public function setRequestHeaders(array $headers) {
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                $this->setRequestHeader($value);
            } else {
                $this->setRequestHeader($key, $value);
            }
        }
    }

    /**
     * Set request body.
     *
     * @param mixed $body
     */
    public function setRequestBody($body) {
        // @todo add more rules if needed
        if ($this->_method != 'GET') {
            if (is_array($body)) {
                $body = http_build_query($body);
            }
            $this->_requestBody = trim($body);
            $this->_options[CURLOPT_POSTFIELDS] = $this->_requestBody;
        }
    }

    /**
     * Get raw request.
     *
     * @return mixed|null
     */
    public function getRequest() {
        return $this->_request;
    }

    /**
     * Get request body.
     *
     * @return str
     */
    public function getRequestBody() {
        return $this->_requestBody;
    }

    /**
     * Get request header.
     *
     * @param string $key
     */
    public function getRequestHeader($key) {
        return isset($this->_requestHeaders[$key])
            ? $this->_requestHeaders[$key] : null;
    }

    /**
     * Get request headers (raw).
     *
     * @param bool $raw
     * @return mixed|null
     */
    public function getRequestHeaders($raw = false) {
        return !$raw
            ? $this->_requestHeaders
            : $this->_requestHeadersRaw;
    }

    /**
     * Get raw response.
     *
     * @return mixed|null
     */
    public function getResponse() {
        return $this->_response;
    }

    /**
     * Get response body.
     *
     * @return str|null
     */
    public function getResponseBody() {
        return $this->_responseBody;
    }

    /**
     * Get response header.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getResponseHeader($key) {
        return isset($this->_responseHeaders[$key])
            ? $this->_responseHeaders[$key] : null;
    }

    /**
     * Get response headers (raw).
     *
     * @param  bool $raw
     * @return array|string
     */
    public function getResponseHeaders($raw = false) {
        return !$raw
            ? $this->_responseHeaders
            : $this->_responseHeadersRaw;
    }

    /**
     * Get response status.
     *
     * @return string
     */
    public function getStatus() {
        return isset($this->_responseHeaders['_status'])
            ? $this->_responseHeaders['_status'] : '';
    }

    /**
     * Get response status code.
     *
     * @return int
     */
    public function getStatusCode() {
        return isset($this->_responseHeaders['_status_code'])
            ? $this->_responseHeaders['_status_code'] : 0;
    }

    /**
     * Get response status text.
     *
     * @return string
     */
    public function getStatusText() {
        return isset($this->_responseHeaders['_status_text'])
            ? $this->_responseHeaders['_status_text'] : '';
    }

    /**
     * Whether response body will be stored in ACurl object.
     *
     * @param bool $option
     */
    public function storeResponseBody($option) {
        $this->_storeResponseBody = (bool) $option;
    }

    /**
     * Whether response headers will be stored in ACurl object.
     *
     * @param bool $option
     */
    public function storeResponseHeaders($option) {
        $this->_storeResponseHeaders = (bool) $option;
    }

    /**
     * Set response body.
     *
     * @param string $body
     */
    protected function _setResponseBody($body) {
        if ($this->_storeResponseBody) {
            $this->_responseBody = trim($body);
        }
    }

    /**
     * Set response headers.
     *
     * @param string $headers
     */
    protected function _setResponseHeaders($headers) {
        if ($this->_storeResponseHeaders) {
            $this->_responseHeaders = $this->_parseHeaders($headers, 'response');
            $this->_responseHeadersRaw = trim($headers);
        }
    }

    /**
     * Set URL params.
     *
     * @param array $params
     */
    protected function _setUrlParams(array $params) {
        foreach ($params as $key => $val) {
            $this->_urlParams[$key] = $val;
        }
        $this->_url .= (
            strpos($this->_url, '?') === false
                ? '?'. http_build_query($this->_urlParams)
                : '&'. http_build_query($this->_urlParams)
        );
        $this->_options[CURLOPT_URL] = $this->_url;
    }

    /**
     * Prepare option key (curl_setopt constants).
     *
     * @param  string $key
     * @return string $key
     * @throws ACurlException
     * @note - see available constants: php.net/curl_setopt
     */
    protected function _prepareOptionKey($key) {
        // simply convert option keys to CURL_* constans
        if (!defined($key)) {
            $key = strtoupper(trim($key));
            if (strpos($key, 'CURLOPT_') !== 0) {
                $key = 'CURLOPT_'. ltrim($key, '_');
            }
            // check constant
            if (!defined($key)) {
                throw new ACurlException('cURL constant is not defined! key: `%s`', $key);
            }
            // set key as constant
            $key = constant($key);
        }

        return $key;
    }

    /**
     * Set cURL options.
     */
    protected function _setOptionArray() {
        // merge/overwrite default options
        $this->_options =
            $this->_options += $this->_optionsDefault;
        // pass all options to cURL handle
        curl_setopt_array($this->_ch, $this->_options);
    }

    /**
     * Prepares header key.
     *
     * @param string $key
     * @note - converts all header keys to "underscored"
     */
    protected function _prepareHeaderKey($key) {
        return preg_replace(['~\s+~', '~(\s|-)~'], [' ', '_'], strtolower($key));
    }

    /**
     * Parse headers.
     *
     * @param  array|string $headers
     * @return array
     */
    protected function _parseHeaders($headers, $source) {
        $return = [];
        // could be array (internally used)
        if (is_string($headers)) {
            (array) $headers =@ explode("\r\n", trim($headers));
        }

        // if we have headers
        if (!empty($headers)) {
            // set response status stuff
            if ($source == 'response'
                // HTTP/1.1 200 OK
                && preg_match('~^HTTP/\d\.\d (\d+) ([\w- ]+)~i', array_shift($headers), $matches)
                    && isset($matches[1], $matches[2])
            ) {
                $statusCode = (int) $matches[1];
                $statusText = preg_replace_callback('~(\w[^ ]+)~', function($m) {
                    // make an expected status text
                    return ($m[1] == 'OK') ? 'OK' : mb_convert_case($m[1], MB_CASE_TITLE);
                }, trim($matches[2]));
                $return['_status']      = $statusCode .' '. $statusText;
                $return['_status_code'] = $statusCode;
                $return['_status_text'] = $statusText;
            }
            // split key-value pairs
            foreach ($headers as $header) {
                @list($key, $value) = explode(':', $header, 2);
                if ($key) {
                    $key = $this->_prepareHeaderKey($key);
                    $value = trim($value);
                    // handle multi-headers as array
                    if (array_key_exists($key, $return)) {
                        $return[$key] = array_merge((array) $return[$key], [$value]);
                        continue;
                    }
                    $return[$key] = $value;
                }
            }
            // sort by key
            ksort($return);
        }

        return $return;
    }

    /**
     * Sets ACurl info.
     */
    protected function _setInfo() {
        // get cURL info
        $info = curl_getinfo($this->_ch);

        if (!empty($info)
            && isset($info['request_header'])
                && ($requestHeader = trim($info['request_header']))
        ) {
            (array) $headers =@ explode("\r\n", $requestHeader);
            if (!empty($headers)) {
                // set request stuff
                $theRequest = array_shift($headers);
                // GET /user/123 HTTP/1.1
                sscanf($theRequest, '%s %s %s', $requestMethod, $requestUri, $requestProtocol);
                $this->_info['_request_method']   = $requestMethod;
                $this->_info['_request_uri']      = $requestUri;
                $this->_info['_request_protocol'] = $requestProtocol;
                $this->_info['_request_header']   = $requestHeader;
                $this->_info['_request_headers']  = $this->_parseHeaders($headers, 'request');

                // set request body
                if (isset($this->_options[CURLOPT_POSTFIELDS])) {
                    $this->_info['request_body'] = $this->_options[CURLOPT_POSTFIELDS];
                }

                // set request property
                $this->_request = $requestHeader ."\r\n\r\n".
                    (isset($this->_info['request_body']) ? trim($this->_info['request_body']) : '');
                // set request headers property
                foreach ($this->_info['_request_headers'] as $key => $val) {
                    $this->_requestHeaders[$key] = $val;
                }
                // set request headers raw property
                $this->_requestHeadersRaw = $requestHeader;
            }
        }

        // merge
        $this->_info += $info;
        // sort by key
        ksort($this->_info);
    }
}
