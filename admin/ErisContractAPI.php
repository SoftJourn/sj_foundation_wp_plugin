<?php

class ErisContractAPI
{

    const BASE_URL = "http://192.168.102.45:8888";

    //const BASE_URL = "https://sjcoins-testing.softjourn.if.ua/coins";
    static function getContractTypes()
    {

        $baseUrl = self::BASE_URL;
//        $sj = new SJAuth();
//        $sj->login("vromanchuk", "");
//        var_dump(SJAuth::getAccessToken());
        $token = "eyJhbGciOiJSUzI1NiJ9.eyJleHAiOjE0ODI5Mjk1MjUsInVzZXJfbmFtZSI6InZyb21hbmNodWsiLCJhdXRob3JpdGllcyI6WyJST0xFX0JJTExJTkciLCJST0xFX1VTRVJfTUFOQUdFUiIsIlJPTEVfVVNFUiIsIlJPTEVfSU5WRU5UT1JZIl0sImp0aSI6ImQ1MjI3NDVjLWVlMzEtNDk4Yi1iYTgyLTE4MjMzMDA2NDk5NiIsImNsaWVudF9pZCI6InVzZXJfY3JlZCIsInNjb3BlIjpbInJlYWQiLCJ3cml0ZSJdfQ.IAvEPweDhwVBUGjUQparPucOLhUAYNHNg1AavIwVjlEvugwNq7HJS-GwMHY4IQokl5dNfIDiNzOZp8T0mm8nYzzT54pU-ZIvu8oqZCbWB1Z52xQE9a4KiRcvQ6a_r3yQ9gqiSRs5vcPr95qhdaGJkxmiTEsRz-wSVAjhUUk0EO_TJLUAojUD8i1V-GS9Pe7XoKyN32sQcKYv8GgrZ3WW4SZgM0uU57_v0u2QF_th_MU9PdLRC3WS5pW0zGiMa9LIu15CoiIKkhtJBq9aD5Q9rREWzBjZMt6J-UE-ZP-9ho6wzFj60Jlbr24OH-4bS-gxgEEqp_h4tTxB5cF5boJZcw";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "${baseUrl}/api/v1/contracts",
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        $resultArray = json_decode($resp);

        return $resultArray;
    }

    static function getIdFromName($name){
        $types = self::getContractTypes();
        foreach ( $types as $val){
            if($val->name == $name)
                return $val->id;
        }
        return -1;
    }
}