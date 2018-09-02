<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 14:01
 */

namespace App\Api;


use App\Http\Client;

abstract class AbstractApiClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client(new Client\Config($this->getClientConfig()));
    }

    /**
     * @return array
     */
    abstract protected function getClientConfig():array;

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        return $this->client;
    }
}