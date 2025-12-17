<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

use App\Services\DockerCompose\DockerComposeFile;
use App\Services\FileSystemEnvironmentServices;
use Symfony\Bundle\MakerBundle\Generator;

/**
 * Classe abstraite pour les services Docker de base de données.
 * Mutualise les propriétés et getters communs à tous les services de BDD.
 */
abstract readonly class AbstractDatabaseDockerService extends AbstractDockerService implements DatabaseDockerServiceInterface
{
    public function __construct(
        protected string $rootPassword,
        protected string $database,
        protected string $dbUser,
        protected string $dbPassword,
        DockerComposeFile $dockerComposeFile,
        Generator $makerGenerator,
        FileSystemEnvironmentServices $fileSystemEnvironmentServices,
    ) {
        parent::__construct($dockerComposeFile, $makerGenerator, $fileSystemEnvironmentServices);
    }

    public function getConnectionPassword(): string
    {
        return $this->dbPassword;
    }

    public function getConnectionUser(): string
    {
        return $this->dbUser;
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    public function getDatabasePassword(): string
    {
        return $this->rootPassword;
    }

    public function getDatabaseUser(): string
    {
        return $this->dbUser;
    }

    public function getDatabaseRootPassword(): string
    {
        return $this->rootPassword;
    }
}
