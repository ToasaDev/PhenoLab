<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantTagAssignment;
use App\Models\UserPlantTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserPlantTagController extends Controller
{
    /**
     * List tags visible to the current user (own + group).
     */
    public function index(): JsonResponse
    {
        $tags = UserPlantTag::active()
            ->visibleTo(Auth::user())
            ->with('group:id,name')
            ->withCount('plants')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id'           => $t->id,
                'name'         => $t->name,
                'slug'         => $t->slug,
                'color'        => $t->color,
                'creator_id'   => $t->creator_id,
                'group_id'     => $t->group_id,
                'group_name'   => $t->group?->name,
                'is_mine'      => $t->creator_id === Auth::id(),
                'plants_count' => $t->plants_count,
            ]);

        return response()->json($tags);
    }

    /**
     * Create a new tag.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'color'    => ['nullable', 'string', Rule::in(array_keys(UserPlantTag::COLORS))],
            'group_id' => ['nullable', 'exists:user_groups,id'],
        ]);

        // Verify user belongs to the group if specified
        if (!empty($data['group_id'])) {
            $groupIds = Auth::user()->groupIds();
            if (!in_array((int) $data['group_id'], $groupIds) && !Auth::user()->is_staff) {
                return response()->json(['message' => 'Vous ne faites pas partie de ce groupe.'], 403);
            }
        }

        $data['creator_id'] = Auth::id();

        $tag = UserPlantTag::create($data);

        $tag->load('group:id,name');

        return response()->json([
            'id'           => $tag->id,
            'name'         => $tag->name,
            'slug'         => $tag->slug,
            'color'        => $tag->color,
            'creator_id'   => $tag->creator_id,
            'group_id'     => $tag->group_id,
            'group_name'   => $tag->group?->name,
            'is_mine'      => true,
            'plants_count' => 0,
        ], 201);
    }

    /**
     * Update a tag (creator only).
     */
    public function update(Request $request, UserPlantTag $userPlantTag): JsonResponse
    {
        if ($userPlantTag->creator_id !== Auth::id() && !Auth::user()->is_staff) {
            return response()->json(['message' => 'Seul le créateur peut modifier ce tag.'], 403);
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'color'    => ['nullable', 'string', Rule::in(array_keys(UserPlantTag::COLORS))],
            'group_id' => ['nullable', 'exists:user_groups,id'],
        ]);

        $userPlantTag->update($data);

        $userPlantTag->load('group:id,name');
        $userPlantTag->loadCount('plants');

        return response()->json([
            'id'           => $userPlantTag->id,
            'name'         => $userPlantTag->name,
            'slug'         => $userPlantTag->slug,
            'color'        => $userPlantTag->color,
            'creator_id'   => $userPlantTag->creator_id,
            'group_id'     => $userPlantTag->group_id,
            'group_name'   => $userPlantTag->group?->name,
            'is_mine'      => $userPlantTag->creator_id === Auth::id(),
            'plants_count' => $userPlantTag->plants_count,
        ]);
    }

    /**
     * Delete a tag (creator only, removes all assignments).
     */
    public function destroy(UserPlantTag $userPlantTag): JsonResponse
    {
        if ($userPlantTag->creator_id !== Auth::id() && !Auth::user()->is_staff) {
            return response()->json(['message' => 'Seul le créateur peut supprimer ce tag.'], 403);
        }

        $userPlantTag->delete();

        return response()->json(null, 204);
    }

    /**
     * Assign a tag to a plant.
     */
    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plant_id' => ['required', 'exists:plants,id'],
            'tag_id'   => ['required', 'exists:user_plant_tags,id'],
        ]);

        // Verify the tag is visible to the user
        $tag = UserPlantTag::visibleTo(Auth::user())->find($data['tag_id']);
        if (!$tag) {
            return response()->json(['message' => 'Tag non trouvé.'], 404);
        }

        $assignment = PlantTagAssignment::firstOrCreate(
            ['plant_id' => $data['plant_id'], 'tag_id' => $data['tag_id']],
            ['assigned_by_id' => Auth::id()]
        );

        return response()->json($assignment, 201);
    }

    /**
     * Remove a tag from a plant.
     */
    public function unassign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plant_id' => ['required', 'exists:plants,id'],
            'tag_id'   => ['required', 'exists:user_plant_tags,id'],
        ]);

        PlantTagAssignment::where('plant_id', $data['plant_id'])
            ->where('tag_id', $data['tag_id'])
            ->delete();

        return response()->json(null, 204);
    }

    /**
     * Get tags for a specific plant (only those visible to current user).
     */
    public function forPlant(int $plantId): JsonResponse
    {
        $user = Auth::user();
        $groupIds = $user->groupIds();

        $tags = UserPlantTag::active()
            ->whereHas('plants', fn ($q) => $q->where('plants.id', $plantId))
            ->where(function ($q) use ($user, $groupIds) {
                $q->where('creator_id', $user->id);
                if (!empty($groupIds)) {
                    $q->orWhereIn('group_id', $groupIds);
                }
            })
            ->get()
            ->map(fn ($t) => [
                'id'         => $t->id,
                'name'       => $t->name,
                'slug'       => $t->slug,
                'color'      => $t->color,
                'creator_id' => $t->creator_id,
                'is_mine'    => $t->creator_id === $user->id,
            ]);

        return response()->json($tags);
    }
}
