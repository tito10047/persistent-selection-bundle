<?php

namespace Tito10047\PersistentSelectionBundle\Tests\App\AssetMapper\Src\Normalizer;

use Tito10047\PersistentSelectionBundle\Normalizer\IdentifierNormalizerInterface;

/**
 * Jednoduchý testovací normalizer pre polia.
 * Podporuje typ "array" (volané z testu) a normalizuje polo zadané cez $identifierPath.
 */
class TestArrayNormalizer implements IdentifierNormalizerInterface
{
    public function supports(mixed $item): bool
    {
        // V SelectionManager testoch posielame string "array" ako typ,
        // ale pre priame použitie očakávame aj skutočné pole.
        return $item === 'array' || is_array($item);
    }

    public function normalize(mixed $item, ?string $identifierPath): string|int
    {
        if (!is_array($item)) {
            throw new \RuntimeException('TestArrayNormalizer očakáva pole.');
        }

        if ($identifierPath === null || $identifierPath === '') {
            throw new \RuntimeException('identifierPath musí byť zadaný pre normalizáciu poľa.');
        }

        if (!array_key_exists($identifierPath, $item)) {
            throw new \RuntimeException(sprintf('Kľúč "%s" sa v poli nenachádza.', (string) $identifierPath));
        }

        $value = $item[$identifierPath];
        if (is_int($value) || is_string($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        throw new \RuntimeException('Hodnota identifikátora musí byť scalar alebo __toString objekt.');
    }
}
