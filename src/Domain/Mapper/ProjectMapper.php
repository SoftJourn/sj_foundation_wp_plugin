<?php

namespace SJFoundation\Domain\Mapper;

use SJFoundation\Domain\Project;

class ProjectMapper {

    public function toDomainObject($data, $projectPostType) {
        return new Project(
            $projectPostType,
            $data->id,
            $data->price,
            $data->status,
            $data->canDonateMore,
            new \DateTime($data->dueDate),
            $data->published,
            $data->timeCreated,
            $data->donationStatus,
            $data->withdraw,
            $data->duration
        );
    }
}