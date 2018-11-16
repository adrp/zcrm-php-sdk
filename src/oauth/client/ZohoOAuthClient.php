<?php

namespace ZCRM\oauth\client;

use ZCRM\ZCRMRestClient;
use ZCRM\common\ZCRMConfigUtil;
use ZCRM\common\APIConstants;
use ZCRM\oauth\common\OAuthLogger;
use ZCRM\oauth\common\ZohoOAuthHTTPConnector;
use ZCRM\oauth\common\ZohoOAuthConstants;
use ZCRM\oauth\common\ZohoOAuthTokens;
use ZCRM\oauth\common\ZohoOAuthException;

class ZohoOAuthClient {

  private $zohoOAuthParams;

  private static $zohoOAuthClient;

  private function __construct($params) {
    $this->zohoOAuthParams = $params;
  }

  public static function getInstance($params) {
    if (self::$zohoOAuthClient == NULL) {
      self::$zohoOAuthClient = new ZohoOAuthClient($params);
    }
    return self::$zohoOAuthClient;
  }

  public static function getInstanceWithOutParam() {
    return self::$zohoOAuthClient;
  }

  /**
   * @param $userEmailId
   *
   * @return mixed
   * @throws ZohoOAuthException
   */
  public function getAccessToken($userEmailId) {
    $persistence = ZohoOAuth::getPersistenceHandlerInstance();
    $tokens;
    try {
      $tokens = $persistence->getOAuthTokens($userEmailId);
    } catch (ZohoOAuthException $ex) {
      OAuthLogger::severe("Exception while retrieving tokens from persistence - " . $ex);
      throw $ex;
    } catch (\Exception $ex) {
      OAuthLogger::severe("Exception while retrieving tokens from persistence - " . $ex);
      throw new ZohoOAuthException($ex);
    }
    try {
      return $tokens->getAccessToken();
    } catch (ZohoOAuthException $ex) {
      OAuthLogger::info("Access Token has expired. Hence refreshing.");
      $tokens = self::refreshAccessToken($tokens->getRefreshToken(), $userEmailId);
      return $tokens->getAccessToken();
    }
  }

  /**
   * @param $grantToken
   *
   * @return ZohoOAuthTokens
   * @throws ZohoOAuthException
   */
  public function generateAccessToken($grantToken) {
    if ($grantToken == NULL) {
      throw new ZohoOAuthException("Grant Token is not provided.");
    }
    try {
      $url = ZohoOAuth::getTokenURL();
      $conn = self::getZohoConnector($url);
      $conn->addParam('access_type', 'offline');
      $grant_type = ZohoOAuthConstants::GRANT_TYPE;
      $grant_type_auth_code = ZohoOAuthConstants::GRANT_TYPE_AUTH_CODE;

      $conn->addParam($grant_type, $grant_type_auth_code);
      $code = ZohoOAuthConstants::CODE;
      $conn->addParam($code, $grantToken);
      $resp = $conn->post();


      $responseJSON = self::processResponse($resp);
      if (array_key_exists(ZohoOAuthConstants::ACCESS_TOKEN, $responseJSON)) {
        $tokens = self::getTokensFromJSON($responseJSON);
        $tokens->setUserEmailId(self::getUserEmailIdFromIAM($tokens->getAccessToken()));
        ZohoOAuth::getPersistenceHandlerInstance()
          ->saveOAuthData($tokens);
        return $tokens;
      }
      else {
        throw new ZohoOAuthException("Exception while fetching access token from grant token - " . $resp);
      }
    } catch (ZohoOAuthException $ex) {
      throw new ZohoOAuthException($ex);
    }
  }

  /**
   * @param $refreshToken
   * @param $userEmailId
   *
   * @throws ZohoOAuthException
   */
  public function generateAccessTokenFromRefreshToken($refreshToken, $userEmailId) {
     self::refreshAccessToken($refreshToken, $userEmailId);
  }

  /**
   * @param $refreshToken
   * @param $userEmailId
   *
   * @return ZohoOAuthTokens
   * @throws ZohoOAuthException
   */
  public function refreshAccessToken($refreshToken, $userEmailId) {
    if ($refreshToken == NULL) {
      throw new ZohoOAuthException("Refresh token is not provided.");
    }
    try {
      $conn = self::getZohoConnector(ZohoOAuth::getRefreshTokenURL());
      $conn->addParam(ZohoOAuthConstants::GRANT_TYPE, ZohoOAuthConstants::GRANT_TYPE_REFRESH);
      $conn->addParam(ZohoOAuthConstants::REFRESH_TOKEN, $refreshToken);
      $response = $conn->post();
      $responseJSON = self::processResponse($response);
      if (array_key_exists(ZohoOAuthConstants::ACCESS_TOKEN, $responseJSON)) {
        $tokens = self::getTokensFromJSON($responseJSON);
        $tokens->setRefreshToken($refreshToken);
        $tokens->setUserEmailId($userEmailId);
        ZohoOAuth::getPersistenceHandlerInstance()->saveOAuthData($tokens);
        return $tokens;
      }
      else {
        throw new ZohoOAuthException("Exception while fetching access token from refresh token - " . $response);
      }
    } catch (ZohoOAuthException $ex) {
      throw new ZohoOAuthException($ex);
    }
  }

  private function getZohoConnector($url) {
    $zohoHttpCon = new ZohoOAuthHTTPConnector();
    $zohoHttpCon->setUrl($url);
    $zohoHttpCon->addParam(ZohoOAuthConstants::CLIENT_ID, $this->zohoOAuthParams->getClientId());
    $zohoHttpCon->addParam(ZohoOAuthConstants::CLIENT_SECRET, $this->zohoOAuthParams->getClientSecret());
    $zohoHttpCon->addParam(ZohoOAuthConstants::REDIRECT_URL, $this->zohoOAuthParams->getRedirectURL());
    return $zohoHttpCon;
  }

  private function getTokensFromJSON($responseObj) {
    $oAuthTokens = new ZohoOAuthTokens();
    $expiresIn = $responseObj[ZohoOAuthConstants::EXPIRES_IN];
    $oAuthTokens->setExpiryTime($oAuthTokens->getCurrentTimeInMillis() + $expiresIn);

    $accessToken = $responseObj[ZohoOAuthConstants::ACCESS_TOKEN];
    $oAuthTokens->setAccessToken($accessToken);
    if (array_key_exists(ZohoOAuthConstants::REFRESH_TOKEN, $responseObj)) {
      $refreshToken = $responseObj[ZohoOAuthConstants::REFRESH_TOKEN];
      $oAuthTokens->setRefreshToken($refreshToken);
    }
    return $oAuthTokens;
  }

  /**
   * zohoOAuthParams
   *
   * @return unkown
   */
  public function getZohoOAuthParams() {
    return $this->zohoOAuthParams;
  }

  /**
   * zohoOAuthParams
   *
   * @param unkown $zohoOAuthParams
   */
  public function setZohoOAuthParams($zohoOAuthParams) {
    $this->zohoOAuthParams = $zohoOAuthParams;
  }

  public function getUserEmailIdFromIAM($accessToken) {
    /**
     * Based on:
     * @see https://github.com/adamdyson/zcrm-php-sdk/blob/master/src/com/zoho/oauth/client/ZohoOAuthClient.php#L175-L193

     * For details on why these changes were needed, see:
     * https://help.zoho.com/portal/community/topic/error-invalid-oauthscope-in-request-to-url-https-accounts-zoho-com-oauth-user-info?action=communityTopicLike&actionId=2266000011255084
     *
     * Also see that the Python SDK used the email config value, no need for a second request:
     * https://github.com/zoho/zcrm-python-sdk/blob/master/zcrmsdk/OAuthClient.py#L112
     */
    if (($currentUserEmail = ZCRMRestClient::getCurrentUserEmailID()) != null
      || ($currentUserEmail = ZCRMConfigUtil::getConfigValue(APIConstants::CURRENT_USER_EMAIL)) != null
    ) {
        return $currentUserEmail;
    }
    else {
        throw new ZCRMException("Current user should either be set in the server environment or via configuration");
    }
  }

  public function processResponse($apiResponse) {
    list($headers, $content) = explode("\r\n\r\n", $apiResponse, 2);
    $jsonResponse = json_decode($content, TRUE);

    return $jsonResponse;
  }

}

