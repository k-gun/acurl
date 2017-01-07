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

use ACurl\Http\Request;
use ACurl\Http\Response;

/**
 * @package ACurl
 * @object  ACurl\ClientBase
 * @author  Kerem Güneş <k-gun@mail.com>
 */
abstract class ClientBase
{
    /**
     * Version.
     * @const string
     */
    const VERSION = '2.2.0';

    /**
     * Request.
     * @var ACurl\Http\Request
     */
    protected $request;

    /**
     * Response.
     * @var ACurl\Http\Response
     */
    protected $response;

    /**
     * Options.
     * @var array
     */
    protected $options = [];

    /**
     * Options default.
     * @var array
     */
    protected $optionsDefault = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        // required for a proper response body and headers
        CURLOPT_HTTPHEADER     => ['Expect:'],
        CURLINFO_HEADER_OUT    => true,
    ];

    /**
     * Autoclose.
     * @var bool
     */
    protected $autoClose = true;

    /**
     * cURL handler.
     * @var resource
     */
    protected $ch;

    /**
     * Info
     * @var array|null
     */
    protected $info;

    /**
     * Set magic.
     * @param string $key
     * @param any    $value
     * @return void
     * @throws \Exception
     */
    final public function __set(string $key, $value)
    {
        switch ($key) {
            case 'autoClose':
                $this->{$key} = $value;
                break;
            case property_exists($this, $key):
                throw new \Exception("Cannot access protected property '{$key}'!");
            default:
                throw new \Exception("'{$key}' property doesn't exists on this object!");
        }
    }

    /**
     * Get magic.
     * @param  string $key
     * @return any
     * @throws \Exception
     */
    final public function __get(string $key)
    {
        switch ($key) {
            case 'request':
            case 'response':
                return $this->{$key};
            case property_exists($this, $key):
                throw new \Exception("Cannot access protected property '{$key}'!");
            default:
                throw new \Exception("'{$key}' property doesn't exists on this object!");
        }
    }

    /**
     * Get request.
     * @return ACurl\Http\Request
     */
    final public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get response.
     * @return ACurl\Http\Response
     */
    final public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Set option.
     * @param  int|string $key
     * @param  any        $value
     * @return self
     * @throws \InvalidArgumentException
     */
    final public function setOption($key, $value): self
    {
        if (is_string($key)) {
            // add 'curlopt_' prefix & get constant value
            $keyValue =@ constant('CURLOPT_'. strtoupper($key));
            if ($keyValue === null) {
                throw new \InvalidArgumentException(
                    "Invalid key '{$key}' given! ".
                    "Pass all string option keys without 'curlopt_' prefix and ".
                    "see for available options here: http://php.net/curl_setopt."
                );
            }
            $key = $keyValue;
        }

        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Set options.
     * @param  array $options
     * @return self
     */
    final public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }

        return $this;
    }

    /**
     * Get optionç
     * @param  int|string $key
     * @param  any        $valueDefault
     * @return any
     */
    final public function getOption($key, $valueDefault = null)
    {
        if (is_string($key)) {
            // add 'curlopt_' prefix & get constant value
            $keyValue =@ constant('CURLOPT_'. strtoupper($key));
            if ($keyValue === null) {
                throw new \InvalidArgumentException(
                    "Invalid key '{$key}' given! ".
                    "Pass all string option keys without 'curlopt_' prefix and ".
                    "see for available options here: http://php.net/curl_setopt."
                );
            }
            $key = $keyValue;
        }

        return $this->options[$key] ?? $valueDefault;
    }

    /**
     * Get options.
     * @return array
     */
    final public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set auto close.
     * @param  bool $autoClose
     * @return self
     */
    final public function setAutoClose(bool $autoClose): self
    {
        $this->autoClose = $autoClose;

        return $this;
    }

    /**
     * Get auto close.
     * @return bool
     */
    final public function getAutoClose(): bool
    {
        return $this->autoClose;
    }

    /**
     * Get info.
     * @return array|null
     */
    final public function getInfo()
    {
        return $this->info;
    }

    /**
     * Get info value.
     * @param  string $key
     * @param  any    $valueDefault
     * @return any
     */
    final public function getInfoValue(string $key, $valueDefault = null)
    {
        return $this->info[$key] ?? $valueDefault;
    }
}
