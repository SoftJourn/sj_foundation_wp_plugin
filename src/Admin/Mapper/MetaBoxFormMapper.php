<?php

namespace SJFoundation\Admin\Mapper;

use DateTime;
use DateTimeZone;
use SJFoundation\Admin\Model\MetaBoxForm;

class MetaBoxFormMapper {

    public function toObject($post) {
        $category = isset($post['radio_tax_input']['category'][0]) ? $post['radio_tax_input']['category'][0] : '';

        return new MetaBoxForm(
            $post['post_ID'],
            $post['post_title'],
            $this->getPricePostData(),
            $this->getDueDatePostData(),
            $this->getStatusPostData(),
            $this->getCanDonateMore(),
            $this->getDurationPostData(),
            $this->getPostContractType($post),
            $category,
            get_the_author_meta('user_login', $post['post_author'])
        );
    }

    /**
     * get due date value from post
     * @param $post
     * @return string
     */
    public function getPostContractType($post)
    {
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
        $due = new DateTime($post['sj_project_due_date'].' '.$post['sj_project_due_time'], new DateTimeZone('Europe/Kiev'));
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
        return sanitize_text_field($post['sj_project_due_date'].' '.$post['sj_project_due_time']);
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
}