<?php

class ErisContractAPI
{

//    const BASE_URL = "http://192.168.102.45:8888";

    const BASE_URL = "https://sjcoins-testing.softjourn.if.ua/coins";
    //https://sjcoins-testing.softjourn.if.ua/coins/api/v1/contracts/types/project
    const PROJECT_TYPE = "project";
    const CURRENCY_TYPE = "currency";

    static function getProjectContractTypes()
    {

        $baseUrl = self::BASE_URL;
        $projectType = self::PROJECT_TYPE;
        $token = SJAuth::getAccessToken();
        echo "TOKEN:";
        var_dump(SJAuth::getAccessToken());

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}/api/v1/contracts/types/${projectType}",
            CURLOPT_HTTPHEADER => array("Authorization: bearer ${token}")
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        $resultArray = json_decode($resp);

        return $resultArray;
    }

    static function getCurrencyContractTypes(){
        $baseUrl = self::BASE_URL;
        $currencyType = self::CURRENCY_TYPE;
        $token = SJAuth::getAccessToken();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}/api/v1/contracts/types/${currencyType}",
            CURLOPT_HTTPHEADER => array("Authorization: bearer ${token}")
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        $resultArray = json_decode($resp);
        return $resultArray;
    }

    static function getInstances($id){
        $baseUrl = self::BASE_URL;
        $token = SJAuth::getAccessToken();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}/api/v1/contracts/instances/${id}",
            CURLOPT_HTTPHEADER => array("Authorization: bearer ${token}")
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        $resultArray = json_decode($resp);

        return $resultArray;
    }

    static function createContract($id){
        $curl = curl_init();
        $baseUrl = self::BASE_URL;
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}/api/v1/contracts/instances",
            CURLOPT_POST => 1,

        ));
        $resp = curl_exec($curl);
        curl_close($curl);
    }

}