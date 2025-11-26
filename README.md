![Tests](https://github.com//tito10047/persistent-selection-bundle/actions/workflows/symfony.yml/badge.svg)

# 🛒 Persistent Selection Bundle

<p align="center">
<img src="docs/preview.png"><br>
</p>

## Make true "Select All" and persistent state management effortless in Symfony.

**More than just checkboxes.** This bundle provides a robust engine for managing persistent 
selections and state across sessions, pages, and filters.

It allows you to store **IDs + Metadata** (context) efficiently. Whether you need a "Select All" 
for 50,000 items in an admin grid, a session-based, database-based or any-based, Shopping Cart for
domains, or simply need to remember which accordion items are expanded—this bundle handles the 
persistence layer so you don't have to.

> ⚠️ **v0.1.0 Stable Beta:** Public API is frozen but the bundle is under active development.

---

## ✨ Key Features

- **True "Select All":** Efficiently handle selections across thousands of records using Doctrine-optimized loaders (ID-only).
- **Metadata Support:** Store context with your selection (e.g., `['qty' => 5]` or `['variant' => 'XL']`) alongside the ID.
- **Context-Aware:** Manage multiple independent selections simultaneously (e.g., `main_grid`, `wishlist`, `user_123_cart`).
- **Flexible Inputs:** Accepts Entities, UUIDs, Integers, or Strings.
- **Memory Safe:** Works with scalar IDs internally; hydrates objects only when you need them.
- **Zero-Config UI:** Includes Twig helpers and Stimulus controllers for instant integration.

---

## 📌 Use Cases

### 🛠️ Admin & Batch Operations

- **Mass Actions:** Select all users in a filtered view (spanning 50 pages) and apply a "Block" action.
- **Invoicing:** Select specific invoices across pagination, then export them to a single ZIP.
- **Inverted Selection:** "Select All" 10,000 items, uncheck 3 specific exceptions, and process the rest.

### 🛒 State & Metadata (New in v0.5.0)

- **Shopping Carts:** Build a domain registrar cart where users select domains (ID) and years (Metadata) without page reloads.
- **UI Persistence:** Remember which tree-view nodes are expanded or which tabs are active across page refreshes.
- **Wizards:** Collect items across multiple steps of a wizard before final processing.

---

## 🚀 Quick Start

### 1) Configure the bundle

```yaml
# config/packages/persistent_selection.yaml
persistent_selection:
    default:
        normalizer: 'persistent_selection.normalizer.object' # Auto-detects IDs
        storage: 'persistent_selection.storage.session'      # Uses PHP Session
    scalar:
        normalizer: 'persistent_selection.normalizer.scalar'
    array:
        normalizer: 'persistent_selection.normalizer.array'
        identifier_path: 'id'
```

```yaml
# config/routes/persistent_selection.yaml
persistent_selection:
    resource: '@PersistentSelectionBundle/config/routes.php'
```

### 2) Usage in Controller (The Manager Pattern)

The SelectionManager acts as a factory. You request a specific context (e.g., 'event_attendees' or 'my_cart') and interact with that state object.

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tito10047\PersistentSelectionBundle\Service\SelectionManagerInterface;
use App\Entity\Product;

final class CartController extends AbstractController
{
    public function __construct(
        private readonly SelectionManagerInterface $selectionManager,
    ) {}

    public function addToCart(Product $product, Request $request): Response
    {
        // 1. Get the interface for a specific context
        $cart = $this->selectionManager->getSelection('my_cart');

        // 2. Add item with Metadata (Contextual Data)
        // You can pass null, an array, or a serializable object
        $cart->select($product, [
            'quantity' => $request->get('qty', 1),
            'added_at' => new \DateTime()
        ]);

        return $this->json(['count' => $cart->getTotal()]);
    }

    public function checkout(): void
    {
        $cart = $this->selectionManager->getSelection('my_cart');

        // 3. Retrieve hydrated objects with their metadata
        // Returns: [ 101 => ['quantity' => 2], 102 => ['quantity' => 1] ]
        $selectedItems = $cart->getSelectedObjects(); 
        
        // ... process checkout logic ...

        // 4. Cleanup
        $cart->destroy();
    }
}
```

---

### 3) "Select All" with Doctrine Source

For mass actions in Grids, register a source (QueryBuilder) so the bundle knows how to fetch "All" IDs when the user clicks "Select All".

```php
public function list(SelectionManagerInterface $manager): void
{
    $qb = $this->repo->createQueryBuilder('u')->where('u.active = true');

    // Register the source to enable "Select All" functionality for this context
    $manager->registerSource('user_grid', $qb);
}
```

---

### 4) Wire up the UI (Twig)

The bundle provides powerful Twig helpers to check state and retrieve metadata.

```twig
{# Check global state #}
{% set isAllSelected = persistent_selection_is_selected_all('user_grid') %}

<table>
    <thead>
        <tr>
            <th>
                {# Stimulus controller handles the UI toggling #}
                <div {{ persistent_selection_stimulus_controller('user_grid') }}>
                    <button data-action="{{ persistent_selection_stimulus_controller_name }}#selectAll">Select All</button>
                    <button data-action="{{ persistent_selection_stimulus_controller_name }}#unselectAll">Unselect All</button>
                </div>
            </th>
            <th>Product</th>
            <th>Qty</th>
        </tr>
    </thead>
    <tbody>
    {% for product in products %}
        <tr>
            <td>
                {# Check individual state #}
                {% if persistent_selection_is_selected('user_grid', product) %}
                    <input type="checkbox" checked>
                {% endif %}
            </td>
            <td>{{ product.name }}</td>
            <td>
                {# Retrieve Metadata #}
                {% set meta = persistent_selection_metadata('user_grid', product) %}
                
                {# Access metadata values easily #}
                {{ meta.quantity|default(0) }}
            </td>
        </tr>
    {% endfor %}
    </tbody>
</table>
```

---

## 🧠 Architecture

- The bundle is built on stable interfaces to ensure long-term compatibility:
- SelectionManager (Factory): Creates context-aware instances.
- SelectionInterface (Stateful): The main API (select, unselect, getMetadata).
- StorageInterface: "Dumb" persistence layer (Session, Redis, DB) handling map storage.
- MetadataConverter: Handles complex object serialization for metadata.

---

This documentation covers the most important extension points of the bundle with focused, example‑driven guides.

- Identifier normalization — converting items to scalar IDs:
    - See: [docs/identifier-normalizer.md](docs/identifier-normalizer.md)
- Loaders — how IDs are collected from various sources:
    - See: [docs/identity-loader.md](docs/identity-loader.md)
- Storage — how selections are persisted (per user, per context):
    - See: [docs/storage.md](docs/storage.md)
- Twig helpers — UI functions available in templates:
    - See: [docs/twig-selection-extension.md](docs/twig-selection-extension.md)

    