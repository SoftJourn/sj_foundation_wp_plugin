<?php


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
        add_action('add_meta_boxes', array($this, 'add_project_metabox'));
        add_action('save_post', array($this, 'project_save_post_data'));
        add_action('init', array($this, 'action_init_taxonomies'));
        add_action('wp_trash_post', array($this, 'project_delete_post_data'));
        wp_enqueue_script('jquery-ui-datepicker');
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

        $priceTaxonomy = wp_get_post_terms($post_id, 'sj_project_price', array('fields' => 'all'));
        $dueDateTaxonomy = wp_get_post_terms($post_id, 'sj_project_due_date', array('fields' => 'all'));
        $project = SJProjectsApi::getProject($post_id);


        $canDonateMore = false;
        $price = '';
        $dueDate = '';
        if (isset($priceTaxonomy[0])) {
            $price = $priceTaxonomy[0]->name;
        }
        if (isset($dueDateTaxonomy[0])) {
            $dueDate = $dueDateTaxonomy[0]->name;
        }
        if ($project && $project->canDonateMore) {
            $canDonateMore = $project->canDonateMore;
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
                <input type="date" id="datepicker" name="sj_project_due_date" value="<?php echo $dueDate ?>"/>
                <p>Status</p>
                <select name="sj_project_status">
                    <option value="active">Donation open</option>
                    <option value="founded">Won</option>
                    <option value="not_founded">Last</option>
                </select>
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


    public function project_save_post_data($post_id)
    {

        if (!$this->checkPostData($post_id)) {
            return false;
        }

        $price = $this->getPricePostData();
        $dueDate = $this->getDueDatePostData();
        $status = $this->getStatusPostData();
        $canDonateMore = $this->getCanDonateMore();
        $projectTypeId = $this->getPostContractType();

        $currencyTypes = ErisContractAPI::getCurrencyContractTypes();
        $coin = $currencyTypes[0];
        //List of currencies
        $coins = ErisContractAPI::getInstances($coin->id);
        $coinsAddress = [];
        foreach ($coins as $val) {
            $coinsAddress[] = $val->address;
        }
        //Date min diff
        $current = new DateTime();
        $current->add(new DateInterval("PT2H"));
        $current->sub(new DateInterval("P1D"));
        $dateMinDiff = intval((strtotime(date("2016-12-29")) - $current->getTimestamp()) / 60);

        //TODO create contract code
        //Test request string
        $address = ErisContractAPI::getOwnerErisAccount();
        $options = array($address, (int) $price, (int) $dateMinDiff, $canDonateMore, $coinsAddress);
        ErisContractAPI::createContract($projectTypeId,$options);
        //TODO add parameter address of contract
        SJProjectsApi::createProject($post_id, $_POST['post_title'], $price, $status, $canDonateMore);
        SJProjectsApi::updateProjectTransactionsStatus($post_id, $status);

        wp_set_object_terms($post_id, [$price], 'sj_project_price', false);
        wp_set_object_terms($post_id, [$dueDate], 'sj_project_due_date', false);
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
