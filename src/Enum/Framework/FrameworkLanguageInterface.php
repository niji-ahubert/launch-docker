<?php

declare(strict_types=1);

namespace App\Enum\Framework;

/**
 * @template-covariant T as FrameworkLanguagePhp|FrameworkLanguageNode
 */
interface FrameworkLanguageInterface
{
    public function getValue(): string;
}
