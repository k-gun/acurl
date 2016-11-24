<?php
declare(strict_types=1);

namespace ACurl\Http;

abstract class Stream implements StreamInterface
{
    protected $type;
    protected $body;
    protected $headers = [];
    protected $cookies = [];
    protected $failCode = 0,
              $failtext = '';

    final public function getType(): int
    {
        return $this->type;
    }

    final public function getBody()
    {
        return $this->body;
    }

    final public function getHeader(string $key, $valueDefault = null)
    {
        return $this->headers[$key] ?? $valueDefault;
    }

    final public function getHeaders(): array
    {
        return $this->headers;
    }

    final public function setHeader(string $key, $value): StreamInterface
    {
        $this->headers[$key] = trim((string) $value);
        return $this;
    }

    final public function setHeaders(array $headers): StreamInterface
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
        return $this;
    }

    final public function getCookie(string $key, $valueDefault = null)
    {
        return $this->cookies[$key] ?? $valueDefault;
    }

    final public function getCookies(): array
    {
        return $this->cookies;
    }

    final public function setCookie(string $key, $value): StreamInterface
    {
        $this->cookies[$key] = trim((string) $value);
        return $this;
    }

    final public function setCookies(array $cookies): StreamInterface
    {
        foreach ($cookies as $key => $value) {
            $this->setCookie($key, $value);
        }
        return $this;
    }

    final public function setBody($body): StreamInterface
    {
        if (is_array($body) || is_object($body)) {
            $body = http_build_query((array) $body);
        }
        $this->body = $body;
        return $this;
    }

    final public function toString(): string
    {}

    final public static function parseHeaders($headers, int $type): array
    {
        $return = [];
        // could be array (internally used)
        if (is_string($headers)) {
            (array) $headers =@ explode("\r\n", trim($headers));
        }

        // if we have headers
        if (!empty($headers)) {
            // set response status stuff
            if ($type == StreamInterface::TYPE_RESPONSE
                // HTTP/1.1 200 OK
                && preg_match('~^HTTP/\d\.\d\s+(\d+)\s+([\w- ]+)~i', array_shift($headers), $matches)
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
                @ list($key, $value) = explode(':', $header, 2);
                if ($key !== null) {
                    $key = self::headerKeyToSnakeCase($key);
                    $value = trim((string) $value);
                    // handle multi-headers as array
                    if (array_key_exists($key, $return)) {
                        $return[$key] = array_merge((array) $return[$key], [$value]);
                        continue;
                    }
                    $return[$key] = $value;
                }
            }

            ksort($return);
        }

        return $return;
    }

    final public static function parseCookies($cookies): array
    {
        $return = [];
        if (is_string($cookies)) {
            $cookies = array_slice(explode(';', $cookies, 2), 0, 1);
        }

        if (!empty($cookies)) {
            foreach ($cookies as $cookie) {
                @ list($key, $value) = explode('=', $cookie, 2);
                if ($key !== null) {
                    $return[$key] = trim((string) $value);
                }
            }

            ksort($return);
        }

        return $return;
    }

    final public static function headerKeyToSnakeCase(string $key): string
    {
        return preg_replace(['~\s+~', '~[\s-]+~'], [' ', '_'], strtolower($key));
    }

    final public static function headerKeyToDashCase(string $key): string
    {
        return preg_replace_callback('~_(\w)~', function($matches) {
            return '-'. ucfirst($matches[1]);
        }, ucfirst($key));
    }
}
