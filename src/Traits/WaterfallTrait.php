<?php

namespace CapMousse\ReactRestify\Traits;

trait WaterfallTrait
{
    /**
     * Call callback one
     * @param  \Closure $fn 
     * @return \Closure
     */
    private function callOnce ($fn) 
    {
        return function (...$args) use ($fn) {
            if ($fn === null) return;
            $fn(...$args);
            $fn = null;
        };
    }

    /**
     * Run tasks in series
     * @param  \Closure[]  $tasks 
     * @param  array       $args
     * @return void
     */
    private function waterfall (array $tasks, array $args)
    {
        $index = 0;

        $next = function () use (&$index, &$tasks, &$next, &$args) {
            if ($index == count($tasks)) { 
                return;
            }
            
            $callback = $this->callOnce(function () use (&$next) {
                $next();
            });

            $tasks[$index++]($callback, ...$args);
        };

        $next();
    }
}