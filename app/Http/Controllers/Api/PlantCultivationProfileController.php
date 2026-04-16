<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Models\PlantCultivationProfile;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlantCultivationProfileController extends Controller
{
    private function findPlantOrFail(int $plantId): Plant
    {
        return Plant::findOrFail($plantId);
    }

    private function canManagePlant(Plant $plant): bool
    {
        $user = Auth::user();

        return $user !== null && ($user->is_staff || $plant->owner_id === $user->id);
    }

    /**
     * Show the cultivation profile of a plant (visible to anyone who can see the plant).
     */
    public function show(int $plantId): JsonResponse
    {
        $plant = $this->findPlantOrFail($plantId);
        $user = Auth::user();
        $isStaff = $user?->is_staff ?? false;
        $isOwner = $user && $plant->owner_id === $user->id;

        if (! $isStaff && ! $isOwner) {
            // Hide if both plant and site are private
            if ($plant->is_private && $plant->site && $plant->site->is_private) {
                return response()->json(['detail' => 'Introuvable.'], 404);
            }
        }

        $profile = $plant->cultivationProfile;

        return response()->json($profile ?? new \stdClass());
    }

    /**
     * Create or update the cultivation profile (1:1).
     */
    public function upsert(Request $request, int $plantId): JsonResponse
    {
        $plant = $this->findPlantOrFail($plantId);

        if (! $this->canManagePlant($plant)) {
            return response()->json(['detail' => 'Action non autorisee.'], 403);
        }

        $validated = $this->validatePayload($request);

        $profile = PlantCultivationProfile::updateOrCreate(
            ['plant_id' => $plant->id],
            array_merge($validated, ['updated_by_id' => Auth::id()]),
        );

        return response()->json($profile->fresh(), $profile->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Delete the cultivation profile.
     */
    public function destroy(int $plantId): JsonResponse
    {
        $plant = $this->findPlantOrFail($plantId);

        if (! $this->canManagePlant($plant)) {
            return response()->json(['detail' => 'Action non autorisee.'], 403);
        }

        $plant->cultivationProfile?->delete();

        return response()->json(null, 204);
    }

    private function validatePayload(Request $request): array
    {
        $monthArray = ['nullable', 'array'];
        $monthItem  = ['integer', 'between:1,12'];

        return $request->validate([
            // WHEN
            'planting_months'   => $monthArray,
            'planting_months.*' => $monthItem,
            'sowing_months'     => $monthArray,
            'sowing_months.*'   => $monthItem,
            'harvest_months'    => $monthArray,
            'harvest_months.*'  => $monthItem,
            'flowering_months'  => $monthArray,
            'flowering_months.*' => $monthItem,

            // WHERE
            'exposure'              => ['nullable', Rule::in(array_keys(PlantCultivationProfile::EXPOSURES))],
            'hardiness_min'         => 'nullable|string|max:20',
            'usda_zone'             => 'nullable|string|max:20',
            'suitable_environments' => 'nullable|array',
            'suitable_environments.*' => ['string', Rule::in(array_keys(Site::ENVIRONMENT_TYPES))],
            'soil_types'            => 'nullable|array',
            'soil_types.*'          => ['string', Rule::in(array_keys(PlantCultivationProfile::SOIL_TYPES))],
            'soil_ph'               => 'nullable|string|max:30',
            'soil_drainage'         => ['nullable', Rule::in(array_keys(PlantCultivationProfile::SOIL_DRAINAGE))],
            'soil_fertility'        => ['nullable', Rule::in(array_keys(PlantCultivationProfile::SOIL_FERTILITY))],
            'mature_height_min'     => 'nullable|numeric|min:0',
            'mature_height_max'     => 'nullable|numeric|min:0',
            'mature_spread_min'     => 'nullable|numeric|min:0',
            'mature_spread_max'     => 'nullable|numeric|min:0',

            // CARE
            'watering_needs'         => ['nullable', Rule::in(array_keys(PlantCultivationProfile::WATERING_NEEDS))],
            'watering_notes'         => 'nullable|string',
            'fertilizing_frequency'  => 'nullable|string|max:50',
            'fertilizing_notes'      => 'nullable|string',
            'pruning_period'         => 'nullable|string|max:100',
            'pruning_notes'          => 'nullable|string',
            'mulching'               => 'nullable|string|max:50',
            'winter_protection'      => 'nullable|string|max:100',
            'pest_susceptibility'    => 'nullable|string',
            'disease_susceptibility' => 'nullable|string',
            'companion_plants'       => 'nullable|array',
            'companion_plants.*'     => 'string|max:120',
            'avoid_near'             => 'nullable|array',
            'avoid_near.*'           => 'string|max:120',
            'propagation_methods'    => 'nullable|string|max:200',
            'cultivation_difficulty' => ['nullable', Rule::in(array_keys(PlantCultivationProfile::DIFFICULTIES))],
            'usage_types'            => 'nullable|array',
            'usage_types.*'          => ['string', Rule::in(array_keys(PlantCultivationProfile::USAGE_TYPES))],
            'is_edible'              => 'nullable|boolean',
            'is_toxic'               => 'nullable|boolean',

            // META
            'notes'  => 'nullable|string',
            'source' => 'nullable|string|max:255',
            'extra'  => 'nullable|array',
        ]);
    }
}
