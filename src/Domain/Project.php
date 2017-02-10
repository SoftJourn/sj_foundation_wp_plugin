<?php

namespace SJFoundation\Domain;

use Attachments;
use SJFoundation\Api\Dto\Project as ProjectDto;
use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;

/**
 * Project domain model
 */
class Project {

    const STATUS_DRAFT = 'draft';
    const STATUS_FOUNDED = 'closed';
    const STATUS_OPEN = 'open';

    const DONATION_STATUS_OPEN = 'open';
    const DONATIONS_STATUS_WIN = 'won';
    const DONATIONS_STATUS_LOST = 'lost';

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

    public $timeCreated;

    public $donationStatus;

    public $withdraw;

    public $duration;

    /**
     * Project constructor.
     * @param \WP_Post $projectPostType
     * @param $id
     * @param $price
     * @param $status
     * @param $canDonateMore
     * @param $dueDate
     * @param $published
     */
    public function __construct(
        \WP_Post $projectPostType,
        $id,
        $price,
        $status,
        $canDonateMore,
        $dueDate,
        $published,
        $timeCreated,
        $donationStatus,
        $withdraw,
        $duration
    ) {
        $this->projectPostType = $projectPostType;
        $this->id = $id;
        $this->price = $price;
        $this->status = $status;
        $this->canDonateMore = $canDonateMore;
        $this->published = $published;
        $this->dueDate = $dueDate;
        $this->timeCreated = $timeCreated;
        $this->donationStatus = $donationStatus;
        $this->withdraw = $withdraw;
        $this->duration = $duration;
    }

    private function getDurationLeftInMinutes() {
        if ($this->status == self::STATUS_DRAFT || $this->status == '') {
            return $this->duration;
        }
        $now = time()/60;
        if($this->status == self::STATUS_DRAFT) {

            return $this->duration;
        }
        $dueTime = ($this->timeCreated/60)+$this->duration;
        return round(($dueTime - $now));
    }


    private function getStatus() {
        return $this->status;
    }

    private function getThumbnailUrl() {
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

    public function getSupportersCount($transactions) {
        $supporters = [];
        foreach ($transactions as $transaction) {
            $supporters[$transaction->accountId] = true;
        }
        return count($supporters);
    }

    public function getRaisedCount($transactions) {
        $amount = 0;
        foreach ($transactions as $transaction) {
            $amount += $transaction->amount;
        }
        return $amount;
    }

    public function getUserRaisedCount($transactions) {
        $supporters = [];
        foreach ($transactions as $transaction) {
            if(!isset($supporters[$transaction->accountId])) {
                $supporters[$transaction->accountId] = 0;
            }
            $supporters[$transaction->accountId] += $transaction->amount;
        }
        return isset($supporters[get_current_user_id()]) ? $supporters[get_current_user_id()] : 0;
    }

    public function getCanDonate($raised) {
        if ($this->getDurationLeftInMinutes() <= 0) {
            return false;
        } else if ($this->canDonateMore) {
            return true;
        } else if($raised < $this->price) {
            return true;
        }
        return false;
    }

    public function getCanWithdraw() {
        if ($this->getDurationLeftInMinutes() <= 0 && !$this->withdraw) {
            return true;
        }
    }

    public function getDonationStatus($raised) {
        if ($this->getDurationLeftInMinutes() > 0 && $this->canDonateMore) {
            return self::DONATION_STATUS_OPEN;
        } else if ($this->getDurationLeftInMinutes() < 0 && $raised >= $this->price) {
            return self::DONATIONS_STATUS_WIN;
        } else if($this->getDurationLeftInMinutes() < 0 && $raised < $this->price ) {
            return self::DONATIONS_STATUS_LOST;
        } else if($this->getDurationLeftInMinutes() >= 0 && $raised >= $this->price && !$this->canDonateMore) {
            return self::DONATIONS_STATUS_WIN;
        }
        return 'open';
    }

    public function getAuthor() {
        $authorId = $this->projectPostType->post_author;
        return get_the_author_meta( 'display_name', $authorId);
    }

    public function render() {

        $projectDto = new ProjectDto();
        $projectDto->id = $this->id;
        $projectDto->slug = $this->projectPostType->post_name;
        $projectDto->slug = $this->projectPostType->post_name;
        $projectDto->title = $this->projectPostType->post_title;
        $projectDto->content = apply_filters( 'the_content', $this->projectPostType->post_content );
        $projectDto->shortDescription = $this->projectPostType->post_excerpt;
        $projectDto->price = $this->price;
        $projectDto->status = $this->getStatus();
        $projectDto->canDonateMore = $this->canDonateMore;
        $projectDto->durationLeft = $this->getDurationLeftInMinutes();
        $projectDto->thumbnailUrl = $this->getThumbnailUrl();
        $projectDto->categories = $this->getCategories();
        $projectDto->attachments = $this->getAttachments();
        $projectDto->transactions = $this->getTransactions();
        $projectDto->commentsCount = wp_count_comments($this->id);
        $projectDto->supporters = $this->getSupportersCount($projectDto->transactions);
        $projectDto->raised = $this->getRaisedCount($projectDto->transactions);
        $projectDto->userRaised = $this->getUserRaisedCount($projectDto->transactions);
        $projectDto->canDonate = $this->getCanDonate($projectDto->raised);
        $projectDto->canWithdraw = $this->getCanWithdraw();
        $projectDto->donationStatus = $this->getDonationStatus($projectDto->raised);
        $projectDto->author = $this->getAuthor();

        return $projectDto;
    }
}