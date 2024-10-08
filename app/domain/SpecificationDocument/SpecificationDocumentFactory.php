<?php

namespace App\Domain\SpecificationDocument;

use App\Domain\SpecificationDocument\ValueObject\Summary;
use App\Domain\SpecificationDocument\ValueObject\Title;
use DateTimeImmutable;

final class SpecificationDocumentFactory
{
    public static function create(SpecificationDocumentDto $dto): SpecificationDocumentEntity
    {
        return new SpecificationDocumentEntity(
            $dto->id,
            $dto->projectId,
            $dto->userId,
            new Title($dto->title),
            new Summary($dto->summary),
            new DateTimeImmutable($dto->updatedAt),
            $dto->userName,
        );
    }
}
