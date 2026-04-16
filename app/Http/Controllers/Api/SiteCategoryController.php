<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiteCategoryController extends Controller
{
    use Concerns\SanitizesOrdering;

    private function ensureStaff(): ?JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->is_staff) {
            return response()->json(['detail' => 'Reserve aux administrateurs.'], 403);
        }

        return null;
    }

    /**
     * Flat list with sites_count and breadcrumb.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SiteCategory::query()
            ->withCount('sites')
            ->with('parent:id,name');

        if ($request->boolean('active_only')) {
            $query->active();
        }

        if ($request->boolean('roots_only')) {
            $query->roots();
        }

        if ($search = $request->query('search')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        [$column, $direction] = $this->parseOrdering(
            $request->query('ordering', 'sort_order'),
            ['name', 'slug', 'sort_order', 'created_at', 'id'],
            'sort_order'
        );
        $query->orderBy($column, $direction)->orderBy('name');

        $categories = $query->get()->map(function (SiteCategory $c) {
            return array_merge($c->toArray(), [
                'breadcrumb' => $c->breadcrumb(),
                'depth'      => $c->depth(),
            ]);
        });

        return response()->json($categories);
    }

    /**
     * Hierarchical tree (roots with nested children, deep).
     */
    public function tree(): JsonResponse
    {
        $roots = SiteCategory::with(['children.children.children.children'])
            ->withCount('sites')
            ->roots()
            ->ordered()
            ->get();

        return response()->json($roots);
    }

    public function show(int $id): JsonResponse
    {
        $category = SiteCategory::with('parent:id,name', 'children')
            ->withCount('sites')
            ->findOrFail($id);

        return response()->json(array_merge($category->toArray(), [
            'breadcrumb' => $category->breadcrumb(),
            'depth'      => $category->depth(),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        if ($r = $this->ensureStaff()) {
            return $r;
        }

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'slug'        => ['nullable', 'string', 'max:180', 'unique:site_categories,slug'],
            'description' => ['nullable', 'string'],
            'icon'        => ['nullable', 'string', 'max:50'],
            'color'       => ['nullable', 'string', 'max:30'],
            'parent_id'   => ['nullable', 'integer', 'exists:site_categories,id'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $category = SiteCategory::create($data);

        return response()->json($category->load('parent:id,name'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($r = $this->ensureStaff()) {
            return $r;
        }

        $category = SiteCategory::findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:150'],
            'slug'        => ['sometimes', 'nullable', 'string', 'max:180', "unique:site_categories,slug,{$id}"],
            'description' => ['nullable', 'string'],
            'icon'        => ['nullable', 'string', 'max:50'],
            'color'       => ['nullable', 'string', 'max:30'],
            'parent_id'   => ['nullable', 'integer', 'exists:site_categories,id', "different:id"],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        // Prevent assigning self or a descendant as parent (would create a cycle).
        if (isset($data['parent_id']) && $data['parent_id'] !== null) {
            $forbidden = $category->descendantIds();
            if (in_array((int) $data['parent_id'], $forbidden, true)) {
                return response()->json([
                    'message' => 'Impossible : le parent choisi est cette categorie ou un de ses descendants.',
                ], 422);
            }
        }

        $category->update($data);

        return response()->json($category->load('parent:id,name'));
    }

    public function destroy(int $id): JsonResponse
    {
        if ($r = $this->ensureStaff()) {
            return $r;
        }

        $category = SiteCategory::withCount('sites', 'children')->findOrFail($id);

        if ($category->sites_count > 0 || $category->children_count > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer : cette categorie contient encore des sites ou des sous-categories.',
            ], 422);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
