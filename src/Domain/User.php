<?php

namespace SJFoundation\Domain;

/**
 * Class User
 * @package SJFoundation\Domain
 */
class User {

    public $isModerator;

    static function isModerator() {
        $user = wp_get_current_user();
        if ( in_array( 'editor', (array) $user->roles ) ) {
            return true;
        } elseif ( in_array( 'administrator', (array) $user->roles ) ) {
            return true;
        }
        return false;
    }
}