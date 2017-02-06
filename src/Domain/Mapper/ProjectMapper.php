<?php

namespace SJFoundation\Domain\Mapper;

use SJFoundation\Domain\Project;

class ProjectMapper {

    public function toDomainObject($data) {
        return new Project(
            $data->id,
            $data->price,
            $data->status,
            $data->canDonateMore,
            new \DateTime($data->dueDate)
        );
    }
}