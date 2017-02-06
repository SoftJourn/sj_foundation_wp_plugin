<?php

namespace SJFoundation\Admin;

use DateTime;
use SJFoundation\Infrastructure\SJAuth;
use SJFoundation\Infrastructure\CoinsApi\ErisContractAPI;
use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;

class ProjectMetabox
{

    protected static $taxonomies = array('event_category');

    const METABOX_TEMPLATE = 'project_metabox.php';

    const PROJECT_STATUS_ACTIVE = 'active';
    const PROJECT_STATUS_FOUNDED = 'active';
    const PROJECT_STATUS_NOT_FOUNDED = 'active';

    private $statuses = [
        'active' => 'Active',
        'founded' => 'Founded',
        'not_founded' => 'Not Founded',
    ];

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
        add_action( 'admin_head', array($this, 'hide_comments_metabox') );
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

    public function hide_comments_metabox() {
        ?>
            <style>
                #commentstatusdiv { display: none; }
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
            array($this, 'render_project_metabox'),
            'project_type',
            'side'
        );
    }

    public function project_delete_post_data($postId)
    {
        SJProjectsApi::deleteProject($postId);
    }

    /**
     * render meta box
     * @return string
     */
    public function render_project_metabox()
    {
        $post_id = get_the_ID();
        $project = SJProjectsApi::getProject($post_id);

        $canDonateMore = false;
        $price = 0;
        $dueDate = '';
        if (!isset($project->error)) {
            if ($project->published) {
                $this->renderPublishedMetaBox();
                return;
            }
            $price = $project->price;
            $canDonateMore = $project->canDonateMore;
            $dueDate = date_create($project->dueDate)->format('Y-m-d');
        }

        $projectTypes = ErisContractAPI::getProjectContractTypes();

        ?>
            <div>
                <form>
                    <?php wp_nonce_field($post_id, "sj-project-meta-box-nonce"); ?>
                    <p>Price</p>
                    <input type="text" name="sj_project_price" value="<?php echo $price ?>"/>
                    <p><input type="checkbox"
                              name="sj_project_can_donate_more" <?php echo $canDonateMore ? 'checked' : '' ?> /> can donate
                        more</p>
                    <p>Due date</p>
                    <input type="date" id="datepicker" name="sj_project_due_date" value="<?php echo $dueDate; ?>"/>
                </form>
            </div>

            <div>
                <p>Contract type</p>
                <select name="sj_project_contract_type">
                    <?php foreach ($projectTypes as $value)
                        echo "<option value='$value->id'>$value->name</option>" ?>
                </select>
            </div>
        <?php
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


    public function project_save_post_data($post_id)
    {
        if (!$this->checkPostData($post_id)) {
            return false;
        }

        $price = $this->getPricePostData();
        $dueDate = $this->getDueDatePostData();
        $status = $this->getStatusPostData();
        $canDonateMore = $this->getCanDonateMore();
        $duration = $this->getDurationPostData();

        SJProjectsApi::createProject(
            $post_id,
            $_POST['post_title'],
            $price,
            $status,
            $canDonateMore,
            $duration,
            $dueDate
        );
        SJProjectsApi::updateProjectTransactionsStatus($post_id, $status);

        wp_set_object_terms($post_id, [$price], 'sj_project_price', false);
        wp_set_object_terms($post_id, [$dueDate], 'sj_project_due_date', false);
    }

    public function project_publish_post_data($post_id) {
        if (!$this->checkPostData($post_id)) {
            return false;
        }
        $this->createErisContract($post_id);

    }

    public function createErisContract($post_id) {
        $price = $this->getPricePostData();
        $dueDate = $this->getDueDatePostData();
        $canDonateMore = $this->getCanDonateMore();
        $projectTypeId = $this->getPostContractType();
        $duration = $this->getDurationPostData();

        $currencyTypes = ErisContractAPI::getCurrencyContractTypes();
        $coin = $currencyTypes[0];
        //List of currencies
        $coins = ErisContractAPI::getInstances($coin->id);
        $coinsAddress = [];
        foreach ($coins as $val) {
            $coinsAddress[] = $val->address;
        }
        $address = ErisContractAPI::getOwnerErisAccount();
        $options = array($address, (int) $price, (int) $duration, !$canDonateMore, $coinsAddress);
        $contractAddress = ErisContractAPI::createContract($projectTypeId,$options);
        if (!$contractAddress || true) {
            add_filter( 'redirect_post_location', array( $this, 'add_notice_contract_error' ), 99 );
            $this->unPublishPost($post_id);
            return;
        }
        SJProjectsApi::addContractToProject($post_id, $contractAddress, $coinsAddress);
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
     * get due date value from post
     * @return string
     */
    public function getPostContractType()
    {
        $post = $_POST;
        if (!isset($post['sj_project_contract_type'])) {
            return '';
        }
        return sanitize_text_field($post['sj_project_contract_type']);
    }

    /**
     * get price value from post
     * @return string
     */
    public function getPricePostData()
    {
        $post = $_POST;
        if (!isset($post['sj_project_price'])) {
            return '';
        }
        return sanitize_text_field($post['sj_project_price']);
    }

    /**
     * get due date value from post
     * @return int
     */
    public function getDurationPostData()
    {
        $post = $_POST;
        if (!isset($post['sj_project_due_date'])) {
            return '';
        }
        $current = new DateTime();
        $due = new DateTime($post['sj_project_due_date']);
        $due->setTime(23, 59, 59);
        $duration = intval(($due->getTimestamp() - $current->getTimestamp()) / 60);
        return $duration;
    }

    /**
     * get duration
     * @return string
     */
    public function getDueDatePostData()
    {
        $post = $_POST;
        if (!isset($post['sj_project_due_date'])) {
            return '';
        }
        return sanitize_text_field($post['sj_project_due_date']);
    }

    /**
     * get due date value from post
     * @return string
     */
    public function getStatusPostData()
    {
        $post = $_POST;
        if (!isset($post['sj_project_status'])) {
            return '';
        }
        return sanitize_text_field($post['sj_project_status']);
    }

    /**
     * get can donate more
     * @return string
     */
    public function getCanDonateMore()
    {
        $post = $_POST;
        if (!isset($post['sj_project_can_donate_more'])) {
            return false;
        }
        return $post['sj_project_can_donate_more'] === 'on';
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

}
