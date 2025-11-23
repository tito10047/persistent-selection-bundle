# IdentityLoaderInterface

Purpose: Provide identifiers and total count for a given source. Loaders abstract how IDs are fetched (Doctrine queries, arrays, external APIs, …) so the selection logic stays generic.

Interface

```php
interface IdentityLoaderInterface {
    /**
     * @return array<int|string> List of scalar identifiers for the CURRENT result set.
     */
    public function loadAllIdentifiers(?IdentifierNormalizerInterface $resolver, mixed $source, ?string $identifierPath): array;

    public function getTotalCount(mixed $source): int;

    public function supports(mixed $source): bool;
}
```

Contract

- `supports($source)`: return true if this loader knows how to extract IDs from this source.
- `loadAllIdentifiers(...)`: return only the identifiers for the current selection (e.g. current filter) or for the full in-memory list. Use the provided `IdentifierNormalizerInterface` when the source items aren’t already scalar IDs.
- `getTotalCount($source)`: return the total number of items in the entire result (not just the current page), used to compute “Select All”.

Built-in loaders (examples in the bundle)

- Array/collection loaders
- Doctrine Query/QueryBuilder loaders

Custom example: TagFilteredApiLoader

Scenario

You fetch articles from an external API that returns a paginated JSON payload. Each item contains an `id` and a list of `tags`. The UI needs to select by article IDs, but the total count depends on the remote API.

Example response

```json
{
  "page": 1,
  "perPage": 50,
  "total": 1240,
  "items": [
    {"id": 101, "title": "…", "tags": ["php", "symfony"]},
    {"id": 102, "title": "…", "tags": ["ux", "stimulus"]}
  ]
}
```

Loader implementation

```php
use Tito10047\BatchSelectionBundle\Loader\IdentityLoaderInterface;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;

/**
 * @param array{endpoint:string, query:array, items:array, total:int} $source
 */
final class TagFilteredApiLoader implements IdentityLoaderInterface
{
    public function __construct(
        private readonly App\MyApi $api
    ) {}


    public function supports(mixed $source): bool
    {
        return is_array($source) && isset($source['items'], $source['total']);
    }

    public function loadAllIdentifiers(?IdentifierNormalizerInterface $resolver, mixed $filters, ?string $identifierPath): array
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Unsupported source for TagFilteredApiLoader');
        }

        $ids = [];
        while($items = $this->api->fetchNext($filters)){
            foreach ($items as $item) {
                if ($resolver) {
                    $ids[] = $resolver->normalize($item, $identifierPath ?? 'id');
                } else {
                    $ids[] = is_array($item) && array_key_exists('id', $item) ? (string) $item['id'] : (string) $item;
                }
            }
        }
        return $ids;
    }

    public function getTotalCount(mixed $source): int
    {
        if (!$this->supports($source)) {
            return 0;
        }
        return (int) $source['total'];
    }
}
```

Usage

Register and use
```yaml
batch_selection:
    author:
        loader: App\Normalizer\TagFilteredApiLoader

```
```php
use \Tito10047\BatchSelectionBundle\Service\SelectionManagerInterface;
// In your controller/service where you register the source
class Foo extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController {
    public function __construct(
        #[Autowire(service: 'batch_selection.manager.author')]
        private readonly SelectionManagerInterface$selectionManager,
        private readonly App\MyApi $api
    ) {
            
    }
    
    public function list() {
        //$filters gets from form        
        $selectionManager->registerSource('remote_articles', $filters);
        
        return $this->render("list.html.twig",[
            "remote_articles"=>$this->api->firstPage($filters)
        ])
    }
}
```

```yaml
{{ batch_selection_row_selector('articles', article) }}
```

Notes

- Keep loaders side-effect free; they should not mutate the source.
- Mix and match with `IdentifierNormalizerInterface` for arrays, objects, or complex IDs.
