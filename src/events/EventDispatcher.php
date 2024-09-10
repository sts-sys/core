<?php
namespace STS\Core\Events;

class EventDispatcher
{
    /**
     * Lista de ascultători înregistrați pentru fiecare eveniment.
     *
     * @var array
     */
    protected array $listeners = [];

    /**
     * Adaugă un ascultător pentru un eveniment specific.
     *
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function addListener(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Trimite un eveniment tuturor ascultătorilor înregistrați.
     *
     * @param string $event
     * @param mixed ...$args
     * @return void
     */
    public function dispatch(string $event, ...$args): void
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                call_user_func_array($listener, $args);
            }
        }
    }

    /**
     * Elimină un ascultător pentru un eveniment specific.
     *
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function removeListener(string $event, callable $listener): void
    {
        if (isset($this->listeners[$event])) {
            $index = array_search($listener, $this->listeners[$event], true);
            if ($index !== false) {
                unset($this->listeners[$event][$index]);
            }
        }
    }
}