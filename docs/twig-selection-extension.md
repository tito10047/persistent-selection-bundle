# Twig: SelectionExtension helpers

The bundle exposes a small set of Twig helper functions for building selection UIs. They integrate with the Stimulus controller and Selection Manager.

Available functions

1) `persistent_selection_is_selected(string key, mixed item, string manager = 'default'): bool`
- Returns true if the given `item` is currently selected in the selection identified by `key`.
- The item is normalized to a scalar ID internally (via the configured `IdentifierNormalizerInterface`).

2) `persistent_selection_is_selected_all(string key, string manager = 'default'): bool`
- Returns true if the current mode is “Select All” for the given `key`.

3) `persistent_selection_row_selector(string key, mixed item, array attributes = [], string manager = 'default'): string`
- Renders a single row checkbox for the given `item`.
- Returns safe HTML. You can pass HTML attributes in the `attributes` array (e.g. `{'class': 'form-check-input'}`).
- The checkbox includes data attributes for the Stimulus controller and is auto‑checked when the item is selected.

4) `persistent_selection_row_selector_all(string key, array attributes = [], string manager = 'default'): string`
- Renders the master “Select All on page / toggle all” checkbox.
- Returns safe HTML. Accepts additional HTML attributes.

5) `persistent_selection_total(string key, string manager = 'default'): int`
- Returns the total number of items in the registered source (not just the current page).

6) `persistent_selection_count(string key, string manager = 'default'): int`
- Returns how many items are currently selected (computed from storage and mode).

7) `persistent_selection_stimulus_controller(string key, ?string controller = null, array variables = [], string manager = 'default', bool asArray = false): string|array`
- Returns HTML attributes for wiring the Stimulus controller to a container element.
- When `asArray = false` (default), returns a string like `data-controller="…" data-…` ready to be injected into a tag.
- When `asArray = true`, returns an associative array of attributes.
- The function sets controller values like `urlToggle`, `urlSelectAll`, `urlSelectRange`, and includes the active `key` and `manager`.

Global

- `persistent_selection_stimulus_controller_name`: The default Stimulus controller name to be used in `data-action` or `data-target` attributes.

Attribute merging rules

When you pass `attributes` to the render helpers, they are merged with reasonable defaults:
- Defaults come first; your custom attributes overwrite them.
- For `class` and `data-controller`, tokens are concatenated and deduplicated (default first, then custom).
- Passing `false` or `null` for a key removes the attribute.

Examples

Basic table with row selectors

```twig
{% set isAllSelected = persistent_selection_is_selected_all('articles') %}

<table {{ persistent_selection_stimulus_controller('articles') }}>
    <thead>
    <tr>
        <th>
            {{ persistent_selection_row_selector_all('articles', { class: 'form-check-input' }) }}
        </th>
        <th>Title</th>
    </tr>
    </thead>
    <tbody>
    {% for article in articles %}
        <tr>
            <td>
                {{ persistent_selection_row_selector('articles', article, { class: 'form-check-input' }) }}
            </td>
            <td>{{ article.title }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>

<div class="small text-muted mt-2">
    Selected {{ persistent_selection_count('articles') }} of {{ persistent_selection_total('articles') }}
    {% if isAllSelected %}(All mode){% endif %}
    <!-- Use the global name for data-action bindings -->
    <button class="btn btn-sm btn-outline-secondary" data-action="{{ persistent_selection_stimulus_controller_name }}#selectCurrentPage">Select visible</button>
    <button class="btn btn-sm btn-outline-secondary" data-action="{{ persistent_selection_stimulus_controller_name }}#selectAll">Select all</button>
    <button class="btn btn-sm btn-outline-secondary" data-action="{{ persistent_selection_stimulus_controller_name }}#unselectAll">Unselect all</button>
    <button class="btn btn-sm btn-outline-secondary" data-action="{{ persistent_selection_stimulus_controller_name }}#clear">Clear</button>
    <button class="btn btn-sm btn-outline-secondary" data-action="{{ persistent_selection_stimulus_controller_name }}#selectRange">Select range</button>
  </div>
```

Custom controller and variables

```twig
<div {{ persistent_selection_stimulus_controller('articles', 'my-custom-controller', {
    selectAllClass: 'btn-primary',
    unselectAllClass: 'btn-outline-secondary',
}) }}>
    …
</div>
```

Notes

- Always register your source with the same `key` you use in Twig.
- If you use a non-default manager, pass its service ID/name as `manager` to every helper consistently.
- The helpers return raw HTML or strings for attributes; do not escape them again.
