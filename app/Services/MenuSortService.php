<?php

namespace App\Services;

class MenuSortService
{
    /**
     * Get the menu sorted by saved order.
     */
    public function getSortedMenu(): array
    {
        $menu = config('admin_menu');
        $order = $this->getSavedOrder();

        if (empty($order)) {
            return $menu;
        }

        // Sort top-level items
        $menu = $this->sortItems($menu, $order['top'] ?? []);

        // Sort sub-menus
        foreach ($menu as &$item) {
            if (!empty($item['sub_menu']) && !empty($order['sub'][$item['key']])) {
                $item['sub_menu'] = $this->sortItems($item['sub_menu'], $order['sub'][$item['key']]);
            }
        }

        return $menu;
    }

    /**
     * Save the menu order.
     */
    public function saveOrder(array $order): void
    {
        $this->setSetting('admin_menu_order', json_encode($order));
    }

    /**
     * Get saved order from settings.
     */
    public function getSavedOrder(): array
    {
        $order = $this->getSetting('admin_menu_order');

        if (!$order) {
            return [];
        }

        return json_decode($order, true) ?: [];
    }

    /**
     * Sort items array by key order.
     */
    protected function sortItems(array $items, array $keyOrder): array
    {
        if (empty($keyOrder)) {
            return $items;
        }

        $indexed = [];
        foreach ($items as $item) {
            $indexed[$item['key']] = $item;
        }

        $sorted = [];
        foreach ($keyOrder as $key) {
            if (isset($indexed[$key])) {
                $sorted[] = $indexed[$key];
                unset($indexed[$key]);
            }
        }

        // Append any items not in the order (new items)
        foreach ($indexed as $item) {
            $sorted[] = $item;
        }

        return $sorted;
    }

    protected function getSetting(string $key): ?string
    {
        try {
            return \App\Models\Setting::where('group', 'system')->where('key', $key)->value('value');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function setSetting(string $key, string $value): void
    {
        \App\Models\Setting::updateOrCreate(
            ['group' => 'system', 'key' => $key],
            ['value' => $value]
        );
    }
}