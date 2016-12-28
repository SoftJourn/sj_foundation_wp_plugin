<?php


class SJLogin {

    function sj_authenticate() {
        add_filter('authenticate', array($this,'authenticate'), 20, 3);
    }

    function authenticate($user, $username, $password) {
        if ( is_a($user, 'WP_User') ) {
            return $user;
        }

        if (!$username) {
            return $user;
        }

        $sjAuth = new SJAuth();
        $sjAuth->login($username, $password);
        $sjAuth::refreshToken();
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
            'ID' => $username,
            'user_login' => $username,
            'user_email' => $email,
            'first_name' => $firstname,
            'last_name' => $lastname
        ));

        $user = new WP_User($user_id);
        $user->set_role('contributor');
        return $user;
    }
}