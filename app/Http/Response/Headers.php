<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 0:42
 */

namespace App\Http\Response;


class Headers
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param string $key
     * @param bool $asArray
     * @return mixed|null|string
     */
    public function get(string $key, bool $asArray = true)
    {
        $key = strtolower($key);
        if (!isset($this->data[$key])) {
            return null;
        }
        
        return $asArray ? $this->data[$key] : $this->flatten($this->data[$key]);
    }

    /**
     * @param string $key
     * @param string $value
     * @return Headers
     */
    public function add(string $key, string $value): Headers
    {
        $key = strtolower($key);

        if (!isset($this->data[$key])) {
            $this->data[$key] = array();
        }

        $this->data[$key][] = $value;

        return $this;
    }

    /**
     * @param $value
     * @return string
     */
    private function flatten($value) :string
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        return $value;
    }
}