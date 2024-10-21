<?php

namespace ZCRM\oauth\common;

class ZohoOAuthException extends \Exception {
    protected string $message = 'Unknown exception';     // Exception message
    private string $string = "";                            // Unknown
    protected int $code = 0;                           // User-defined exception code
    protected string $file = "";                              // Source filename of exception
    protected int $line;                              // Source line of exception
    private array $trace = [];


    public function __construct($message = null, $code = 0) {
        if (!$message) {
            throw new $this('Unknown ' . get_class($this));
        }
        parent::__construct($message, $code);
    }

    public function __toString() {
        return get_class($this) . " Caused by:'{$this->message}' in {$this->file}({$this->line})\n" . "{$this->getTraceAsString()}";
    }
}

