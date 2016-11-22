<?php

class SJProjectsApi {

//    const API_ENDPOINT = 'http://node:3010/api/';
    const API_ENDPOINT = 'http://localhost:3010/api/';

    static function put($endpoint, $params) {
        $args = array(
            'headers' =>  array('content-type' => 'application/json'),
            'method' => 'PUT',
            'body'    =>  json_encode($params),
        );

        $url = self::API_ENDPOINT . $endpoint;

        wp_remote_request($url, $args);
    }

    static function post($endpoint, $params) {
        $args = array(
            'headers' =>  array('content-type' => 'application/json'),
            'method' => 'POST',
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

    static function createProject($id, $name, $price, $status, $canDonateMore = false) {
        $params = [
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'status' => $status,
            'canDonateMore' => $canDonateMore
        ];
        self::put('projects', $params);
    }

    static function getProject($id) {
        $response = wp_remote_get(self::API_ENDPOINT.'projects/'.$id);
        $balanceObject = json_decode($response['body']);
        return $balanceObject;
    }

    static function getProjectPledgeSum($id) {
        $transactions = self::getProjectTransactions($id);
        $sum = 0;
        foreach ($transactions as $transaction) {
            $sum += $transaction->amount;
        }
        return $sum;
    }

    static function backProject($userId, $projectId, $amount) {
        $params = [
            'accountId' => $userId,
            'projectId' => $projectId,
            'amount' => $amount,
            'status' => 'active',
        ];
        self::put('transactions', $params);
    }

    static function getProjectTransactions($id) {
        $response = wp_remote_get(self::API_ENDPOINT.'projects/'. $id .'/transactions');

        return json_decode($response['body']);
    }

    static function getProjectAccountTransactions($userId, $projectId) {
        $response = wp_remote_get(self::API_ENDPOINT.'accounts/'. $userId .'/transactions?filter={"where":{"projectId":'.$projectId.'}}');

        return json_decode($response['body']);
    }

    static function updateProjectTransactionsStatus($projectId, $status) {
        $params = [
            'status' => $status,
        ];

        self::post('transactions/update?where={"projectId":'.$projectId.'}', $params);
    }

    static function updateProjectStatus($projectId, $status) {
        $params = [
            'status' => $status,
        ];

        self::post('projects/update?where={"id":'.$projectId.'}', $params);
    }

    static function getAccountBalance($id) {
        $response = wp_remote_get(self::API_ENDPOINT.'accounts/getBalance?id='. $id);
        $balanceObject = json_decode($response['body']);
        return $balanceObject;
    }

    static function getAccountTransactions($id) {
        $response = wp_remote_get(self::API_ENDPOINT.'transactions?filter={"include":"project", "where":{"accountId": '.$id.'}, "order":"id DESC"}');
        $transactionsObject = json_decode($response['body']);
        return $transactionsObject;
    }

    static function setCoinsToAll($amount) {
        return wp_remote_get(self::API_ENDPOINT . 'accounts/setCoinsToAll?amount='.(int)$amount);
    }
}
