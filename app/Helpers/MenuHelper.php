<?php
namespace App\Helpers;

class MenuHelper
{
    public static function isActive($slug, ?array $routeParams = null)
    {
        $currentRoute = request()->route()->getName();

        if (!str_starts_with($currentRoute, $slug)) {
            return false;
        }

        // If route_params specified, match against current route parameters
        if ($routeParams) {
            $currentParams = array_values(request()->route()->parameters());
            return $currentParams === $routeParams;
        }

        return true;
    }
}
