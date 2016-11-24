<?php
declare(strict_types=1);

namespace ACurl\Http;

final class Request extends Stream
{
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

    private $method;
    private $uri;
    private $uriParams = [];

    final public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    final public function getMethod(): string
    {
        return $this->method;
    }

    final public function setUri(string $uri): self
    {
        if (!preg_match('~^[\w]+://~', $uri)) {
            $uri = 'http://'. $uri;
        }
        $this->uri = $uri;
        return $this;
    }

    final public function getUri(): string
    {
        return $this->uri;
    }

    final public function getUriFull(): string
    {
        $uri = $this->uri;
        if ($this->uriParams) {
            $uri = $uri .'?'. http_build_query($this->uriParams);
        }
        return $uri;
    }

    final public function setUriParams(array $uriParams): self
    {
        $this->uriParams = array_merge($this->uriParams, $uriParams);
        return $this;
    }

    final public function getUriParam(string $key)
    {
        return $this->uriParams[$key] ?? null;
    }

    final public function getUriParams(): array
    {
        return $this->uriParams;
    }
}
