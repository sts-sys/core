<?php
namespace sts\events;

/**
 * Clasa Event reprezintă un eveniment în aplicație.
 */
class Event
{
    protected string $name;
    protected array $data = [];
    protected bool $propagationStopped = false;

    /**
     * Constructorul clasei Event.
     *
     * @param string $name Numele evenimentului
     * @param array $data Datele asociate evenimentului
     */
    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Obține numele evenimentului.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Obține datele asociate evenimentului.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Oprește propagarea evenimentului.
     *
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Verifică dacă propagarea evenimentului a fost oprită.
     *
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
