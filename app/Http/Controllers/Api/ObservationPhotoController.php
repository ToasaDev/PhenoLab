<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\ObservationPhoto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ObservationPhotoController extends Controller
{
    private function canManagePhoto(ObservationPhoto $photo): bool
    {
        $user = Auth::user();
        $photo->loadMissing('observation.plant:id,owner_id');

        return $user !== null && (
            $user->is_staff
            || $photo->photographer_id === $user->id
            || $photo->observation?->observer_id === $user->id
            || $photo->observation?->plant?->owner_id === $user->id
        );
    }

    private function canViewPhoto(ObservationPhoto $photo): bool
    {
        if ($this->canManagePhoto($photo)) {
            return true;
        }

        $photo->loadMissing('observation.plant.site:id,is_private');

        return $photo->is_public
            && $photo->observation?->is_public
            && ! $photo->observation?->plant?->is_private
            && ! $photo->observation?->plant?->site?->is_private;
    }

    private function visiblePhotosQuery(): Builder
    {
        $query = ObservationPhoto::query();
        $user = Auth::user();

        if ($user?->is_staff) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where(function (Builder $public) {
                $public->where('is_public', true)
                    ->whereHas('observation', function (Builder $observation) {
                        $observation->where('is_public', true)
                            ->whereHas('plant', function (Builder $plant) {
                                $plant->where('is_private', false)
                                    ->whereHas('site', fn (Builder $site) => $site->where('is_private', false));
                            });
                    });
            });

            if ($user !== null) {
                $visible->orWhere('photographer_id', $user->id)
                    ->orWhereHas('observation', fn (Builder $observation) => $observation->where('observer_id', $user->id))
                    ->orWhereHas('observation.plant', fn (Builder $plant) => $plant->where('owner_id', $user->id));
            }
        });
    }

    private function resolveImagePath(string $relativePath): ?string
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($relativePath)) {
                return Storage::disk($disk)->path($relativePath);
            }
        }

        return null;
    }

    /**
     * List observation photos with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->visiblePhotosQuery()->with('observation:id,observation_date,plant_id', 'photographer:id,name');

        if ($v = $request->query('observation')) {
            $query->where('observation_id', $v);
        }

        if ($v = $request->query('photo_type')) {
            $query->where('photo_type', $v);
        }

        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        if ($search = $request->query('search')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('display_order')->orderByDesc('created_at');

        $perPage = min((int) $request->query('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Show a single observation photo.
     */
    public function show(int $id): JsonResponse
    {
        $photo = $this->visiblePhotosQuery()->with(
            'observation:id,observation_date,plant_id',
            'photographer:id,name'
        )->findOrFail($id);

        return response()->json($photo);
    }

    public function image(int $id): BinaryFileResponse
    {
        $photo = ObservationPhoto::findOrFail($id);

        abort_unless($this->canViewPhoto($photo), 404);

        $path = $photo->image ? $this->resolveImagePath($photo->image) : null;
        abort_unless($path !== null, 404);

        return response()->file($path);
    }

    /**
     * Upload a new observation photo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'observation_id' => ['required', 'exists:observations,id'],
            'image'          => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'title'          => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'photo_type'     => ['nullable', 'string', 'in:phenological_state,detail,comparison,context,measurement'],
            'display_order'  => ['nullable', 'integer', 'min:0'],
            'is_public'      => ['nullable', 'boolean'],
        ]);

        $observation = Observation::with('plant:id,owner_id')->findOrFail($data['observation_id']);

        if (
            ! Auth::user()?->is_staff
            && (int) $observation->observer_id !== (int) Auth::id()
            && (int) $observation->plant?->owner_id !== (int) Auth::id()
        ) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $file = $request->file('image');
        $path = $file->store("photos/observations/{$data['observation_id']}", 'local');

        // Extract image dimensions
        $imageInfo = getimagesize($file->getRealPath());
        $width = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;

        $photo = ObservationPhoto::create([
            'observation_id'  => $data['observation_id'],
            'image'           => $path,
            'title'           => $data['title'] ?? '',
            'description'     => $data['description'] ?? '',
            'photo_type'      => $data['photo_type'] ?? 'phenological_state',
            'photographer_id' => Auth::id(),
            'width'           => $width,
            'height'          => $height,
            'file_size'       => $file->getSize(),
            'display_order'   => $data['display_order'] ?? 0,
            'is_public'       => $data['is_public'] ?? true,
        ]);

        return response()->json($photo->load('observation:id,observation_date,plant_id', 'photographer:id,name'), 201);
    }

    /**
     * Update observation photo metadata.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $photo = ObservationPhoto::findOrFail($id);

        if (Auth::id() !== $photo->photographer_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $data = $request->validate([
            'title'         => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'photo_type'    => ['nullable', 'string', 'in:phenological_state,detail,comparison,context,measurement'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_public'     => ['nullable', 'boolean'],
        ]);

        $photo->update($data);

        return response()->json($photo);
    }

    /**
     * Delete an observation photo (file + record).
     */
    public function destroy(int $id): JsonResponse
    {
        $photo = ObservationPhoto::findOrFail($id);

        if (Auth::id() !== $photo->photographer_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        if ($photo->image) {
            Storage::disk('local')->delete($photo->image);
            Storage::disk('public')->delete($photo->image);
        }

        $photo->delete();

        return response()->json(null, 204);
    }

    /**
     * Return the current user's observation photos.
     */
    public function myPhotos(Request $request): JsonResponse
    {
        $photos = ObservationPhoto::where('photographer_id', Auth::id())
            ->with('observation:id,observation_date,plant_id')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json($photos);
    }

    /**
     * Photos for a specific observation.
     */
    public function byObservation(Request $request): JsonResponse
    {
        $request->validate([
            'observation_id' => ['required', 'exists:observations,id'],
        ]);

        $photos = $this->visiblePhotosQuery()
            ->where('observation_id', $request->query('observation_id'))
            ->with('photographer:id,name')
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($photos);
    }
}
