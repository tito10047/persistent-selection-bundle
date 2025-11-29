<?php

declare(strict_types=1);

namespace Tito10047\PersistentSelectionBundle\Loader;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Tito10047\PersistentSelectionBundle\Normalizer\IdentifierNormalizerInterface;

/**
 * Loader pre Doctrine QueryBuilder.
 * Zachováva pôvodné WHERE/JOINS a prepisuje len SELECT časť.
 */
final class DoctrineQueryBuilderLoader implements IdentityLoaderInterface {

	public function __construct(
		private IdentifierNormalizerInterface $arrayNormalizer
	) {
	}

	public function supports(mixed $source): bool {
		return $source instanceof QueryBuilder;
	}

	/**
	 * @param QueryBuilder $source
	 *
	 * @return array<int|string>
	 */
	public function loadAllIdentifiers(?IdentifierNormalizerInterface $resolver, mixed $source, ?string $identifierPath): array {
		if (!$this->supports($source)) {
			throw new InvalidArgumentException('Source must be a Doctrine QueryBuilder instance.');
		}

		$baseQb = clone $source;

		$em = $baseQb->getEntityManager();

		$rootAliases  = $baseQb->getRootAliases();
		$rootEntities = $baseQb->getRootEntities();
		if (empty($rootAliases) || empty($rootEntities)) {
			// fallback – vytiahni z DQL
			$query = $baseQb->getQuery();
			[$rootEntity, $rootAlias] = $this->resolveRootFromDql($query);
		} else {
			$rootAlias  = $rootAliases[0];
			$rootEntity = $rootEntities[0];
		}

		$metadata         = $em->getClassMetadata($rootEntity);
		$identifierFields = $metadata->getIdentifierFieldNames();
		if (count($identifierFields) !== 1) {
			throw new RuntimeException('Composite alebo neštandardný identifikátor nie je podporovaný pre loadAllIdentifiers().');
		}

		$defaultIdField  = $identifierFields[0];
		$identifierField = ($identifierPath !== null && $identifierPath !== '') ? $identifierPath : $defaultIdField;

		// prepis SELECT, ostatné časti dotazu (WHERE, JOIN, GROUP BY, HAVING, ORDER BY) ponechaj
		$baseQb->resetDQLPart('select');
		$baseQb->select($rootAlias . '.' . $identifierField);

		// ignoruj stránkovanie – chceme všetky identifikátory z danej filtrácie
		$baseQb->setFirstResult(null);
		$baseQb->setMaxResults(null);

		// Získaj skalárne výsledky jedného stĺpca
		$rows   = $baseQb->getQuery()->getScalarResult();
		$values = array_map('current', $rows);

		// Pretypuj podľa Doctrine typu ID poľa, aby sa vracali stabilné typy (int pre integer ID)
		$fieldType = $metadata->getTypeOfField($identifierField);
		$values    = array_map(function ($v) use ($fieldType) {
			return self::castByDoctrineType($v, $fieldType);
		}, $values);

		return $values;
	}

	/**
	 * @param QueryBuilder $source
	 */
	public function getTotalCount(mixed $source): int {
		if (!$this->supports($source)) {
			throw new InvalidArgumentException('Source must be a Doctrine QueryBuilder instance.');
		}

		$baseQb = clone $source;

		$em = $baseQb->getEntityManager();

		$rootAliases  = $baseQb->getRootAliases();
		$rootEntities = $baseQb->getRootEntities();
		if (empty($rootAliases) || empty($rootEntities)) {
			// fallback – vytiahni z DQL
			$query = $baseQb->getQuery();
			[$rootEntity, $rootAlias] = $this->resolveRootFromDql($query);
		} else {
			$rootAlias  = $rootAliases[0];
			$rootEntity = $rootEntities[0];
		}

		$metadata         = $em->getClassMetadata($rootEntity);
		$identifierFields = $metadata->getIdentifierFieldNames();

		// COUNT výraz
		if (count($identifierFields) === 1) {
			$countExpr = 'COUNT(DISTINCT ' . $rootAlias . '.' . $identifierFields[0] . ')';
		} else {
			$countExpr = 'COUNT(' . $rootAlias . ')'; // fallback
		}

		// uprav SELECT na COUNT, odstráň orderBy, ignoruj stránkovanie
		$baseQb->resetDQLPart('select');
		$baseQb->resetDQLPart('orderBy');
		$baseQb->select($countExpr);
		$baseQb->setFirstResult(null);
		$baseQb->setMaxResults(null);

		try {
			return (int) $baseQb->getQuery()->getSingleScalarResult();
		} catch (Exception $e) {
			throw new RuntimeException('Failed to execute count query.', 0, $e);
		}
	}

	/**
	 * Cast hodnoty podľa Doctrine typu poľa, aby sa napr. integer ID vracali ako int a nie string.
	 */
	private static function castByDoctrineType(mixed $value, ?string $doctrineType): int|string {
		$intTypes = ['integer', 'smallint', 'bigint'];
		if ($doctrineType !== null && in_array($doctrineType, $intTypes, true)) {
			return (int) $value;
		}
		return is_int($value) || is_string($value) ? $value : (string) $value;
	}

	/**
	 * Vytiahne root entitu a alias z Query DQL pomocou Doctrine Parsera.
	 *
	 * @return array{0:string,1:string} [entityClass, alias]
	 */
	private function resolveRootFromDql(Query $query): array {
		$AST = $query->getAST();

		/** @var Query\AST\IdentificationVariableDeclaration $from */
		$from = $AST->fromClause->identificationVariableDeclarations[0] ?? null;
		if ($from === null || $from->rangeVariableDeclaration === null) {
			throw new RuntimeException('Nepodarilo sa zistiť root entitu z DQL dotazu.');
		}

		$rootEntity = $from->rangeVariableDeclaration->abstractSchemaName;
		$rootAlias  = $from->rangeVariableDeclaration->aliasIdentificationVariable;

		if (!is_string($rootEntity) || !is_string($rootAlias)) {
			throw new RuntimeException('Neplatný FROM klauzula v DQL dotaze.');
		}

		return [$rootEntity, $rootAlias];
	}

	public function getCacheKey(mixed $source): string {
		if (!$this->supports($source)) {
			throw new InvalidArgumentException('Source must be a Doctrine QueryBuilder instance.');
		}

		/** @var QueryBuilder $source */
		// Use the generated DQL from the QB for a stable representation of structure
		$dql        = $source->getQuery()->getDQL();
		$params     = $source->getParameters();
		$normParams = [];
		foreach ($params as $p) {
			$name         = method_exists($p, 'getName') ? $p->getName() : null;
			$value        = method_exists($p, 'getValue') ? $p->getValue() : null;
			$normParams[] = [
				'name'  => $name,
				'value' => self::normalizeValue($value),
			];
		}
		usort($normParams, function ($a, $b) {
			return strcmp((string) $a['name'], (string) $b['name']);
		});

		return 'doctrine_qb:' . md5(serialize([$dql, $normParams]));
	}

	/**
	 * Normalize values for a deterministic cache key.
	 */
	private static function normalizeValue(mixed $value): mixed {
		if (is_scalar($value) || $value === null) {
			return $value;
		}
		if ($value instanceof \DateTimeInterface) {
			return ['__dt__' => true, 'v' => $value->format(DATE_ATOM)];
		}
		if (is_array($value)) {
			$normalized = [];
			foreach ($value as $k => $v) {
				$normalized[$k] = self::normalizeValue($v);
			}
			if (!array_is_list($normalized)) {
				ksort($normalized);
			}
			return $normalized;
		}
		if (is_object($value)) {
			$vars = get_object_vars($value);
			ksort($vars);
			return ['__class__' => get_class($value), 'props' => self::normalizeValue($vars)];
		}
		return (string) $value;
	}
}
