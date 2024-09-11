<?php
namespace sts\events\interface;

use sts\events\event as Event;
use sts\containers\di\container_interface as ContainerInterface;

/**
 * Interfața ListenerInterface definește contractul pentru un ascultător de evenimente.
 */
interface ListenerInterface
{
    /**
     * Metodă pentru gestionarea unui eveniment.
     *
     * @param Event $event Evenimentul care trebuie gestionat
     * @param ContainerInterface $container Containerul de servicii pentru a rezolva dependențele
     * @return void
     */
    public function handle(Event $event, ContainerInterface $container): void;
}
