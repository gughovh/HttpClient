<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 01.09.2018
 * Time: 19:04
 */

namespace App\Http\Transport;


interface TransportInterface
{
    /**
     * @param string $url
     * @param array|null $data
     * @param array|null $headers
     * @param array|null $options
     * @return array
     */
    public function request(string $url, array $data = null, array $headers = null, array $options = null) :array;
}