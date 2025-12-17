<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build\EnvModifier;

use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Strategy\DockerService\AbstractDockerService;
use App\Strategy\DockerService\DatabaseDockerServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class DatabaseUrlEnvModifier implements EnvModifierInterface
{
    /**
     * @param iterable<AbstractDockerService> $dockerServices
     */
    public function __construct(
        #[AutowireIterator('app.docker_service')]
        private iterable $dockerServices,
    ) {
    }

    public function modify(string $content, AbstractContainer $serviceContainer, Project $project): string
    {
        $databaseService = null;
        /** @var DatabaseDockerServiceInterface|null $databaseDockerService */
        $databaseDockerService = null;

        // Recherche d'un service de base de données dans le projet
        foreach ($project->getServiceContainer() as $container) {
            // Recherche du DockerService correspondant qui implémente DatabaseDockerServiceInterface
            foreach ($this->dockerServices as $dockerService) {
                if (!$dockerService instanceof DatabaseDockerServiceInterface) {
                    continue;
                }
                if (!$dockerService instanceof AbstractDockerService) {
                    continue;
                }
                if (!$dockerService->support($container)) {
                    continue;
                }
                $databaseService = $container;
                $databaseDockerService = $dockerService;
                break 2;
            }
        }

        if (null === $databaseService || null === $databaseDockerService) {
            return $content;
        }

        // Le nom du service docker est utilisé comme host
        $host = $databaseService->getDockerServiceName();

        // Récupération dynamique du port
        $port = 3306; // Default fallback
        /** @var AbstractDockerService&DatabaseDockerServiceInterface $databaseDockerService */
        $ports = $databaseDockerService->getDefaultPorts($databaseService);
        if (!empty($ports)) {
            $port = (int) $ports[0];
        }

        // Récupération de toutes les informations via l'interface
        $protocol = $databaseDockerService->getDsnProtocol();
        $password = $databaseDockerService->getConnectionPassword();
        $user = $databaseDockerService->getConnectionUser();
        $database = $databaseDockerService->getDatabaseName();

        // Construction du DSN
        // Format: protocol://user:pass@host:port/db
        $dsn = sprintf(
            '%s://%s:%s@%s:%d/%s',
            $protocol,
            $user,
            $password,
            $host,
            $port,
            $database
        );

        // Remplacement de la valeur DATABASE_URL
        // On cherche DATABASE_URL=... et on remplace
        return preg_replace(
            '/^DATABASE_URL=.*$/m',
            sprintf('DATABASE_URL="%s"', $dsn),
            $content
        ) ?? $content;
    }
}
