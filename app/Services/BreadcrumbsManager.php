<?php

namespace App\Services;

use Closure;

class BreadcrumbsManager
{
    protected static array $breadcrumbs = [];

    /**
     * Define a breadcrumb for a route.
     * Accepts an array or a closure returning an array.
     */
    public static function for(string $routeName, array|Closure $crumbs)
    {
        self::$breadcrumbs[$routeName] = $crumbs;
    }

    /**
     * Get breadcrumbs for the current route.
     * If a closure is stored, call it with optional params.
     */
    public static function get(string $routeName, array $params = []): array
    {
        $crumbs = self::$breadcrumbs[$routeName] ?? [];

        if ($crumbs instanceof Closure) {
            return $crumbs(...array_values($params));
        }

        return $crumbs;
    }
}
