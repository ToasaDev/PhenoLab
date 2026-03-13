<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlantPositionController extends Controller
{
    /**
     * List plant positions with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PlantPosition::with('site:id,name', 'owner:id,name');

        if ($site = $request->query('site')) {
            $query->where('site_id', $site);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($owner = $request->query('owner')) {
            $query->where('owner_id', $owner);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('site_id')->orderBy('label');

        $perPage = min((int) $request->query('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Show a single plant position with succession history.
     */
    public function show(int $id): JsonResponse
    {
        $position = PlantPosition::with([
            'site:id,name',
            'owner:id,name',
            'plants' => fn ($q) => $q->orderBy('planting_date')->with('taxon:id,binomial_name,common_name_fr'),
        ])->findOrFail($id);

        $data = $position->toArray();
        $data['succession_history'] = $data['plants'];

        return response()->json($data);
    }

    /**
     * Create a new plant position.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_id'           => ['required', 'exists:sites,id'],
            'label'             => ['required', 'string', 'max:100'],
            'description'       => ['nullable', 'string'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy'      => ['nullable', 'numeric'],
            'site_position_x'   => ['nullable', 'numeric'],
            'site_position_y'   => ['nullable', 'numeric'],
            'soil_notes'        => ['nullable', 'string'],
            'exposure_notes'    => ['nullable', 'string'],
            'microclimate_notes'=> ['nullable', 'string'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        // Validate unique label per site
        $exists = PlantPosition::where('site_id', $data['site_id'])
            ->where('label', $data['label'])
            ->exists();

        if ($exists) {
            return response()->json([
                'errors' => ['label' => ['Ce label existe deja pour ce site.']],
            ], 422);
        }

        $data['owner_id'] = Auth::id();

        $position = PlantPosition::create($data);

        return response()->json($position->load('site:id,name'), 201);
    }

    /**
     * Update a plant position.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $position = PlantPosition::findOrFail($id);

        $data = $request->validate([
            'label'             => ['sometimes', 'required', 'string', 'max:100'],
            'description'       => ['nullable', 'string'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy'      => ['nullable', 'numeric'],
            'site_position_x'   => ['nullable', 'numeric'],
            'site_position_y'   => ['nullable', 'numeric'],
            'soil_notes'        => ['nullable', 'string'],
            'exposure_notes'    => ['nullable', 'string'],
            'microclimate_notes'=> ['nullable', 'string'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        // Validate unique label per site if label is being changed
        if (isset($data['label']) && $data['label'] !== $position->label) {
            $exists = PlantPosition::where('site_id', $position->site_id)
                ->where('label', $data['label'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'errors' => ['label' => ['Ce label existe deja pour ce site.']],
                ], 422);
            }
        }

        $position->update($data);

        return response()->json($position);
    }

    /**
     * Delete a plant position.
     */
    public function destroy(int $id): JsonResponse
    {
        $position = PlantPosition::findOrFail($id);
        $position->delete();

        return response()->json(null, 204);
    }

    /**
     * Succession history for a specific position.
     */
    public function succession(int $id): JsonResponse
    {
        $position = PlantPosition::findOrFail($id);

        $plants = $position->plants()
            ->with('taxon:id,binomial_name,common_name_fr')
            ->orderBy('planting_date')
            ->get();

        return response()->json([
            'position' => $position,
            'plants'   => $plants,
        ]);
    }
}
