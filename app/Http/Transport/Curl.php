<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 01.09.2018
 * Time: 19:03
 */

namespace App\Http\Transport;


use App\Http\Client;
use App\Http\Exception\TransportException;

class Curl implements TransportInterface
{
    private $handle;
    private $headers;
    private $streamHandle;
    private $responseData;
    private $responseBytes;
    private $options;

    public function __construct()
    {
        $this->handle = curl_init();

        curl_setopt_array($this->handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
        ]);

        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($this->handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            curl_setopt($this->handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        }
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
    }

    /**
     * @param string $url
     * @param array|null $data
     * @param array|null $headers
     * @param array|null $options
     * @return array
     * @throws TransportException
     */
    public function request(string $url, array $data = null, array $headers = null, array $options = null) :array
    {
        $this->options = $options;
        $this->reset();
        $this->setupHandle($url, $data, $headers, $options);

        curl_exec($this->handle);

        if (($errno = curl_errno($this->handle)) === CURLE_WRITE_ERROR || $errno === CURLE_BAD_CONTENT_ENCODING) {
            $this->reset();
            curl_setopt($this->handle, CURLOPT_ENCODING, 'none');
        }

        if (curl_errno($this->handle)) {
            $error = sprintf(
                'cURL error %s: %s',
                curl_errno($this->handle),
                curl_error($this->handle)
            );

            throw new TransportException($error);
        }

        curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, null);
        curl_setopt($this->handle, CURLOPT_WRITEFUNCTION, null);

        return [
            'headers' => $this->headers,
            'body' => $this->responseData
        ];
    }

    /**
     * @param string $url
     * @param array|null $data
     * @param array|null $headers
     * @param array|null $options
     */
    private function setupHandle(string $url, array $data = null, array $headers = null, array $options = null)
    {
        $curlOptions = [
            CURLOPT_URL => $url,
        ];

        if (isset($options['method'])) {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = $options['method'];
        }
        if (isset($options['timeout'])) {
            $curlOptions[CURLOPT_TIMEOUT_MS] = $options['timeout'];
        }
        if (isset($options['connectTimeout'])) {
            $curlOptions[CURLOPT_CONNECTTIMEOUT_MS] = $options['connectTimeout'];
        }
        if (isset($options['port'])) {
            $curlOptions[CURLOPT_PORT] = $options['port'];
        }
        if ($options['protocolVersion'] === 1.1) {
            $curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        } else {
            $curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
        }

        if ($options['blocking'] === true) {
            curl_setopt($this->handle, CURLOPT_HEADERFUNCTION, [$this, 'streamHeaders']);
            curl_setopt($this->handle, CURLOPT_WRITEFUNCTION, [$this, 'streamBody']);
            curl_setopt($this->handle, CURLOPT_BUFFERSIZE, Client::BUFFER_SIZE);
        }

        if (isset($options['verify'])) {
            if ($options['verify'] === false) {
                $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
                $curlOptions[CURLOPT_SSL_VERIFYPEER] = 0;
            } elseif (is_string($options['verify'])) {
                $curlOptions[CURLOPT_CAINFO] = $options['verify'];
            }
        }

        if (isset($options['verifyname']) && $options['verifyname'] === false) {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        if (!is_null($data)) {
            $curlOptions[CURLOPT_POSTFIELDS] = $data;
        }

        if (!is_null($headers)) {
            $curlOptions[CURLOPT_HEADER] = $headers;
        }

        curl_setopt_array($this->handle, $curlOptions);
    }

    /**
     * @param $handle
     * @param $headers
     * @return int
     */
    public function streamHeaders($handle, $headers)
    {
        $this->headers .= $headers;
        return strlen($headers);
    }

    /**
     * @param $handle
     * @param $data
     * @return int
     */
    public function streamBody($handle, $data)
    {
        $dataLength = strlen($data);
        if ($this->streamHandle) {
            fwrite($this->streamHandle, $data);
        } else {
            $this->responseData .= $data;
        }
        $this->responseBytes += strlen($data);
        return $dataLength;
    }

    /**
     * Reset state
     */
    private function reset()
    {
        $this->headers = '';
        $this->responseData = '';
        $this->responseBytes = 0;

        if ($this->options['filename'] !== false) {
            $this->streamHandle = fopen($this->options['filename'], 'wb');
        }
    }
}