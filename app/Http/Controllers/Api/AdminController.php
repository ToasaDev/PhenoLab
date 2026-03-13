<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Import ODS CSV file.
     */
    public function importOdsCsv(Request $request): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:102400'],
            'clear'    => ['nullable', 'boolean'],
        ]);

        $file = $request->file('csv_file');
        $path = $file->storeAs('imports', 'ods_import_' . now()->format('Ymd_His') . '.csv');
        $fullPath = storage_path('app/' . $path);

        try {
            if ($request->boolean('clear')) {
                DB::table('ods_observations')->truncate();
            }

            $exitCode = Artisan::call('ods:import', [
                'csv_file'      => $fullPath,
                '--no-progress' => true,
            ]);

            $output = Artisan::output();

            // Count imported rows
            $count = DB::table('ods_observations')->count();

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0
                    ? "Import ODS terminé. {$count} observations en base."
                    : 'Erreur lors de l\'import.',
                'output'  => $output,
                'count'   => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import Tela Botanica CSV file.
     */
    public function importTelaCsv(Request $request): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:102400'],
        ]);

        $file = $request->file('csv_file');
        $path = $file->storeAs('imports', 'tela_import_' . now()->format('Ymd_His') . '.csv');
        $fullPath = storage_path('app/' . $path);

        try {
            $exitCode = Artisan::call('tela:import', [
                'csv_file' => $fullPath,
            ]);

            $output = Artisan::output();
            $count = DB::table('tela_observations')->count();

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0
                    ? "Import Tela terminé. {$count} observations en base."
                    : 'Erreur lors de l\'import.',
                'output'  => $output,
                'count'   => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed phenological stages from the seeder.
     */
    public function seedPhenologicalStages(): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        try {
            Artisan::call('db:seed', [
                '--class' => 'PhenologicalStageSeeder',
                '--force' => true,
            ]);

            $count = DB::table('phenological_stages')->count();

            return response()->json([
                'success' => true,
                'message' => "Stades phénologiques synchronisés. {$count} stades en base.",
                'count'   => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed categories from the seeder.
     */
    public function seedCategories(): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        try {
            Artisan::call('db:seed', [
                '--class' => 'CategorySeeder',
                '--force' => true,
            ]);

            $count = DB::table('categories')->count();

            return response()->json([
                'success' => true,
                'message' => "Catégories synchronisées. {$count} catégories en base.",
                'count'   => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dashboard stats for admin page.
     */
    public function dashboard(): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        return response()->json([
            'taxons_count'             => DB::table('taxons')->count(),
            'taxons_with_gbif'         => DB::table('taxons')->whereNotNull('gbif_id')->count(),
            'categories_count'         => DB::table('categories')->count(),
            'phenological_stages_count'=> DB::table('phenological_stages')->count(),
            'ods_observations_count'   => DB::table('ods_observations')->count(),
            'tela_observations_count'  => DB::table('tela_observations')->count(),
            'users_count'              => DB::table('users')->count(),
            'plants_count'             => DB::table('plants')->count(),
            'observations_count'       => DB::table('observations')->count(),
        ]);
    }
}
