<?php

namespace ZCRM\common;

use ZCRM\common\ZCRMConfigUtil;
use ZCRM\common\LogHandlerInterface;
use ZCRM\common\LogHandlerToFile;

class LogHandler implements LogHandlerInterface {
    public static function log($msg, $severity, $source) {
        switch ($source) {
            case 'api':   $config_key = 'api_log_handler_class';
                          $log_handler_class = ZCRMConfigUtil::getConfigValue($config_key);
                          break;
            case 'oauth': $config_key = 'oauth_log_handler_class';
                          $log_handler_class = ZCRMConfigUtil::getConfigValue($config_key);
                          break;
            default: throw new \Exception("Log source '$source' not recognized.");
        }
        $log_handler_namespaced_class = $log_handler_class;
        if ($log_handler_class == 'LogHandlerToFile') {
          $log_handler_namespaced_class = 'ZCRM\\common\\' . $log_handler_class;
        }

        if (!class_exists($log_handler_namespaced_class)) {
          throw new \Exception("Critical: '$log_handler_namespaced_class' class not defined (set by '$config_key' OAuth config).");
        }

        $log_handler_namespaced_class::log($msg, $severity, $source);
    }
}
