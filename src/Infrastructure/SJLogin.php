<?php

namespace SJFoundation\Infrastructure;

use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;
use WP_Error;
use WP_User;

class SJLogin {

    function sj_authenticate() {
        if (!session_id()) {
            session_start();
        }
        add_filter('authenticate', array($this,'authenticate'), 20, 3);
    }

    function authenticate($user, $username, $password) {
        $sjAuth = new SJAuth();
        $sjAuth->logout();


        if (!$username) {
            return $user;
        }

        if(!$sjAuth->login($username, $password)) {
            return $user;
        }

        if ( is_a($user, 'WP_User')) {
            return $user;
        }

        $userData = $sjAuth::getAccount();

        $user = get_user_by('login', $username);

        if (!$user) {
            $user = $this->create_user($username, $username.'@softjourn.com' , $userData->name, $userData->surname);
            if (!$user) {
                return new WP_Error('invalid_username', __('SJ Login Error: Create wp user error'));
            }
        }
        return new WP_User($user->ID);

    }

    function create_user($username, $email, $firstname, $lastname) {
        if ( empty($username) || empty($email)) return null;
        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'first_name' => $firstname,
            'last_name' => $lastname
        ));

        $user = new WP_User($user_id);

        SJProjectsApi::createUser($user->ID, $user->user_email, $user->display_name);
        $user->set_role('contributor');
        return $user;
    }
}