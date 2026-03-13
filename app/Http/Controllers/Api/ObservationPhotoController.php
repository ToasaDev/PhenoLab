<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ObservationPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ObservationPhotoController extends Controller
{
    /**
     * List observation photos with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ObservationPhoto::with('observation:id,observation_date,plant_id', 'photographer:id,name');

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
        $photo = ObservationPhoto::with(
            'observation:id,observation_date,plant_id',
            'photographer:id,name'
        )->findOrFail($id);

        return response()->json($photo);
    }

    /**
     * Upload a new observation photo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'observation_id' => ['required', 'exists:observations,id'],
            'image'          => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'title'          => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'photo_type'     => ['nullable', 'string', 'in:phenological_state,detail,comparison,context,measurement'],
            'display_order'  => ['nullable', 'integer', 'min:0'],
            'is_public'      => ['nullable', 'boolean'],
        ]);

        $file = $request->file('image');
        $path = $file->store("photos/observations/{$data['observation_id']}", 'public');

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

        $photos = ObservationPhoto::where('observation_id', $request->query('observation_id'))
            ->with('photographer:id,name')
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($photos);
    }
}
