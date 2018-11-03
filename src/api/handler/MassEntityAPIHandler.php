<?php

namespace ZCRM\api\handler;

use ZCRM\crud\ZCRMRecord;
use ZCRM\crud\ZCRMTrashRecord;
use ZCRM\common\APIConstants;
use ZCRM\api\APIRequest;
use ZCRM\exception\APIExceptionHandler;
use ZCRM\exception\ZCRMException;


class MassEntityAPIHandler extends APIHandler {

  private $module = NULL;

  public function __construct($moduleInstance) {
    $this->module = $moduleInstance;
  }

  public static function getInstance($moduleInstance) {
    return new MassEntityAPIHandler($moduleInstance);
  }

  public function createRecords($records) {
    if (sizeof($records) > 100) {
      throw new ZCRMException(APIConstants::API_MAX_RECORDS_MSG, APIConstants::RESPONSECODE_BAD_REQUEST);
    }
    try {
      $this->urlPath = $this->module->getAPIName();
      $this->requestMethod = APIConstants::REQUEST_METHOD_POST;
      $this->addHeader("Content-Type", "application/json");
      $requestBodyObj = [];
      $dataArray = [];
      foreach ($records as $record) {
        if ($record->getEntityId() == NULL) {
          array_push($dataArray, EntityAPIHandler::getInstance($record)
            ->getZCRMRecordAsJSON());
        }
        else {
          throw new ZCRMException("Entity ID MUST be null for create operation.", APIConstants::RESPONSECODE_BAD_REQUEST);
        }
      }
      $requestBodyObj["data"] = $dataArray;
      $this->requestBody = $requestBodyObj;

      //Fire Request
      $bulkAPIResponse = APIRequest::getInstance($this)->getBulkAPIResponse();
      $createdRecords = [];
      $responses = $bulkAPIResponse->getEntityResponses();
      $size = sizeof($responses);
      for ($i = 0; $i < $size; $i++) {
        $entityResIns = $responses[$i];
        if (APIConstants::STATUS_SUCCESS === $entityResIns->getStatus()) {
          $responseData = $entityResIns->getResponseJSON();
          $recordDetails = $responseData["details"];
          $newRecord = $records[$i];
          EntityAPIHandler::getInstance($newRecord)
            ->setRecordProperties($recordDetails);
          array_push($createdRecords, $newRecord);
          $entityResIns->setData($newRecord);
        }
        else {
          $entityResIns->setData(NULL);
        }
      }
      $bulkAPIResponse->setData($createdRecords);
      return $bulkAPIResponse;
    } catch (ZCRMException $e) {
      throw $e;
    }
  }

  public function upsertRecords($records) {
    if (sizeof($records) > 100) {
      throw new ZCRMException(APIConstants::API_MAX_RECORDS_MSG, APIConstants::RESPONSECODE_BAD_REQUEST);
    }
    try {
      $this->urlPath = $this->module->getAPIName() . "/upsert";
      $this->requestMethod = APIConstants::REQUEST_METHOD_POST;
      $this->addHeader("Content-Type", "application/json");
      $requestBodyObj = [];
      $dataArray = [];
      foreach ($records as $record) {
        $recordJSON = EntityAPIHandler::getInstance($record)
          ->getZCRMRecordAsJSON();
        if ($record->getEntityId() != NULL) {
          $recordJSON['id'] = $record->getEntityId();
        }
        array_push($dataArray, $recordJSON);
      }
      $requestBodyObj["data"] = $dataArray;
      $this->requestBody = $requestBodyObj;

      //Fire Request
      $bulkAPIResponse = APIRequest::getInstance($this)->getBulkAPIResponse();
      $upsertRecords = [];
      $responses = $bulkAPIResponse->getEntityResponses();
      $size = sizeof($responses);
      for ($i = 0; $i < $size; $i++) {
        $entityResIns = $responses[$i];
        if (APIConstants::STATUS_SUCCESS === $entityResIns->getStatus()) {
          $responseData = $entityResIns->getResponseJSON();
          $recordDetails = $responseData["details"];
          $newRecord = $records[$i];
          EntityAPIHandler::getInstance($newRecord)
            ->setRecordProperties($recordDetails);
          array_push($upsertRecords, $newRecord);
          $entityResIns->setData($newRecord);
        }
        else {
          $entityResIns->setData(NULL);
        }
      }
      $bulkAPIResponse->setData($upsertRecords);
      return $bulkAPIResponse;
    } catch (ZCRMException $e) {
      throw $e;
    }
  }

  public function updateRecords($records) {
    if (sizeof($records) > 100) {
      throw new ZCRMException(APIConstants::API_MAX_RECORDS_MSG, APIConstants::RESPONSECODE_BAD_REQUEST);
    }
    try {
      $this->urlPath = $this->module->getAPIName();
      $this->requestMethod = APIConstants::REQUEST_METHOD_PUT;
      $this->addHeader("Content-Type", "application/json");
      $requestBodyObj = [];
      $dataArray = [];
      foreach ($records as $record) {
        $recordJSON = EntityAPIHandler::getInstance($record)
          ->getZCRMRecordAsJSON();
        if ($record->getEntityId() != NULL) {
          $recordJSON['id'] = $record->getEntityId();
        }
        array_push($dataArray, $recordJSON);
      }
      $requestBodyObj["data"] = $dataArray;
      $this->requestBody = $requestBodyObj;

      //Fire Request
      $bulkAPIResponse = APIRequest::getInstance($this)->getBulkAPIResponse();
      $upsertRecords = [];
      $responses = $bulkAPIResponse->getEntityResponses();
      $size = sizeof($responses);
      for ($i = 0; $i < $size; $i++) {
        $entityResIns = $responses[$i];
        if (APIConstants::STATUS_SUCCESS === $entityResIns->getStatus()) {
          $responseData = $entityResIns->getResponseJSON();
          $recordDetails = $responseData["details"];
          $newRecord = $records[$i];
          EntityAPIHandler::getInstance($newRecord)
            ->setRecordProperties($recordDetails);
          array_push($upsertRecords, $newRecord);
          $entityResIns->setData($newRecord);
        }
        else {
          $entityResIns->setData(NULL);
        }
      }
      $bulkAPIResponse->setData($upsertRecords);
      return $bulkAPIResponse;
    } catch (ZCRMException $e) {
      throw $e;
    }
  }

  public function deleteRecords($entityIds) {
    if (sizeof($entityIds) > 100) {
      throw new ZCRMException(APIConstants::API_MAX_RECORDS_MSG, APIConstants::RESPONSECODE_BAD_REQUEST);
    }
    try {
      $this->urlPath = $this->module->getAPIName();
      $this->requestMethod = APIConstants::REQUEST_METHOD_DELETE;
      $this->addHeader("Content-Type", "application/json");
      $this->addParam("ids", implode(",", $entityIds));//converts array to string with specified seperator

      //Fire Request
      $bulkAPIResponse = APIRequest::getInstance($this)->getBulkAPIResponse();
      $responses = $bulkAPIResponse->getEntityResponses();

      foreach ($responses as $entityResIns) {
        $responseData = $entityResIns->getResponseJSON();
        $responseJSON = $responseData["details"];
        $record = ZCRMRecord::getInstance($this->module->getAPIName(), $responseJSON["id"]);
        $entityResIns->setData($record);
      }
      return $bulkAPIResponse;
    } catch (ZCRMException $exception) {
      APIExceptionHandler::logException($exception);
      throw $exception;
    }
  }

  public function getAllDeletedRecords() {
    return self::getDeletedRecords("all");
  }

  public function getRecycleBinRecords() {
    return self::getDeletedRecords("recycle");
  }

  public function getPermanentlyDeletedRecords() {
    return self::getDeletedRecords("permanent");
  }

  private function getDeletedRecords($type) {
    try {
      $this->urlPath = $this->module->getAPIName() . "/deleted";
      $this->requestMethod = APIConstants::REQUEST_METHOD_GET;
      $this->addHeader("Content-Type", "application/json");
      $this->addParam("type", $type);

      $responseInstance = APIRequest::getInstance($this)->getBulkAPIResponse();
      $responseJSON = $responseInstance->getResponseJSON();
      $trashRecords = $responseJSON["data"];
      $trashRecordList = [];
      foreach ($trashRecords as $trashRecord) {
        $trashRecordInstance = ZCRMTrashRecord::getInstance($trashRecord['type'], $trashRecord['id']);
        self::setTrashRecordProperties($trashRecordInstance, $trashRecord);
        array_push($trashRecordList, $trashRecordInstance);
      }

      $responseInstance->setData($trashRecordList);

      return $responseInstance;
    } catch (ZCRMException $exception) {
      APIExceptionHandler::logException($exception);
      throw $exception;
    }
  }

  public function setTrashRecordProperties($trashRecordInstance, $recordProperties) {
    if ($recordProperties['display_name'] != NULL) {
      $trashRecordInstance->setDisplayName($recordProperties['display_name']);
    }
    if ($recordProperties['created_by'] != NULL) {
      $createdBy = $recordProperties['created_by'];
      $createdBy_User = ZCRMUser::getInstance($createdBy['id'], $createdBy['name']);
      $trashRecordInstance->setCreatedBy($createdBy_User);
    }
    if ($recordProperties['deleted_by'] != NULL) {
      $deletedBy = $recordProperties['deleted_by'];
      $deletedBy_User = ZCRMUser::getInstance($deletedBy['id'], $deletedBy['name']);
      $trashRecordInstance->setDeletedBy($deletedBy_User);
    }
    $trashRecordInstance->setDeletedTime($recordProperties['deleted_time']);

  }

  public function getRecords($cvId, $sortByField, $sortOrder, $page, $perPage) {
    try {
      $this->urlPath = $this->module->getAPIName();
      $this->requestMethod = APIConstants::REQUEST_METHOD_GET;
      $this->addHeader("Content-Type", "application/json");
      if ($cvId != NULL) {
        $this->addParam("cvid", $cvId);
      }
      if ($sortByField != NULL) {
        $this->addParam("sort_by", $sortByField);
      }
      if ($sortOrder != NULL) {
        $this->addParam("sort_order", $sortOrder);
      }

      $this->addParam("page", $page);
      $this->addParam("per_page", $perPage);

      $responseInstance = APIRequest::getInstance($this)->getBulkAPIResponse();
      $responseJSON = $responseInstance->getResponseJSON();
      $records = $responseJSON["data"];
      $recordsList = [];
      foreach ($records as $record) {
        $recordInstance = ZCRMRecord::getInstance($this->module->getAPIName(), $record["id"]);
        EntityAPIHandler::getInstance($recordInstance)
          ->setRecordProperties($record);
        array_push($recordsList, $recordInstance);
      }

      $responseInstance->setData($recordsList);

      return $responseInstance;
    } catch (ZCRMException $exception) {
      APIExceptionHandler::logException($exception);
      throw $exception;
    }
  }

  public function getRecordsModifiedSince($cvId, $modifiedSince, $sortByField, $sortOrder, $page, $perPage) {
    try {
      $this->urlPath = $this->module->getAPIName();
      $this->requestMethod = APIConstants::REQUEST_METHOD_GET;
      $this->addHeader("Content-Type", "application/json");
      if ($modifiedSince != NULL) {
        $this->addHeader("If-Modified-Since", $modifiedSince);
      }
      if ($cvId != NULL) {
        $this->addParam("cvid", $cvId);
      }
      if ($sortByField != NULL) {
        $this->addParam("sort_by", $sortByField);
      }
      if ($sortOrder != NULL) {
        $this->addParam("sort_order", $sortOrder);
      }

      $this->addParam("page", $page);
      $this->addParam("per_page", $perPage);

      $responseInstance = APIRequest::getInstance($this)->getBulkAPIResponse();
      $responseJSON = $responseInstance->getResponseJSON();
      $records = $responseJSON["data"];
      $recordsList = [];
      foreach ($records as $record) {
        $recordInstance = ZCRMRecord::getInstance($this->module->getAPIName(), $record["id"]);
        EntityAPIHandler::getInstance($recordInstance)
          ->setRecordProperties($record);
        array_push($recordsList, $recordInstance);
      }

      $responseInstance->setData($recordsList);

      return $responseInstance;
    } catch (ZCRMException $exception) {
      APIExceptionHandler::logException($exception);
      throw $exception;
    }
  }

  public function searchRecords($searchWord, $page, $perPage, $type) {
    try {
      $this->urlPath = $this->module->getAPIName() . "/search";
      $this->requestMethod = APIConstants::REQUEST_METHOD_GET;
      $this->addHeader("Content-Type", "application/json");
      $this->addParam("page", $page);
      $this->addParam("per_page", $perPage);

      switch ($type) {
        case "word":
          $this->addParam("word", $searchWord);
          break;
        case "phone":
          $this->addParam("phone", $searchWord);
          break;
        case "email":
          $this->addParam("email", $searchWord);
          break;
        case "criteria":
          $this->addParam("criteria", $searchWord);
          break;
      }


      $responseInstance = APIRequest::getInstance($this)->getBulkAPIResponse();
      $responseJSON = $responseInstance->getResponseJSON();
      $records = $responseJSON["data"];
      $recordsList = [];
      foreach ($records as $record) {
        $recordInstance = ZCRMRecord::getInstance($this->module->getAPIName(), $record["id"]);
        EntityAPIHandler::getInstance($recordInstance)
          ->setRecordProperties($record);
        array_push($recordsList, $recordInstance);
      }

      $responseInstance->setData($recordsList);

      return $responseInstance;
    } catch (ZCRMException $exception) {
      APIExceptionHandler::logException($exception);
      throw $exception;
    }
  }

  public function massUpdateRecords($idList, $apiName, $value) {
    if (sizeof($idList) > 100) {
      throw new ZCRMException(APIConstants::API_MAX_RECORDS_MSG, APIConstants::RESPONSECODE_BAD_REQUEST);
    }
    try {
      $inputJSON = self::constructJSONForMassUpdate($idList, $apiName, $value);
      $this->urlPath = $this->module->getAPIName();
      $this->requestMethod = APIConstants::REQUEST_METHOD_PUT;
      $this->addHeader("Content-Type", "application/json");
      $this->requestBody = $inputJSON;
      $this->apiKey = 'data';
      $bulkAPIResponse = APIRequest::getInstance($this)->getBulkAPIResponse();

      $updatedRecords = [];
      $responses = $bulkAPIResponse->getEntityResponses();
      $size = sizeof($responses);
      for ($i = 0; $i < $size; $i++) {
        $entityResIns = $responses[$i];
        if (APIConstants::STATUS_SUCCESS === $entityResIns->getStatus()) {
          $responseData = $entityResIns->getResponseJSON();
          $recordJSON = $responseData["details"];

          $updatedRecord = ZCRMRecord::getInstance($this->module->getAPIName(), $recordJSON["id"]);
          EntityAPIHandler::getInstance($updatedRecord)
            ->setRecordProperties($recordJSON);
          array_push($updatedRecords, $updatedRecord);
          $entityResIns->setData($updatedRecord);
        }
        else {
          $entityResIns->setData(NULL);
        }
      }
      $bulkAPIResponse->setData($updatedRecords);

      return $bulkAPIResponse;
    } catch (ZCRMException $exception) {
      APIExceptionHandler::logException($exception);
      throw $exception;
    }
  }

  public function constructJSONForMassUpdate($idList, $apiName, $value) {
    $massUpdateArray = [];
    foreach ($idList as $id) {
      $updateJson = [];
      $updateJson["id"] = "" . $id;
      $updateJson[$apiName] = $value;
      array_push($massUpdateArray, $updateJson);
    }

    return ["data" => $massUpdateArray];
  }
}

