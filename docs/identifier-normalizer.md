# IdentifierNormalizerInterface

Purpose: Convert any item (entity, array, value object, scalar) into a scalar identifier (`string`|`int`) that can be stored and compared efficiently.

Interface

```php
interface IdentifierNormalizerInterface
{
    public function supports(mixed $item): bool;
    public function normalize(mixed $item, ?string $identifierPath): string|int;
}
```

When it’s used

- Every time the bundle needs to decide if a row is selected, it first normalizes the given item to a scalar ID.
- The optional `identifierPath` lets you point to a specific property (e.g. `uuid`, `author.id`, `slug`).

Example: Article selection by author

Scenario

You display a list of `Article` entities, but users should select by the article's author. That is, when a row is toggled, you want to store the author’s identifier (because later you’ll “select all articles by these authors”).

Entities

```php
final class Author
{
    public function __construct(
        private int $id,
        private string $name,
    ) {}

    public function getId(): int { return $this->id; }
}

final class Article
{
    public function __construct(
        private int $id,
        private Author $author,
        private string $title,
    ) {}

    public function getAuthor(): Author { return $this->author; }
}
```

Normalizer: Author from Article

```php
use Tito10047\PersistentSelectionBundle\Normalizer\IdentifierNormalizerInterface;

final class ArticleAuthorNormalizer implements IdentifierNormalizerInterface
{
    public function supports(mixed $item): bool
    {
        return $item instanceof Article || $item instanceof Author;
    }

    public function normalize(mixed $item, ?string $identifierPath): string|int
    {
        // Allow both Article and Author items to be normalized
        $author = $item instanceof Article ? $item->getAuthor() : $item;

        // We ignore $identifierPath here and always return author's ID
        return $author->getId();
    }
}
```

Register and use
```yaml
persistent_selection:
    author:
        normalizer: App\Normalizer\ArticleAuthorNormalizer

```
```php
use \Tito10047\PersistentSelectionBundle\Service\SelectionManagerInterface;
// In your controller/service where you register the source
class Foo extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController {
    public function __construct(
        #[Autowire(service: 'persistent_selection.manager.author')]
        private readonly SelectionManagerInterface$selectionManager,
        private readonly \Doctrine\ORM\EntityManagerInterface $em
    ) {
            
    }
    
    public function list() {
        $qb = $this->em->createQueryBuilder()->select("a")->from(Article::class,'a')
        
        $selectionManager->registerSource('articles', $qb);
        
        return $this->render("list.html.twig",[
            "articles"=>$qb->getQuery()->getResult();
        ])
    }
}
```

```yaml
{{ persistent_selection_row_selector('articles', article) }}
```

Notes

- Keep normalization fast and pure (no I/O); only compute a stable scalar.
- If your IDs are UUID objects, convert them to a string representation.
- Provide multiple normalizers if needed (array/object/scalar are already included by default in the bundle).
