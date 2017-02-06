<?php

namespace SJFoundation\Domain;

/**
 * Project domain model
 */
class Project {

    const STATUS_NEW = 'new';
    const STATUS_FOUNDED = 'founded';
    const STATUS_OPEN = 'open';

    public $id;

    public $price;

    public $status;

    public $canDonateMore;

    public $dueDate;

    public $published;

    /**
     * Project constructor.
     * @param $id
     * @param $status
     * @param $canDonateMore
     * @param $dueDate
     */
    public function __construct($id, $price, $status, $canDonateMore, $dueDate)
    {
        $this->id = $id;
        $this->price = $price;
        $this->status = $status;
        $this->canDonateMore = $canDonateMore;
        $this->dueDate = $dueDate;
    }

}