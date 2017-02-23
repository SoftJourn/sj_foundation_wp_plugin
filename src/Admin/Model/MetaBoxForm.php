<?php

namespace SJFoundation\Admin\Model;

use DateTime;

class MetaBoxForm {

    public $id;
    public $price;
    public $dueDate;
    public $status;
    public $canDonateMore;
    public $duration;
    public $title;
    public $projectTypeId;
    public $category;
    public $author;
    public $crowdsaleAddress;
    public $isPublic;

    /**
     * MetaBoxForm constructor.
     * @param $id
     * @param $price
     * @param $dueDate
     * @param $status
     * @param $canDonateMore
     * @param $duration
     * @param $title
     */
    public function __construct(
        $id,
        $title,
        $price,
        $dueDate,
        $status,
        $canDonateMore,
        $duration,
        $projectTypeId,
        $category,
        $author,
        $crowdsaleAddress,
        $isPublic
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
        $this->dueDate = $dueDate;
        $this->status = $status;
        $this->canDonateMore = $canDonateMore;
        $this->duration = $duration;
        $this->projectTypeId = $projectTypeId;
        $this->category = $category;
        $this->author = $author;
        $this->crowdsaleAddress = $crowdsaleAddress;
        $this->isPublic = $isPublic;
    }

}