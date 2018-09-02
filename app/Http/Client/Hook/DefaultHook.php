<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 3:25
 */

namespace App\Http\Client\Hook;


use App\Http\Client\Hook;

class DefaultHook implements Hook
{
    /**
     * @var array
     */
    private $hooks = [];

    /**
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @return Hook
     */
    public function register(string $hook, callable $callback, int $priority = 0): Hook
    {
        $this->hooks[$hook][$priority][] = $callback;
        return $this;
    }

    /**
     * @param string $hook
     * @param array|null $parameters
     * @return bool
     */
    public function dispatch(string $hook, array $parameters = []): bool
    {
        if (empty($this->hooks[$hook])) {
            return false;
        }

        $hooks = $this->hooks[$hook];
        ksort($hooks);

        foreach ($hooks as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$parameters);
            }
        }

        return true;
    }
}