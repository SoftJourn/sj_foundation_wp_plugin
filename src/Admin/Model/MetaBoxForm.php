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
    public function __construct($id, $title, $price, $dueDate, $status, $canDonateMore, $duration, $projectTypeId, $category)
    {
        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
        $this->dueDate = $dueDate;
        $this->status = $status;
        $this->canDonateMore = $canDonateMore;
        $this->duration = $duration;
        $this->projectTypeId = $projectTypeId;
        $this->category = $category;
    }

}