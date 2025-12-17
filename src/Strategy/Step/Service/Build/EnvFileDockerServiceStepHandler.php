<?php

declare(strict_types=1);

namespace App\Strategy\Step\Service\Build;

use App\Enum\ApplicationStep;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Services\FileSystemEnvironmentServices;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use App\Services\StrategyManager\EnvFileGeneratorService;
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use Symfony\Bundle\MakerBundle\Generator;


final class EnvFileDockerServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices   $fileSystemEnvironmentServices,
        private readonly Generator               $makerGenerator,
        private readonly EnvFileGeneratorService $envFileGeneratorService,
        MercureService                  $mercureService,
        ProcessRunnerService            $processRunner,

    )
    {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {

        $this->mercureService->dispatch(
            message: '📦 Création mise à jour des .env',
            type: TypeLog::START
        );

        $configPath = $this->fileSystemEnvironmentServices->getConfigPath($project);

        // Regenerate environment file on each run (specific to docker startup)
        $envContent = $this->envFileGeneratorService->generateEnvContent($serviceContainer, $project);
        $envFilePath = \sprintf('%s/%s.env', $configPath, $serviceContainer->getFolderName());

        $this->makerGenerator->dumpFile($envFilePath, $envContent);
        $this->makerGenerator->writeChanges();

        $this->mercureService->dispatch(
            message: '✅ Création mise à jour des .env success',
            type: TypeLog::COMPLETE,
            exitCode: 0
        );
    }

    public static function getPriority(): int
    {
        return 1;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::ENV_FILE;
    }
}
