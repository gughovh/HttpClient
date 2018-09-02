<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 1:00
 */

namespace App\Http;


use App\Http\Exception\HttpException;
use App\Http\Exception\ResponseException;
use App\Http\Response\Headers;

class Response
{
    const ENCODINNG_CHUNKED = 'chunked';

    private $url;
    private $headers;
    private $body;
    private $protocolVersion;
    private $statusCode;
    private $success = false;
    private $redirects = 0;
    private $before;
    private $error;

    public function __construct()
    {
        $this->headers = new Headers;
    }

    /**
     * @param string $url
     * @return Response
     */
    public function setUrl(string $url): Response
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl() :?string
    {
        return $this->url;
    }

    /**
     * @param mixed $headers
     * @return Response
     * @throws ResponseException
     */
    public function setHeaders($headers) :Response
    {
        if (is_string($headers)) {
            $headers = str_replace("\r\n", "\n", $headers);
            $headers = preg_replace('/\n[ \t]/', ' ', $headers);
            $headers = explode("\n", $headers);

            preg_match('#^HTTP/(1\.\d)[ \t]+(\d+)#i', array_shift($headers), $matches);
            if (empty($matches)) {
                throw new ResponseException('Response could not be parsed');
            }

            $this->protocolVersion = (float) $matches[1];
            $this->statusCode = (int) $matches[2];

            if ($this->statusCode >= 200 && $this->statusCode < 300) {
                $this->success = true;
            }
        }

        if (is_array($headers)) {
            foreach ($headers as $header) {
                list($key, $value) = explode(':', $header, 2);

                $key = trim($key);

                if (empty($key)) {
                    continue;
                }

                $value = trim($value);
                preg_replace('#(\s+)#i', ' ', $value);
                $this->headers->add($key, $value);
            }
        }

        return $this;
    }

    /**
     * @return Headers
     */
    public function getHeaders() :Headers
    {
        return $this->headers;
    }

    /**
     * @param mixed $body
     * @return Response
     */
    public function setBody(string $body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBody() :?string
    {
        return $this->body;
    }

    /**
     * @param int $redirects
     * @return Response
     */
    public function setRedirects(int $redirects): Response
    {
        $this->redirects = $redirects;
        return $this;
    }

    /**
     * @return int
     */
    public function getRedirects(): int
    {
        return $this->redirects;
    }

    /**
     * @return bool
     */
    public function isRedirect() :bool
    {
        $code = $this->statusCode;
        return in_array($code, array(300, 301, 302, 303, 307)) || $code > 307 && $code < 400;
    }

    /**
     * @return mixed
     */
    public function getProtocolVersion() :?float
    {
        return $this->protocolVersion;
    }

    /**
     * @return mixed
     */
    public function getStatusCode() :?int
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $before
     * @return Response
     */
    public function setBefore(Response $before) :Response
    {
        $this->before = $before;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBefore() :?Response
    {
        return $this->before;
    }

    /**
     * @param HttpException $error
     * @return $this
     */
    public function setError(HttpException $error) :Response
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getError() :?HttpException
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function hasError() :bool
    {
        return $this->error instanceof HttpException;
    }
}