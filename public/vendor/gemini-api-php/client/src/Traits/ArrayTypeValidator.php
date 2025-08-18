<?php

declare(strict_types=1);

namespace GeminiAPI\Traits;

use InvalidArgumentException;

use function gettype;
use function is_float;
use function is_object;
use function is_string;
use function sprintf;

trait ArrayTypeValidator
{
    /**
     * @param array<int, mixed> $items
     * @param class-string $classString
     * @return void
     * @throws InvalidArgumentException
     */
    private function ensureArrayOfType(array $items, string $classString): void
    {
        foreach ($items as $item) {
            if (!$item instanceof $classString) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected type %s but found %s',
                        $classString,
                        is_object($item) ? $item::class : gettype($item),
                    ),
                );
            }
        }
    }

    /**
     * @param array<int, mixed> $items
     * @return void
     * @throws InvalidArgumentException
     */
    private function ensureArrayOfString(array $items): void
    {
        foreach ($items as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected string but found %s',
                        is_object($item) ? $item::class : gettype($item),
                    ),
                );
            }
        }
    }

    /**
     * @param array<int, mixed> $items
     * @return void
     * @throws InvalidArgumentException
     */
    private function ensureArrayOfFloat(array $items): void
    {
        foreach ($items as $item) {
            if (!is_float($item)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected float but found %s',
                        is_object($item) ? $item::class : gettype($item),
                    ),
                );
            }
        }
    }
}
