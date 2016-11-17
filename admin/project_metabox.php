<?php


class ProjectMetabox
{

    protected static $taxonomies = array( 'event_category' );

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
    public function init() {
        add_action( 'add_meta_boxes', array($this, 'add_project_metabox') );
        add_action( 'save_post', array($this, 'project_save_post_data') );
        add_action( 'init', array( $this, 'action_init_taxonomies' ) );
        wp_enqueue_script('jquery-ui-datepicker');
    }



    public function action_init_taxonomies() {

        $object_types = array( 'project' );

        $args = array(
            'hierarchical'          => false,
            'show_ui'               => false,
            'show_admin_column'     => false,
            'query_var'             => true,
        );

        $args['rewrite'] = array( 'slug' => 'sj_project_price' );
        register_taxonomy( 'sj_project_price', $object_types, $args );

        $args['rewrite'] = array( 'slug' => 'sj_project_due_date' );
        register_taxonomy( 'sj_project_due_date', $object_types, $args );

    }


    public function add_project_metabox() {
        add_meta_box(
            'sj_project_metabox',
            __( 'Project Meta', 'sj_projects' ),
            array($this, 'render_project_metabox'),
            'project_type',
            'side'
        );
    }

    /**
     * render meta box
     * @return string
     */
    public function render_project_metabox() {
        $post_id = get_the_ID();

        $priceTaxonomy = wp_get_post_terms($post_id, 'sj_project_price', array('fields' => 'all'));
        $dueDateTaxonomy = wp_get_post_terms($post_id, 'sj_project_due_date', array('fields' => 'all'));

        $price = '';
        $dueDate = '';
        if (isset($priceTaxonomy[0])) {
            $price = $priceTaxonomy[0]->name;
        }
        if (isset($dueDateTaxonomy[0])) {
            $dueDate = $dueDateTaxonomy[0]->name;
        }
        ?>

        <div>
            <form>
                <?php wp_nonce_field($post_id, "sj-project-meta-box-nonce"); ?>
                <p>Price</p>
                <input type="text" name="sj_project_price" value="<?php echo $price?>" />
                <p><input type="checkbox"/> can pledge more</p>
                <p>Due date</p>
                <input type="date" id="datepicker" name="sj_project_due_date" value="<?php echo $dueDate?>" />
                <p>Status</p>
                <select name="sj_project_status">
                    <option value="active">Active</option>
                    <option value="founded">Founded</option>
                    <option value="not_founded">Not Founded</option>
                </select>
            </form>
        </div>


        <?php
    }


    public function project_save_post_data( $post_id ) {

        if (!$this->checkPostData($post_id)) {
            return false;
        }

        $price = $this->getPricePostData();
        $dueDate = $this->getDueDatePostData();
        $status = $this->getStatusPostData();

        SJProjectsApi::createProject($post_id, $_POST['post_title'], $price, $status);
        SJProjectsApi::updateProjectTransactionsStatus($post_id, $status);

        wp_set_object_terms( $post_id, [ $price ], 'sj_project_price', false );
        wp_set_object_terms( $post_id, [ $dueDate ], 'sj_project_due_date', false );
    }

    /**
     * get price value from post
     * @return string
     */
    public function getPricePostData() {
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
    public function getDueDatePostData() {
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
    public function getStatusPostData() {
        $post = $_POST;
        if (!isset($post['sj_project_status'])) {
            return '';
        }
        return sanitize_text_field($post['sj_project_status']);
    }


    private function checkPostData($post_id) {
        $post = $_POST;
        if (
            isset($post['sj-project-meta-box-nonce']) &&
            wp_verify_nonce($post['sj-project-meta-box-nonce'], $post_id)
        ){
            return true;
        }
        return false;
    }

}
