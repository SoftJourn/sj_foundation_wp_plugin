<?php

class ErisContractAPI
{

//    const BASE_URL = "http://192.168.102.45:8888";

    const BASE_URL = "https://sjcoins-testing.softjourn.if.ua/coins";
    const PROJECT_TYPE = "project";

    static function getProjectContractTypes()
    {

        $baseUrl = self::BASE_URL;
        $projectType = self::PROJECT_TYPE;
        $token = SJAuth::getAccessToken();
//        var_dump(SJAuth::getAccessToken());

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