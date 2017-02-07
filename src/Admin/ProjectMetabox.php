<?php

namespace SJFoundation\Admin;

use SJFoundation\Admin\Mapper\MetaBoxFormMapper;
use SJFoundation\Domain\Service\ProjectService;
use SJFoundation\Domain\User;
use SJFoundation\Infrastructure\SJAuth;
use SJFoundation\Infrastructure\CoinsApi\ErisContractAPI;
use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;
use Timber\Timber;

class ProjectMetabox
{

    const PROJECT_STATUS_ACTIVE = 'active';
    const PROJECT_STATUS_FOUNDED = 'active';
    const PROJECT_STATUS_NOT_FOUNDED = 'active';

    /**
     * init meta box
     * @return void
     */
    public function init()
    {
        wp_enqueue_script('jquery-ui-datepicker');

        add_action('add_meta_boxes', array($this, 'add_project_metabox'));
        add_action('publish_project_type', array($this, 'project_publish_post_data'));
        add_action('save_post', array($this, 'project_save_post_data'));
        add_action('init', array($this, 'action_init_taxonomies'));
        add_action('wp_trash_post', array($this, 'project_delete_post_data'));
        add_action( 'admin_notices', array($this, 'ldap_admin_notice') );
        add_action( 'admin_head', array($this, 'hide_publish_button_editor') );
    }

    public function ldap_admin_notice() {
        if ( isset( $_GET['create_contract_error'] ) ) {
            $message = 'Error create crowdsale contract (check coins api)';
            $this->showError($message);
        }

        $account = SJAuth::getAccount();
        if ($account) {
            return;
        }

        $message = 'You need to login with your SoftJourn LDAP account to publish project! You can save draft only';
        $this->showError($message);

    }

    public function hide_publish_button_editor() {
        $account = SJAuth::getAccount();
        if ($account) {
            return;
        }
        ?>
            <style>
                #publishing-action { display: none; }
            </style>
        <?php
    }

    public function showError($message) {?>
            <div class="notice error">
                <p><?php _e( $message, 'sj_foundation_domain'.$message ); ?></p>
            </div><?php
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

    public function renderPublishedMetaBox() {
        $post_id = get_the_ID();
        $project = SJProjectsApi::getProject($post_id);

        $price = $project->price;
        $canDonateMore = $project->canDonateMore;
        $dueDate = date_create($project->dueDate)->format('Y-m-d');
        $projectTypes = ErisContractAPI::getProjectContractTypes();

        ?>
            <div>
                <form>
                    <?php wp_nonce_field($post_id, "sj-project-meta-box-nonce"); ?>
                    <p>Price</p>
                    <input type="text" disabled value="<?php echo $price ?>"/>
                    <p><input type="checkbox" disabled <?php echo $canDonateMore ? 'checked' : '' ?> /> can donate more</p>
                    <p>Due date</p>
                    <input type="date" disabled value="<?php echo $dueDate; ?>"/>
                </form>
            </div>

            <div>
                <p>Contract type</p>
                <select disabled >
                    <?php foreach ($projectTypes as $value)
                        echo "<option value='$value->id'>$value->name</option>" ?>
                </select>
            </div>
        <?php
    }


    public function project_save_post_data($postId)
    {
        if (!$this->checkPostData($postId)) {
            return false;
        }

        $metaBoxFormMapper = new MetaBoxFormMapper();
        $metaBoxFormModel = $metaBoxFormMapper->toObject($_POST);

        SJProjectsApi::createProject(
            $metaBoxFormModel->id,
            $metaBoxFormModel->title,
            $metaBoxFormModel->price,
            $metaBoxFormModel->status,
            $metaBoxFormModel->canDonateMore,
            $metaBoxFormModel->duration,
            $metaBoxFormModel->dueDate
        );
        SJProjectsApi::updateProjectTransactionsStatus($postId, $metaBoxFormModel->status);

        wp_set_object_terms($postId, [$metaBoxFormModel->price], 'sj_project_price', false);
        wp_set_object_terms($postId, [$metaBoxFormModel->dueDate], 'sj_project_due_date', false);
    }

    public function project_publish_post_data($post_id) {
        if (!$this->checkPostData($post_id)) {
            return false;
        }
        $this->createErisContract($post_id);

    }

    public function createErisContract($post_id) {
        $metaBoxFormMapper = new MetaBoxFormMapper();
        $metaBoxFormModel = $metaBoxFormMapper->toObject($_POST);
        $currencyTypes = ErisContractAPI::getCurrencyContractTypes();
        $coin = $currencyTypes[0];
        //List of currencies
        $coins = ErisContractAPI::getInstances($coin->id);
        $coinsAddress = [];
        foreach ($coins as $val) {
            $coinsAddress[] = $val->address;
        }
        $address = ErisContractAPI::getOwnerErisAccount();
        $options = array(
            $address,
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
            add_filter( 'redirect_post_location', array( $this, 'add_notice_contract_error' ), 99 );
            $this->unPublishPost($post_id);
            return;
        }
        SJProjectsApi::addContractToProject(
            $post_id,
            $contractApiResponse['address'],
            $coinsAddress
        );
    }

    public function add_notice_contract_error( $location ) {
        remove_filter( 'redirect_post_location', array( $this, 'add_notice_contract_error' ), 99 );
        return add_query_arg( array( 'create_contract_error' => 'api' ), $location );
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
        $context['wp_nonce_field'] = wp_nonce_field(get_the_ID(), "sj-project-meta-box-nonce");

        Timber::render('views/metabox.html.twig', $context);
    }
}
