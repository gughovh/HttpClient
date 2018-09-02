<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 31.08.2018
 * Time: 0:13
 */

namespace App\Http\Client;


use App\Http\Exception\ConfigException;
use function App\Http\Helper\configs;
use App\Http\Helper\Log;

class Config
{
    const REQUIRED = 'required';
    const SETTER = 'setter';

    const RULES = [
        'port' => [
            self::SETTER => 'setPort'
        ],
        'address' => [
            self::SETTER => 'setAddress',
            self::REQUIRED => true
        ],
        // microseconds
        'timeout' => [
            self::SETTER => 'setTimeout'
        ],
        // microseconds
        'connectTimeout' => [
            self::SETTER => 'setConnectTimeout'
        ],
        // microseconds
        'threshold' => [
            self::SETTER => 'setThreshold'
        ],
    ];

    /**
     * @var string
     */
    private $address;

    /**
     * @var int
     */
    private $port = 80;

    /**
     * @var int milliseconds
     */
    private $timeout = 1e3;

    /**
     * @var int milliseconds
     */
    private $connectTimeout = 1e2;

    /**
     * @var int milliseconds
     */
    private $threshold = 3e2;

    /**
     * Config constructor.
     * @param array $configs
     * @throws ConfigException
     */
    public function __construct(array $configs)
    {
        if (isset(configs()['client'])) {
            $configs = array_merge(configs()['client'], $configs);
        }

        try {
            foreach (static::RULES as $config => $params) {

                if (!array_key_exists($config, $configs)) {

                    if ($params[self::REQUIRED] ?? false) {
                        throw new ConfigException("The {$config} is required.");
                    }

                    continue;
                }

                $setter = $params[self::SETTER] ?? 'set'.ucfirst($config);
                $this->{$setter}($configs[$config]);
            }
        } catch (ConfigException $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * @param mixed $address
     * @return Config
     * @throws ConfigException
     */
    public function setAddress($address)
    {
        if (!preg_match('/^http(s)?:\/\//i', $address)) {
            throw new ConfigException('Only HTTP(S) requests are handled.');
        }

        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param int $port
     * @return Config
     * @throws ConfigException
     */
    public function setPort($port)
    {
        if (!is_int($port)) {
            throw new ConfigException('Port should be an integer.');
        }

        $this->port = $port;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $threshold
     * @return Config
     * @throws ConfigException
     */
    public function setThreshold($threshold)
    {
        if (!is_int($threshold)) {
            throw new ConfigException('The threshold should be an integer.');
        }

        $this->threshold = $threshold;
        return $this;
    }

    /**
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param int $timeout
     * @return Config
     * @throws ConfigException
     */
    public function setTimeout($timeout)
    {
        if (!is_int($timeout)) {
            throw new ConfigException('The timeout should be an integer');
        }

        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $connectTimeout
     * @return Config
     * @throws ConfigException
     */
    public function setConnectTimeout($connectTimeout): Config
    {
        if (!is_int($connectTimeout)) {
            throw new ConfigException('The connect timeout should be an integer');
        }
        $this->connectTimeout = $connectTimeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }
}