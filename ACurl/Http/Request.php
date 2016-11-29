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
 * @package ACurl
 * @object  ACurl\Request
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Request extends Stream
{
    /**
     * Methods.
     * @const string
     */
    const METHOD_GET        = 'GET',
          METHOD_POST       = 'POST',
          METHOD_PUT        = 'PUT',
          METHOD_PATCH      = 'PATCH',
          METHOD_DELETE     = 'DELETE',
          METHOD_OPTIONS    = 'OPTIONS',
          METHOD_HEAD       = 'HEAD',
          METHOD_TRACE      = 'TRACE',
          METHOD_CONNECT    = 'CONNECT',
          // non-standard
          METHOD_COPY       = 'COPY',
          METHOD_MOVE       = 'MOVE';

    /**
     * Method.
     * @var string
     */
    private $method;

    /**
     * URI.
     * @var string
     */
    private $uri;

    /**
     * Method.
     * @var array
     */
    private $uriParams = [];

    /**
     * Constructor.
     */
    final public function __construct()
    {
        $this->type = StreamInterface::TYPE_REQUEST;
    }

    /**
     * Set method.
     * @param string $method
     */
    final public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Get method.
     * @return string
     */
    final public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Sert URI.
     * @param  string $uri
     * @return self
     */
    final public function setUri(string $uri): self
    {
        if (!preg_match('~^[\w]+://~', $uri)) {
            $uri = 'http://'. $uri;
        }
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get URI.
     * @return string
     */
    final public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get full URI.
     * @return string
     */
    final public function getUriFull(): string
    {
        $uri = $this->uri;
        if ($this->uriParams) {
            $uri = $uri .'?'. http_build_query($this->uriParams);
        }

        return $uri;
    }

    /**
     * Set URI params.
     * @param  array $uriParams
     * @return self
     */
    final public function setUriParams(array $uriParams): self
    {
        $this->uriParams = array_merge($this->uriParams, $uriParams);

        return $this;
    }

    /**
     * Get URI param.
     * @param  string $key
     * @return any|null
     */
    final public function getUriParam(string $key)
    {
        return $this->uriParams[$key] ?? null;
    }

    /**
     * Get URI params.
     * @return array
     */
    final public function getUriParams(): array
    {
        return $this->uriParams;
    }
}
