<?php

namespace CapMousse\ReactRestify\Evenement;

class EventEmitter extends \Evenement\EventEmitter
{
    /**
     * @var array
     */
    protected $anyListeners = [];

    /**
     * Listen all event
     *
     * @param Callable $listener
     *
     * @return void
     */
    public function onAny($listener)
    {
        $this->anyListeners[] = $listener;
    }

    /**
     * Disable a onAny listener
     *
     * @param Callable $listener
     *
     * @return Void
     */
    public function offAny($listener)
    {
        if (false !== $index = array_search($listener, $this->anyListeners, true)) {
            unset($this->anyListeners[$index]);
        }
    }

    /**
     * Emit an event
     *
     * @param string $event
     * @param array  $arguments
     *
     * @return Void
     */
    public function emit($event, array $arguments = [])
    {
        foreach ($this->anyListeners as $listener) {
            call_user_func_array($listener, [$event , $arguments]);
        }

        parent::emit($event, $arguments);
    }
}
