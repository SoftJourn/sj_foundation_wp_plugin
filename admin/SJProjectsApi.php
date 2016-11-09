<?php

class SJProjectsApi {

    const API_ENDPOINT = 'http://node:3010/api/';

    static function put($endpoint, $params) {
        $args = array(
            'headers' =>  array('content-type' => 'application/json'),
            'method' => 'PUT',
            'body'    =>  json_encode($params),
        );

        $url = self::API_ENDPOINT . $endpoint;

        wp_remote_request($url, $args);
    }

    static function createUser($id, $email, $name) {
        $params = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'amount' => 100
        ];
        self::put('accounts', $params);
    }

    static function getUser($id) {
        return wp_remote_get(self::API_ENDPOINT.'accounts/'.$id);
    }

    static function createProject($id, $name, $price) {
        $params = [
            'id' => $id,
            'name' => $name,
            'price' => $price
        ];
        self::put('projects', $params);
    }

    static function getProject($id) {
        return wp_remote_get(self::API_ENDPOINT.'projects/'.$id);
    }

    static function backProject($userId, $projectId, $amount) {
        $params = [
            'accountId' => $userId,
            'projectId' => $projectId,
            'amount' => $amount
        ];
        self::put('transactions', $params);
    }

    static function getProjectTransactions($id) {
        $response = wp_remote_get(self::API_ENDPOINT.'projects/'.$id . '/transactions');
        return json_decode($response['body']);
    }
}
