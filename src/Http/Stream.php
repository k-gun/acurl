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

namespace ACurl\Http;

/**
 * @package    ACurl
 * @subpackage ACurl\Http
 * @object     ACurl\Http\Stream
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Stream implements StreamInterface
{
    /**
     * Type.
     * @var string
     */
    protected $type;

    /**
     * Body.
     * @var string
     */
    protected $body;

    /**
     * Headers.
     * @var array
     */
    protected $headers = [];

    /**
     * Cookies.
     * @var array
     */
    protected $cookies = [];

    /**
     * Run.
     * @var bool
     */
    protected $run = false;

    /**
     * String magic.
     * @return string
     */
    final public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Get type.
     * @return int
     */
    final public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set body
     * @param  string|array|object $body
     * @return ACurl\StreamInterface
     */
    final public function setBody($body): StreamInterface
    {
        if (is_array($body) || is_object($body)) {
            $body = http_build_query((array) $body);
        }

        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     * @return string|null
     */
    final public function getBody()
    {
        return $this->body;
    }

    /**
     * Set header.
     * @param  string          $key
     * @param  string|int|null $value
     * @return ACurl\StreamInterface
     */
    final public function setHeader(string $key, $value): StreamInterface
    {
        if (is_array($value)) {
            if (!isset($this->headers[$key])) {
                $this->headers[$key] = [];
            }
            $this->headers[$key] = array_map('trim', $value);
        } else {
            $this->headers[$key] = trim((string) $value);
        }

        return $this;
    }

    /**
     * Set headers.
     * @param  array $headers
     * @return ACurl\StreamInterface
     */
    final public function setHeaders(array $headers): StreamInterface
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }

        return $this;
    }

    /**
     * Get header.
     * @param  string   $key
     * @param  any|null $valueDefault
     * @return any|null
     */
    final public function getHeader(string $key, $valueDefault = null)
    {
        $value = $this->headers[$key] ?? $valueDefault;
        if ($value === null) {
            $value = $this->headers[self::headerKeyToSnakeCase($key)] ?? $valueDefault;
        }

        return $value;
    }

    /**
     * Get headers.
     * @return array
     */
    final public function getHeaders(): array
    {
        if (!$this->run) {
            return $this->headers;
        }

        $headers = [];
        if ($this->type == StreamInterface::TYPE_REQUEST) {
            $found = false;
            foreach ($this->headers as $key => $value) {
                if (!$found && $key == '_') {
                    $found = true;
                }
                if ($found) {
                    $headers[$key] = $value;
                }
            }
        } elseif ($this->type == StreamInterface::TYPE_RESPONSE) {
            $headers = $this->headers;
        }

        return $headers;
    }

    /**
     * Get headers string.
     * @return string
     */
    final public function getHeadersString(): string
    {
        $return = '';
        if (!empty($this->headers)) {
            foreach ($this->getHeaders() as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $return .= sprintf("%s: %s\n", self::headerKeyToDashCase($key), $v);
                    }
                } elseif ($key[0] != '_') {
                    $return .= sprintf("%s: %s\n", self::headerKeyToDashCase($key), $value);
                }
            }
        }

        return $return;
    }

    /**
     * Get raw headers.
     * @return string
     */
    final public function getHeadersRaw(): string
    {
        $return = '';
        foreach ($this->getHeaders() as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $return .= sprintf("%s: %s\n", self::headerKeyToDashCase($key), $v);
                }
            } elseif ($key == '_') {
                $return .= sprintf("%s\n", $value);
            } elseif ($key[0] != '_') {
                $return .= sprintf("%s: %s\n", self::headerKeyToDashCase($key), $value);
            }
        }

        return $return;
    }

    /**
     * Set cookie.
     * @param  string          $key
     * @param  string|int|null $value
     * @return ACurl\StreamInterface
     */
    final public function setCookie(string $key, $value): StreamInterface
    {
        $this->cookies[$key] = trim((string) $value);

        return $this;
    }

    /**
     * Set cookies.
     * @param  array $cookies
     * @return ACurl\StreamInterface
     */
    final public function setCookies(array $cookies): StreamInterface
    {
        foreach ($cookies as $key => $value) {
            $this->setCookie($key, $value);
        }

        return $this;
    }

    /**
     * Get cookie.
     * @param  string   $key
     * @param  any|null $valueDefault
     * @return any|null
     */
    final public function getCookie(string $key, $valueDefault = null)
    {
        return $this->cookies[$key] ?? $valueDefault;
    }

    /**
     * Get cookies.
     * @return array
     */
    final public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Get cookies string.
     * @return string
     */
    final public function getCookiesString(): string
    {
        $return = '';
        if (!empty($this->cookies)) {
            $cookies = [];
            foreach ($this->cookies as $key => $value) {
                $cookies[] = $key .'='. $value;
            }
            $return = join('; ', $cookies);
        }

        return $return;
    }

    /**
     * To string.
     * @return string
     */
    final public function toString(): string
    {
        $return  = trim($this->getHeadersRaw());
        $return .= "\r\n\r\n";
        $return .= $this->getBody();

        return $return;
    }

    /**
     * Set run.
     * @param  bool $run
     * @return void
     */
    final public function setRun(bool $run)
    {
        $this->run = $run;
    }

    /**
     * Parse headers.
     * @param  string $headers
     * @param  int    $type
     * @return array
     */
    final public static function parseHeaders($headers, int $type): array
    {
        $return = [];

        // could be array (internally used)
        if (is_string($headers)) {
            (array) $headers =@ explode("\r\n", trim($headers));
        }

        // if we have headers
        if (!empty($headers)) {
            $headersFirst = array_shift($headers);
            if ($type == StreamInterface::TYPE_REQUEST
                // GET / HTTP/1.1
                && preg_match('~^([A-Z]+)\s+(.+)\s+HTTP/\d\.\d~', $headersFirst, $matches)) {
                $return['_']            = $matches[0];
            } elseif ($type == StreamInterface::TYPE_RESPONSE
                // HTTP/1.1 200 OK
                && preg_match('~^HTTP/\d\.\d\s+(\d+)\s+([\w- ]+)~i', $headersFirst, $matches)) {
                $statusCode = (int) $matches[1];
                $statusText = preg_replace_callback('~(\w[^ ]+)~', function($m) {
                    // make an expected status text
                    return ($m[1] == 'OK') ? 'OK' : mb_convert_case($m[1], MB_CASE_TITLE);
                }, trim($matches[2]));
                $return['_']            = $matches[0];
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

    /**
     * Parse cookies.
     * @param  string $cookies
     * @return array
     */
    final public static function parseCookies($cookies): array
    {
        $return = [];

        if (is_string($cookies)) {
            $cookies = (array) explode(';', $cookies, 2)[0];
        } elseif (is_array($cookies)) {
            foreach ($cookies as $i => $cookie) {
                $cookies[$i] = explode(';', $cookie, 2)[0];
            }
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

    /**
     * Header key to snakecase.
     * @param  string $key
     * @return string
     */
    final public static function headerKeyToSnakeCase(string $key): string
    {
        return preg_replace(['~\s+~', '~[\s-]+~'], [' ', '_'], strtolower($key));
    }

    /**
     * Header key to dashcase.
     * @param  string $key
     * @return string
     */
    final public static function headerKeyToDashCase(string $key): string
    {
        return preg_replace_callback('~_(\w)~', function($matches) {
            return '-'. ucfirst($matches[1]);
        }, ucfirst($key));
    }
}
