<?php

namespace SJFoundation\Domain\Project;

/**
 * Project domain model
 */
class Project {

    const STATUS_NEW = 'new';
    const STATUS_FOUNDED = 'founded';
    const STATUS_OPEN = 'open';

    public $status;

}