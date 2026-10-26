<?php

namespace App\Services\Backup\Support;

/** Result of a successful store() call on a destination driver. */
final class StoredFile
{
    public function __construct(
        public readonly string $path,
        public readonly ?string $externalId,
        public readonly int $size,
    ) {
    }
}
