<?php

declare(strict_types=1);

namespace Tito10047\BatchSelectionBundle\Loader;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use InvalidArgumentException;

/**
 * Loader responsible for extracting identifiers and counts from a Doctrine ORM Query object.
 * This class modifies the underlying DQL for optimized SELECT and COUNT queries.
 */
class DoctrineQueryLoader implements IdentityLoaderInterface
{

	/**
	 * @inheritDoc
	 */
	public function supports(mixed $source): bool
	{
		// Source must be a Doctrine Query object
		return $source instanceof Query || $source instanceof QueryBuilder;
	}

	/**
	 * @inheritDoc
	 * @param Query|QueryBuilder $source
	 * @return array<int|string>
	 */
	public function loadAllIdentifiers(mixed $source, string $identifierPath = 'id'): array
	{
		if (!$this->supports($source)) {
			throw new InvalidArgumentException('Source must be a Doctrine Query instance.');
		}

		if ($source instanceof QueryBuilder) {
			$source = $source->getQuery();
		}

		/** @var Query $baseQuery */
		$baseQuery = clone $source;

		$entityManager = $baseQuery->getEntityManager();

		// zisti root entitu a alias z povodnej DQL
		[$rootEntity, $rootAlias] = $this->resolveRootFromDql($baseQuery);

		// zisti skutočné meno ID poľa (iba jednoduchý, nie zložený kľúč)
		$metadata = $entityManager->getClassMetadata($rootEntity);
		$identifierFields = $metadata->getIdentifierFieldNames();
		if (count($identifierFields) !== 1) {
			throw new RuntimeException('Composite alebo neštandardný identifikátor nie je podporovaný pre loadAllIdentifiers().');
		}

		$defaultIdField = $identifierFields[0];
		$identifierField = $identifierPath !== '' ? $identifierPath : $defaultIdField;

		$qb = $entityManager->createQueryBuilder();
		$qb->select($rootAlias . '.' . $identifierField)
			->from($rootEntity, $rootAlias);

		$idQuery = $qb->getQuery();
		$rows = $idQuery->getScalarResult();

		return array_map('current', $rows);
	}

	/**
	 * @inheritDoc
	 * @param Query $source The Doctrine Query instance.
	 */
	public function getTotalCount(mixed $source): int
	{
		if (!$this->supports($source)) {
			throw new InvalidArgumentException('Source must be a Doctrine Query instance.');
		}

		if ($source instanceof QueryBuilder) {
			$source = $source->getQuery();
		}

		/** @var Query $baseQuery */
		$baseQuery = clone $source;
		$entityManager = $baseQuery->getEntityManager();

		[$rootEntity, $rootAlias] = $this->resolveRootFromDql($baseQuery);

		// ak je k dispozícii jednoduché ID pole, počítaj nad ním, inak COUNT(alias)
		$metadata = $entityManager->getClassMetadata($rootEntity);
		$identifierFields = $metadata->getIdentifierFieldNames();
		$countExpr = null;
		if (count($identifierFields) === 1) {
			$countExpr = $rootAlias . '.' . $identifierFields[0];
		} else {
			$countExpr = $rootAlias; // fallback
		}

		$qb = $entityManager->createQueryBuilder();
		$qb->select($qb->expr()->count($countExpr))
			->from($rootEntity, $rootAlias);

		$countQuery = $qb->getQuery();
		try {
			return (int) $countQuery->getSingleScalarResult();
		} catch (\Exception $e) {
			throw new RuntimeException('Failed to execute optimized count query.', 0, $e);
		}
	}

	/**
	 * Vytiahne root entitu a alias z Query DQL pomocou Doctrine Parsera.
	 *
	 * @return array{0:string,1:string} [entityClass, alias]
	 */
	private function resolveRootFromDql(Query $query): array
	{
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


}