<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use Concerns\SanitizesOrdering;
    /**
     * List categories with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::withCount('plants');

        if ($type = $request->query('category_type')) {
            $query->where('category_type', $type);
        }

        if ($search = $request->query('search')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        [$column, $direction] = $this->parseOrdering(
            $request->query('ordering', 'name'),
            ['name', 'category_type', 'created_at', 'id'],
            'name'
        );
        $query->orderBy($column, $direction);

        return response()->json($query->get());
    }

    /**
     * Show a single category with plants count.
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::withCount('plants')->findOrFail($id);

        return response()->json($category);
    }

    /**
     * Create a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description'   => ['nullable', 'string'],
            'icon'          => ['nullable', 'string', 'max:50'],
            'category_type' => ['required', 'string', 'in:trees,shrubs,plants,animals,insects'],
        ]);

        $category = Category::create($data);

        return response()->json($category, 201);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name'          => ['sometimes', 'required', 'string', 'max:100', "unique:categories,name,{$id}"],
            'description'   => ['nullable', 'string'],
            'icon'          => ['nullable', 'string', 'max:50'],
            'category_type' => ['sometimes', 'required', 'string', 'in:trees,shrubs,plants,animals,insects'],
        ]);

        $category->update($data);

        return response()->json($category);
    }

    /**
     * Delete a category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }

    /**
     * Group categories by type.
     */
    public function byType(): JsonResponse
    {
        $categories = Category::withCount('plants')
            ->orderBy('name')
            ->get()
            ->groupBy('category_type');

        return response()->json($categories);
    }
}
