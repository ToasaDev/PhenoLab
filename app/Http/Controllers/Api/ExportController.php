<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use App\Services\HugoExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * Generate and download a PhenoLab export package.
     *
     * Query parameters:
     *   format      full|csv|json  (default: full)
     *   year        int|"all"      filter by observation year or all years
     *   site_id     int            filter by site
     *   category    int            filter by category
     *   status      string         filter by plant status
     *   taxon       int            filter by taxon
     *   plant_id    int            filter by plant
     *   stage       string         filter by phenological stage code
     *   date_from   Y-m-d          filter observations from date
     *   date_to     Y-m-d          filter observations to date
     */
    public function download(Request $request): BinaryFileResponse
    {
        $request->validate([
            'format'    => ['nullable', 'in:full,csv,json'],
            'year'      => ['nullable'],
            'site_id'   => ['nullable', 'integer', 'exists:sites,id'],
            'category'  => ['nullable', 'integer', 'exists:categories,id'],
            'status'    => ['nullable', 'string', 'in:alive,dead,replaced,removed'],
            'taxon'     => ['nullable', 'integer', 'exists:taxons,id'],
            'plant_id'  => ['nullable', 'integer', 'exists:plants,id'],
            'stage'     => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $filters = array_filter($request->only([
            'year', 'site_id', 'category', 'status', 'taxon',
            'plant_id', 'stage', 'date_from', 'date_to',
        ]), fn ($v) => $v !== null && $v !== '');

        // Normalize year: keep "all" as string, cast numeric values
        if (isset($filters['year']) && $filters['year'] !== 'all') {
            $filters['year'] = (int) $filters['year'];
        }

        $format = $request->query('format', 'full');

        $service = new ExportService($filters);
        $zipPath = $service->export($format);

        $filename = 'phenolab_export_' . now()->format('Y-m-d_H-i') . '.zip';

        return response()
            ->download($zipPath, $filename, [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Generate and download a Hugo static site archive.
     * Restricted to superusers only.
     */
    public function hugo(Request $request): BinaryFileResponse
    {
        if (! $request->user()?->is_superuser) {
            abort(403, 'Acces reserve aux super-administrateurs.');
        }

        $service = new HugoExportService();
        $zipPath = $service->export();

        $filename = 'phenolab_hugo_' . now()->format('Y-m-d_H-i') . '.zip';

        return response()
            ->download($zipPath, $filename, [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);
    }
}
