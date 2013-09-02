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

/**
* @class aCurl v0.1
*
* A cURL object.
*/
class aCurl
{
    protected static $_version = '0.1';

    // Request methods
    const
        METHOD_GET    = 'GET',
        METHOD_POST   = 'POST',
        METHOD_PUT    = 'PUT',
        METHOD_DELETE = 'DELETE';

    // cURL handler
    protected $_ch = null;
    // Target URL
    protected $_url;
    // Target URL params
    protected $_urlParams = array();
    // cURL method
    protected $_method = self::METHOD_GET;
    // cURL info
    protected $_info = array();
    // cURL errno & errstr
    protected $_failCode = 0,
              $_failText = '';

    // Used for auto curl_close()
    protected $_autoClose = true;

    // cURL options & default options
    protected $_options = array(),
              $_optionsDefault = array(
                  CURLOPT_CUSTOMREQUEST  => self::METHOD_GET,
                  CURLOPT_RETURNTRANSFER => 1,
                  CURLOPT_HEADER         => 1,
                  // Expect -> Required for a proper response headers & body
                  CURLOPT_HTTPHEADER     => array('Expect:'),
                  CURLINFO_HEADER_OUT    => 1,
              );

    // Request stuff
    protected $_request = null,
              $_requestBody = '',
              $_requestHeaders = array(),
              $_requestHeadersRaw = '';
    // Response stuff
    protected $_response = null,
              $_responseBody = '',
              $_responseHeaders = array(),
              $_responseHeadersRaw = '';

    // Store reponse headers & body?
    protected $_storeResponseHeaders = true,
              $_storeResponseBody = true;


    /**
     * Make a new aCurl instance with the given arguments.
     *
     * @param string $url (optional)
     * @param array  $options (optional)
     * @throws aCurlException
     */
    public function __construct($url = '', Array $options = null) {
        // Check php-curl extension
        if (!extension_loaded('curl')) {
            throw new aCurlException('cURL extension not found!');
        }
        // Init cURL handle
        $this->_ch = curl_init();
        // Set URL if provided
        if ($url != '') {
            $this->setUrl($url);
        }
        // Set options if provided
        $this->setOption($options);
        // Set user agent
        $this->_optionsDefault[CURLOPT_USERAGENT] = 'aCurl/v'. self::$_version;
    }

    /**
     * Unset aCurl instance.
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Actually, only checks set & get operations for CURLOPT_*.
     *
     * @param string $call
     * @param array  $args
     * @note See for available cURL constants: http://tr.php.net/curl_setopt
     * @usage i.e: for CURLOPT_FRESH_CONNECT -> setFreshConnect(1)
     */
    public function __call($call, $args = array()) {
        if (!method_exists($this, $call)) {
            // Easy set-get option handle
            $cmd = substr($call, 0, 3);
            if ($cmd == 'set' || $cmd == 'get') {
                $opt = substr($call, 3);
                // Upper chars to underscore
                $opt = preg_replace_callback('~([A-Z])~', function($m) {
                    return strtoupper('_'. $m[1]);
                }, $opt);
                if ($cmd == 'set') { // Setter
                    return $this->setOption($opt, $args[0]);
                }
                if ($cmd == 'get') { // Getter
                    return $this->getOption($opt);
                }
            }
        }
    }

    /**
     * Execute cURL.
     */
    public function run() {
        try {
            // Prepare all options [NOTE: Be sure already set all needed options before run()]
            $this->_setOptionArray();

            if (empty($this->_url)) {
                throw new aCurlException('I need an URL! :(');
            }

            // Outputs whole reponse if "CURLOPT_RETURNTRANSFER=0"
            ob_start();
            $curlResult        = curl_exec($this->_ch);
            $this->_response   = ob_get_clean();
            if (is_string($curlResult)) {
                $this->_response = $curlResult;
            }

            // Set curl info
            $this->_setInfo();

            // We have error?
            if ($curlResult === false) {
                $this->_failCode = curl_errno($this->_ch);
                $this->_failText = curl_error($this->_ch);
            } else {
                // Correction for explode operation
                if (!$this->getOption('header')) {
                    $this->_response = "\r\n\r\n". $this->_response;
                }

                // Parse response & set headers and body
                @ list($headers, $body) = explode("\r\n\r\n", $this->_response, 2);
                $this->_setResponseHeaders($headers);
                $this->_setResponseBody($body);
            }

            // Close curl if "autoClose=true"
            if ($this->_autoClose) {
                $this->close();
            }
        } catch (aCurlException $e) {
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
     * Set aCurl URL.
     *
     * @param string $url (reqired)
     */
    public function setUrl($url) {
        $this->_url = trim($url);
        $this->_options[CURLOPT_URL] = $this->_url;
    }

    /**
     * Get aCurl URL.
     */
    public function getUrl() {
        return $this->_url;
    }

    /**
     * Set aCurl method.
     *
     * @param string $method (reqired)
     */
    public function setMethod($method) {
        if (array_key_exists(CURLOPT_POST, $this->_options)) {
            unset($this->_options[CURLOPT_POST]);
        }
        $this->_method = strtoupper($method);
        $this->_options[CURLOPT_CUSTOMREQUEST] = $this->_method;
        // See: http://tr.php.net/manual/en/function.curl-setopt.php#109634 :)
        if ($this->_method == self::METHOD_PUT) {
            $this->_optionsDefault[CURLOPT_HTTPHEADER][] = 'X-HTTP-Method-Override: PUT';
        }
    }

    /**
     * Get aCurl method.
     */
    public function getMethod() {
        return $this->_method;
    }

    /**
     * Set aCurl URL param.
     *
     * @param string $key (reqired)
     * @param mixed  $val (reqired|optional)
     */
    public function setUrlParam($key, $val = null) {
        if (is_array($key)) {
            return $this->_setUrlParams($key);
        }
        $this->_setUrlParams(array($key => $val));
    }

    /**
     * Get aCurl URL param.
     *
     * @param string $key (reqired)
     * @param string $defaulValue (optional)
     */
    public function getUrlParam($key) {
        if (isset($this->_urlParams[$key])) {
            return $this->_urlParams[$key];
        }
    }

    /**
     * Get aCurl URL params.
     */
    public function getUrlParams() {
        return $this->_urlParams;
    }

    /**
     * Set aCurl cURL option.
     *
     * @param string $key (reqired)
     * @param mixed  $val (reqired|optional)
     * @note See for available constants: http://tr.php.net/curl_setopt
     * @usage i.e: for CURLOPT_FRESH_CONNECT
     *      -> setOption('fresh_connect', 1)
     *      -> setOption(CURLOPT_FRESH_CONNECT, 1)
     */
    public function setOption($key, $val = null) {
        if (is_array($key) && !empty($key)) {
            foreach ($options as $key => $val) {
                $this->setOption($key, $val);
            }
        }
        $key = $this->_prepareOptionKey($key);
        if ($key) {
            $this->_options[$key] = $val;
        }
        return $this;
    }

    /**
     * Get aCurl cURL option.
     *
     * @param string $key (reqired)
     * @note See for available keys: http://tr.php.net/curl_getinfo
     */
    public function getOption($key) {
        $key = $this->_prepareOptionKey($key);
        if ($key && array_key_exists($key, $this->_options)) {
            return $this->_options[$key];
        }
    }

    /**
     * Get aCurl cURL options.
     */
    public function getOptions() {
        return $this->_options;
    }

    /**
     * Set aCurl autoClose.
     *
     * @param $val (required)
     * @note If false, needs a manual call for aCurl::close() after aCurl::run()
     */
    public function autoClose($val) {
        $this->_autoClose = (bool) $val;
    }

    /**
     * Get aCurl cURL info.
     *
     * @note See for available keys: http://tr.php.net/curl_getinfo
     */
    public function getInfo($key = null) {
        if ($key && isset($this->_info[$key])) {
            return $this->_info[$key];
        }
        return $this->_info;
    }

    /**
     * Check aCurl error.
     */
    public function isFail() {
        return (bool) ($this->_failText !== '');
    }

    /**
     * Get aCurl error code.
     */
    public function getFailCode() {
        return $this->_failCode;
    }

    /**
     * Get aCurl error text.
     */
    public function getFailText() {
        return $this->_failText;
    }

    /**
     * Set request header(s).
     *
     * @param mixed $key (required)
     * @param mixed $val (required|optional)
     */
    public function setRequestHeader($key, $val = null) {
        if (is_array($key) && !empty($key)) {
            foreach ($key as $k => $v) {
                if (is_int($k)) {
                    $this->setRequestHeader($v);
                } else {
                    $this->setRequestHeader($k, $v);
                }
            }
        }
        if ($val === null) {
            @ list($key, $val) = explode(':', $key, 2);
        }
        if ($key) {
            $val = trim($val);
            $this->_optionsDefault[CURLOPT_HTTPHEADER][] = "$key: $val";
        }
        return $this;
    }

    /**
     * Set request body.
     *
     * @param mixed $body (required)
     */
    public function setRequestBody($body) {
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
     */
    public function getRequest() {
        return $this->_request;
    }

    /**
     * Get request body.
     */
    public function getRequestBody() {
        return $this->_requestBody;
    }

    /**
     * Get request header.
     *
     * @param string $key (required)
     */
    public function getRequestHeader($key) {
        if (isset($this->_requestHeaders[$key])) {
            return $this->_requestHeaders[$key];
        }
    }

    /**
     * Get request headers (raw).
     *
     * @param bool $raw (optinal)
     */
    public function getRequestHeaders($raw = false) {
        return !$raw
            ? $this->_requestHeaders
            : $this->_requestHeadersRaw;
    }

    /**
     * Get raw response.
     */
    public function getResponse() {
        return $this->_response;
    }

    /**
     * Get response body.
     */
    public function getResponseBody() {
        return $this->_responseBody;
    }

    /**
     * Get response header.
     *
     * @param string $key (required)
     */
    public function getResponseHeader($key) {
        if (isset($this->_responseHeaders[$key])) {
            return $this->_responseHeaders[$key];
        }
    }

    /**
     * Get response headers (raw).
     *
     * @param bool $raw (optinal)
     */
    public function getResponseHeaders($raw = false) {
        return !$raw
            ? $this->_responseHeaders
            : $this->_responseHeadersRaw;
    }

    /**
     * Whether response headers will be stored in aCurl object.
     *
     * @param bool $raw (optinal)
     */
    public function storeResponseHeaders($val) {
        $this->_storeResponseHeaders = (bool) $val;
    }

    /**
     * Whether response body will be stored in aCurl object.
     *
     * @param bool $raw (optinal)
     */
    public function storeResponseBody($val) {
        $this->_storeResponseBody = (bool) $val;
    }

    /**
     * Set response headers.
     *
     * @param mixed $headers (required)
     */
    protected function _setResponseHeaders($headers) {
        if ($this->_storeResponseHeaders && $this->getOption('header')) {
            $this->_responseHeaders   += $this->_parseHeaders($headers);
            $this->_responseHeadersRaw = trim($headers);
        }
    }

    /**
     * Set response body.
     *
     * @param mixed $body (required)
     */
    protected function _setResponseBody($body) {
        if ($this->_storeResponseBody && !$this->getOption('nobody')) {
            $this->_responseBody = trim($body);
        }
    }

    /**
     * Set URL params.
     *
     * @param array $params (required)
     */
    protected function _setUrlParams(Array $params) {
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
     * @param string $key (required)
     */
    protected function _prepareOptionKey($key) {
        // Simply convert option keys to CURL_* constans
        if (is_string($key)) {
            $key = strtoupper(trim($key));
            if (strpos($key, 'CURLOPT_') !== 0) {
                $key = 'CURLOPT_'. ltrim($key, '_');
            }
            $key = constant($key);
        }
        return $key;
    }

    /**
     * Set aCurl options
     */
    protected function _setOptionArray() {
        // Easy!
        $this->_options =
            $this->_options += $this->_optionsDefault;
        curl_setopt_array($this->_ch, $this->_options);
    }

    /**
     * Parse headers.
     *
     * @param mixed $headers (required)
     */
    protected function _parseHeaders($headers) {
        $headersArr = array();
        $headersTmp = $headers;
        if (is_string($headersTmp)) {
            $headersTmp =@ explode("\r\n", $headers);
        }

        if (is_array($headersTmp) || !empty($headersTmp)) {
            foreach ($headersTmp as $header) {
                // HTTP/1.1 200 OK
                if (preg_match('~^HTTP/[\d\.]+ (\d+) ([\w- ]+)~i', $header, $matches)) {
                    $headersArr['response_code'] = (int) $matches[1];
                    $headersArr['response_text'] = $matches[2];
                    continue;
                }
                @ list($key, $val) = explode(':', $header, 2);
                if ($key) {
                    $key = $this->_prepareHeaderKey($key);
                    $val = trim($val);
                    // Handle multi-headers as array
                    if (array_key_exists($key, $headersArr)) {
                        $headersArr[$key] = array_merge((array) $headersArr[$key], array($val));
                        continue;
                    }
                    $headersArr[$key] = $val;
                }
            }
            ksort($headersArr);
        }

        return $headersArr;
    }

    /**
     * Praper header key.
     *
     * @param string $key (required)
     * @note Converts all header keys to "underscored"
     */
    protected function _prepareHeaderKey($key) {
        return preg_replace(
            array('~\s+~', '~(\s|-)~'),
            array(' ', '_'),
            strtolower($key)
        );
    }

    /**
     * Set aCurl info.
     */
    protected function _setInfo() {
        $infoArr = array();
        $infoTmp = curl_getinfo($this->_ch);
        if (!empty($infoTmp) && isset($infoTmp['request_header'])) {
            $tmp = explode("\r\n", trim($infoTmp['request_header']));
            // GET /user/123 HTTP/1.1
            $theRequest = array_shift($tmp);
            sscanf($theRequest, '%s %s %s', $requestMethod, $requestUri, $requestProtocol);
            $infoArr['request_method']   = $requestMethod;
            $infoArr['request_uri']      = $requestUri;
            $infoArr['request_protocol'] = $requestProtocol;

            $infoArr['request_header']   = $infoTmp['request_header'];
            $infoArr['request_headers']  = $this->_parseHeaders($tmp);
            if (isset($this->_options[CURLOPT_POSTFIELDS])) {
                $infoArr['request_body'] = $this->_options[CURLOPT_POSTFIELDS];
            }

            // Set _request
            $requestHeader  = trim($infoTmp['request_header']);
            $this->_request = $requestHeader ."\r\n\r\n".
                              isset($infoArr['request_body']) ? trim($infoArr['request_body']) : '';
            // Set _requestHeaders
            foreach ($infoArr['request_headers'] as $key => $val) {
                $this->_requestHeaders[$key] = $val;
            }
            // Set _requestHeadersRaw
            $this->_requestHeadersRaw = $requestHeader;
        }
        // Merge
        $infoArr += $infoTmp;

        $this->_info = $infoArr;
    }
}