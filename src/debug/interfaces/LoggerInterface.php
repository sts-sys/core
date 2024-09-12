<?php

namespace sts\logger\interfaces;

/**
 * Interfața LoggerInterface definește contractul pentru toate clasele de logare.
 * Aceasta specifică metodele de logare de bază, precum și funcționalitățile extinse
 * pentru personalizarea formatării, gestionarea erorilor, callback-uri, filtrarea logurilor și politica de retenție.
 */
interface LoggerInterface
{
    /**
     * Loghează un mesaj cu un anumit nivel de severitate.
     *
     * @param string $level Nivelul de logare (ex: 'info', 'error', 'critical').
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function log($level, $message, array $context = []);

    /**
     * Loghează un mesaj de tip 'debug'.
     *
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function debug($message, array $context = []);

    /**
     * Loghează un mesaj de tip 'info'.
     *
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function info($message, array $context = []);

    /**
     * Loghează un mesaj de tip 'notice'.
     *
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function notice($message, array $context = []);

    /**
     * Loghează un mesaj de tip 'warning'.
     *
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function warning($message, array $context = []);

    /**
     * Loghează un mesaj de tip 'error'.
     *
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function error($message, array $context = []);

    /**
     * Loghează un mesaj de tip 'critical'.
     *
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function critical($message, array $context = []);

    /**
     * Setează un formatter personalizat pentru mesajele de logare.
     *
     * @param callable $formatter Funcția de formatter personalizat.
     */
    public function setFormatter(callable $formatter);

    /**
     * Înregistrează handler-ele pentru gestionarea erorilor și excepțiilor.
     */
    public function registerErrorHandlers();

    /**
     * Adaugă un callback care va fi apelat după fiecare operațiune de logare.
     *
     * @param callable $callback Funcția de callback.
     */
    public function addCallback(callable $callback);

    /**
     * Adaugă un filtru pentru loguri.
     *
     * @param callable $filter Funcția de filtru care determină dacă un mesaj trebuie logat.
     */
    public function addFilter(callable $filter);

    /**
     * Setează politica de retenție pentru loguri, specificând câte zile vor fi păstrate logurile.
     *
     * @param int $days Numărul de zile pentru care logurile vor fi păstrate.
     */
    public function setRetentionPolicy(int $days);
}
