# StorageInterface

Purpose: Persist the selection state for a given context (e.g., a list key) in a simple, scalable way. Storages deal only with scalar IDs and the selection mode.

Interface

```php
interface StorageInterface
{
    public function add(string $context, array $ids): void;
    public function remove(string $context, array $ids): void;
    public function clear(string $context): void;
    /** @return array<int|string> */
    public function getStoredIdentifiers(string $context): array;
    public function hasIdentifier(string $context, string|int $id): bool;
    public function setMode(string $context, SelectionMode $mode): void;
    public function getMode(string $context): SelectionMode;
}
```

Key ideas

- Context: A unique key for the selection source (e.g. `orders_list`, `articles`).
- Mode: `INCLUDE` means stored IDs are selected; `EXCLUDE` means “select all except these IDs”.
- Storage is intentionally dumb: it does not validate whether IDs exist.

Default implementation: SessionStorage

- Ships with the bundle and stores per HTTP session.
- Great for typical web flows and single-user session state.

Example: DoctrineStorage (persist per user)

Goal

Store selection state in the database so each authenticated user has their own persistent selection across devices and sessions.

Schema suggestion

```sql
CREATE TABLE selection_state (
    id CHAR(36) PRIMARY KEY,        -- store UUID as 36-char string (or use BINARY(16) if preferred)
    user_id CHAR(36) NOT NULL,      -- per-user isolation key
    context VARCHAR(255) NOT NULL,
    mode VARCHAR(16) NOT NULL,       -- 'include' | 'exclude'
    ids JSON NOT NULL,               -- array of scalar identifiers
    updated_at TIMESTAMP NOT NULL
);
CREATE UNIQUE INDEX selection_state_user_context_idx ON selection_state(user_id, context);
```

Entity (simplified)

```php
use Tito10047\PersistentSelectionBundle\Enum\SelectionMode;

final class SelectionState
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $context,
        private string $mode = SelectionMode::INCLUDE->value,
        private array $ids = [],
        private \DateTimeImmutable $updatedAt = new \DateTimeImmutable()
    ) {}

    // getters/setters omitted for brevity
}
```

DoctrineStorage implementation

```php
use Doctrine\ORM\EntityManagerInterface;
use Tito10047\PersistentSelectionBundle\Enum\SelectionMode;
use Tito10047\PersistentSelectionBundle\Storage\StorageInterface;

final class DoctrineStorage implements StorageInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserIdProvider $userIdProvider // your service returning a stable user ID
    ) {}

    public function add(string $context, array $ids): void
    {
        $state = $this->getOrCreate($context);
        $state->setIds(array_values(array_unique(array_merge($state->getIds(), $ids))))
              ->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function remove(string $context, array $ids): void
    {
        $state = $this->getOrCreate($context);
        $state->setIds(array_values(array_diff($state->getIds(), $ids)))
              ->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function clear(string $context): void
    {
        $state = $this->getOrCreate($context);
        $state->setIds([])
              ->setMode(SelectionMode::INCLUDE->value)
              ->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function getStoredIdentifiers(string $context): array
    {
        return $this->getOrCreate($context)->getIds();
    }

    public function hasIdentifier(string $context, int|string $id): bool
    {
        return in_array($id, $this->getOrCreate($context)->getIds(), true);
    }

    public function setMode(string $context, SelectionMode $mode): void
    {
        $state = $this->getOrCreate($context);
        $state->setMode($mode->value)->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function getMode(string $context): SelectionMode
    {
        $state = $this->getOrCreate($context);
        return SelectionMode::tryFrom($state->getMode()) ?? SelectionMode::INCLUDE;
    }

    private function getOrCreate(string $context): SelectionState
    {
        $userId = $this->userIdProvider->getUserId();
        $repo = $this->em->getRepository(SelectionState::class);
        $state = $repo->findOneBy(['userId' => $userId, 'context' => $context]);
        if (!$state) {
            $state = new SelectionState(Uuid::v4()->toRfc4122(), $userId, $context);
            $this->em->persist($state);
        }
        return $state;
    }
}
```

Usage

- Register this storage as a service and point your selection manager configuration to it for the desired manager key.
- Each user’s selection is isolated via `user_id` in the database table.

Notes

- Consider TTL cleanup (e.g., delete states not updated for N days).
- If you need multi-tenant, include `tenant_id` in the unique index.
- Ensure IDs remain small and scalar; normalize entities beforehand.
