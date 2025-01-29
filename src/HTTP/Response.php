<?php

declare(strict_types=1);

namespace Excalibur\HTTP;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected mixed $content = '';
    protected string $contentType = 'text/html';

    public function __construct(mixed $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function json(mixed $data, int $status = 200): self
    {
        $this->content = $data;
        $this->statusCode = $status;
        $this->contentType = 'application/json';
        return $this;
    }

    public function send(): never
    {
        if (!headers_sent()) {
            // Set status code
            http_response_code($this->statusCode);

            // Set content type
            header('Content-Type: ' . $this->contentType);

            // Set custom headers
            foreach ($this->headers as $key => $value) {
                header("$key: $value");
            }
        }

        if (is_string($this->content) || is_numeric($this->content)) {
            echo $this->content;
        } elseif ($this->contentType === 'application/json') {
            echo json_encode($this->content);
        }

        exit;
    }

    public static function make(mixed $content = '', int $status = 200, array $headers = []): self
    {
        return new static($content, $status, $headers);
    }
} 