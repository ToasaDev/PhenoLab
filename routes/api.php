<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ComparisonController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\ObservationController;
use App\Http\Controllers\Api\ObservationPhotoController;
use App\Http\Controllers\Api\ODSController;
use App\Http\Controllers\Api\PhenologicalStageController;
use App\Http\Controllers\Api\PlantController;
use App\Http\Controllers\Api\PlantPhotoController;
use App\Http\Controllers\Api\PlantPositionController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\TaxonController;
use App\Http\Controllers\Api\TelaObservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {

    // ── Auth ──────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::get('csrf-token', [AuthController::class, 'csrfToken']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('status', [AuthController::class, 'status']);
    });

    // ── Categories ───────────────────────────────────────
    Route::get('categories/by-type', [CategoryController::class, 'byType']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy'])->middleware(['auth:sanctum', 'staff']);

    // ── Phenological Stages ──────────────────────────────
    Route::get('phenological-stages/by-event', [PhenologicalStageController::class, 'byEvent']);
    Route::apiResource('phenological-stages', PhenologicalStageController::class)->only(['index', 'show']);
    Route::apiResource('phenological-stages', PhenologicalStageController::class)->only(['store', 'update', 'destroy'])->middleware(['auth:sanctum', 'staff']);

    // ── Sites ────────────────────────────────────────────
    Route::get('sites/geojson', [SiteController::class, 'geojson']);
    Route::get('sites/nearby', [SiteController::class, 'nearby']);
    Route::get('sites/my-sites', [SiteController::class, 'mySites'])->middleware('auth:sanctum');
    Route::get('sites/{site}/plants', [SiteController::class, 'plants']);
    Route::get('sites/{site}/statistics', [SiteController::class, 'statistics']);
    Route::patch('sites/{site}/drawing-overlay', [SiteController::class, 'updateDrawingOverlay'])->middleware('auth:sanctum');
    Route::get('sites/{site}/layers', [SiteController::class, 'listLayers']);
    Route::post('sites/{site}/layers', [SiteController::class, 'createLayer'])->middleware('auth:sanctum');
    Route::patch('sites/{site}/layers/{layer}', [SiteController::class, 'updateLayer'])->middleware('auth:sanctum');
    Route::delete('sites/{site}/layers/{layer}', [SiteController::class, 'deleteLayer'])->middleware('auth:sanctum');
    Route::apiResource('sites', SiteController::class)->only(['index', 'show']);
    Route::apiResource('sites', SiteController::class)->only(['store', 'update', 'destroy'])->middleware('auth:sanctum');

    // ── Taxons ───────────────────────────────────────────
    Route::post('taxons/sync-gbif', [TaxonController::class, 'syncGbif'])->middleware(['auth:sanctum', 'staff']);
    Route::post('taxons/import-family', [TaxonController::class, 'importFamily'])->middleware(['auth:sanctum', 'staff']);
    Route::post('taxons/bulk-sync-gbif', [TaxonController::class, 'bulkSyncGbif'])->middleware(['auth:sanctum', 'staff']);
    Route::post('taxons/{taxon}/sync-from-gbif', [TaxonController::class, 'syncSingleFromGbif'])->middleware(['auth:sanctum', 'staff']);
    Route::apiResource('taxons', TaxonController::class)->only(['index', 'show']);
    Route::apiResource('taxons', TaxonController::class)->only(['store', 'update', 'destroy'])->middleware(['auth:sanctum', 'staff']);

    // ── Plant Positions ──────────────────────────────────
    Route::get('plant-positions/{position}/succession', [PlantPositionController::class, 'succession']);
    Route::apiResource('plant-positions', PlantPositionController::class)->only(['index', 'show']);
    Route::apiResource('plant-positions', PlantPositionController::class)->only(['store', 'update', 'destroy'])->middleware('auth:sanctum');

    // ── Plants ───────────────────────────────────────────
    Route::get('plants/my-plants', [PlantController::class, 'myPlants'])->middleware('auth:sanctum');
    Route::get('plants/by-category', [PlantController::class, 'byCategory']);
    Route::get('plants/by-site', [PlantController::class, 'bySite']);
    Route::get('plants/site-map', [PlantController::class, 'siteMap']);
    Route::get('plants/nearby', [PlantController::class, 'nearbyPlants']);
    Route::get('plants/export', [PlantController::class, 'exportWithObservations']);
    Route::post('plants/bulk-update-map-positions', [PlantController::class, 'bulkUpdateMapPositions'])->middleware('auth:sanctum');
    Route::get('plants/{plant}/observations', [PlantController::class, 'observations']);
    Route::get('plants/{plant}/photos', [PlantController::class, 'photos']);
    Route::get('plants/{plant}/statistics', [PlantController::class, 'statistics']);
    Route::post('plants/{plant}/update-gps', [PlantController::class, 'updateGpsLocation'])->middleware('auth:sanctum');
    Route::post('plants/{plant}/mark-dead', [PlantController::class, 'markDead'])->middleware('auth:sanctum');
    Route::post('plants/{plant}/replace', [PlantController::class, 'replace'])->middleware('auth:sanctum');
    Route::apiResource('plants', PlantController::class)->only(['index', 'show']);
    Route::apiResource('plants', PlantController::class)->only(['store', 'update', 'destroy'])->middleware('auth:sanctum');

    // ── Observations ─────────────────────────────────────
    Route::get('observations/my-observations', [ObservationController::class, 'myObservations'])->middleware('auth:sanctum');
    Route::get('observations/by-plant', [ObservationController::class, 'byPlant']);
    Route::get('observations/by-stage', [ObservationController::class, 'byStage']);
    Route::get('observations/years-available', [ObservationController::class, 'yearsAvailable']);
    Route::get('observations/monthly-counts', [ObservationController::class, 'monthlyCounts']);
    Route::post('observations/{observation}/validate', [ObservationController::class, 'validateObservation'])->middleware(['auth:sanctum', 'staff']);
    Route::post('observations/bulk-validate', [ObservationController::class, 'bulkValidate'])->middleware(['auth:sanctum', 'staff']);
    Route::post('observations/bulk-visibility', [ObservationController::class, 'bulkVisibility'])->middleware(['auth:sanctum', 'staff']);
    Route::apiResource('observations', ObservationController::class)->only(['index', 'show']);
    Route::apiResource('observations', ObservationController::class)->only(['store', 'update', 'destroy'])->middleware('auth:sanctum');

    // ── Tela Observations (read-only) ────────────────────
    Route::get('tela-observations/by-taxon', [TelaObservationController::class, 'byTaxon']);
    Route::get('tela-observations/statistics', [TelaObservationController::class, 'statistics']);
    Route::apiResource('tela-observations', TelaObservationController::class)->only(['index', 'show']);

    // ── Comparison & Statistics (authenticated users only) ─
    Route::get('comparison', [ComparisonController::class, 'compare'])->middleware('auth:sanctum');
    Route::get('statistics', [StatisticsController::class, 'index'])->middleware('auth:sanctum');

    // ── ODS ──────────────────────────────────────────────
    Route::get('ods-search', [ODSController::class, 'search']);
    Route::get('ods-stats', [ODSController::class, 'stats']);
    Route::get('ods-evolution', [ODSController::class, 'evolution']);

    // ── Global Search ────────────────────────────────────
    Route::get('search', [GlobalSearchController::class, 'search']);

    // ── Plant Photos ─────────────────────────────────────
    Route::get('plant-photos/my-photos', [PlantPhotoController::class, 'myPhotos'])->middleware('auth:sanctum');
    Route::get('plant-photos/by-plant', [PlantPhotoController::class, 'byPlant']);
    Route::get('plant-photos/{photo}/image', [PlantPhotoController::class, 'image']);
    Route::get('plant-photos/main-photos', [PlantPhotoController::class, 'mainPhotos']);
    Route::post('plant-photos/{photo}/set-as-main', [PlantPhotoController::class, 'setAsMain'])->middleware('auth:sanctum');
    Route::apiResource('plant-photos', PlantPhotoController::class)->only(['index', 'show']);
    Route::apiResource('plant-photos', PlantPhotoController::class)->only(['store', 'update', 'destroy'])->middleware('auth:sanctum');

    // ── Observation Photos ───────────────────────────────
    Route::get('observation-photos/my-photos', [ObservationPhotoController::class, 'myPhotos'])->middleware('auth:sanctum');
    Route::get('observation-photos/by-observation', [ObservationPhotoController::class, 'byObservation']);
    Route::get('observation-photos/{photo}/image', [ObservationPhotoController::class, 'image']);
    Route::apiResource('observation-photos', ObservationPhotoController::class)->only(['index', 'show']);
    Route::apiResource('observation-photos', ObservationPhotoController::class)->only(['store', 'update', 'destroy'])->middleware('auth:sanctum');

    // ── Admin (staff only) ────────────────────────────────
    Route::prefix('admin')->middleware(['auth:sanctum', 'staff'])->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::post('import-ods', [AdminController::class, 'importOdsCsv']);
        Route::post('import-tela', [AdminController::class, 'importTelaCsv']);
        Route::post('seed-stages', [AdminController::class, 'seedPhenologicalStages']);
        Route::post('seed-categories', [AdminController::class, 'seedCategories']);
    });

    // ── Activity Log (authenticated users only) ──────────
    Route::get('activity', [ActivityLogController::class, 'index'])->middleware('auth:sanctum');
});
