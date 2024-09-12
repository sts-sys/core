<?php
namespace sts\exception\exceptions;

use \Exception;
use \Throwable;

/**
 * Clasa de bază pentru toate excepțiile personalizate din aplicație.
 */
class CoreException extends Exception
{
    /**
     * Date suplimentare pentru eroare.
     *
     * @var array
     */
    protected array $context;

    /**
     * Nivelul severității erorii (e.g., alert, critical, error, warning).
     *
     * @var string
     */
    protected string $severity;

    /**
     * Constructor pentru CoreException.
     *
     * @param string $message Mesajul de eroare
     * @param int $code Codul de eroare (implicit 0)
     * @param array $context Date suplimentare pentru contextul erorii
     * @param string $severity Nivelul severității erorii
     * @param Throwable|null $previous Excepția anterioară, dacă există
     */
    public function __construct(string $message = "", int $code = 0, array $context = [], string $severity = 'error', Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->severity = $severity;
    }

    /**
     * Obține contextul adițional al excepției.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Obține nivelul de severitate al excepției.
     *
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    /**
     * Loghează detaliile excepției.
     *
     * @param callable|null $logger Un logger personalizat
     * @return void
     */
    public function log(?callable $logger = null): void
    {
        $logMessage = sprintf(
            "[%s] [%s] %s in %s:%d\nContext: %s\nStack trace: %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($this->severity),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
            json_encode($this->context),
            $this->getTraceAsString()
        );

        if ($logger) {
            $logger($logMessage);
        } else {
            // Logare implicită în fișier
            error_log($logMessage, 3, storage_path('logs', 'error.log'));
        }
    }

    /**
     * Afișează un mesaj de eroare prietenos utilizatorului.
     *
     * @param bool $debug Dacă este activată afișarea mesajelor detaliate
     * @return string
     */
    public function getUserFriendlyMessage(bool $debug = false): string
    {
        if ($debug) {
            return sprintf("An error occurred: %s. Please contact support.", $this->getMessage());
        }
        return "An unexpected error occurred. Please try again later.";
    }

    /**
     * Trimite detaliile excepției către un sistem de monitorizare extern.
     *
     * @param callable $monitoringServiceCallback Callback pentru a integra cu sistemul extern
     * @return void
     */
    public function notify(callable $monitoringServiceCallback): void
    {
        $monitoringServiceCallback([
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'severity' => $this->severity,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}