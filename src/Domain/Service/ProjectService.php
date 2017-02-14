<?php

namespace SJFoundation\Domain\Service;

use SJFoundation\Domain\Mapper\ProjectMapper;
use SJFoundation\Infrastructure\CoinsApi\ErisContractAPI;
use SJFoundation\Infrastructure\LoopBack\SJProjectsApi;

class ProjectService {

    public function getProject($postId = 0) {
        if (!$postId) {
            $postId = get_the_ID();
        }

        $projectLoopBack = SJProjectsApi::getProject($postId);
        $projectPostType = get_post($postId);
        if (!$projectPostType || $projectPostType->post_status == 'auto-draft') {
            return null;
        }

        $projectMapper = new ProjectMapper();

        return $projectMapper->toDomainObject($projectLoopBack, $projectPostType);
    }

    public function getProjectContractTypes() {
        $contractTypes = ErisContractAPI::getProjectContractTypes();
        $contractTypesActive = [];
        foreach ($contractTypes as $contractType) {
            if ($contractType->active == true) {
                $contractTypesActive[] = $contractType;
            }
        }
        return $contractTypesActive;
    }

    public function getProjectBySlug($slug) {
        $args = array(
            'name'        => urlencode($slug),
            'post_type'   => 'project_type',
            'numberposts' => 1
        );
        $projects = get_posts($args);
        if (!$projects) {
            return [];
        }
        $project = $projects[0];

        return $this->getProject($project->ID);
    }

    public function getProjectById($id) {
        return $this->getProject($id);
    }

}