<?php

declare(strict_types=1);

namespace App\Enum\ContainerType;

use App\Enum\Framework\FrameworkLanguageInterface;
use App\Enum\Framework\FrameworkLanguageNode;
use App\Enum\Framework\FrameworkLanguagePhp;
use App\Enum\ServiceVersion\VersionNodeSupported;
use App\Enum\ServiceVersion\VersionPhpSupported;
use App\Enum\ServiceVersion\VersionServiceSupportedInterface;
use App\Enum\UtilityTrait;

/**
 * @implements TypeContainerInterface<self>
 */
enum ProjectContainer: string implements TypeContainerInterface
{
    use UtilityTrait;

    case PHP = 'php';
    case NODE = 'node';

    /**
     * @phpstan-return FrameworkLanguagePhp|FrameworkLanguageNode
     */
    public function getFrameworkEnum(string $value): ?FrameworkLanguageInterface
    {
        return match ($this) {
            self::PHP => FrameworkLanguagePhp::tryFrom($value),
            self::NODE => FrameworkLanguageNode::tryFrom($value),
        };
    }

    /**
     * @phpstan-return VersionNodeSupported|VersionPhpSupported|null
     */
    public function getServiceVersionEnum(string $value): ?VersionServiceSupportedInterface
    {
        return match ($this) {
            self::PHP => VersionPhpSupported::tryFrom($value),
            self::NODE => VersionNodeSupported::tryFrom($value),
        };
    }
}
