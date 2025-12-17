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
use App\Strategy\Step\AbstractBuildServiceStepHandler;
use Symfony\Component\Filesystem\Filesystem;

final class EntrypointAddonCopyDockerServiceStepHandler extends AbstractBuildServiceStepHandler
{
    public function __construct(
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
        MercureService $mercureService,
        ProcessRunnerService $processRunner,
        private readonly Filesystem $filesystem,
        private readonly string $projectDir,
    ) {
        parent::__construct($fileSystemEnvironmentServices, $mercureService, $processRunner);
    }

    public function __invoke(AbstractContainer $serviceContainer, Project $project): void
    {
        $this->mercureService->dispatch(
            message: '📦 Copie du script entrypoint addon',
            type: TypeLog::START,
        );

        $this->filesystem->copy(
            \sprintf('%s/%s', $this->projectDir, FileSystemEnvironmentServices::BIN_ENTRYPOINT_ADDON_SH),
            $this->fileSystemEnvironmentServices->getProjectComponentEntrypointAddonPath($project, $serviceContainer),
        );

        $this->mercureService->dispatch(
            message: '✅ Copie du script entrypoint addon success',
            type: TypeLog::COMPLETE,
            exitCode: 0,
        );
    }

    public static function getPriority(): int
    {
        return 1;
    }

    public function getStepName(): ApplicationStep
    {
        return ApplicationStep::ENTRYPOINT_ADDON_COPY;
    }
}
