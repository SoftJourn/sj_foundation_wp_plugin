<?php

class SJAuth {

    const BASE_URL = 'https://vending.softjourn.if.ua/api/';
    const BASE_KEY = 'dXNlcl9jcmVkOnN1cGVyc2VjcmV0';


    static function getAccessToken() {
        return $_SESSION['access_token'];
    }

    function login($username, $password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL. 'auth/oauth/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "username=${username}&password=${password}&grant_type=password");
        curl_setopt($ch, CURLOPT_USERPWD, base64_decode(self::BASE_KEY));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $response = curl_exec ($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close ($ch);

        $body = json_decode($body);

        $_SESSION['token'] = $body->token;
        $_SESSION['refresh_token'] = $body->token;
        $_SESSION['access_token'] = $body->token;

        return $body;
    }

    static function refreshToken()
    {
        $refreshToken = $_SESSION['refresh_token'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL. 'auth/oauth/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "refresh_token=${refreshToken}&grant_type=refresh_token");
        curl_setopt($ch, CURLOPT_USERPWD, base64_decode(self::BASE_KEY));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $response = curl_exec ($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close ($ch);

        return json_decode($body);
    }

    static function getAccount()
    {
        $accessToken = $_SESSION['access_token'];
        $headers = array(
            "Authorization: Bearer ${accessToken}"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL. 'coins/api/v1/account');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $response = curl_exec ($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close ($ch);

        return json_decode($body);
    }
}