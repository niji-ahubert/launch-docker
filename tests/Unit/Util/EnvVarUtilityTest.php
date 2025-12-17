<?php

declare(strict_types=1);

namespace App\Tests\Unit\Util;

use App\Util\EnvVarUtility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnvVarUtilityTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/env_var_utility_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    }

    /**
     * Teste le chargement de variables d'environnement avec différents contenus de fichier.
     */
    #[DataProvider('provideEnvFileContent')]
    public function testLoadEnvironmentVariables(
        string $fileContent,
        array $expectedResult,
        string $description
    ): void {
        $envFile = $this->tempDir . '/.env.test';
        file_put_contents($envFile, $fileContent);

        $result = EnvVarUtility::loadEnvironmentVariables($envFile);

        $this->assertEquals($expectedResult, $result, $description);
    }

    /**
     * Teste le chargement depuis un fichier non existant.
     */
    public function testLoadEnvironmentVariablesWithNonExistentFile(): void
    {
        $envFile = $this->tempDir . '/.env.nonexistent';

        $result = EnvVarUtility::loadEnvironmentVariables($envFile);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Fournit les données de test pour différents contenus de fichier .env.
     *
     * @return \Generator<string, array{fileContent: string, expectedResult: array<string, string>, description: string}>
     */
    public static function provideEnvFileContent(): \Generator
    {
        yield 'fichier valide avec plusieurs variables' => [
            'fileContent' => <<<ENV
            APP_ENV=test
            DATABASE_URL="mysql://user:pass@localhost/db"
            DEBUG=true
            PORT=8080
            ENV,
            'expectedResult' => [
                'APP_ENV' => 'test',
                'DATABASE_URL' => 'mysql://user:pass@localhost/db',
                'DEBUG' => 'true',
                'PORT' => '8080',
            ],
            'description' => 'Devrait parser correctement toutes les variables',
        ];

        yield 'fichier vide' => [
            'fileContent' => '',
            'expectedResult' => [],
            'description' => 'Devrait retourner un tableau vide',
        ];

        yield 'fichier avec commentaires et lignes vides' => [
            'fileContent' => <<<ENV
            # This is a comment
            APP_ENV=production
            
            # Another comment
            DATABASE_URL=postgres://localhost/mydb
            
            # Empty lines should be ignored
            
            DEBUG=false
            ENV,
            'expectedResult' => [
                'APP_ENV' => 'production',
                'DATABASE_URL' => 'postgres://localhost/mydb',
                'DEBUG' => 'false',
            ],
            'description' => 'Devrait ignorer les commentaires et lignes vides',
        ];

        yield 'fichier avec valeurs entre guillemets' => [
            'fileContent' => <<<ENV
            SINGLE_QUOTED='value with spaces'
            DOUBLE_QUOTED="another value"
            NO_QUOTES=simple
            ENV,
            'expectedResult' => [
                'SINGLE_QUOTED' => 'value with spaces',
                'DOUBLE_QUOTED' => 'another value',
                'NO_QUOTES' => 'simple',
            ],
            'description' => 'Devrait retirer les guillemets simples et doubles',
        ];

        yield 'fichier avec espaces autour des clés et valeurs' => [
            'fileContent' => <<<ENV
              KEY1  =  value1  
            KEY2="  value2  "
              KEY3  =  'value3'  
            ENV,
            'expectedResult' => [
                'KEY1' => '  value1',
                'KEY2' => '  value2  ',
                'KEY3' => '  \'value3\'',
            ],
            'description' => 'Devrait trim les espaces autour des clés et retirer uniquement les guillemets externes',
        ];
    }
}
