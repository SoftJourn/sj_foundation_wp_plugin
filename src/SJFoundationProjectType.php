<?php

namespace SJFoundation;

use Attachments;
use DateTime;
use SJFoundation\Admin\ProjectMetabox;
use SJFoundation\Infrastructure\CoinsApi\ErisContractAPI;
use SJFoundation\Infrastructure\SJLogin;
use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;

class SJFoundationProjectType {

    public function __construct() {

    }

    public function init() {

        $sjLogin = new SJLogin();
        $sjLogin->sj_authenticate();

        add_action('rest_api_init', array($this, 'initRoutes'));
        add_action( 'init', array($this, 'initProjectPostType'), 0 );
        add_action( 'user_register', array($this, 'registration_save'), 10, 1 );
        add_action( 'profile_update', array($this, 'profile_update'), 10, 2 );
        add_action( 'attachments_register', [$this, 'projectAttachmentsInit'] );
        add_filter('show_admin_bar', '__return_false');
        add_filter( 'rest_prepare_project_type', [$this, 'prepareProjectPost'], 10, 3 );
        add_filter( 'preview_post_link', [$this, 'change_post_preview_link'], 10, 2 );
        add_filter( 'excerpt_length', array($this, 'wpdocs_custom_excerpt_length'), 999 );

        add_image_size( 'project-image-size', 620, 320 );
        $this->initProjectMetabox();
    }

    function set_default_admin_color($user_id) {
        $args = array(
            'ID' => $user_id,
            'admin_color' => 'light'
        );
        wp_update_user( $args );
    }

    public function initProjectMetabox() {
        $project_metabox = new ProjectMetabox();
        $project_metabox->init();
    }

    public function registration_save($user_id) {
        $name = isset($_POST['first_name']) ? $_POST['first_name'] : '' . isset($_POST['last_name']) ? $_POST['last_name'] : '';
        if ( isset( $_POST['email'] ) ) {
            SJProjectsApi::createUser($user_id, $_POST['email'], $name);
        }
    }

    public function profile_update( $user_id ) {
        if ( isset( $_POST['email'] ) ) {
            SJProjectsApi::createUser($user_id, $_POST['email'], $_POST['first_name'].' '.$_POST['last_name']);
        }
    }

    public function initRoutes() {
        $myProductController = new RestController('project_type');
        $myProductController->register_routes();
    }

    public function after_login_default_page() {
        return '/';
    }

    public function initProjectPostType() {

        $labels = array(
            'name'                  => _x( 'Project Type', 'Post Type General Name', 'text_domain' ),
            'singular_name'         => _x( 'Project Type', 'Post Type Singular Name', 'text_domain' ),
            'menu_name'             => __( 'Projects', 'text_domain' ),
            'name_admin_bar'        => __( 'Project', 'text_domain' ),
            'archives'              => __( 'Project Archives', 'text_domain' ),
            'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
            'all_items'             => __( 'All Projects', 'text_domain' ),
            'add_new_item'          => __( 'Add New Project', 'text_domain' ),
            'add_new'               => __( 'Add New', 'text_domain' ),
            'new_item'              => __( 'New Project', 'text_domain' ),
            'edit_item'             => __( 'Edit Project', 'text_domain' ),
            'update_item'           => __( 'Update Project', 'text_domain' ),
            'view_item'             => __( 'View Project', 'text_domain' ),
            'view_items'             => __( 'View Projects', 'text_domain' ),
            'search_items'          => __( 'Search Project', 'text_domain' ),
            'not_found'             => __( 'Not found', 'text_domain' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
            'featured_image'        => __( 'Featured Image', 'text_domain' ),
            'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
            'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
            'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
            'insert_into_item'      => __( 'Insert into project', 'text_domain' ),
            'uploaded_to_this_item' => __( 'Uploaded to this project', 'text_domain' ),
            'items_list'            => __( 'Project list', 'text_domain' ),
            'items_list_navigation' => __( 'Projects list navigation', 'text_domain' ),
            'filter_items_list'     => __( 'Filter project list', 'text_domain' ),
        );
        $args = array(
            'label'                 => __( 'Project Type', 'text_domain' ),
            'description'           => __( 'Project', 'text_domain' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'revisions'),
            'taxonomies'            => array( 'category' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-format-aside',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => true,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'rewrite'            => array( 'slug' => 'project' ),
            'show_in_rest'          => true,
            'rest_base'             => 'projects',
            'rest_controller_class' => 'WP_REST_Project_Controller',
        );
        register_post_type( 'project_type', $args );
    }

    public function prepareProjectPost($data, $post, $request) {
        $_data = $data->data;
        $user = wp_get_current_user();
        $thumbnail_id = get_post_thumbnail_id( $post->ID );
        $thumbnail = wp_get_attachment_image_src( $thumbnail_id, 'project-image-size' );
        $_data['featured_image_thumbnail_url'] = $thumbnail[0];
        $projectApiData = SJProjectsApi::getProject($post->ID);

        $price = $projectApiData->price;
        $dueDate = $projectApiData->dueDate;

        $attachments = [];
        $attachmentsObject = new Attachments( 'project_attachments', $post->ID );
        while($attachmentsObject->get()){
            $attachment = [];
            $attachment['id'] = $attachmentsObject->id();
            $attachment['url'] = $attachmentsObject->url();
            $attachment['title'] = $attachmentsObject->field('title');
            $attachment['thumbnail'] = $attachmentsObject->image( 'thumbnail' );
            $attachment['caption'] = $attachmentsObject->field('caption');
            $attachments[] = $attachment;
        }

        $now = new DateTime();
        $dateDueDateTime = new DateTime($dueDate);
        $dateDueDateTime->setTime(23, 59, 59);
        $days = (int)$now->diff($dateDueDateTime)->days;
        $donationType = 'closed';
        if (
            $days >= 0 &&
            isset($projectApiData->status) &&
            (($projectApiData->status === '' || $projectApiData->canDonateMore) || !$projectApiData->price)
        ) {
            $donationType = 'open';
        }

        $_data['contract_project'] = ErisContractAPI::getErisProjectByAddress($projectApiData->contractAddress);
        $_data['attachments'] = $attachments;
        $_data['donation_type'] = $donationType;
        $_data['price'] = $price;
        $_data['days_remain'] = $days;
        $_data['due_date'] = $dueDate;
        $_data['api_data'] = $projectApiData ? $projectApiData : ['canDonateMore' => false];
        $_data['transactions'] = SJProjectsApi::getProjectTransactions($post->ID);
        $_data['user_transactions'] = SJProjectsApi::getProjectAccountTransactions($user->ID, $post->ID);
        $_data['comments_count'] = wp_count_comments( $post->ID );
        $_data['prev_project'] = $this->get_previous_project_slug($post->ID);
        $_data['next_project'] = $this->get_next_project_slug($post->ID);
        $_data['categories'] = wp_get_object_terms($post->ID, 'category');

        $data->data = $_data;
        return $data;
    }

    function get_previous_project_slug( $post_id ) {
        global $post;
        $oldGlobal = $post;
        $post = get_post( $post_id );
        $previous_post = get_previous_post();
        $post = $oldGlobal;
        if ( '' == $previous_post ) {
            return '';
        }
        return $previous_post->post_name;
    }

    function get_next_project_slug( $post_id ) {
        global $post;
        $oldGlobal = $post;
        $post = get_post( $post_id );
        $next_project = get_next_post();
        $post = $oldGlobal;
        if ( '' == $next_project ) {
            return '';
        }
        return $next_project->post_name;
    }

    public function projectAttachmentsInit( Attachments $attachments )
    {
        $fields         = array(
            array(
                'name'      => 'title',                         // unique field name
                'type'      => 'text',                          // registered field type
                'label'     => __( 'Title', 'attachments' ),    // label to display
                'default'   => 'title',                         // default value upon selection
            ),
            array(
                'name'      => 'caption',                       // unique field name
                'type'      => 'textarea',                      // registered field type
                'label'     => __( 'Caption', 'attachments' ),  // label to display
                'default'   => 'caption',                       // default value upon selection
            ),
        );

        $args = array(
            'label'         => 'Project Attachments',
            'post_type'     => array( 'project_type' ),
            'position'      => 'normal',
            'priority'      => 'high',
            'note'          => 'Attach files here!',
            'append'        => true,
            'button_text'   => __( 'Attach Files', 'attachments' ),
            'modal_text'    => __( 'Attach', 'attachments' ),
            'router'        => 'browse',
            'post_parent'   => false,
            'fields'        => $fields,
        );

        $attachments->register( 'project_attachments', $args ); // unique instance name
    }

    public function custom_menu_page_removing() {
        remove_menu_page( 'edit.php' );
        remove_menu_page( 'tools.php' );
    }

    public function change_post_preview_link($preview_link, $post) {

        return home_url().'/preview/'.$post->ID;
    }

    public function wpdocs_custom_excerpt_length( $length ) {
        return 30;
    }
}