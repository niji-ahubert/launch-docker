<?php

declare(strict_types=1);

namespace App\Strategy\Step;

use App\Enum\DockerAction;
use App\Strategy\Trait\ExecuteInContainerTrait;

abstract class AbstractStartServiceStepHandler extends AbstractServiceStepHandler
{
    use ExecuteInContainerTrait;

    public static function getDockerAction(): DockerAction
    {
        return DockerAction::START;
    }
}
