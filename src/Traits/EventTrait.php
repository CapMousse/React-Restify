<?php

namespace CapMousse\ReactRestify\Traits;

use CapMousse\ReactRestify\Container\Container;

trait EventTrait
{
    /**
     * On event fired
     * @param  string   $event    
     * @param  callable $callback [description]
     * @return void
     */
    abstract public function on($event, callable $callback);

    /**
     * Emit event
     * @param  string $event     
     * @param  array  $arguments 
     * @return void
     */
    abstract public function emit($event, array $arguments = []);

    /**
     * Helper to listing to after event
     *
     * @param  Callable $callback
     * @return Void
     */
    public function after($callback)
    {
        $container  = Container::getInstance();

        $this->on('after', function ($request, $response) use (&$container, &$callback) {
            try {
                $container->call($callback, func_get_args());
            } catch (\Exception $e) {
                $this->emit('error', [$request, $response, $e->getMessage()]);
            }
        });
    }
}