<?php

namespace App\Util;

use App\Enum\ContainerType\ServiceContainer;
use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;

final readonly class DockerComposeUtility
{

    public static function getProjectServiceName(Project $project, AbstractContainer $service): string
    {
        return \sprintf('%s-%s-%s-%s', $project->getClient(), $project->getProject(), $service->getFolderName(), $project->getEnvironmentContainer()->value);
    }

    /**
     * @return string[]
     */
    public static function getContainerWebserver(Project $project, WebServer $webServer): array
    {
        $services = [];

        foreach ($project->getServiceContainer() as $container) {
            if ($container->getServiceContainer() instanceof ServiceContainer) {
                continue;
            }

            if ($container->getWebServer()?->getWebServer() === $webServer) {
                $services[] = self::getProjectServiceName($project, $container);
            }
        }

        return $services;
    }

}
