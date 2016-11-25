<?php
declare(strict_types=1);

namespace ACurl;

use ACurl\Http\Stream;
use ACurl\Http\Request;
use ACurl\Http\Response;

final class Client extends ClientBase
{
    final public function __construct($options = null)
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL extension not found!');
        }

        $this->request = new Request();
        $this->response = new Response();

        if (is_string($options)) {
            $this->setRequestMethodAndUri($options);
        } elseif (is_array($options)) {
            isset($options['method'])
                && $this->request->setMethod($options['method']);
            isset($options['uri'])
                && $this->request->setUri($options['uri']);
            isset($options['uriParams'])
                && $this->request->setUriParams($options['uriParams']);
            isset($options['body'])
                && $this->request->setBody($options['body']);

            isset($options['options'])
                && $this->setOptions($options['options']);
        }
    }

    final public function __destruct()
    {
        $this->close();
    }

    final public function send(string $uri = null, array $uriParams = null, $body = null,
        array $headers = null, array $cookies = null): self
    {
        if ($uri) {
            $this->setRequestMethodAndUri($uri);
        }

        $this->request->setUriParams((array) $uriParams)
                      ->setHeaders((array) $headers)
                      ->setCookies((array) $cookies);

        try {
            $uri = $this->request->getUriFull();
            if ($uri == '') {
                throw new \Exception('I need an URL! :(');
            }

            $this->open();

            $options = $this->options + $this->optionsDefault;
            if (!isset($options[CURLOPT_URL])) {
                $options[CURLOPT_URL] = $uri;
            }

            if (!isset($options[CURLOPT_USERAGENT])) {
                $options[CURLOPT_USERAGENT] = 'ACurl/v'. self::VERSION .' (https://github.com/k-gun/acurl)';
            }

            $method = $this->request->getMethod();
            if ($method != Request::METHOD_GET && $method != Request::METHOD_POST) {
                $options[CURLOPT_HTTPHEADER][] = 'X-HTTP-Method-Override: '. $method;
            }

            if ($headers = $this->request->getHeaders()) {
                foreach ($headers as $key => $value) {
                    $options[CURLOPT_HTTPHEADER][] = $key .': '. $value;
                }
            }
            if ($cookies = $this->request->getCookies()) {
                $cookieArray = [];
                foreach ($cookies as $key => $value) {
                    $cookieArray[] = $key .'='. $value;
                }
                $options[CURLOPT_HTTPHEADER][] = 'Cookie: '. join('; ', $cookieArray);
            }

            curl_setopt_array($this->ch, $options);

            ob_start();
            $result =@ curl_exec($this->ch);
            $resultOutput = ob_get_clean();
            if (is_string($result)) {
                $resultOutput = $result;
            }

            if ($result === false) {
                $this->failCode = curl_errno($this->ch);
                $this->failText = curl_error($this->ch);
            } else {
                $this->info = curl_getinfo($this->ch);

                if (isset($this->info['request_header'])) {
                    $this->request->setHeaders($headers = Stream::parseHeaders($this->info['request_header'],
                        Stream::TYPE_REQUEST));
                    if (isset($headers['cookie'])) {
                        $this->request->setCookies(Stream::parseCookies($headers['cookie']));
                    }
                }

                if (!isset($options[CURLOPT_HEADER])) {
                    $resultOutput = "\r\n\r\n". $resultOutput;
                }

                @ list($headers, $body) = explode("\r\n\r\n", $resultOutput, 2);
                $this->response->setBody($body);

                $this->response->setHeaders($headers = Stream::parseHeaders($headers, Stream::TYPE_RESPONSE));
                if (isset($headers['set_cookie'])) {
                    $this->response->setCookies(Stream::parseCookies($headers['set_cookie']));
                }
            }
        } catch (\Throwable $e) {
            $this->failText = $e->getMessage();
        } finally {
            if ($this->autoClose) {
                $this->close();
            }
        }

        return $this;
    }

    final public function sendFunc(callable $func, string $uri = null, array $uriParams = null, $body = null,
        array $headers = null, array $cookies = null)
    {
        $this->send($uri, $uriParams, $body, $headers, $cookies);

        return $func($this->request, $this->response);
    }

    final public function get(string $uri = null, array $uriParams = null,
        array $headers = null, array $cookies = null): self
    {
        return $this->send(Request::METHOD_GET .' '. $uri, $uriParams, $headers, $cookies);
    }

    final public function getFunc(callable $func, string $uri = null, array $uriParams = null,
        array $headers = null, array $cookies = null)
    {
        return $this->sendFunc(Request::METHOD_GET .' '. $uri, $uriParams, $headers, $cookies, $func);
    }

    // ...

    final public function open()
    {
        $this->ch =@ curl_init();
        if (!is_resource($this->ch)) {
            throw new \RuntimeException('Could not initialize cURL session!');
        }
    }
    final public function close()
    {
        if (is_resource($this->ch)) {
            curl_close($this->ch);
            $this->ch = null;
        }
    }



    final private function setRequestMethodAndUri(string $input)
    {
        if (preg_match('~^(?P<method>\w+)\s+(?<uri>.+)~', $input, $matches)) {
            $this->request->setMethod($matches['method'])
                          ->setUri($matches['uri']);
        } else {
            $this->request->setMethod(Request::METHOD_GET)
                          ->setUri($input);
        }
    }

}
