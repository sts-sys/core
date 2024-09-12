<?php
namespace sts\core\events;

use sts\containers\di\container_interface as ContainerInterface;
use sts\core\events\event as Event;
/**
 * Clasa event_manager gestionează evenimentele și ascultătorii de evenimente.
 */
class event_manager
{
    protected ContainerInterface $container;
    protected array $listeners = [];

    /**
     * Constructorul clasei event_manager.
     *
     * @param ContainerInterface $container Containerul de servicii
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Înregistrează un ascultător pentru un eveniment.
     *
     * @param string $event Numele evenimentului
     * @param string|callable $listener Ascultătorul evenimentului (nume de clasă sau funcție)
     * @param int $priority Prioritatea ascultătorului (opțional)
     */
    public function addListener(string $event, string|callable $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;
        // Sortează ascultătorii în funcție de prioritate
        krsort($this->listeners[$event], SORT_NUMERIC);
    }

    /**
     * Elimină un ascultător pentru un eveniment.
     *
     * @param string $event Numele evenimentului
     * @param string|callable $listener Ascultătorul evenimentului
     */
    public function removeListener(string $event, string|callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $priority => $listeners) {
            $key = array_search($listener, $listeners, true);
            if ($key !== false) {
                unset($this->listeners[$event][$priority][$key]);
            }
        }
    }

    /**
     * Declanșează un eveniment și notifică toți ascultătorii.
     *
     * @param Event $event Evenimentul de declanșat
     */
    public function dispatch(Event $event): void
    {
        $eventName = $event->getName();
        if (empty($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $listener) {
                if (is_callable($listener)) {
                    $listener($event, $this->container);
                } elseif (is_string($listener) && class_exists($listener)) {
                    $listenerInstance = $this->container->get($listener);
                    if ($listenerInstance instanceof ListenerInterface) {
                        $listenerInstance->handle($event, $this->container);
                    }
                }

                // Verifică dacă propagarea a fost oprită
                if ($event->isPropagationStopped()) {
                    return;
                }
            }
        }
    }
}
