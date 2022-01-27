<?php

namespace Lightpack\Http;

class Response
{
    /**
     * Represents HTTP Content-Type
     *
     * @var string
     */
    private $type;

    /**
     * Represents HTTTP response body
     *
     * @var string
     */
    private $body;

    /**
     * Represents HTTP response status code
     *
     * @var int
     */
    private $code;

    /**
     * Represents HTTP response status message
     *
     * @var string
     */
    private $message;

    /**
     * Represents HTTP response headers.
     * 
     * @var array
     */
    private $headers;

    /**
     * Class contructor
     *
     * @access  public
     */
    public function __construct()
    {
        $this->code    = 200;
        $this->type    = 'text/html';
        $this->message = 'OK';
        $this->headers = [];
        $this->body    = '';
    }

    /**
     * Return HTTP response status code.
     *
     * @access  public
     * @return int  $code
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Return HTTP response content type.
     *
     * @access  public
     * @return string  $type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Return HTTP response status message.
     *
     * @access  public
     * @return string  $message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Return HTTP response headers.
     *
     * @access  public
     * @return array  $headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Return HTTP response body.
     *
     * @access  public
     * @return string  $body
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * This method sets the HTTP response code.
     *
     * @access  public
     * @param  int  $code 
     * @return  self
     */
    public function setCode(int $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * This method sets a response header.
     *
     * @access  public
     * @param  string  $name  
     * @param  string  $value  
     * @return  self
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * This method sets multiple response headers.
     *
     * @access  public
     * @param  array  $headers  An array of $name => $value header pairs.
     * @return  self
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * This method sets the HTTP response message supplied by the client.
     *
     * @access  public
     * @param  string  $message 
     * @return  self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * This method sets the HTTP response content type.
     *
     * @access  public
     * @param  string  $message 
     * @return  self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * This method sets the HTTP response content.
     *
     * @access  public
     * @param  string  $body
     * @return  self
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * This method sets the HTTP response content as JSON.
     * 
     * @access  public
     * @param  mixed  $data
     * @return  self
     */
    public function json($data): self
    {
        $json = json_encode($data);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed encoding JSON content: ' . json_last_error_msg());
        }

        $this->setType('application/json');
        $this->setBody($json);

        return $this;
    }

    /**
     * This method sets the HTTP response content as JSON.
     * 
     * @access  public
     * @param  string  $data    XML formatted string.
     * @return  self
     */
    public function xml(string $data): self
    {
        $this->setType('text/xml');
        $this->setBody($data);
        return $this;
    }

    /**
     * This method sends a download response to the client.
     *
     * @param string $path  The path of file to download.
     * @param string $name  Custom name for downloaded file.
     * @param array $headers  Additional headers for download response.
     * @return void
     */
    public function download(string $path, string $name = null, array $headers = [])
    {
        $name = $name ?? basename($path);

        $headers = array_merge([
            'Content-Type'              => MimeTypes::getMime($path),
            'Content-Disposition'       => 'attachment; filename="' . $name . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => 0,
            'Cache-Control'             => 'private',
            'Pragma'                    => 'private',
            'Content-Length'            => filesize($path),
        ], $headers);

        $this->setBody(file_get_contents($path));
        $this->setHeaders($headers);
        $this->send();
    }

    /**
     * This method display a file directly in the browser instead of downloading.
     *
     * @param string $path  The path of file to download.
     * @param string $name  Custom name for downloaded file.
     * @param array $headers  Additional headers for download response.
     * @return void
     */
    public function file(string $path, string $name = null, array $headers = [])
    {
        $name = $name ?? basename($path);

        $headers = array_merge([
            'Content-Disposition' => 'inline; filename=' . $name
        ], $headers);

        $this->download($path, $name, $headers);
    }

    /**
     * This method sets the HTTP response content as HTML.
     * 
     * @access  public
     * @param  string  $data    XML formatted string.
     * @return  self
     */
    public function render(string $file, array $data = []): self
    {
        $template = app('template')->setData($data)->render($file);

        $this->setBody($template);
        return $this;
    }

    /**
     * This method sends the response to the client.
     * 
     * @access  public
     * @return  void
     */
    public function send(): void
    {
        if (!headers_sent()) {
            $this->sendHeaders();
        }

        $this->sendContent();
    }

    /**
     * This method sends all HTTP headers.
     *
     * @access  private
     * @return  void
     */
    private function sendHeaders(): void
    {
        header(sprintf("HTTP/1.1 %s %s", $this->code, $this->message));
        header(sprintf("Content-Type: %s; charset=UTF-8", $this->type));

        foreach ($this->headers as $name => $value) {
            header(sprintf("%s: %s", $name, $value), true, $this->getCode());
        }
    }

    /**
     * This method outputs the HTTP response body.
     *
     * @access  private
     * @return  void
     */
    private function sendContent(): void
    {
        echo $this->body;
    }
}
