<?php
declare(strict_types=1);

namespace ACurl;
use ACurl\Http\Request;
use ACurl\Http\Response;

// client icine tasi en sonra isterse?
abstract class ClientBase
{
    const VERSION = '2.0.0';

    protected $request;
    protected $response;
    protected $options = [];
    protected $optionsDefault = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        // required for a proper response body and headers
        CURLOPT_HTTPHEADER     => ['Expect:'],
        CURLINFO_HEADER_OUT    => true,
    ];
    protected $autoClose = true;

    protected $ch;
    protected $info;

    protected $failCode = 0,
              $failText = '';

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

    final public function getRequest(): Request
    {
        return $this->request;
    }
    final public function getResponse(): Response
    {
        return $this->response;
    }

    final public function setOption(int $key, $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }

    final public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
        return $this;
    }

    final public function getOption($key, $valueDefault = null)
    {
        return $this->options[$key] ?? $valueDefault;
    }

    final public function getOptions(): array
    {
        return $this->options;
    }

    final public function setAutoClose(bool $autoClose): self
    {
        $this->autoClose = $autoClose;
        return $this;
    }

    final public function getAutoClose(): bool
    {
        return $this->autoClose;
    }

    final public function getInfo(string $key = null, $valueDefault = null)
    {
        if ($key === null) {
            return $this->info;
        }
        return $this->info[$key] ?? $valueDefault;
    }

    final public function getFailCode(): int
    {
        return $this->failCode;
    }

    final public function getFailText(): string
    {
        return $this->failText;
    }
}
