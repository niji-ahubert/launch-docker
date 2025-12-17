<?php

declare(strict_types=1);

namespace App\Tests\Unit\Util;

use App\Enum\Environment;
use App\Model\Project;
use App\Model\Service\AbstractContainer;
use App\Util\DockerComposeUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DockerComposeUtilityTest extends TestCase
{
    /**
     * Teste la génération du nom de service Docker Compose avec différents paramètres.
     */
    #[DataProvider('provideProjectServiceNameData')]
    public function testGetProjectServiceName(
        string $client,
        string $project,
        Environment $environment,
        string $serviceName,
        string $expectedResult
    ): void {
        $projectMock = $this->createMock(Project::class);
        $projectMock->method('getClient')->willReturn($client);
        $projectMock->method('getProject')->willReturn($project);
        $projectMock->method('getEnvironmentContainer')->willReturn($environment);

        $serviceMock = $this->createMock(AbstractContainer::class);
        $serviceMock->method('getFolderName')->willReturn($serviceName);

        $result = DockerComposeUtility::getProjectServiceName($projectMock, $serviceMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Fournit les données de test pour la génération du nom de service.
     *
     * @return \Generator<string, array{client: string, project: string, environment: Environment, serviceName: string, expectedResult: string}>
     */
    public static function provideProjectServiceNameData(): \Generator
    {
        yield 'dev environment with mysql' => [
            'client' => 'acme',
            'project' => 'webapp',
            'environment' => Environment::DEV,
            'serviceName' => 'mysql',
            'expectedResult' => 'acme-webapp-mysql-dev',
        ];

        yield 'prod environment with redis' => [
            'client' => 'client1',
            'project' => 'api',
            'environment' => Environment::PROD,
            'serviceName' => 'redis',
            'expectedResult' => 'client1-api-redis-prod',
        ];

        yield 'dev environment with nginx' => [
            'client' => 'test-client',
            'project' => 'test-project',
            'environment' => Environment::DEV,
            'serviceName' => 'nginx',
            'expectedResult' => 'test-client-test-project-nginx-dev',
        ];
    }
}
