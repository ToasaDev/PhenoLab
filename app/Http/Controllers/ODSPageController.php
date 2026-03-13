<?php

namespace App\Http\Controllers;

use App\Models\ODSObservation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ODSPageController extends Controller
{
    /**
     * Display the standalone ODS observations browse page.
     * Equivalent to Django's ODSObservationsView (TemplateView).
     */
    public function index(Request $request): View
    {
        $query = ODSObservation::query()->orderByDesc('date');

        $searchQuery = $request->query('q', '');
        $scientificName = $request->query('scientific_name', '');
        $department = $request->query('department', '');
        $year = $request->query('year', '');
        $phenologicalStage = $request->query('phenological_stage', '');

        // Free-text search
        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('scientific_name', 'like', "%{$searchQuery}%")
                  ->orWhere('vernacular_name', 'like', "%{$searchQuery}%")
                  ->orWhere('station_name', 'like', "%{$searchQuery}%");
            });
        }

        // Direct filters
        if ($scientificName) {
            $query->where('scientific_name', $scientificName);
        }

        if ($department) {
            $query->where('department', $department);
        }

        if ($year) {
            $query->whereYear('date', $year);
        }

        if ($phenologicalStage) {
            $query->where('phenological_stage', $phenologicalStage);
        }

        $totalObservations = $query->count();
        $observations = $query->paginate(50);

        // Build filter dropdowns
        $uniqueSpecies = ODSObservation::distinct()
            ->whereNotNull('scientific_name')
            ->where('scientific_name', '!=', '')
            ->orderBy('scientific_name')
            ->limit(100)
            ->pluck('scientific_name');

        $uniqueDepartments = ODSObservation::distinct()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->orderBy('department')
            ->pluck('department');

        $uniqueStages = ODSObservation::distinct()
            ->whereNotNull('phenological_stage')
            ->where('phenological_stage', '!=', '')
            ->orderBy('phenological_stage')
            ->limit(50)
            ->pluck('phenological_stage');

        $years = range(date('Y'), 2006, -1);

        $hasFilters = $searchQuery || $scientificName || $department || $year || $phenologicalStage;

        return view('ods-observations', compact(
            'observations',
            'totalObservations',
            'searchQuery',
            'scientificName',
            'department',
            'year',
            'phenologicalStage',
            'uniqueSpecies',
            'uniqueDepartments',
            'uniqueStages',
            'years',
            'hasFilters',
        ));
    }
}
