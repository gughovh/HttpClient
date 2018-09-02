<?php
/**
 * Created by PhpStorm.
 * User: Gurgen
 * Date: 02.09.2018
 * Time: 3:22
 */

namespace App\Http\Client;


interface Hook
{
    /**
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @return Hook
     */
    public function register(string $hook, callable $callback, int $priority = 0) :Hook;

    /**
     * @param string $callback
     * @param array|null $parameters
     * @return bool
     */
    public function dispatch(string $callback, array $parameters = []) :bool;
}