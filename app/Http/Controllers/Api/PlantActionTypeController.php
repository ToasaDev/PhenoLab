<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantActionType;
use Illuminate\Http\JsonResponse;

class PlantActionTypeController extends Controller
{
    /**
     * List all active action types.
     */
    public function index(): JsonResponse
    {
        $types = PlantActionType::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'category', 'icon', 'color', 'applies_to']);

        return response()->json($types);
    }

    /**
     * Group action types by category.
     */
    public function byCategory(): JsonResponse
    {
        $types = PlantActionType::active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'category', 'icon', 'color', 'applies_to']);

        $grouped = $types->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category,
                'label' => PlantActionType::CATEGORIES[$category] ?? $category,
                'types' => $items->values(),
            ];
        })->values();

        return response()->json($grouped);
    }
}
