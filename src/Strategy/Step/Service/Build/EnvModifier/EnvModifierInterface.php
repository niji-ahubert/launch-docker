<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build\EnvModifier;

use App\Model\Project;
use App\Model\Service\AbstractContainer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.env_modifier')]
interface EnvModifierInterface
{
    public function modify(string $content, AbstractContainer $serviceContainer, Project $project): string;
}
