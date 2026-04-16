<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantActionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
     * List ALL action types (including inactive) for admin.
     */
    public function adminIndex(): JsonResponse
    {
        $types = PlantActionType::orderBy('sort_order')
            ->orderBy('name')
            ->withCount('actions')
            ->get();

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

    /**
     * Create a new action type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['nullable', 'string', 'max:120', 'unique:plant_action_types,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'category'    => ['required', 'string', Rule::in(array_keys(PlantActionType::CATEGORIES))],
            'icon'        => ['nullable', 'string', 'max:50'],
            'color'       => ['nullable', 'string', 'max:30'],
            'applies_to'  => ['required', 'string', Rule::in(array_keys(PlantActionType::APPLIES_TO))],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $type = PlantActionType::create($validated);

        return response()->json($type, 201);
    }

    /**
     * Update an action type.
     */
    public function update(Request $request, PlantActionType $plantActionType): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['nullable', 'string', 'max:120', Rule::unique('plant_action_types', 'slug')->ignore($plantActionType->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'category'    => ['required', 'string', Rule::in(array_keys(PlantActionType::CATEGORIES))],
            'icon'        => ['nullable', 'string', 'max:50'],
            'color'       => ['nullable', 'string', 'max:30'],
            'applies_to'  => ['required', 'string', Rule::in(array_keys(PlantActionType::APPLIES_TO))],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $plantActionType->update($validated);

        return response()->json($plantActionType->fresh());
    }

    /**
     * Delete an action type (only if no actions use it).
     */
    public function destroy(PlantActionType $plantActionType): JsonResponse
    {
        if ($plantActionType->actions()->exists()) {
            return response()->json([
                'message' => "Impossible de supprimer : {$plantActionType->actions()->count()} action(s) utilisent ce type.",
            ], 422);
        }

        $plantActionType->delete();

        return response()->json(null, 204);
    }
}
