<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

class CustomMetadataAwareNameConverter implements NameConverterInterface
{
    private MetadataAwareNameConverter $decorated;

    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory,
        NameConverterInterface $camelCaseToSnakeCase
    ) {
        $this->decorated = new MetadataAwareNameConverter($classMetadataFactory, $camelCaseToSnakeCase);
    }

    public function normalize(string $propertyName): string
    {
        return $this->decorated->normalize($propertyName);
    }

    public function denormalize(string $propertyName): string
    {
        return $this->decorated->denormalize($propertyName);
    }
}
