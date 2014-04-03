<?php

namespace CapMousse\ReactRestify\Evenement;


class EventEmitter extends \Evenement\EventEmitter 
{
    protected $anyListeners = array();

    public function onAny($listener)
    {
        $this->anyListeners[] = $listener;
    }

    public function offAny($listener)
    {
        if (false !== $index = array_search($listener, $this->anyListeners, true)) {
            unset($this->anyListeners[$index]);
        }
    }

    public function emit($event, array $arguments = array())
    {
        foreach ($this->anyListeners as $listener) {
            call_user_func_array($listener, [$event , $arguments]);
        }

        parent::emit($event, $arguments);
    }
}