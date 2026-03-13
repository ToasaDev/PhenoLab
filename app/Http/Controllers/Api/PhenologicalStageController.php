<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhenologicalStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhenologicalStageController extends Controller
{
    /**
     * List phenological stages with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PhenologicalStage::withCount('observations');

        if ($code = $request->query('main_event_code')) {
            $query->where('main_event_code', $code);
        }

        if ($scale = $request->query('phenological_scale')) {
            $query->where('phenological_scale', $scale);
        }

        if ($search = $request->query('search')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('stage_code', 'like', "%{$search}%")
                  ->orWhere('stage_description', 'like', "%{$search}%")
                  ->orWhere('main_event_description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('stage_code');

        return response()->json($query->get());
    }

    /**
     * Show a single phenological stage.
     */
    public function show(int $id): JsonResponse
    {
        $stage = PhenologicalStage::withCount('observations')->findOrFail($id);

        return response()->json($stage);
    }

    /**
     * Create a new phenological stage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'stage_code'             => ['required', 'string', 'max:10', 'unique:phenological_stages,stage_code'],
            'stage_description'      => ['required', 'string', 'max:255'],
            'main_event_code'        => ['required', 'integer'],
            'main_event_description' => ['required', 'string', 'max:255'],
            'phenological_scale'     => ['nullable', 'string', 'max:100'],
        ]);

        $data['phenological_scale'] = $data['phenological_scale'] ?? 'BBCH Tela Botanica';

        $stage = PhenologicalStage::create($data);

        return response()->json($stage, 201);
    }

    /**
     * Update a phenological stage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $stage = PhenologicalStage::findOrFail($id);

        $data = $request->validate([
            'stage_code'             => ['sometimes', 'required', 'string', 'max:10', "unique:phenological_stages,stage_code,{$id}"],
            'stage_description'      => ['sometimes', 'required', 'string', 'max:255'],
            'main_event_code'        => ['sometimes', 'required', 'integer'],
            'main_event_description' => ['sometimes', 'required', 'string', 'max:255'],
            'phenological_scale'     => ['nullable', 'string', 'max:100'],
        ]);

        $stage->update($data);

        return response()->json($stage);
    }

    /**
     * Delete a phenological stage.
     */
    public function destroy(int $id): JsonResponse
    {
        $stage = PhenologicalStage::findOrFail($id);
        $stage->delete();

        return response()->json(null, 204);
    }

    /**
     * Group stages by main event code.
     */
    public function byEvent(): JsonResponse
    {
        $stages = PhenologicalStage::withCount('observations')
            ->orderBy('stage_code')
            ->get()
            ->groupBy('main_event_code');

        return response()->json($stages);
    }
}
