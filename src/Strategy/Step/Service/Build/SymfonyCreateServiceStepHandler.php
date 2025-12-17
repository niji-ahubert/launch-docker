<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use Monolog\Level;

final class SymfonyCreateServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService $mercureService,
        ProcessRunnerService $processRunner,
        private readonly string $hostUid,
        private readonly string $hostGid,
        private readonly string $projectRoot,
    ) {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $applicationProjectPath = $this->fileSystemEnvironmentServices->getApplicationProjectPath($project, $serviceContainer);

        if (false === $this->fileSystemEnvironmentServices->isDirectoryEmpty($applicationProjectPath)) {
            $this->mercureService->dispatch(
                message: \sprintf('Le dossier %s n\'est pas vide, l\'opération Création du projet Symfony est annulée', $applicationProjectPath),
                level: Level::Warning,
            );

            return;
        }

        $command = [
            'docker',
            'run',
            '--rm',
            '--user', \sprintf('%s:%s', $this->hostUid, $this->hostGid),
            '--volume', \sprintf('%s:/app', str_replace('/var/www/html', $this->projectRoot, $applicationProjectPath)),
            'composer',
            'create-project',
            'symfony/skeleton',
            '.',
            '--no-interaction',
        ];

        $this->processRunner->run(
            $command,
            '⚙️ Création du projet Symfony',
            $applicationProjectPath,
        );
    }

    public static function getPriority(): int
    {
        return 4;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::SYMFONY_CREATE;
    }
}
