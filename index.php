<?php
/*
* Plugin Name: SJ Foundation
* Description: SJ Foundation wp plugin
* Version: 0.0.1
* Author: SoftJourn
* Author URI: https://softjourn.com
*/

define( 'PROJECT_PLUGIN_FILE',  __FILE__ );
define( 'PROJECT_BASENAME', plugin_basename( PROJECT_PLUGIN_FILE ) );
define( 'PROJECT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PROJECT_URL', plugin_dir_url( __FILE__ ) );
define( 'PROJECT_ADMIN', PROJECT_PATH . 'admin' . DIRECTORY_SEPARATOR );
define( 'PROJECT_ADMIN_TEMPLATE_PATH', PROJECT_ADMIN . 'templates' . DIRECTORY_SEPARATOR );
define( 'PROJECT_ASSETS_DIR', PROJECT_ADMIN . 'assets' . DIRECTORY_SEPARATOR );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
require_once(ABSPATH . 'wp-content/plugins/rest-api/plugin.php');

require PROJECT_PATH . 'WP_REST_Project_Controller.php';
require PROJECT_ADMIN . 'SJProjectsApi.php';
require PROJECT_ADMIN. 'ErisContractAPI.php';
require PROJECT_PATH . 'login/SJAuth.php';
require PROJECT_PATH . 'login/SJLogin.php';

add_action('rest_api_init', function () {
    $myProductController = new WP_REST_Project_Controller('project_type');
    $myProductController->register_routes();
});


function project_post_type() {

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
add_action( 'init', 'project_post_type', 0 );

function init_project_meta_box() {
    require_once ( PROJECT_ADMIN . 'project_metabox.php' );
    $project_metabox = new ProjectMetabox();
    $project_metabox->init();
}

function my_rest_prepare_post( $data, $post, $request ) {
    $_data = $data->data;
    $user = wp_get_current_user();
    $thumbnail_id = get_post_thumbnail_id( $post->ID );
    $thumbnail = wp_get_attachment_image_src( $thumbnail_id, 'project-image-size' );
    $_data['featured_image_thumbnail_url'] = $thumbnail[0];

    $priceTaxonomy = wp_get_post_terms($post->ID, 'sj_project_price', array('fields' => 'all'));
    $dueDateTaxonomy = wp_get_post_terms($post->ID, 'sj_project_due_date', array('fields' => 'all'));

    $projectApiData = SJProjectsApi::getProject($post->ID);

    $price = '';
    $dueDate = '';
    if (isset($priceTaxonomy[0])) {
        $price = $priceTaxonomy[0]->name;
    }
    if (isset($dueDateTaxonomy[0])) {
        $dueDate = $dueDateTaxonomy[0]->name;
    }

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
    $days = (int)$now->diff($dateDueDateTime)->days;
    $donationType = 'closed';
    if (
        $days > 0 &&
        isset($projectApiData->status) &&
        (($projectApiData->status === 'active' || $projectApiData->canDonateMore) || !$projectApiData->price)
    ) {
        $donationType = 'open';
    }

    $_data['attachments'] = $attachments;
    $_data['donation_type'] = $donationType;
    $_data['price'] = $price;
    $_data['days_remain'] = $days;
    $_data['due_date'] = $dueDate;
    $_data['api_data'] = $projectApiData ? $projectApiData : ['canDonateMore' => false];
    $_data['transactions'] = SJProjectsApi::getProjectTransactions($post->ID);
    $_data['user_transactions'] = SJProjectsApi::getProjectAccountTransactions($user->ID, $post->ID);
    $_data['comments_count'] = wp_count_comments( $post->ID );
    $_data['prev_project'] = get_previous_project_slug($post->ID);
    $_data['next_project'] = get_next_project_slug($post->ID);
    $_data['categories'] = wp_get_object_terms($post->ID, 'category');

    $data->data = $_data;
    return $data;
}
add_filter( 'rest_prepare_project_type', 'my_rest_prepare_post', 10, 3 );
add_image_size( 'project-image-size', 620, 320 );
function wpdocs_custom_excerpt_length( $length ) {
    return 30;
}
add_filter( 'excerpt_length', 'wpdocs_custom_excerpt_length', 999 );

init_project_meta_box();

/*
 * HIDE ADMIN BAR
 */
add_filter('show_admin_bar', '__return_false');

/**
 *
 * CREATE/UPDATE USER ACTIONS
 */
add_action( 'user_register', 'registration_save', 10, 1 );
function registration_save( $user_id ) {
    $name = isset($_POST['first_name']) ? $_POST['first_name'] : '' . isset($_POST['last_name']) ? $_POST['last_name'] : '';
    if ( isset( $_POST['email'] ) ) {
        SJProjectsApi::createUser($user_id, $_POST['email'], $name);
    }

}
add_action( 'profile_update', 'my_profile_update', 10, 2 );
function my_profile_update( $user_id ) {
    if ( isset( $_POST['email'] ) ) {
        SJProjectsApi::createUser($user_id, $_POST['email'], $_POST['first_name'].' '.$_POST['last_name']);
    }
}

/**
 * get prev and next posts slug
 */
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

function project_attachments( $attachments )
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

        // title of the meta box (string)
        'label'         => 'Project Attachments',

        // all post types to utilize (string|array)
        'post_type'     => array( 'project_type' ),

        // meta box position (string) (normal, side or advanced)
        'position'      => 'normal',

        // meta box priority (string) (high, default, low, core)
        'priority'      => 'high',

        // allowed file type(s) (array) (image|video|text|audio|application)
        'filetype'      => null,  // no filetype limit

        // include a note within the meta box (string)
        'note'          => 'Attach files here!',

        // by default new Attachments will be appended to the list
        // but you can have then prepend if you set this to false
        'append'        => true,

        // text for 'Attach' button in meta box (string)
        'button_text'   => __( 'Attach Files', 'attachments' ),

        // text for modal 'Attach' button (string)
        'modal_text'    => __( 'Attach', 'attachments' ),

        // which tab should be the default in the modal (string) (browse|upload)
        'router'        => 'browse',

        // whether Attachments should set 'Uploaded to' (if not already set)
        'post_parent'   => false,

        // fields array
        'fields'        => $fields,

    );

    $attachments->register( 'project_attachments', $args ); // unique instance name
}

add_action( 'attachments_register', 'project_attachments' );


function custom_menu_page_removing() {
    remove_menu_page( 'edit.php' );
    remove_menu_page( 'tools.php' );
}
add_action( 'admin_menu', 'custom_menu_page_removing' );

function change_post_preview_link($preview_link, $post) {

    return home_url().'/preview/'.$post->ID;
}
add_filter( 'preview_post_link', 'change_post_preview_link', 10, 2 );

function after_login_default_page() {
    return '/';
}

add_filter('login_redirect', 'after_login_default_page');

function remove_dashboard_meta() {
    remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
}
add_action( 'admin_init', 'remove_dashboard_meta' );

$sjLogin = new SJLogin();
$sjLogin->sj_authenticate();