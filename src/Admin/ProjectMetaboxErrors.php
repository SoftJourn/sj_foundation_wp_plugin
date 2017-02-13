<?php

namespace SJFoundation\Admin;

use SJFoundation\Infrastructure\SJAuth;

class ProjectMetaboxErrors {

    public static function add_notice_contract_error( $location ) {
        remove_filter( 'redirect_post_location', array( 'ProjectMetaboxErrors', 'add_notice_contract_error' ), 99 );
        return add_query_arg( array( 'create_contract_error' => 'api' ), $location );
    }

    public static function add_notice_contract_author_error( $location ) {
        remove_filter( 'redirect_post_location', array( 'ProjectMetaboxErrors', 'add_notice_contract_error' ), 99 );
        return add_query_arg( array( 'create_contract_error' => 'author' ), $location );
    }

    public static function add_notice_contract_price_error( $location ) {
        remove_filter( 'redirect_post_location', array( 'ProjectMetaboxErrors', 'add_notice_contract_error' ), 99 );
        return add_query_arg( array( 'create_contract_error' => 'price' ), $location );
    }

    public static function ldap_admin_notice() {
        if ( isset( $_GET['create_contract_error']) &&  $_GET['create_contract_error'] == 'api' ) {
            $message = 'Error create crowdsale contract (check coins api)';
            self::showError($message);
        }

        if( isset( $_GET['create_contract_error']) &&  $_GET['create_contract_error'] == 'author' ) {
            $message = 'Author is not ldap account';
            self::showError($message);
        }

        if( isset( $_GET['create_contract_error']) &&  $_GET['create_contract_error'] == 'price' ) {
            $message = 'Fixed price can\'t  be 0' ;
            self::showError($message);
        }

        $account = SJAuth::getAccount();
        if (!$account) {
            $message = 'You need to login with your SoftJourn LDAP account to publish project! You can save draft only';
            self::showError($message);
        }


    }

    public function showError($message) {?>
        <div class="notice error">
        <p><?php _e( $message, 'sj_foundation_domain'.$message ); ?></p>
        </div><?php
    }
}