<?php

namespace SJFoundation\Domain\Service;

use SJFoundation\Domain\Mapper\ProjectMapper;
use SJFoundation\Domain\Project;
use SJFoundation\Infrastructure\CoinsApi\ErisContractAPI;
use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;

class ProjectService {

    public function getProject() {
        $post_id = get_the_ID();
        $project = SJProjectsApi::getProject($post_id);
        $projectMapper = new ProjectMapper();

        return $projectMapper->toDomainObject($project);
    }

    public function getProjectContractTypes() {
        return ErisContractAPI::getProjectContractTypes();
    }

}