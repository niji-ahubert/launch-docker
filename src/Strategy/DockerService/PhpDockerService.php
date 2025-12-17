<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ProjectContainer;
use App\Enum\WebServer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\DockerCompose\DockerComposeFile;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class PhpDockerService extends AbstractDockerService
{
    /**
     * @param iterable<AbstractDockerService> $dockerServices
     */
    public function __construct(
        #[AutowireIterator('app.docker_service')]
        private iterable $dockerServices,
        DockerComposeFile $dockerComposeFile,
        Generator $makerGenerator,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
    ) {
        parent::__construct($dockerComposeFile, $makerGenerator, $fileSystemEnvironmentServices);
    }

    public function support(AbstractContainer $service): bool
    {
        return ProjectContainer::PHP === $service->getServiceContainer();
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {
        $configPhp = [
            'extends' => [
                'file' => \sprintf('../../../resources/docker-compose/%s.docker-compose.yml', $service->getServiceContainer()->value),
                'service' => \sprintf('%s-%s', $service->getServiceContainer()->value, $project->getEnvironmentContainer()->value),
            ],
        ];

        if ($service->getWebServer() instanceof \App\Model\Service\WebServer && WebServer::LOCAL !== $service->getWebServer()->getWebServer()) {
            $configPhp['labels'] = ['traefik.enable=false']; // si le webserver est ds un autre container on desactive treafik
        }

        $dependsOn = [];
        if (!\in_array($service->getDataStorages(), [null, []], true)) {
            $dataStorageValues = array_map(
                fn ($ds) => $ds->value,
                $service->getDataStorages(),
            );

            foreach ($project->getServiceContainer() as $container) {
                foreach ($this->dockerServices as $dockerService) {
                    /** @var AbstractDockerService $dockerService */
                    if (
                        $dockerService instanceof DatabaseDockerServiceInterface
                        && $dockerService->support($container)
                        && \in_array($container->getServiceContainer()->value, $dataStorageValues, true)
                    ) {
                        $serviceName = $container->getDockerServiceName();
                        if (null !== $serviceName) {
                            $dependsOn[] = $serviceName;
                        }
                        break;
                    }
                }
            }
        }

        if ([] !== $dependsOn) {
            $configPhp['depends_on'] = $dependsOn;
        }

        return $configPhp;
    }
}
