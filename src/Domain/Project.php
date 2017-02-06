<?php

namespace SJFoundation\Domain;

use Attachments;
use SJFoundation\Api\Dto\Project as ProjectDto;
use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;

/**
 * Project domain model
 */
class Project {

    const STATUS_NEW = 'new';
    const STATUS_FOUNDED = 'founded';
    const STATUS_OPEN = 'open';

    /**
     * @var \WP_Post
     */
    public $projectPostType;

    public $id;

    public $price;

    public $status;

    public $canDonateMore;

    /**
     * @var \DateTime
     */
    public $dueDate;

    public $published;

    /**
     * Project constructor.
     * @param $projectPostType
     * @param $id
     * @param $price
     * @param $status
     * @param $canDonateMore
     * @param $dueDate
     */
    public function __construct(\WP_Post $projectPostType, $id, $price, $status, $canDonateMore, $dueDate)
    {
        $this->projectPostType = $projectPostType;
        $this->id = $id;
        $this->price = $price;
        $this->status = $status;
        $this->canDonateMore = $canDonateMore;
        $this->dueDate = $dueDate;
    }

    private function getDurationLeft() {
        $now = time();
        $dueTime = $this->dueDate->getTimestamp();
        return round(($dueTime - $now)/60);
    }

    public function getThumbnailUrl() {
        $thumbnail_id = get_post_thumbnail_id( $this->id );
        $thumbnail = wp_get_attachment_image_src( $thumbnail_id, 'project-image-size' );

        return isset($thumbnail[0]) ? $thumbnail[0] : '';
    }

    private function getAttachments() {
        $attachments = [];
        $attachmentsObject = new Attachments( 'project_attachments', $this->id );
        while($attachmentsObject->get()){
            $attachment = [];
            $attachment['id'] = $attachmentsObject->id();
            $attachment['url'] = $attachmentsObject->url();
            $attachment['title'] = $attachmentsObject->field('title');
            $attachment['thumbnail'] = $attachmentsObject->image( 'thumbnail' );
            $attachment['caption'] = $attachmentsObject->field('caption');
            $attachments[] = $attachment;
        }
        return $attachments;
    }

    private function getTransactions() {
        return SJProjectsApi::getProjectTransactions($this->id);
    }

    private function getCategories() {
        return wp_get_object_terms($this->id, 'category');
    }

    public function render() {

        $projectDto = new ProjectDto();
        $projectDto->id = $this->id;
        $projectDto->title = $this->projectPostType->post_title;
        $projectDto->content = get_post_field('post_content', $this->id);
        $projectDto->price = $this->price;
        $projectDto->durationLeft = $this->getDurationLeft();
        $projectDto->thumbnailUrl = $this->getThumbnailUrl();
        $projectDto->categories = $this->getCategories();
        $projectDto->attachments = $this->getAttachments();
        $projectDto->transactions = $this->getTransactions();
        return $projectDto;
    }
}