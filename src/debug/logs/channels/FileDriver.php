<?php

namespace sts\logger\logs\channels;

use sts\logger\interfaces\LogChannelInterface;

/**
 * Clasa FileDriver gestionează logarea în fișiere, rotația și arhivarea fișierelor de log
 * și logarea structurată în format JSON.
 */
class FileDriver implements LogChannelInterface
{
    private $logFile;
    private $cache = [];
    private $cacheSize;
    private $maxFileSize;
    private $cacheLimit;
    private $structuredLogging;

    /**
     * Constructor pentru FileDriver.
     *
     * @param string $filePath Calea către fișierul de log.
     * @param int $cacheLimit Numărul de loguri în cache înainte de a scrie în fișier.
     * @param int $maxFileSize Dimensiunea maximă a fișierului de log înainte de rotație.
     * @param bool $structuredLogging Activează logarea structurată (JSON) dacă este true.
     */
    public function __construct($filePath, $cacheLimit = 10, $maxFileSize = 1048576, $structuredLogging = false)
    {
        $this->logFile = $filePath;
        $this->cacheLimit = $cacheLimit;
        $this->maxFileSize = $maxFileSize;
        $this->structuredLogging = $structuredLogging;
    }

    /**
     * Loghează un mesaj în fișier, aplicând cache-ul, rotația și logarea structurată.
     *
     * @param string $level Nivelul de logare (ex: 'info', 'error', 'critical').
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     */
    public function log($level, $message, array $context = [])
    {
        // Formatează mesajul
        $formattedMessage = $this->formatMessage($level, $message, $context);

        // Adaugă mesajul în cache
        $this->cache[] = $formattedMessage;

        // Dacă cache-ul a atins limita specificată, scrie în fișier
        if (count($this->cache) >= $this->cacheLimit) {
            $this->flushCache();
        }
    }

    /**
     * Formatează mesajul de logare.
     *
     * @param string $level Nivelul de logare.
     * @param string $message Mesajul de logare.
     * @param array $context Contextul suplimentar pentru mesaj.
     * @return string Mesajul formatat.
     */
    private function formatMessage($level, $message, array $context)
    {
        $date = new \DateTime();

        // Logare structurată (JSON) dacă este activată
        if ($this->structuredLogging) {
            return json_encode([
                'timestamp' => $date->format('Y-m-d H:i:s'),
                'level' => strtoupper($level),
                'message' => $message,
                'context' => $context,
            ]) . PHP_EOL;
        }

        // Logare standard
        return sprintf("[%s] [%s]: %s %s%s", $date->format('Y-m-d H:i:s'), strtoupper($level), $message, json_encode($context), PHP_EOL);
    }

    /**
     * Scrie toate mesajele din cache în fișier și golește cache-ul.
     */
    private function flushCache()
    {
        // Verifică dacă fișierul de log trebuie rotit
        $this->rotateFileIfNeeded();

        // Scrie cache-ul în fișier
        file_put_contents($this->logFile, implode('', $this->cache), FILE_APPEND);
        // Golește cache-ul
        $this->cache = [];
    }

    /**
     * Verifică dacă fișierul de log trebuie rotit și efectuează rotația dacă este necesar.
     */
    private function rotateFileIfNeeded()
    {
        if (file_exists($this->logFile) && filesize($this->logFile) >= $this->maxFileSize) {
            // Crează un backup pentru fișierul de log
            $backupFile = $this->logFile . '.' . date('Y-m-d_H-i-s') . '.bak';
            rename($this->logFile, $backupFile);
        }
    }

    /**
     * Destructor pentru a se asigura că toate logurile din cache sunt scrise în fișier.
     */
    public function __destruct()
    {
        // Salvează orice loguri rămase în cache la finalizarea scriptului
        if (!empty($this->cache)) {
            $this->flushCache();
        }
    }
}
