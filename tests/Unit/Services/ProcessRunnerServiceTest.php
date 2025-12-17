<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Enum\Log\LoggerChannel;
use App\Enum\Log\TypeLog;
use App\Model\Project;
use App\Services\Mercure\MercureService;
use App\Services\ProcessRunnerService;
use Monolog\Level;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProcessRunnerServiceTest extends TestCase
{
    /**
     * Teste l'exécution de commandes avec différents codes de sortie.
     *
     * @param string[] $command
     */
    #[DataProvider('provideCommandExecutionData')]
    public function testRunCommand(
        array $command,
        int $expectedExitCode,
        string $description
    ): void {
        $mercureService = $this->createMock(MercureService::class);
        $mercureService->method('getProject')->willReturn($this->createMock(Project::class));
        $mercureService->method('getLoggerChannel')->willReturn(LoggerChannel::BUILD);

        $service = new ProcessRunnerService($mercureService);

        $exitCode = $service->run(
            command: $command,
            startMessage: 'Starting test command'
        );

        $this->assertEquals($expectedExitCode, $exitCode, $description);
    }

    /**
     * Teste que le service dispatche un message de démarrage.
     */
    public function testRunDispatchesStartMessage(): void
    {
        $mercureService = $this->createMock(MercureService::class);
        $mercureService->method('getProject')->willReturn($this->createMock(Project::class));
        $mercureService->method('getLoggerChannel')->willReturn(LoggerChannel::BUILD);

        $startMessageDispatched = false;
        $mercureService->method('dispatch')
            ->willReturnCallback(function (string $message, ?TypeLog $type = null) use (&$startMessageDispatched) {
                if ($message === 'Test start message' && $type === TypeLog::START) {
                    $startMessageDispatched = true;
                }
            });

        $service = new ProcessRunnerService($mercureService);

        $service->run(
            command: ['echo', 'test'],
            startMessage: 'Test start message'
        );

        $this->assertTrue($startMessageDispatched, 'Le message de démarrage devrait être dispatché');
    }

    /**
     * Teste que le service dispatche un message de complétion pour une commande réussie.
     */
    public function testRunDispatchesCompleteMessageOnSuccess(): void
    {
        $mercureService = $this->createMock(MercureService::class);
        $mercureService->method('getProject')->willReturn($this->createMock(Project::class));
        $mercureService->method('getLoggerChannel')->willReturn(LoggerChannel::BUILD);

        $completeMessageDispatched = false;
        $mercureService->method('dispatch')
            ->willReturnCallback(function (string $message, ?TypeLog $type = null) use (&$completeMessageDispatched) {
                if ($type === TypeLog::COMPLETE) {
                    $completeMessageDispatched = true;
                }
            });

        $service = new ProcessRunnerService($mercureService);

        $service->run(
            command: ['echo', 'success'],
            startMessage: 'Starting'
        );

        $this->assertTrue($completeMessageDispatched, 'Le message de complétion devrait être dispatché');
    }

    /**
     * Teste que le service dispatche un message d'erreur pour une commande échouée.
     */
    public function testRunDispatchesErrorMessageOnFailure(): void
    {
        $mercureService = $this->createMock(MercureService::class);
        $mercureService->method('getProject')->willReturn($this->createMock(Project::class));
        $mercureService->method('getLoggerChannel')->willReturn(LoggerChannel::BUILD);

        $dispatchCalls = [];
        $mercureService->method('dispatch')
            ->willReturnCallback(function (string $message, ?TypeLog $type = null, ?Level $level = null) use (&$dispatchCalls) {
                $dispatchCalls[] = ['message' => $message, 'type' => $type, 'level' => $level];
            });

        $service = new ProcessRunnerService($mercureService);

        $service->run(
            command: ['php', '-r', 'exit(1);'],
            startMessage: 'Starting failing command'
        );

        // Vérifier qu'un message d'erreur a été dispatché
        $errorMessages = array_filter($dispatchCalls, fn($call) => $call['type'] === TypeLog::ERROR);
        $this->assertNotEmpty($errorMessages, 'Un message d\'erreur devrait être dispatché');
    }

    /**
     * Teste l'exécution avec des variables d'environnement.
     */
    public function testRunWithEnvironmentVariables(): void
    {
        $mercureService = $this->createMock(MercureService::class);
        $mercureService->method('getProject')->willReturn($this->createMock(Project::class));
        $mercureService->method('getLoggerChannel')->willReturn(LoggerChannel::BUILD);

        $service = new ProcessRunnerService($mercureService);

        $exitCode = $service->run(
            command: ['php', '-r', 'echo getenv("TEST_VAR");'],
            startMessage: 'Testing env vars',
            env: ['TEST_VAR' => 'test_value']
        );

        $this->assertEquals(0, $exitCode);
    }

    /**
     * Fournit les données de test pour l'exécution de commandes.
     *
     * @return \Generator<string, array{command: string[], expectedExitCode: int, description: string}>
     */
    public static function provideCommandExecutionData(): \Generator
    {
        yield 'commande réussie avec echo' => [
            'command' => ['echo', 'test'],
            'expectedExitCode' => 0,
            'description' => 'Une commande echo devrait retourner le code 0',
        ];

        yield 'commande échouée avec exit 1' => [
            'command' => ['php', '-r', 'exit(1);'],
            'expectedExitCode' => 1,
            'description' => 'Une commande avec exit(1) devrait retourner le code 1',
        ];

        yield 'commande réussie avec php' => [
            'command' => ['php', '-r', 'echo "success";'],
            'expectedExitCode' => 0,
            'description' => 'Une commande PHP valide devrait retourner le code 0',
        ];
    }
}
