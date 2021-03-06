<?php

namespace ZCRM\oauth\client;

use ZCRM\oauth\common\ZohoOAuthUtil;
use ZCRM\oauth\common\ZohoOAuthConstants;
use ZCRM\oauth\common\ZohoOAuthParams;
use ZCRM\oauth\clientapp\ZohoOAuthPersistenceHandler;
use ZCRM\oauth\clientapp\ZohoOAuthPersistenceByFile;
use ZCRM\oauth\common\OAuthLogger;

class ZohoOAuth {
    
    private static $configProperties = array();

    public static function initialize($config) {

        try {

            self::$configProperties = $config['oauth'];

            $oAuthParams = new ZohoOAuthParams();
            $oAuthParams->setAccessType(self::getConfigValue(ZohoOAuthConstants::ACCESS_TYPE));
            $oAuthParams->setClientId(self::getConfigValue(ZohoOAuthConstants::CLIENT_ID));
            $oAuthParams->setClientSecret(self::getConfigValue(ZohoOAuthConstants::CLIENT_SECRET));
            $oAuthParams->setRedirectURL(self::getConfigValue(ZohoOAuthConstants::REDIRECT_URL));
            ZohoOAuthClient::getInstance($oAuthParams);

        } catch (IOException $ioe) {
            OAuthLogger::warn("Exception while initializing Zoho OAuth Client.. " . ioe);
            throw ioe;
        }
    }

    public static function getConfigValue($key) {
        $value = self::$configProperties[$key];
        return $value;
    }

    public static function getAllConfigs() {
        return self::$configProperties;
    }

    public static function getIAMUrl() {
        return self::getConfigValue(ZohoOAuthConstants::IAM_URL);
    }

    public static function getGrantURL() {
        return self::getIAMUrl() . "/oauth/v2/auth";
    }

    public static function getTokenURL() {
        return self::getIAMUrl() . "/oauth/v2/token";
    }

    public static function getRefreshTokenURL() {
        return self::getIAMUrl() . "/oauth/v2/token";
    }

    public static function getRevokeTokenURL() {
        return self::getIAMUrl() . "/oauth/v2/token/revoke";
    }

    public static function getUserInfoURL() {
        return self::getIAMUrl() . "/oauth/user/info";
    }

    public static function getClientID() {
        return self::getConfigValue(ZohoOAuthConstants::CLIENT_ID);
    }

    public static function getClientSecret() {
        return self::getConfigValue(ZohoOAuthConstants::CLIENT_SECRET);
    }

    public static function getRedirectURL() {
        return self::getConfigValue(ZohoOAuthConstants::REDIRECT_URL);
    }

    public static function getAccessType() {
        return self::getConfigValue(ZohoOAuthConstants::ACCESS_TYPE);
    }

    public static function getPersistenceHandlerInstance() {
        try {
            if (empty(self::getConfigValue('persistence_handler_class'))) {
              throw new \Exception("Ciritical: 'persistence_handler_class' OAuth config not set.");
            }

            $persistence_class = self::getConfigValue('persistence_handler_class');

            // "Built in" classes get prefixed with namespace (so "new $class()" construction works.
            // Custom classes have to be passed to configuration already with a namespace.
            if (in_array($persistence_class, ['ZohoOAuthPersistenceByFile', 'ZohoOAuthPersistenceHandler'])) {
              $persistence_namespaced_class = 'ZCRM\\oauth\\clientapp\\' . $persistence_class;
            }
            else {
              $persistence_namespaced_class = $persistence_class;
            }

            if (!class_exists($persistence_namespaced_class)) {
              throw new \Exception("Critical: '$persistence_namespaced_class' class not defined (set by 'persistence_handler_class' OAuth config).");
            }

            switch ($persistence_class) {
              case 'ZohoOAuthPersistenceByFile': $required_config = ['token_persistence_path'];
                   break;
              case 'ZohoOAuthPersistenceHandler': $required_config = ['db_host', 'db_user', 'db_pass', 'db_name'];
                   break;
              default: $required_config = [];
            }

            if (!empty($missing_config = array_diff($required_config, array_keys(self::getAllConfigs())))) {
              throw new \Exception("Critical: required configuration missing for '$persistence_class' persistence class: '" . implode("', '", $required_config));
            }

            return new $persistence_namespaced_class();
        } catch (Exception $ex) {
            throw new ZohoOAuthException($ex);
        }
    }

    public static function getClientInstance() {
        if (ZohoOAuthClient::getInstanceWithOutParam() == null) {
            throw new ZohoOAuthException("ZohoOAuth.initialize() must be called before this.");
        }
        return ZohoOAuthClient::getInstanceWithOutParam();
    }

}

