<?php

namespace SJFoundation\Admin;

use SJFoundation\Admin\Mapper\MetaBoxFormMapper;
use SJFoundation\Domain\Service\ProjectService;
use SJFoundation\Domain\User;
use SJFoundation\Infrastructure\SJAuth;
use SJFoundation\Infrastructure\CoinsApi\ErisContractAPI;
use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;
use Timber\Timber;
use SJFoundation\Admin\ProjectMetaboxErrors;

class ProjectMetabox
{

    const PROJECT_STATUS_ACTIVE = 'active';
    const PROJECT_STATUS_FOUNDED = 'active';
    const PROJECT_STATUS_NOT_FOUNDED = 'active';

    /**
     * @var ProjectMetaboxErrors
     */
    private $errorClass;

    /**
     * init meta box
     * @return void
     */
    public function init()
    {
        $this->errorClass = new ProjectMetaboxErrors();
        wp_enqueue_script('jquery-ui-datepicker');

        add_action('add_meta_boxes', array($this, 'add_project_metabox'));
        add_action('publish_project_type', array($this, 'project_publish_post_data'));
        add_action('save_post', array($this, 'project_save_post_data'));
        add_action('init', array($this, 'action_init_taxonomies'));
        add_action('wp_trash_post', array($this, 'project_delete_post_data'));
        add_action( 'admin_notices', array($this->errorClass, 'ldap_admin_notice') );
        add_action( 'admin_head', array($this, 'hide_publish_button_editor') );
        add_action( 'admin_enqueue_scripts', [$this, 'sj_foundation_project_metabox_script'] );
        add_action( 'admin_print_styles', [$this, 'sj_foundation_project_metabox_styles'] );
    }

    public function sj_foundation_project_metabox_script() {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script("jquery-ui-core");
        wp_enqueue_script( 'jquery_timepicker_script' );
        wp_enqueue_script( 'sj_project_metabox_script' );
        wp_enqueue_style("jquery_timepicker_style");
        wp_enqueue_style("sj_jquery_ui_style");
        wp_enqueue_style("sj_project_metabox_style");
    }
    public function sj_foundation_project_metabox_styles() {
        wp_enqueue_style("sj_jquery_ui_style");
    }

    public function hide_publish_button_editor() {
        $account = SJAuth::getAccount();
        if ($account) {
            return;
        }
        if (!User::isModerator()) {
            ?>
                <style>
                    .subsubsub { display: none; }
                    #postexcerpt .inside p { display: none; }
                </style>
            <?php
        }
        ?>
            <style>
                #postexcerpt .inside p { display: none; }
            </style>
        <?php
    }

    public function action_init_taxonomies()
    {

        $object_types = array('project');

        $args = array(
            'hierarchical' => false,
            'show_ui' => false,
            'show_admin_column' => false,
            'query_var' => true,
        );

        $args['rewrite'] = array('slug' => 'sj_project_price');
        register_taxonomy('sj_project_price', $object_types, $args);

        $args['rewrite'] = array('slug' => 'sj_project_due_date');
        register_taxonomy('sj_project_due_date', $object_types, $args);

    }

    public function add_project_metabox()
    {
        add_meta_box(
            'sj_project_metabox',
            __('Project Meta', 'sj_projects'),
            array($this, 'renderMetaBox'),
            'project_type',
            'side'
        );
    }

    public function project_delete_post_data($postId)
    {
        SJProjectsApi::deleteProject($postId);
    }


    public function project_save_post_data($postId)
    {
        if (!$this->checkPostData($postId)) {
            return false;
        }

        $metaBoxFormMapper = new MetaBoxFormMapper();
        $metaBoxFormModel = $metaBoxFormMapper->toObject($_POST);

        if (!$metaBoxFormModel->canDonateMore && !$metaBoxFormModel->price) {
            add_filter('redirect_post_location', array($this->errorClass, 'add_notice_contract_price_error'), 99);
            remove_action('save_post', [$this, 'project_save_post_data']);
            return false;
        }

        SJProjectsApi::createProject(
            $metaBoxFormModel->id,
            $metaBoxFormModel->title,
            $metaBoxFormModel->price,
            $metaBoxFormModel->status,
            $metaBoxFormModel->canDonateMore,
            $metaBoxFormModel->duration,
            $metaBoxFormModel->dueDate,
            $metaBoxFormModel->category,
            $metaBoxFormModel->isPublic
        );
        SJProjectsApi::updateProjectTransactionsStatus($postId, $metaBoxFormModel->status);

        wp_set_object_terms($postId, [$metaBoxFormModel->price], 'sj_project_price', false);
        wp_set_object_terms($postId, [$metaBoxFormModel->dueDate], 'sj_project_due_date', false);
    }

    public function project_publish_post_data($post_id) {
        if ($_POST['original_post_status'] == 'publish') {
            return false;
        }
        if (!$this->checkPostData($post_id)) {
            return false;
        }
        return $this->createErisContract($post_id);

    }

    public function createErisContract($post_id)
    {

        $metaBoxFormMapper = new MetaBoxFormMapper();
        $metaBoxFormModel = $metaBoxFormMapper->toObject($_POST);
//        $author = ErisContractAPI::getErisAccountByUsername($metaBoxFormModel->author);
//        if (!$author) {
//            add_filter('redirect_post_location', array($this->errorClass, 'add_notice_contract_author_error'), 99);
//            $this->unPublishPost($post_id);
//            return false;
//        }

        if (!$metaBoxFormModel->canDonateMore && !$metaBoxFormModel->price) {
            add_filter('redirect_post_location', array($this->errorClass, 'add_notice_contract_price_error'), 99);
            $this->unPublishPost($post_id);

            remove_action('publish_project_type', [$this, 'project_publish_post_data']);
            return false;
        }

        $currencyTypes = ErisContractAPI::getCurrencyContractTypes();
        $coin = $currencyTypes[0];
        //List of currencies
        $coins = ErisContractAPI::getInstances($coin->id);
        $coinsAddress = [];
        foreach ($coins as $val) {
            $coinsAddress[] = $val->address;
        }

        $options = array(
            $metaBoxFormModel->crowdsaleAddress,
            (int)$metaBoxFormModel->price,
            (int)$metaBoxFormModel->duration,
            !$metaBoxFormModel->canDonateMore,
            $coinsAddress
        );

        $contractApiResponse = ErisContractAPI::createContract(
            $metaBoxFormModel->projectTypeId,
            $metaBoxFormModel->title,
            $options
        );
        if (!$contractApiResponse['address']) {
            add_filter('redirect_post_location', array($this->errorClass, 'add_notice_contract_error'), 99);
            $this->unPublishPost($post_id);
            return false;
        }
        SJProjectsApi::addContractToProject(
            $post_id,
            $contractApiResponse['address'],
            $coinsAddress
        );
        return true;
    }

    public function unPublishPost($post_id) {
        $current_post = get_post( $post_id, 'ARRAY_A' );
        $current_post['post_status'] = 'draft';
        wp_update_post($current_post);
    }

    /**
     * check post is valid
     * @param $post_id
     * @return bool
     */
    private function checkPostData($post_id)
    {
        $post = $_POST;
        if (
            isset($post['sj-project-meta-box-nonce']) &&
            wp_verify_nonce($post['sj-project-meta-box-nonce'], $post_id)
        ) {
            return true;
        }
        return false;
    }

    public function renderMetaBox() {
        $projectService = new ProjectService();

        $context = Timber::get_context();
        $context['project'] = $projectService->getProject();
        $context['isModerator'] = User::isModerator();
        $context['projectTypes'] = $projectService->getProjectContractTypes();
        $context['crowdsaleAccounts'] = ErisContractAPI::getCrowdsaleAccounts();
        $context['wp_nonce_field'] = wp_nonce_field(get_the_ID(), "sj-project-meta-box-nonce");

        Timber::render('views/metabox.html.twig', $context);
    }
}
