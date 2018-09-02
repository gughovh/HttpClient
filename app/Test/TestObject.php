<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 14:34
 */

namespace App\Test;


class TestObject
{
    /**
     * @var array|null
     */
    private $data;

    public function __construct(array $data = null)
    {
        if (!is_null($data)) {
            $this->data = $data;
        }
    }

    /**
     * @return array
     */
    public function getData():? array
    {
        return $this->data;
    }
}