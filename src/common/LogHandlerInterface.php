<?php

namespace ZCRM\common;

interface LogHandlerInterface {
    public static function log($msg, $serverity, $source);
}
