<?php

namespace SJFoundation\Infrastructure\CoinsApi;

use SJFoundation\Infrastructure\SJAuth;

class ErisContractAPI
{
    const BASE_URL = "https://sjcoins-testing.softjourn.if.ua/";
//    const BASE_URL = 'https://vending.softjourn.if.ua/api/';
    const PROJECT_TYPE = "project";
    const CURRENCY_TYPE = "currency";

    static function sendRequest($endpoint) {
        $baseUrl = self::BASE_URL;
        $token = SJAuth::getAccessToken();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}${endpoint}",
            CURLOPT_HTTPHEADER => array("Authorization: bearer ${token}")
        ));
        $resp = curl_exec($curl);
        curl_close($curl);
        return json_decode($resp);
    }

    static function sendRequestWithParams($endpoint, $params) {
        $baseUrl = self::BASE_URL;
        $token = SJAuth::getAccessToken();
        $json = json_encode($params);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}${endpoint}",
            CURLOPT_HTTPHEADER => array("Authorization: bearer ${token}", "Content-Type: application/json"),
            CURLOPT_POSTFIELDS => $json
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        return json_decode($resp);
    }

    static function getProjectContractTypes()
    {
        $projectType = self::PROJECT_TYPE;
        $resultArray = self::sendRequest("coins/api/v1/contracts/types/${projectType}");
        return $resultArray;
    }

    static function getCurrencyContractTypes()
    {
        $currencyType = self::CURRENCY_TYPE;
        $resultArray = self::sendRequest("coins/api/v1/contracts/types/${currencyType}");
        return $resultArray;
    }

    static function getInstances($id)
    {
        $resultArray = self::sendRequest("coins/api/v1/contracts/instances/${id}");

        return $resultArray;
    }

    static function createContract($projectId,$options)
    {

        $data = array("contractId" => $projectId, "parameters" => $options);
        $json = json_encode($data);
        $curl = curl_init();
        $baseUrl = self::BASE_URL;
        $token = SJAuth::getAccessToken();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}coins/api/v1/contracts/instances",
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array("Authorization: bearer ${token}"
            ,"Content-Type: application/json"),
            CURLOPT_POSTFIELDS => $json
        ));
        $resp = curl_exec($curl);
        curl_close($curl);
        $resp = json_decode($resp);

        if(isset($resp->address)){
            return $resp->address;
        } else {
            return false;
        }
    }

    static function getOwnerErisAccount()
    {
        return  self::getErisUser()->address;
    }

    static function getErisUser(){
        $resultArray = self::sendRequest("coins/api/v1/eris/account");
        return $resultArray;
    }

    static function getErisProjectByAddress($address) {
        $resultArray = self::sendRequest("coins/api/v1/crowdsale/${address}");
        return $resultArray;
    }

    static function donateProject($coinAddress, $spenderAddress, $amount) {
        $params = [
            'contractAddress' => $coinAddress,
            'spenderAddress' => $spenderAddress,
            'amount' => $amount,
        ];

        $resultArray = self::sendRequestWithParams('coins/api/v1/crowdsale/donate/', $params);

        return $resultArray;
    }

    static function withdraw($address) {
        $resultArray = self::sendRequestWithParams("coins/api/v1/crowdsale/withdraw/${address}", []);
        return $resultArray;
    }
}