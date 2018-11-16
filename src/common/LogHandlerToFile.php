<?php

namespace ZCRM\common;

use ZCRM\common\LogHandlerInterface;

class LogHandlerToFile implements LogHandlerInterface {
    public static function log($msg, $severity, $source) {
        $path = ZCRMConfigUtil::getConfigValue('log_file_path');
        if (empty($path)) {
            throw new \Exception("Please provide a file path for '$source' logs.");
        }

        if ($path{strlen($path) - 1} != '\/') {
            $path = $path . "/";
        }
        $path = str_replace("\n", "", $path);

        $filePointer = fopen($path . $source . '.log', "a");
        if (!$filePointer) {
            return;
        }

        fwrite($filePointer, sprintf("[%s] %s: %s\n", date("Y-m-d H:i:s"), strtoupper($severity), $msg));
        fclose($filePointer);
    }
}
