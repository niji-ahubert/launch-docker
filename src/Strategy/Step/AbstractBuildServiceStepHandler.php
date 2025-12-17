<?php

declare(strict_types=1);

namespace App\Strategy\Step;

use App\Enum\DockerAction;

abstract class AbstractBuildServiceStepHandler extends AbstractServiceStepHandler
{
    public static function getDockerAction(): DockerAction
    {
        return DockerAction::BUILD;
    }
}
