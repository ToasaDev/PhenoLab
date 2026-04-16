<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantAction;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlantActionController extends Controller
{
    use Concerns\SanitizesOrdering;

    private function visibleActionsQuery(): Builder
    {
        $query = PlantAction::query();
        $user = Auth::user();

        if ($user?->is_staff) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where(function (Builder $public) {
                $public->where('is_private', false)
                    ->whereHas('plant', function (Builder $plant) {
                        $plant->where('is_private', false)
                            ->whereHas('site', fn (Builder $site) => $site->where('is_private', false));
                    });
            });

            if ($user !== null) {
                $visible->orWhere('performed_by', $user->id)
                    ->orWhereHas('plant', fn (Builder $plant) => $plant->where('owner_id', $user->id));
            }
        });
    }

    private function canManageAction(PlantAction $action): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->is_staff) {
            return true;
        }

        return $action->performed_by === $user->id
            || $action->plant?->owner_id === $user->id;
    }

    /**
     * Paginated list of plant actions with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->visibleActionsQuery()->with(
            'plant:id,name,site_id',
            'plant.site:id,name',
            'actionType:id,name,slug,category,icon,color',
            'performer:id,name',
        );

        // ── Filters ──────────────────────────
        if ($request->filled('plant_id')) {
            $query->where('plant_id', $request->integer('plant_id'));
        }
        if ($request->filled('site_id')) {
            $query->whereHas('plant', fn (Builder $p) => $p->where('site_id', $request->integer('site_id')));
        }
        if ($request->filled('action_type_id')) {
            $query->where('action_type_id', $request->integer('action_type_id'));
        }
        if ($request->filled('category')) {
            $query->whereHas('actionType', fn (Builder $t) => $t->where('category', $request->input('category')));
        }
        if ($request->filled('date_from')) {
            $query->where('action_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('action_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('year') && $request->input('year') !== 'all') {
            $query->whereYear('action_date', $request->integer('year'));
        }
        if ($request->filled('performed_by')) {
            $query->where('performed_by', $request->integer('performed_by'));
        }
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('method', 'like', "%{$search}%")
                  ->orWhereHas('actionType', fn (Builder $t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        // ── Ordering ─────────────────────────
        $orderBy = $request->query('order_by', '-action_date');
        [$column, $direction] = $this->parseOrdering($orderBy, [
            'action_date', 'created_at', 'cost',
        ], 'action_date');
        if ($column === 'action_date') {
            $direction = $direction === 'asc' ? 'asc' : 'desc';
        }
        $query->orderBy($column, $direction);

        $perPage = min($request->integer('per_page', 25), 200);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'pagination' => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'total_pages'  => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * Show a single action.
     */
    public function show(int $id): JsonResponse
    {
        $action = $this->visibleActionsQuery()
            ->with('plant:id,name,site_id', 'plant.site:id,name', 'actionType', 'performer:id,name')
            ->findOrFail($id);

        return response()->json($action);
    }

    /**
     * Create a new plant action.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plant_id'           => ['required', 'integer', 'exists:plants,id'],
            'action_type_id'     => ['required', 'integer', 'exists:plant_action_types,id'],
            'action_date'        => ['required', 'date'],
            'title'              => ['nullable', 'string', 'max:255'],
            'notes'              => ['nullable', 'string'],
            'product_name'       => ['nullable', 'string', 'max:255'],
            'quantity'           => ['nullable', 'numeric', 'min:0'],
            'unit'               => ['nullable', 'string', 'max:30'],
            'dosage'             => ['nullable', 'string', 'max:100'],
            'method'             => ['nullable', 'string', 'max:255'],
            'performer_name'     => ['nullable', 'string', 'max:100'],
            'cost'               => ['nullable', 'numeric', 'min:0'],
            'weather_conditions' => ['nullable', 'string', 'max:100'],
            'is_private'         => ['nullable', 'boolean'],
        ]);

        // Verify user can act on this plant
        $plant = Plant::findOrFail($validated['plant_id']);
        $user = Auth::user();
        if (! $user->is_staff && $plant->owner_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé pour cette plante.'], 403);
        }

        $validated['performed_by'] = $user->id;

        $action = PlantAction::create($validated);
        $action->load('actionType:id,name,slug,category,icon,color', 'performer:id,name', 'plant:id,name,site_id', 'plant.site:id,name');

        return response()->json($action, 201);
    }

    /**
     * Update an existing plant action.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $action = PlantAction::findOrFail($id);

        if (! $this->canManageAction($action)) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'action_type_id'     => ['sometimes', 'integer', 'exists:plant_action_types,id'],
            'action_date'        => ['sometimes', 'date'],
            'title'              => ['nullable', 'string', 'max:255'],
            'notes'              => ['nullable', 'string'],
            'product_name'       => ['nullable', 'string', 'max:255'],
            'quantity'           => ['nullable', 'numeric', 'min:0'],
            'unit'               => ['nullable', 'string', 'max:30'],
            'dosage'             => ['nullable', 'string', 'max:100'],
            'method'             => ['nullable', 'string', 'max:255'],
            'performer_name'     => ['nullable', 'string', 'max:100'],
            'cost'               => ['nullable', 'numeric', 'min:0'],
            'weather_conditions' => ['nullable', 'string', 'max:100'],
            'is_private'         => ['nullable', 'boolean'],
        ]);

        $action->update($validated);
        $action->load('actionType:id,name,slug,category,icon,color', 'performer:id,name', 'plant:id,name,site_id', 'plant.site:id,name');

        return response()->json($action);
    }

    /**
     * Delete a plant action.
     */
    public function destroy(int $id): JsonResponse
    {
        $action = PlantAction::findOrFail($id);

        if (! $this->canManageAction($action)) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $action->delete();

        return response()->json(null, 204);
    }

    /**
     * List actions for a specific plant.
     */
    public function forPlant(Request $request, int $plantId): JsonResponse
    {
        $query = $this->visibleActionsQuery()
            ->where('plant_id', $plantId)
            ->with('actionType:id,name,slug,category,icon,color', 'performer:id,name');

        if ($request->filled('action_type_id')) {
            $query->where('action_type_id', $request->integer('action_type_id'));
        }
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $actions = $query->orderByDesc('action_date')->get();

        return response()->json($actions);
    }

    /**
     * Available years for actions.
     */
    public function yearsAvailable(): JsonResponse
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $years = PlantAction::selectRaw("strftime('%Y', action_date) as year")
                ->distinct()->orderByDesc('year')->pluck('year');
        } else {
            $years = PlantAction::selectRaw('YEAR(action_date) as year')
                ->distinct()->orderByDesc('year')->pluck('year');
        }

        return response()->json($years);
    }
}
