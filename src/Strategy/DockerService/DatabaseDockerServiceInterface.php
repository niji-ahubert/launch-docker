<?php

declare(strict_types=1);

namespace App\Strategy\DockerService;

/**
 * Identifie les services Docker de type base de données.
 */
interface DatabaseDockerServiceInterface
{
    /**
     * Retourne le protocole utilisé pour le DSN (ex: 'mysql', 'postgresql').
     */
    public function getDsnProtocol(): string;

    /**
     * Retourne le mot de passe à utiliser pour la connexion.
     */
    public function getConnectionPassword(): string;

    /**
     * Retourne l'utilisateur à utiliser pour la connexion.
     */
    public function getConnectionUser(): string;

    /**
     * Retourne le nom de la base de données.
     */
    public function getDatabaseName(): string;
}
