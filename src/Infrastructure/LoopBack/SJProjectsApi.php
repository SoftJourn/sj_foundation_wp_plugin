<?php

namespace SJFoundation\Infrastructure\LoopBack;

use SJFoundation\Domain\Project;

class SJProjectsApi {

    static function apiEndpoint() {
        return sjFoundationConfig()->loopback_api_url;
    }

    static function put($endpoint, $params) {
        $args = array(
            'headers' =>  array('content-type' => 'application/json'),
            'method' => 'PUT',
            'body'    =>  json_encode($params),
        );

        $url = self::apiEndpoint() . $endpoint;

        return wp_remote_request($url, $args);
    }

    static function post($endpoint, $params) {
        $args = array(
            'headers' =>  array('content-type' => 'application/json'),
            'method' => 'POST',
            'body'    =>  json_encode($params),
        );

        $url = self::apiEndpoint() . $endpoint;

        wp_remote_request($url, $args);
    }

    static function delete($endpoint, $params) {
        $args = array(
            'headers' =>  array('content-type' => 'application/json'),
            'method' => 'DELETE',
            'body'    =>  json_encode($params),
        );

        $url = self::apiEndpoint() . $endpoint;

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
        return wp_remote_get(self::apiEndpoint().'accounts/'.$id);
    }

    static function createProject(
        $id,
        $name,
        $price,
        $status,
        $canDonateMore,
        $duration,
        $dueDate,
        $category
    ) {
        $params = [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'canDonateMore' => $canDonateMore,
            'duration' => $duration,
            'dueDate' => $dueDate,
        ];
        $response = self::put('projects', $params);
    }

    static function addContractToProject($id, $contractAddress, $coinsAddress) {
        $params = [
            'id' => $id,
            'status' => Project::STATUS_OPEN,
            'contractAddress' => $contractAddress,
            'coinsAddresses' => $coinsAddress,
            'published' => true,
            'timeCreated' => time(),
            'donationStatus' => 'open'
        ];
        $response = self::put('projects', $params);
    }

    static function getProject($id) {
        $response = wp_remote_get(self::apiEndpoint().'projects/'.$id);
        if (!is_array($response)) {
            return [];
        }
        $balanceObject = json_decode($response['body']);
        return $balanceObject;
    }

    static function getSearchQuery($page = 1, $status = false, $category = false) {
        $query = [
            'where' => [
                'and' => [
                    ['or' => [
                        ['status' => 'open'],
                        ['status' => 'close'],
                        ['status' => 'founded'],
                    ]],
                    ['and' => [
                        ['published' => true,],
                        $status ? ['status' => $status] : null,
                        $category ? ['category' => strtolower(urlencode($category))] : null
                    ]]
                ]

            ],
            'order' => 'timeCreated DESC',
            'limit' => 9,
            'skip' => ($page-1)*9
        ];
        return $query;
    }

    static function getProjects($page = 1, $status = false, $category = false) {
        $filter = self::getSearchQuery($page, $status, $category);

        $response = wp_remote_get(self::apiEndpoint().'projects?filter='.urlencode(json_encode($filter)));
        if (!is_array($response)) {
            return [];
        }
        $projects = json_decode($response['body']);
        return $projects;
    }

    static function getProjectsCount($page = 1, $status = false, $category = false) {
        $filter = self::getSearchQuery($page, $status, $category);
        $response = wp_remote_get(self::apiEndpoint().'projects/count/?where='.urlencode(json_encode($filter['where'])));
        if (!is_array($response)) {
            return 0;
        }
        $projects = json_decode($response['body']);
        return $projects->count;
    }

    static function deleteProject($id) {
        self::delete('projects/' . $id, []);
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

    static function withdraw($projectId) {
        $params = [
            'id' => $projectId,
            'withdraw' => 'true',
        ];
        self::put('projects', $params);
    }

    static function getProjectTransactions($id) {
        $response = wp_remote_get(self::apiEndpoint().'projects/'. $id .'/transactions');
        if (!is_array($response)) {
            return [];
        }
        return json_decode($response['body']);
    }

    static function getProjectAccountTransactions($userId, $projectId) {
        $response = wp_remote_get(self::apiEndpoint().'accounts/'. $userId .'/transactions?filter={"where":{"projectId":'.$projectId.'}}');
        if (!is_array($response)) {
            return [];
        }
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
        $response = wp_remote_get(self::apiEndpoint().'accounts/getBalance?id='. $id);
        if (!is_array($response)) {
            return ['amount' => 0];
        }
        $balanceObject = json_decode($response['body']);
        return $balanceObject;
    }

    static function getAccountTransactions($id) {
        $response = wp_remote_get(self::apiEndpoint().'transactions?filter={"include":"project", "where":{"accountId": '.$id.'}, "order":"id DESC"}');
        if (!is_array($response)) {
            return [];
        }
        $transactionsObject = json_decode($response['body']);
        return $transactionsObject;
    }

    static function setCoinsToAll($amount) {
        return wp_remote_get(self::apiEndpoint() . 'accounts/setCoinsToAll?amount='.(int)$amount);
    }
}
