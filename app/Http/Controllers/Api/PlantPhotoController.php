<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PlantPhotoController extends Controller
{
    /**
     * List plant photos with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PlantPhoto::with('plant:id,name', 'photographer:id,name');

        if ($v = $request->query('plant'))     $query->where('plant_id', $v);
        if ($v = $request->query('photo_type')) $query->where('photo_type', $v);

        if ($request->has('is_main_photo')) {
            $query->where('is_main_photo', $request->boolean('is_main_photo'));
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

        $orderBy = $request->query('ordering', 'display_order');
        $direction = str_starts_with($orderBy, '-') ? 'desc' : 'asc';
        $column = ltrim($orderBy, '-');
        $query->orderBy($column, $direction);

        $perPage = min((int) $request->query('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Show a single plant photo.
     */
    public function show(int $id): JsonResponse
    {
        $photo = PlantPhoto::with('plant:id,name', 'photographer:id,name')->findOrFail($id);

        return response()->json($photo);
    }

    /**
     * Upload a new plant photo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plant_id'      => ['required', 'exists:plants,id'],
            'image'         => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'title'         => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'photo_type'    => ['nullable', 'string', 'in:general,leaves,flowers,fruits,bark,habitat,detail'],
            'taken_date'    => ['nullable', 'date'],
            'camera_model'  => ['nullable', 'string', 'max:100'],
            'focal_length'  => ['nullable', 'string', 'max:20'],
            'aperture'      => ['nullable', 'string', 'max:20'],
            'iso'           => ['nullable', 'string', 'max:20'],
            'is_main_photo' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_public'     => ['nullable', 'boolean'],
        ]);

        $file = $request->file('image');
        $path = $file->store("photos/plants/{$data['plant_id']}", 'public');

        // Extract image dimensions
        $imageInfo = getimagesize($file->getRealPath());
        $width = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;

        $photo = PlantPhoto::create([
            'plant_id'        => $data['plant_id'],
            'image'           => $path,
            'title'           => $data['title'] ?? '',
            'description'     => $data['description'] ?? '',
            'photo_type'      => $data['photo_type'] ?? 'general',
            'photographer_id' => Auth::id(),
            'taken_date'      => $data['taken_date'] ?? null,
            'camera_model'    => $data['camera_model'] ?? '',
            'focal_length'    => $data['focal_length'] ?? '',
            'aperture'        => $data['aperture'] ?? '',
            'iso'             => $data['iso'] ?? '',
            'width'           => $width,
            'height'          => $height,
            'file_size'       => $file->getSize(),
            'is_main_photo'   => $data['is_main_photo'] ?? false,
            'display_order'   => $data['display_order'] ?? 0,
            'is_public'       => $data['is_public'] ?? true,
        ]);

        // If set as main, unset others
        if ($photo->is_main_photo) {
            PlantPhoto::where('plant_id', $photo->plant_id)
                ->where('id', '!=', $photo->id)
                ->where('is_main_photo', true)
                ->update(['is_main_photo' => false]);
        }

        return response()->json($photo->load('plant:id,name', 'photographer:id,name'), 201);
    }

    /**
     * Update photo metadata.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $photo = PlantPhoto::findOrFail($id);

        if (Auth::id() !== $photo->photographer_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $data = $request->validate([
            'title'         => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'photo_type'    => ['nullable', 'string', 'in:general,leaves,flowers,fruits,bark,habitat,detail'],
            'taken_date'    => ['nullable', 'date'],
            'camera_model'  => ['nullable', 'string', 'max:100'],
            'focal_length'  => ['nullable', 'string', 'max:20'],
            'aperture'      => ['nullable', 'string', 'max:20'],
            'iso'           => ['nullable', 'string', 'max:20'],
            'is_main_photo' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_public'     => ['nullable', 'boolean'],
        ]);

        $photo->update($data);

        // Handle main photo logic
        if (! empty($data['is_main_photo']) && $data['is_main_photo']) {
            PlantPhoto::where('plant_id', $photo->plant_id)
                ->where('id', '!=', $photo->id)
                ->where('is_main_photo', true)
                ->update(['is_main_photo' => false]);
        }

        return response()->json($photo);
    }

    /**
     * Delete a photo (file + record).
     */
    public function destroy(int $id): JsonResponse
    {
        $photo = PlantPhoto::findOrFail($id);

        if (Auth::id() !== $photo->photographer_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $wasMain = $photo->is_main_photo;
        $plantId = $photo->plant_id;

        // Delete file from storage
        if ($photo->image) {
            Storage::disk('public')->delete($photo->image);
        }

        $photo->delete();

        // Promote next photo as main if this was the main photo
        if ($wasMain) {
            $next = PlantPhoto::where('plant_id', $plantId)
                ->orderBy('display_order')
                ->orderBy('created_at')
                ->first();

            if ($next) {
                $next->update(['is_main_photo' => true]);
            }
        }

        return response()->json(null, 204);
    }

    /**
     * Return the current user's plant photos.
     */
    public function myPhotos(Request $request): JsonResponse
    {
        $photos = PlantPhoto::where('photographer_id', Auth::id())
            ->with('plant:id,name')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json($photos);
    }

    /**
     * Photos for a specific plant.
     */
    public function byPlant(Request $request): JsonResponse
    {
        $request->validate([
            'plant_id' => ['required', 'exists:plants,id'],
        ]);

        $photos = PlantPhoto::where('plant_id', $request->query('plant_id'))
            ->with('photographer:id,name')
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($photos);
    }

    /**
     * Return all main photos.
     */
    public function mainPhotos(): JsonResponse
    {
        $photos = PlantPhoto::where('is_main_photo', true)
            ->with('plant:id,name', 'photographer:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($photos);
    }

    /**
     * Set a photo as the main photo for its plant (unset others).
     */
    public function setAsMain(Request $request, int $id): JsonResponse
    {
        $photo = PlantPhoto::findOrFail($id);

        // Unset all main photos for this plant
        PlantPhoto::where('plant_id', $photo->plant_id)
            ->where('is_main_photo', true)
            ->update(['is_main_photo' => false]);

        // Set this one as main
        $photo->update(['is_main_photo' => true]);

        return response()->json($photo);
    }
}
