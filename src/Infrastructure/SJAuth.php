<?php

namespace SJFoundation\Infrastructure;

class SJAuth {

    static function getAccessToken() {
        if(!isset($_SESSION['access_token'])) {
            wp_logout();
        }
        self::checkTokenExpiration();
        return $_SESSION['access_token'];
    }

    static function checkTokenExpiration() {
        if (time() > $_SESSION['token_expiration']) {
            self::refreshToken();
        }
    }

    function login($username, $password)
    {
        $_SESSION['refresh_token'] = '';
        $_SESSION['access_token'] = '';
        $post = http_build_query([
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sjFoundationConfig()->coins_api_url . 'auth/oauth/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_USERPWD, base64_decode(sjFoundationConfig()->auth_base_key));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $response = curl_exec ($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close ($ch);
        $body = json_decode($body);

        if (!isset($body->refresh_token)) {
            return false;
        }

        $_SESSION['refresh_token'] = $body->refresh_token;
        $_SESSION['access_token'] = $body->access_token;
        $_SESSION['token_expiration'] = time() + $body->expires_in;

        return $body;
    }

    function logout() {
        unset($_SESSION['refresh_token']);
        unset($_SESSION['access_token']);
        unset($_SESSION['token_expiration']);
    }

    static function refreshToken()
    {
        $refreshToken = $_SESSION['refresh_token'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sjFoundationConfig()->coins_api_url . 'auth/oauth/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "refresh_token=${refreshToken}&grant_type=refresh_token");
        curl_setopt($ch, CURLOPT_USERPWD, base64_decode(sjFoundationConfig()->auth_base_key));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $response = curl_exec ($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close ($ch);

        $body = json_decode($body);
        if (isset($body->refresh_token)) {
            $_SESSION['refresh_token'] = $body->refresh_token;
            $_SESSION['access_token'] = $body->access_token;
            $_SESSION['token_expiration'] = time() + $body->expires_in;
        }

        return $body;
    }

    static function getAccount()
    {
        $accessToken = self::getAccessToken();
        $headers = array(
            "Authorization: Bearer ${accessToken}"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sjFoundationConfig()->coins_api_url . 'coins/api/v1/account');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        $response = curl_exec ($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close ($ch);
        $body = json_decode($body);

        return isset($body->error) ? null : $body;
    }
}