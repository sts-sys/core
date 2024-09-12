<?php

namespace sts\logger\logs;

use sts\logger\interfaces\LoggerInterface;
use sts\logger\interfaces\LogChannelInterface;

/**
 * Clasa Logger implementează funcționalitățile de logare și gestionează logarea mesajelor
 * către multiple canale, aplicând formatter-uri, callback-uri, filtre și politici de retenție.
 */
class Logger implements LoggerInterface
{
    /** @var array Lista canalelor de logare (drivere) */
    private $channels = [];

    /** @var array Lista funcțiilor de filtrare a logurilor */
    private $filters = [];

    /** @var array Lista funcțiilor de callback pentru evenimente de logare */
    private $callbacks = [];

    /** @var callable|null Funcția de formatter personalizat pentru loguri */
    private $formatter;

    /** @var int Politica de retenție în zile pentru loguri */
    private $retentionDays = 30;


    
    /**
     * Adaugă un canal de logare.
     *
     * @param LogChannelInterface $channel Canalul de logare care implementează LogChannelInterface.
     */
    public function addChannel(LogChannelInterface $channel)
    {
        $this->channels[] = $channel;
    }

    /**
     * Loghează un mesaj cu un anumit nivel de severitate.
     *
     * @param string $level Nivelul de logare (ex: 'info', 'error', 'critical').
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->passesFilters($level, $message)) {
            return;
        }

        // Aplică formatter-ul dacă este definit
        if ($this->formatter) {
            $message = call_user_func($this->formatter, $level, $message, $context);
        }

        // Trimite mesajul de logare către fiecare canal
        foreach ($this->channels as $channel) {
            $channel->log($level, $message, $context);
        }

        // Execută callback-urile înregistrate
        $this->executeCallbacks($level, $message, $context);
    }

    /** Funcțiile de logare pentru diferite nivele de severitate **/
    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Setează un formatter personalizat pentru mesajele de logare.
     *
     * @param callable $formatter Funcția de formatter personalizat.
     */
    public function setFormatter(callable $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Înregistrează handler-ele pentru gestionarea erorilor și excepțiilor.
     */
    public function registerErrorHandlers()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Gestionarea erorilor PHP.
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $this->error("Error [$errno]: $errstr in $errfile on line $errline");
    }

    /**
     * Gestionarea excepțiilor necontrolate.
     */
    public function handleException($exception)
    {
        $this->critical('Uncaught exception: ' . $exception->getMessage(), ['exception' => $exception]);
    }

    /**
     * Gestionarea erorilor fatale la închiderea scriptului.
     */
    public function handleShutdown()
    {
        $lastError = error_get_last();
        if ($lastError && ($lastError['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
            $this->critical("Fatal error: {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}");
        }
    }

    /**
     * Adaugă un callback care va fi apelat după fiecare operațiune de logare.
     *
     * @param callable $callback Funcția de callback.
     */
    public function addCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Execută callback-urile înregistrate.
     *
     * @param string $level Nivelul de logare.
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    private function executeCallbacks($level, $message, $context)
    {
        foreach ($this->callbacks as $callback) {
            call_user_func($callback, $level, $message, $context);
        }
    }

    /**
     * Adaugă un filtru pentru loguri.
     *
     * @param callable $filter Funcția de filtru care determină dacă un mesaj trebuie logat.
     */
    public function addFilter(callable $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * Verifică dacă un mesaj de logare trece prin toate filtrele.
     *
     * @param string $level Nivelul de logare.
     * @param string $message Mesajul de logare.
     * @return bool True dacă mesajul trece toate filtrele, altfel false.
     */
    private function passesFilters($level, $message)
    {
        foreach ($this->filters as $filter) {
            if (!$filter($level, $message)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Setează politica de retenție pentru loguri, specificând câte zile vor fi păstrate logurile.
     *
     * @param int $days Numărul de zile pentru care logurile vor fi păstrate.
     */
    public function setRetentionPolicy(int $days)
    {
        $this->retentionDays = $days;
    }

    /**
     * Aplică politica de retenție și șterge logurile mai vechi decât perioada specificată.
     *
     * @param string $logDirectory Directorul unde se află logurile.
     */
    public function applyRetentionPolicy($logDirectory)
    {
        $files = glob($logDirectory . '/*.log');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $this->retentionDays * 86400) { // 86400 secunde într-o zi
                    unlink($file);
                }
            }
        }
    }
}