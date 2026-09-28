<?php

namespace App\Rules;

use App\Services\Dashboard\WidgetCatalog;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A dashboard layout: three zones of items, nested at most like this:
 *
 * - zone: widgets, sections, tabbed sections
 * - tabbed section: tabs, each holding widgets and sections
 * - section: widgets only
 *
 * Every widget id must exist and appear once; item ids must be unique.
 */
class DashboardLayout implements ValidationRule
{
    public const ZONES = ['left', 'middle', 'right'];

    private const MAX_ITEMS = 200;

    /** @var list<string> */
    private array $errors = [];

    /** @var array<string, true> */
    private array $itemIds = [];

    /** @var array<string, true> */
    private array $widgets = [];

    private int $count = 0;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $this->errors = [];
        $this->itemIds = [];
        $this->widgets = [];
        $this->count = 0;

        if (! is_array($value) || ($value['version'] ?? null) !== 1 || ! is_array($value['zones'] ?? null)) {
            $fail('The layout must have version 1 and zones.');

            return;
        }

        if (array_diff(array_keys($value['zones']), self::ZONES) !== [] || array_diff(self::ZONES, array_keys($value['zones'])) !== []) {
            $fail('The layout must have exactly the left, middle and right zones.');

            return;
        }

        foreach (self::ZONES as $zone) {
            $this->items($value['zones'][$zone], "zones.{$zone}", ['widget', 'section', 'tabs']);
        }

        if ($this->count > self::MAX_ITEMS) {
            $this->errors[] = 'The layout has too many items.';
        }

        foreach (array_unique($this->errors) as $error) {
            $fail($error);
        }
    }

    /**
     * @param  list<string>  $allowed  item kinds allowed at this level
     */
    private function items(mixed $items, string $path, array $allowed): void
    {
        if (! is_array($items) || ! array_is_list($items)) {
            $this->errors[] = "{$path} must be a list.";

            return;
        }

        foreach ($items as $index => $item) {
            $this->item($item, "{$path}.{$index}", $allowed);
        }
    }

    /**
     * @param  list<string>  $allowed
     */
    private function item(mixed $item, string $path, array $allowed): void
    {
        $this->count++;

        if (! is_array($item) || ! is_string($item['id'] ?? null) || ! preg_match('/^[\w-]{1,64}$/', $item['id'])) {
            $this->errors[] = "{$path} needs an id.";

            return;
        }

        if (isset($this->itemIds[$item['id']])) {
            $this->errors[] = "Item id {$item['id']} is used twice.";
        }

        $this->itemIds[$item['id']] = true;

        $kind = $item['kind'] ?? null;

        if (! in_array($kind, $allowed, true)) {
            $this->errors[] = match ($kind) {
                'tabs' => 'Tabbed sections can only be placed directly in a zone.',
                'section' => 'Sections can only be placed in a zone or a tab, not inside another section.',
                default => "{$path} has an unknown kind.",
            };

            return;
        }

        $this->span($item, $path);

        match ($kind) {
            'widget' => $this->widget($item, $path),
            'section' => $this->section($item, $path),
            'tabs' => $this->tabs($item, $path),
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function widget(array $item, string $path): void
    {
        $widget = $item['widget'] ?? null;

        if (! is_string($widget) || ! WidgetCatalog::isKnown($widget)) {
            $this->errors[] = "{$path} is not a known widget.";

            return;
        }

        if (isset($this->widgets[$widget])) {
            $this->errors[] = "The {$widget} widget is placed twice.";
        }

        $this->widgets[$widget] = true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function section(array $item, string $path): void
    {
        $this->title($item, $path);
        $this->items($item['items'] ?? null, "{$path}.items", ['widget']);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function tabs(array $item, string $path): void
    {
        $this->title($item, $path, required: false);
        $tabs = $item['tabs'] ?? null;

        if (! is_array($tabs) || ! array_is_list($tabs) || count($tabs) < 1 || count($tabs) > 12) {
            $this->errors[] = "{$path} needs between 1 and 12 tabs.";

            return;
        }

        $tabIds = [];

        foreach ($tabs as $index => $tab) {
            $tabPath = "{$path}.tabs.{$index}";

            if (! is_array($tab) || ! is_string($tab['id'] ?? null) || ! preg_match('/^[\w-]{1,64}$/', $tab['id'])) {
                $this->errors[] = "{$tabPath} needs an id.";

                continue;
            }

            $tabIds[] = $tab['id'];
            $this->title($tab, $tabPath);
            $this->items($tab['items'] ?? null, "{$tabPath}.items", ['widget', 'section']);
        }

        if (! in_array($item['default_tab'] ?? null, $tabIds, true)) {
            $this->errors[] = "{$path} must name one of its tabs as the default.";
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function title(array $item, string $path, bool $required = true): void
    {
        $title = $item['title'] ?? null;

        if (($required || $title !== null) && (! is_string($title) || trim($title) === '' || mb_strlen($title) > 60)) {
            $this->errors[] = "{$path} needs a title of up to 60 characters.";
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function span(array $item, string $path): void
    {
        if (isset($item['columns']) && ! in_array($item['columns'], [1, 2, 3], true)) {
            $this->errors[] = "{$path} columns must be 1, 2 or 3.";
        }

        if (isset($item['rows']) && ! in_array($item['rows'], [1, 2, 3, 4], true)) {
            $this->errors[] = "{$path} rows must be between 1 and 4.";
        }
    }
}
