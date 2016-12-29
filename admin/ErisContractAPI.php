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
//        echo "TOKEN:";
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

    static function getCurrencyContractTypes()
    {
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

    static function getInstances($id)
    {
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

    static function createContract($projectId,$options)
    {
        //TODO parameters
        //TODO get eris address of project creator
        //address ifSuccessfulSendTo,
        //        uint fundingGoalInTokens,
        //      uint durationInMinutes,
        //      bool onGoalReached,
        //      address[] addressOfTokensAccumulated

        $data = array("contractId" => $projectId, "parameters" => $options);
        $json = json_encode($data);
        $curl = curl_init();
        $baseUrl = self::BASE_URL;
        $token = SJAuth::getAccessToken();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}/api/v1/contracts/instances",
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => array("Authorization: bearer ${token}"),
            CURLOPT_POSTFIELDS => $json
        ));
        $resp = curl_exec($curl);
        curl_close($curl);
        var_dump(json_decode($resp));
    }

    static function getOwnerErisAccount()
    {
        return  self::getErisUser()->address;
    }

    static function getErisUser(){
        $curl = curl_init();
        $baseUrl = self::BASE_URL;
        $token = SJAuth::getAccessToken();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}/api/v1/eris/account",
            CURLOPT_HTTPHEADER => array("Authorization: bearer ${token}")
        ));
        $resp = curl_exec($curl);
        curl_close($curl);
        $user = json_decode($resp);
        return $user;
    }

}