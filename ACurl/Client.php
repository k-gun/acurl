<?php
/**
 * Copyright 2015 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * Apache License, Version 2.0
 *    <http://www.apache.org/licenses/LICENSE-2.0>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
declare(strict_types=1);

namespace ACurl;

use ACurl\Http\Stream;
use ACurl\Http\Request;
use ACurl\Http\Response;

/**
 * @package ACurl
 * @object  ACurl\Client
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Client extends ClientBase
{
    /**
     * Constructor.
     * @param string|null $uri
     * @param array|null  $options
     */
    final public function __construct(string $uri = null, array $options = null)
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL extension not found!');
        }

        $this->request = new Request();
        $this->response = new Response();

        if ($uri) {
            // notation: get github.com
            // notation: get >> github.com (more readable?)
            if (preg_match('~^(?P<method>\w+)\s+(?:>>\s+)?(?<uri>.+)~', $uri, $matches)) {
                $this->request->setMethod($matches['method'])
                              ->setUri($matches['uri']);
            }
            // notation: github.com
            else {
                $this->request->setMethod(Request::METHOD_GET)
                              ->setUri($uri);
            }
        }

        if ($options) {
            isset($options['method'])
                && $this->request->setMethod($options['method']);
            isset($options['uri'])
                && $this->request->setUri($options['uri']);
            isset($options['uriParams'])
                && $this->request->setUriParams($options['uriParams']);
            isset($options['headers'])
                && $this->request->setHeaders($options['headers']);
            isset($options['cookies'])
                && $this->request->setCookies($options['cookies']);
            isset($options['body'])
                && $this->request->setBody($options['body']);
            isset($options['options'])
                && $this->setOptions($options['options']);
        }
    }

    /**
     * Destructor.
     */
    final public function __destruct()
    {
        $this->close();
    }

    /**
     * Send.
     * @param  array|null          $uriParams
     * @param  string|array|object $body
     * @param  array|null          $headers
     * @param  array|null          $cookies
     * @return self
     * @throws \InvalidArgumentException, \RuntimeException
     */
    final public function send(array $uriParams = null, $body = null,
        array $headers = null, array $cookies = null): self
    {
        $this->request->setUriParams((array) $uriParams)
                      ->setHeaders((array) $headers)
                      ->setCookies((array) $cookies);

        $uri = $this->request->getUriFull();
        if ($uri == '') {
            throw new \InvalidArgumentException('I need a URL! :(');
        }

        $this->open();

        $options = ($this->options + $this->optionsDefault);
        if (!isset($options[CURLOPT_URL])) {
            $options[CURLOPT_URL] = $uri;
        }

        if (!isset($options[CURLOPT_USERAGENT])) {
            $options[CURLOPT_USERAGENT] = 'ACurl/v'. self::VERSION .' (+https://github.com/k-gun/acurl)';
        }

        $method = $this->request->getMethod();
        if ($method != Request::METHOD_GET && $method != Request::METHOD_POST) {
            $options[CURLOPT_HTTPHEADER][] = 'X-HTTP-Method-Override: '. $method;
        }
        $options[CURLOPT_CUSTOMREQUEST] = $method;

        if ('' != ($headers = $this->request->getHeadersString())) {
            $options[CURLOPT_HTTPHEADER][] = $headers;
        }
        if ('' != ($cookies = $this->request->getCookiesString())) {
            $options[CURLOPT_HTTPHEADER][] = 'Cookie: '. $cookies;
        }
        unset($headers, $cookies);

        curl_setopt_array($this->ch, $options);

        // prevent output whole reponse if CURLOPT_RETURNTRANSFER=0
        ob_start();
        $result = curl_exec($this->ch);
        $resultOutput = ob_get_clean();
        if (is_string($result)) {
            $resultOutput = $result;
        }

        // throw original cURL error
        if ($result === false) {
            throw new \RuntimeException(curl_error($this->ch), curl_errno($this->ch));
        }

        $this->info = curl_getinfo($this->ch);

        if (isset($this->info['request_header'])) {
            $this->request->setHeaders($headers = Stream::parseHeaders($this->info['request_header'],
                Stream::TYPE_REQUEST));
            if (isset($headers['cookie'])) {
                $this->request->setCookies(Stream::parseCookies($headers['cookie']));
            }
        }

        // for proper explode'ing below
        if (!isset($options[CURLOPT_HEADER])) {
            $resultOutput = "\r\n\r\n". $resultOutput;
        }

        @ list($headers, $body) = explode("\r\n\r\n", $resultOutput, 2);
        $this->response->setBody($body);

        $this->response->setHeaders($headers = Stream::parseHeaders($headers, Stream::TYPE_RESPONSE));
        if (isset($headers['set_cookie'])) {
            $this->response->setCookies(Stream::parseCookies($headers['set_cookie']));
        }

        if ($this->autoClose) {
            $this->close();
        }

        return $this;
    }

    /**
     * Get.
     * @inheritDoc self::send()
     */
    final public function get(array $uriParams = null,
        array $headers = null, array $cookies = null): self
    {
        $this->request->setMethod(Request::METHOD_GET);

        return $this->send($uriParams, $headers, $cookies);
    }

    /**
     * Post.
     * @inheritDoc self::send()
     */
    final public function post($body = null, array $uriParams = null,
        array $headers = null, array $cookies = null): self
    {
        $this->request->setMethod(Request::METHOD_POST);

        return $this->send($uriParams, $body, $headers, $cookies);
    }

    /**
     * Put.
     * @inheritDoc self::send()
     */
    final public function put($body = null, array $uriParams = null,
        array $headers = null, array $cookies = null): self
    {
        $this->request->setMethod(Request::METHOD_PUT);

        return $this->send($uriParams, $body, $headers, $cookies);
    }

    /**
     * Patch.
     * @inheritDoc self::send()
     */
    final public function patch($body = null, array $uriParams = null,
        array $headers = null, array $cookies = null): self
    {
        $this->request->setMethod(Request::METHOD_PATCH);

        return $this->send($uriParams, $body, $headers, $cookies);
    }

    /**
     * Get.
     * @inheritDoc self::send()
     */
    final public function delete(array $uriParams = null,
        array $headers = null, array $cookies = null): self
    {
        $this->request->setMethod(Request::METHOD_DELETE);

        return $this->send($uriParams, $headers, $cookies);
    }

    /**
     * Open.
     * @return void
     * @throws \RuntimeException
     */
    final private function open()
    {
        $this->ch = curl_init();
        if (!is_resource($this->ch)) {
            throw new \RuntimeException('Could not initialize cURL session!');
        }
    }

    /**
     * Close.
     * @return void
     */
    final private function close()
    {
        if (is_resource($this->ch)) {
            curl_close($this->ch);
            $this->ch = null;
        }
    }
}
