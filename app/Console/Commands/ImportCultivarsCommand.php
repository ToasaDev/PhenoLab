<?php

namespace App\Console\Commands;

use App\Models\Cultivar;
use App\Models\Taxon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportCultivarsCommand extends Command
{
    protected $signature = 'cultivars:import
        {file : Path to the EUPVP Excel or CSV file}
        {--subtypes=FRU : Comma-separated register subtypes to import (FRU,AGR,VEG or ALL)}
        {--status=Registered : Comma-separated statuses to import (Registered,Surrendered or ALL)}
        {--clear : Clear existing cultivars before import}
        {--dry-run : Show what would be imported without actually importing}';

    protected $description = 'Import cultivars from EUPVP Official List Excel/CSV file';

    /**
     * Mapping UPOV species codes → binomial names for taxon resolution.
     */
    private const UPOV_TO_BINOMIAL = [
        'MALUS_DOM'     => 'Malus domestica',
        'MALUS'         => 'Malus domestica',
        'PYRUS_COM'     => 'Pyrus communis',
        'PYRUS'         => 'Pyrus communis',
        'PRUNU_ARM'     => 'Prunus armeniaca',
        'PRUNU_PER'     => 'Prunus persica',
        'PRUNU_PER_NUC' => 'Prunus persica',
        'PRUNU_AVI'     => 'Prunus avium',
        'PRUNU_CSS'     => 'Prunus cerasus',
        'PRUNU_DOM'     => 'Prunus domestica',
        'PRUNU_DUL'     => 'Prunus dulcis',
        'PRUNU_SAL'     => 'Prunus salicina',
        'PRUNU_SAM'     => 'Prunus salicina',
        'PRUNU'         => 'Prunus',
        'FRAGA_ANA'     => 'Fragaria × ananassa',
        'FRAGA'         => 'Fragaria',
        'FRAGA_VES'     => 'Fragaria vesca',
        'RUBUS_IDA'     => 'Rubus idaeus',
        'RUBUS_EUB'     => 'Rubus fruticosus',
        'RUBUS'         => 'Rubus',
        'RIBES_NIG'     => 'Ribes nigrum',
        'RIBES_RUB'     => 'Ribes rubrum',
        'RIBES_UVA'     => 'Ribes uva-crispa',
        'RIBES'         => 'Ribes',
        'VACCI_COR'     => 'Vaccinium corymbosum',
        'VACCI'         => 'Vaccinium',
        'FICUS_CAR'     => 'Ficus carica',
        'OLEAA_EUR'     => 'Olea europaea',
        'JUGLA_REG'     => 'Juglans regia',
        'CASTA_SAT'     => 'Castanea sativa',
        'CASTA'         => 'Castanea',
        'CITRU_SIN'     => 'Citrus sinensis',
        'CITRU_RET'     => 'Citrus reticulata',
        'CITRU_LIM'     => 'Citrus limon',
        'CITRU_PAR'     => 'Citrus paradisi',
        'CITRU_CLE'     => 'Citrus clementina',
        'CITRU'         => 'Citrus',
        'CRYLS_AVE'     => 'Corylus avellana',
        'CYDON_OBL'     => 'Cydonia oblonga',
        'PISTA_VER'     => 'Pistacia vera',
        'ACTINI'        => 'Actinidia',
        'PYRUS_PYR'     => 'Pyrus pyrifolia',
        'DIOSPYROS'     => 'Diospyros kaki',
        // Rosiers
        'ROSAA'         => 'Rosa',
        'ROSAA_HYB'     => 'Rosa',
        'ROSAA_CAN'     => 'Rosa canina',
        'ROSAA_GAL'     => 'Rosa gallica',
        'ROSAA_DAM'     => 'Rosa damascena',
        'ROSAA_CEN'     => 'Rosa centifolia',
        'ROSAA_MOC'     => 'Rosa moschata',
        'ROSAA_RUG'     => 'Rosa rugosa',
        'ROSAA_MUL'     => 'Rosa multiflora',
        'ROSAA_WIC'     => 'Rosa wichuraiana',
        'ROSAA_CHI'     => 'Rosa chinensis',
        'ROSAA_BAN'     => 'Rosa banksiae',
        // Autres ornementales d'intérêt
        'HYDRA'         => 'Hydrangea',
        'HYDRA_MAC'     => 'Hydrangea macrophylla',
        'HYDRA_PAN'     => 'Hydrangea paniculata',
        'LAVAN'         => 'Lavandula',
        'LAVAN_ANG'     => 'Lavandula angustifolia',
        'CAMLL_JAP'     => 'Camellia japonica',
        'CAMLL'         => 'Camellia',
        'WISTA'         => 'Wisteria',
        'SYRNG'         => 'Syringa',
        'SYRNG_VUL'     => 'Syringa vulgaris',
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $subtypeFilter = $this->option('subtypes');
        $statusFilter  = $this->option('status');
        $dryRun        = $this->option('dry-run');

        $allowedSubtypes = $subtypeFilter === 'ALL' ? null : array_map('trim', explode(',', $subtypeFilter));
        $allowedStatuses = $statusFilter === 'ALL' ? null : array_map('trim', explode(',', $statusFilter));

        // Resolve taxon cache
        $taxonCache = $this->buildTaxonCache();
        $this->info("Taxon cache: {$taxonCache->count()} binomial names mapped");

        if ($this->option('clear') && ! $dryRun) {
            $deleted = Cultivar::count();
            $driver = \Illuminate\Support\Facades\DB::getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
                Cultivar::truncate();
                \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } else {
                Cultivar::truncate();
            }
            $this->warn("Cleared {$deleted} existing cultivars.");
        }

        $this->info("Reading file: {$filePath}");

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $rows = $this->readCsv($filePath);
        } else {
            $rows = $this->readExcel($filePath);
        }

        $this->info("Processing rows...");

        $imported  = 0;
        $skipped   = 0;
        $updated   = 0;
        $noTaxon   = 0;
        $batch     = [];
        $batchSize = 500;

        $bar = $this->output->createProgressBar();
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            $subtype = $row['Register Subtype'] ?? '';
            $status  = $row['Variety Status'] ?? '';

            // Filter by subtype
            if ($allowedSubtypes !== null && ! in_array($subtype, $allowedSubtypes)) {
                $skipped++;
                continue;
            }

            // Filter by status
            if ($allowedStatuses !== null && ! in_array($status, $allowedStatuses)) {
                $skipped++;
                continue;
            }

            $denomination = trim($row['Variety Denomination'] ?? '');
            $upovCode     = trim($row['UPOV Species Code'] ?? '');

            if ($denomination === '' || $upovCode === '') {
                $skipped++;
                continue;
            }

            // Resolve taxon
            $taxonId  = null;
            $binomial = self::UPOV_TO_BINOMIAL[$upovCode] ?? null;

            if ($binomial) {
                $taxonId = $taxonCache->get($binomial);
            }

            if (! $taxonId) {
                $noTaxon++;
            }

            $synonyms = trim($row['Variety Denomination Synonym(s)'] ?? '');
            $breeder  = trim($row['Name of the Breeder(s)'] ?? '');
            $country  = trim($row['Country / Org.'] ?? '');
            $regDate  = $this->parseDate($row['Registration Date'] ?? '');
            $uuid     = trim($row['UUID'] ?? '');
            $natId    = trim($row['National ID'] ?? '');
            $rootstock     = trim($row['Rootstock Info'] ?? '') ?: trim($row['Rootstock'] ?? '');
            $convDenom     = trim($row['Conventional Denomination'] ?? '');
            $tradeNames    = trim($row['Variety Trade Name(s)'] ?? '');

            // Build synonyms list
            $allSynonyms = array_filter(array_unique(array_map('trim', array_merge(
                $synonyms ? explode('/', $synonyms) : [],
                $convDenom && strtolower($convDenom) !== strtolower($denomination) ? [$convDenom] : [],
                $tradeNames ? explode('/', $tradeNames) : [],
            ))));

            $data = [
                'name'                 => $denomination,
                'taxon_id'             => $taxonId,
                'upov_code'            => $upovCode,
                'type'                 => 'cultivar',
                'synonyms'             => implode(', ', $allSynonyms) ?: null,
                'origin_country'       => null,
                'breeder'              => $breeder ?: null,
                'rootstocks'           => $rootstock ?: null,
                'source'               => 'EUPVP',
                'registration_country' => $country ?: null,
                'registration_status'  => $status ?: null,
                'registration_date'    => $regDate,
                'national_id'          => $natId ?: null,
                'eupvp_uuid'           => $uuid ?: null,
            ];

            if ($dryRun) {
                $imported++;
                continue;
            }

            $batch[] = $data;

            if (count($batch) >= $batchSize) {
                $result = $this->upsertBatch($batch);
                $imported += $result['inserted'];
                $updated  += $result['updated'];
                $batch = [];
            }
        }

        // Flush remaining batch
        if (! empty($batch) && ! $dryRun) {
            $result = $this->upsertBatch($batch);
            $imported += $result['inserted'];
            $updated  += $result['updated'];
        }

        $bar->finish();
        $this->newLine(2);

        $prefix = $dryRun ? '[DRY RUN] Would have' : '';
        $this->info("{$prefix} Imported: {$imported} | Updated: {$updated} | Skipped: {$skipped} | No taxon match: {$noTaxon}");
        $this->info("Total cultivars in DB: " . Cultivar::count());

        return 0;
    }

    private function buildTaxonCache(): \Illuminate\Support\Collection
    {
        return Taxon::pluck('id', 'binomial_name');
    }

    private function upsertBatch(array $batch): array
    {
        $inserted = 0;
        $updated  = 0;

        foreach ($batch as $data) {
            $existing = Cultivar::where('name', $data['name'])
                ->where('upov_code', $data['upov_code'])
                ->where('registration_country', $data['registration_country'])
                ->first();

            if ($existing) {
                // Only update if the existing record is from EUPVP (don't overwrite manual edits)
                if ($existing->source === 'EUPVP') {
                    $existing->update($data);
                    $updated++;
                }
            } else {
                $now = now();
                $data['created_at'] = $now;
                $data['updated_at'] = $now;
                Cultivar::insert($data);
                $inserted++;
            }
        }

        return compact('inserted', 'updated');
    }

    private function readExcel(string $filePath): \Generator
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $firstRow = true;

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getValue();
            }

            if ($firstRow) {
                $headers = $cells;
                $firstRow = false;
                continue;
            }

            yield array_combine($headers, array_pad($cells, count($headers), null));
        }

        $spreadsheet->disconnectWorksheets();
    }

    private function readCsv(string $filePath): \Generator
    {
        $handle = fopen($filePath, 'r');

        // Handle BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3);
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $headers = fgetcsv($handle, 0, $delimiter);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            yield array_combine($headers, array_pad($row, count($headers), null));
        }

        fclose($handle);
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Already a DateTime object from PhpSpreadsheet
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Try standard date parsing
        try {
            return (new \DateTime((string) $value))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}
