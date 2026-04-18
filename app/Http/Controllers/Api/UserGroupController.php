<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserGroup;
use App\Models\UserGroupMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserGroupController extends Controller
{
    /**
     * List groups the current user belongs to.
     */
    public function index(): JsonResponse
    {
        $groups = Auth::user()->groups()
            ->withCount('members', 'plants', 'sites')
            ->orderBy('name')
            ->get()
            ->map(fn ($g) => [
                'id'            => $g->id,
                'name'          => $g->name,
                'slug'          => $g->slug,
                'description'   => $g->description,
                'owner_id'      => $g->owner_id,
                'is_active'     => $g->is_active,
                'role'          => $g->pivot->role,
                'members_count' => $g->members_count,
                'plants_count'  => $g->plants_count,
                'sites_count'   => $g->sites_count,
            ]);

        return response()->json($groups);
    }

    /**
     * Create a new group (current user becomes owner).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'slug'        => ['nullable', 'string', 'max:180', 'unique:user_groups,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['owner_id'] = Auth::id();

        $group = UserGroup::create($data);

        // Auto-add creator as owner member
        UserGroupMembership::create([
            'user_id'  => Auth::id(),
            'group_id' => $group->id,
            'role'     => UserGroupMembership::ROLE_OWNER,
        ]);

        return response()->json($group->loadCount('members', 'plants', 'sites'), 201);
    }

    /**
     * Show a single group with members.
     */
    public function show(UserGroup $userGroup): JsonResponse
    {
        $user = Auth::user();

        if (!$user->is_staff && !$userGroup->hasMember($user->id)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $group = $userGroup->loadCount('members', 'plants', 'sites');
        $members = $userGroup->members()
            ->select('users.id', 'users.name', 'users.email')
            ->get()
            ->map(fn ($m) => [
                'id'    => $m->id,
                'name'  => $m->name,
                'email' => $m->email,
                'role'  => $m->pivot->role,
            ]);

        return response()->json([
            'group'   => $group,
            'members' => $members,
        ]);
    }

    /**
     * Update group info (owner or staff only).
     */
    public function update(Request $request, UserGroup $userGroup): JsonResponse
    {
        $user = Auth::user();
        if (!$user->is_staff && !$userGroup->isOwner($user->id)) {
            return response()->json(['message' => 'Seul le propriétaire du groupe peut le modifier.'], 403);
        }

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'slug'        => ['nullable', 'string', 'max:180', Rule::unique('user_groups', 'slug')->ignore($userGroup->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $userGroup->update($data);

        return response()->json($userGroup->fresh()->loadCount('members', 'plants', 'sites'));
    }

    /**
     * Delete group (owner or staff only, no shared resources).
     */
    public function destroy(UserGroup $userGroup): JsonResponse
    {
        $user = Auth::user();
        if (!$user->is_staff && !$userGroup->isOwner($user->id)) {
            return response()->json(['message' => 'Seul le propriétaire du groupe peut le supprimer.'], 403);
        }

        if ($userGroup->plants()->exists() || $userGroup->sites()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un groupe avec des plantes ou sites partagés. Retirez-les d\'abord.',
            ], 422);
        }

        $userGroup->memberships()->delete();
        $userGroup->delete();

        return response()->json(null, 204);
    }

    /**
     * Add a member to the group (owner or staff only).
     */
    public function addMember(Request $request, UserGroup $userGroup): JsonResponse
    {
        $user = Auth::user();
        if (!$user->is_staff && !$userGroup->isOwner($user->id)) {
            return response()->json(['message' => 'Seul le propriétaire du groupe peut ajouter des membres.'], 403);
        }

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role'    => ['nullable', 'string', Rule::in(['owner', 'member'])],
        ]);

        $membership = UserGroupMembership::updateOrCreate(
            ['user_id' => $data['user_id'], 'group_id' => $userGroup->id],
            ['role' => $data['role'] ?? UserGroupMembership::ROLE_MEMBER]
        );

        return response()->json($membership, 201);
    }

    /**
     * Remove a member from the group (owner or staff only).
     */
    public function removeMember(UserGroup $userGroup, int $userId): JsonResponse
    {
        $user = Auth::user();
        if (!$user->is_staff && !$userGroup->isOwner($user->id)) {
            return response()->json(['message' => 'Seul le propriétaire du groupe peut retirer des membres.'], 403);
        }

        // Cannot remove the owner
        if ($userGroup->owner_id === $userId) {
            return response()->json(['message' => 'Le propriétaire ne peut pas être retiré du groupe.'], 422);
        }

        UserGroupMembership::where('group_id', $userGroup->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(null, 204);
    }
}
