<?php

namespace ZCRM\oauth\common;

use ZCRM\common\LogHandler;

class OAuthLogger {
    public static function log($msg, $severity) {
        LogHandler::log($msg, $severity, 'oauth');
    }

    public static function warn($msg) {
        self::log($msg, 'warning');
    }

    public static function info($msg) {
        self::log($msg, 'info');
    }

    public static function severe($msg) {
        self::log($msg, 'severe');
    }

    public static function err($msg) {
        self::log($msg, 'error');
    }

    public static function debug($msg) {
        self::log($msg, 'debug');
    }
}
