<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 31.08.2018
 * Time: 22:16
 */

namespace App\Http;


use App\Http\Client\Config;
use App\Http\Client\Hook;
use App\Http\Client\Hook\DefaultHook;
use App\Http\Exception\ConfigException;
use App\Http\Exception\HttpException;
use App\Http\Exception\RequestException;
use App\Http\Exception\ResponseException;
use function App\Http\Helper\configs;
use App\Http\Helper\Log;
use App\Http\Transport\Curl;
use App\Http\Transport\TransportInterface;

class Client
{
    /**
     * HTTP methods
     */
    const GET = 'GET';
    const POST = 'POST';
    const DELETE = 'DELETE';
    const PUT = 'PUT';

    const BUFFER_SIZE = 1024;

    // hooks
    const HOOK_BEFORE_REQUEST = 'before_request';
    const HOOK_AFTER_REQUEST = 'after_request';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var float
     */
    private $requestTime;

    /**
     * @var HttpException
     */
    private $error;

    /**
     * @var array
     */
    private $hooks = [];

    public function __construct(Config $config)
    {
        $this->setConfig($config);
        $hook = new DefaultHook;
        $hook->register(self::HOOK_AFTER_REQUEST, function (Client $client) {
            if ($client->getRequestTime() >= $client->getConfig()->getThreshold()) {
                $config = $client->getConfig();
                Log::debug(join("\t", [
                    "address: {$config->getAddress()}",
                    "port: {$config->getPort()}",
                    "threshold: {$config->getThreshold()}",
                    "requestTime: {$client->getRequestTime()}",
                    "timeout: {$config->getTimeout()}",
                    "error: {}"
                ]));
            }
        });
        $this->hooks[] = $hook;
    }

    /**
     * @param string $url
     * @param array|null $headers
     * @param array|null $options
     * @return Response
     */
    public function get(string $url = null, array $headers = null, array $options = [])
    {
        return $this->request($url, null, $headers, self::GET, $options);
    }

    /**
     * @param string $url
     * @param array|null $data
     * @param array|null $headers
     * @param array|null $options
     * @return Response
     */
    public function post(string $url = null, array $data = null, array $headers = null, array $options = [])
    {
        return $this->request($url, $data, $headers, self::POST, $options);
    }

    /**
     * @param string $url
     * @param array|null $headers
     * @param array|null $options
     * @return Response
     */
    public function delete(string $url = null, array $headers = null, array $options = [])
    {
        return $this->request($url, null, $headers, self::DELETE, $options);
    }

    /**
     * @param string $url
     * @param array|null $data
     * @param array|null $headers
     * @param array|null $options
     * @return Response
     */
    public function update(string $url = null, array $data = null, array $headers = null, array $options = [])
    {
        return $this->request($url, $data, $headers, self::PUT, $options);
    }

    /**
     * @param string $url
     * @param array|null $data
     * @param array|null $headers
     * @param string $method
     * @param array|null $options
     * @return Response
     */
    private function request(
        string $url = null,
        array $data = null,
        array $headers = null,
        string $method = self::GET,
        array $options = []
    ) {
        $this->error = null;
        $config = $this->getConfig();
        $options = array_merge(
            configs()['client'],
            [
                'timeout' => $config->getTimeout(),
                'connectTimeout' => $config->getConnectTimeout(),
                'port' => $config->getPort(),
            ] ,
            $options
        );
        $options['method'] = $method;
        $transport = $options['transport'];

        if (!($transport instanceof TransportInterface)) {
            $transport = $this->getDefaultTransport();
        }

        $url = rtrim($config->getAddress(), '/').'/'.ltrim($url, '/');

        if (isset($options['query']) && is_array($options['query'])) {
            $url = $url.'?'.http_build_query($options['query']);
        }

        try {
            /** @var Hook $hook */
            foreach ($this->hooks as $hook) {
                $hook->dispatch(self::HOOK_BEFORE_REQUEST, [$this]);
            }
            $time = microtime(true);
            $responseData  = $transport->request($url, $data, $headers, $options);
            $this->requestTime = (microtime(true) - $time) * 1000;

            /** @var Hook $hook */
            foreach ($this->hooks as $hook) {
                $hook->dispatch(self::HOOK_AFTER_REQUEST, [$this]);
            }

            return $this->buildResponse($responseData, $url, $data, $options, $headers);
        } catch (HttpException $e) {
            $this->error = $e;
            Log::error($e);

            return new Response;
        }
    }

    /**
     * @param Config $config
     * @return Client
     */
    public function setConfig(Config $config): Client
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->error instanceof HttpException;
    }

    /**
     * @return mixed
     */
    public function getRequestTime()
    {
        return $this->requestTime;
    }

    /**
     * @param mixed $hook
     * @return Client
     */
    public function addHook($hook)
    {
        $this->hooks[] = $hook;
        return $this;
    }

    /**
     * @return HttpException|null
     */
    public function getError() :?HttpException
    {
        return $this->error;
    }

    /**
     * @return TransportInterface
     */
    private function getDefaultTransport()
    {
        return new Curl;
    }

    /**
     * @param array $resData
     * @param string $url
     * @param array $data
     * @param array $options
     * @param array|null $headers
     * @return Response
     * @throws ConfigException
     * @throws ResponseException
     * @throws RequestException
     */
    private function buildResponse(
        array $resData,
        string $url,
        array $data = null,
        array $options,
        array $headers = null
    ) :Response {
        $response = new Response;

        if ($this->hasError()) {
            $response->setError($this->error);
        }

        if (!$options['blocking']) {
            return $response;
        }

        $response->setHeaders($resData['headers'])
            ->setUrl($url);

        if ($response->getHeaders()->get('transfer-encoding', false) === Response::ENCODINNG_CHUNKED) {
            $resData['body'] = $this->decodeChunked($resData['body']);
        }

        if ($ce = $response->getHeaders()->get('content-encoding')) {
            $resData['body'] = $this->decompress($resData['body'], $ce);
        }

        $response->setBody($resData['body']);

        if ($response->isRedirect() && $options['redirects'] > 0) {

            if (($location = $response->getHeaders()->get('location', false))
                && $response->getRedirects() < $options['redirects']
            ) {

                if ($response->getStatusCode() === 303) {
                    $options['method'] = self::GET;
                }

                $response->setRedirects($response->getRedirects() + 1);

                $this->getConfig()->setAddress($location);
                return $this->request(null, $headers, $data, $options['method'], $options)
                    ->setBefore($response);
            } elseif ($response->getRedirects() >= $options['redirects']) {
                throw new RequestException('Too many redirects');
            }
        }

        return $response;
    }

    /**
     * @param string $body
     * @return string
     */
    private function decodeChunked(string $body)
    {
        if (!preg_match('/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i', trim($body))) {
            return $body;
        }

        $decoded = '';

        while (true) {
            $isChunked = (bool) preg_match(
                '/^([0-9a-f]+)(?:;(?:[\w-]*)(?:=(?:(?:[\w-]*)*|"(?:[^\r\n])*"))?)*\r\n/i',
                $body,
                $matches
            );

            if (!$isChunked) {
                return $body;
            }

            $length = hexdec(trim($matches[1]));

            if ($length === 0) {
                return $decoded;
            }

            $chunkLength = strlen($matches[0]);
            $decoded .= substr($body, $chunkLength, $length);
            $encoded = substr($body, $chunkLength + $length + 2);

            if (trim($encoded) === '0' || empty($encoded)) {
                return $decoded;
            }
        }

        throw new \LogicException('Chunked decode error.');
    }

    /**
     * @param string $body
     * @param array $contentEncodings
     * @return bool|string
     */
    private function decompress(string $body, array $contentEncodings)
    {
        $contentEncodings = array_flip($contentEncodings);

        if (isset($contentEncodings['gzip'])
            && function_exists('gzdecode')
            && ($decoded = @gzdecode($body)) !== false
        ) {
            return $decoded;
        }

        if (isset($contentEncodings['deflate'])
            && function_exists('gzinflate')
            && ($decoded = @gzinflate($body)) !== false
        ) {
            return $decoded;
        }

        if (($decoded = $this->compatibleGzInflate($body))) {
            return $decoded;
        }

        return $body;
    }

    /**
     * @param string $body
     * @return bool|string
     */
    private function compatibleGzInflate(string $body)
    {
        // Compressed data might contain a full zlib header, if so strip it for gzinflate()
        if (substr($body, 0, 3) == "\x1f\x8b\x08") {
            $i = 10;
            $flg = ord(substr($body, 3, 1));

            if ($flg > 0) {
                if ($flg & 4) {
                    list($xlen) = unpack('v', substr($body, $i, 2));
                    $i = $i + 2 + $xlen;
                }
                if ($flg & 8) {
                    $i = strpos($body, "\0", $i) + 1;
                }
                if ($flg & 16) {
                    $i = strpos($body, "\0", $i) + 1;
                }
                if ($flg & 2) {
                    $i = $i + 2;
                }
            }

            $decompressed = $this->compatibleGzInflate(substr($body, $i));

            if (false !== $decompressed) {
                return $decompressed;
            }
        }

        // If the data is Huffman Encoded, we must first strip the leading 2
        // byte Huffman marker for gzinflate()
        // See https://decompres.blogspot.com/ for a quick explanation of this data type
        $huffmanEncoded = false;
        // low nibble of first byte should be 0x08
        list(, $firstNibble)    = unpack('h', $body);
        // First 2 bytes should be divisible by 0x1F
        list(, $firstTwoBytes) = unpack('n', $body);

        if (0x08 == $firstNibble && 0 == ($firstTwoBytes % 0x1F)) {
            $huffmanEncoded = true;
        }

        if ($huffmanEncoded) {
            if (false !== ($decompressed = @gzinflate(substr($body, 2)))) {
                return $decompressed;
            }
        }

        if (substr($body, 0, 4) == "\x50\x4b\x03\x04") {
            // ZIP file format header
            // Offset 6: 2 bytes, General-purpose field
            // Offset 26: 2 bytes, filename length
            // Offset 28: 2 bytes, optional field length
            // Offset 30: Filename field, followed by optional field, followed
            // immediately by data
            list(, $generaPurposeFlag) = unpack('v', substr($body, 6, 2));
            // If the file has been compressed on the fly, 0x08 bit is set of
            // the general purpose field. We can use this to differentiate
            // between a compressed document, and a ZIP file
            if (!(0x08 == (0x08 & $generaPurposeFlag))) {
                // Don't attempt to decode a compressed zip file
                return $body;
            }
            // Determine the first byte of data, based on the above ZIP header offsets:
            $firstFileStart = array_sum(unpack('v2', substr($body, 26, 4)));
            if (($decompressed = @gzinflate(substr($body, 30 + $firstFileStart))) !== false) {
                return $decompressed;
            }

            return false;
        }

        if (($decompressed = @gzinflate($body)) !== false) {
            return $decompressed;
        }

        if (($decompressed = @gzinflate(substr($body, 2))) !== false) {
            return $decompressed;
        }

        return false;
    }
}