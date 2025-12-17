<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Enum\ContainerType\ServiceContainer;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\DockerCompose\DockerComposeFile;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class MysqlDockerService extends AbstractDatabaseDockerService
{
    public function __construct(
        #[Autowire(param: 'bdd.root_password')]
        string                        $rootPassword,
        #[Autowire(param: 'bdd.database')]
        string                        $database,
        #[Autowire(param: 'bdd.user')]
        string                        $dbUser,
        #[Autowire(param: 'bdd.password')]
        string                        $dbPassword,
        DockerComposeFile             $dockerComposeFile,
        Generator                     $makerGenerator,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
    )
    {
        parent::__construct($rootPassword, $database, $dbUser, $dbPassword, $dockerComposeFile, $makerGenerator, $fileSystemEnvironmentServices);
    }

    public function support(AbstractContainer $service): bool
    {
        return ServiceContainer::MYSQL === $service->getServiceContainer();
    }

    #[\Override]
    public function getDefaultPorts(AbstractContainer $service): array
    {
        return ['3306'];
    }

    protected function getServiceSkeleton(string $volumeName, AbstractContainer $service, Project $project): array
    {

        return [
            'image' => \sprintf('%s:%s', ServiceContainer::MYSQL->getValue(), $service->getDockerVersionService()),
            'container_name' => sprintf('%s_service_database', ServiceContainer::MYSQL->getValue()),
            'profiles' => ['runner-dev'],
            'networks' => ['traefik'],
            'volumes' => [sprintf('%s:/var/lib/mysql', $volumeName)],
            'environment' => [
                'MYSQL_ROOT_PASSWORD' => $this->rootPassword,
                'MYSQL_DATABASE' => $this->database,
                'MYSQL_USER' => $this->dbUser,
                'MYSQL_PASSWORD' => $this->dbPassword,
            ],
        ];
    }

    public function getDsnProtocol(): string
    {
        return ServiceContainer::MYSQL->value;
    }
}
