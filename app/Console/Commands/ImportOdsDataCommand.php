<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOdsDataCommand extends Command
{
    protected $signature = 'ods:import {csv_file} {--batch-size=1000} {--clear} {--no-progress} {--progress-every=1000}';
    protected $description = 'Importer les observations ODS depuis un fichier CSV';

    public function handle(): int
    {
        $file = $this->argument('csv_file');
        $batchSize = (int) $this->option('batch-size');
        $progressEvery = max(1, (int) $this->option('progress-every'));

        if (!file_exists($file)) {
            $this->error("Fichier non trouvé : {$file}");
            return Command::FAILURE;
        }

        if ($this->option('clear')) {
            if ($this->confirm('Êtes-vous sûr de vouloir supprimer toutes les observations ODS existantes ?')) {
                DB::table('ods_observations')->truncate();
                $this->info('Table ods_observations vidée.');
            }
        }

        $this->info("Import ODS depuis : {$file}");

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Impossible d'ouvrir le fichier");
            return Command::FAILURE;
        }

        // Handle BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $startPosition = ftell($handle);
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            $this->error("Impossible de lire la première ligne du CSV");
            fclose($handle);
            return Command::FAILURE;
        }

        $delimiter = $this->detectDelimiter($firstLine);
        fseek($handle, $startPosition);

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            $this->error("Impossible de lire les en-têtes CSV");
            fclose($handle);
            return Command::FAILURE;
        }

        $headers = array_map(
            fn ($header) => $this->normalizeHeader(trim($header, "\"\t\n\r\0\x0B")),
            $headers
        );
        $this->info("Colonnes : " . count($headers) . " (séparateur détecté : '{$delimiter}')");

        $totalLines = $this->countDataLines($file, $delimiter);
        $useProgressBar = ! $this->option('no-progress') && $this->output->isDecorated();
        $bar = null;

        $batch = [];
        $total = 0;
        $created = 0;
        $errors = 0;

        if ($useProgressBar) {
            $bar = $this->output->createProgressBar($totalLines);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
            $bar->start();
        } else {
            $this->line("Début import : {$totalLines} lignes de données");
        }

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $total++;
                if ($bar) {
                    $bar->advance();
                } elseif ($total % $progressEvery === 0) {
                    $this->line("Traitées: {$total} / {$totalLines} | Créées: {$created} | Erreurs: {$errors}");
                }

                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                }

                $data = array_combine($headers, array_slice($row, 0, count($headers)));

                try {
                    $batch[] = $this->mapRow($data);

                    if (count($batch) >= $batchSize) {
                        $created += $this->flushBatch($batch);
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    if ($errors <= 10) {
                        $this->warn("Ligne {$total}: {$e->getMessage()}");
                    }
                }
            }

            if (!empty($batch)) {
                $created += $this->flushBatch($batch);
            }
        } catch (\Throwable $e) {
            $this->error("Erreur lors de l'import : {$e->getMessage()}");
            fclose($handle);
            return Command::FAILURE;
        }

        fclose($handle);
        if ($bar) {
            $bar->finish();
            $this->newLine(2);
        } else {
            $this->newLine();
        }

        $this->info("Import terminé !");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total lignes', $total],
                ['Créés', $created],
                ['Erreurs', $errors],
            ]
        );

        return Command::SUCCESS;
    }

    private function countDataLines(string $file, string $delimiter): int
    {
        $rowCount = 0;
        $handle = fopen($file, 'r');

        if (! $handle) {
            return 0;
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        fgetcsv($handle, 0, $delimiter);

        while (fgetcsv($handle, 0, $delimiter) !== false) {
            $rowCount++;
        }

        fclose($handle);

        return $rowCount;
    }

    private function flushBatch(array &$batch): int
    {
        $attempts = 0;

        while (true) {
            try {
                DB::table('ods_observations')->insert($batch);
                $inserted = count($batch);
                $batch = [];

                return $inserted;
            } catch (\Throwable $e) {
                $attempts++;

                if ($attempts < 5 && str_contains(strtolower($e->getMessage()), 'database is locked')) {
                    usleep(200000 * $attempts);
                    continue;
                }

                throw $e;
            }
        }
    }

    private function mapRow(array $data): array
    {
        return [
            'observation_id_ods' => trim($this->value($data, ['id_observation', 'observation_id', 'observation_id_ods']) ?? ''),
            'date' => $this->parseDate($this->value($data, ['date_observation', 'date'])),
            'is_missing' => trim($this->value($data, ['est_manquant', 'is_missing']) ?? ''),
            'details' => $this->value($data, ['details']) ?? '',
            'creation_date' => $this->parseDate($this->value($data, ['date_creation', 'creation_date', 'date_de_creation'])),
            'update_date' => $this->parseDate($this->value($data, ['date_modification', 'update_date', 'mise_a_jour'])),
            'deletion_date' => $this->parseDate($this->value($data, ['date_suppression', 'deletion_date'])),
            'species_id' => trim($this->value($data, ['id_espece', 'species_id']) ?? ''),
            'vernacular_name' => $this->value($data, ['nom_vernaculaire', 'vernacular_name']) ?? '',
            'scientific_name' => $this->value($data, ['nom_scientifique', 'scientific_name']) ?? '',
            'species_type' => $this->value($data, ['type_espece', 'species_type', 'type']) ?? '',
            'plant_or_animal' => $this->value($data, ['plante_ou_animal', 'plant_or_animal']) ?? '',
            'individual_id' => trim($this->value($data, ['id_individu', 'individual_id']) ?? ''),
            'individual_name' => $this->value($data, ['nom_individu', 'individual_name']) ?? '',
            'individual_detail' => $this->value($data, ['detail_individu', 'individual_detail']) ?? '',
            'phenological_stage' => $this->value($data, ['stade_phenologique', 'phenological_stage']) ?? '',
            'bbch_code' => trim($this->value($data, ['code_bbch', 'bbch_code']) ?? ''),
            'station_id' => trim($this->value($data, ['id_station', 'station_id']) ?? ''),
            'station_name' => $this->value($data, ['nom_station', 'station_name']) ?? '',
            'station_description' => $this->value($data, ['description_station', 'station_description']) ?? '',
            'station_locality' => $this->value($data, ['localite_station', 'station_locality']) ?? '',
            'habitat' => $this->value($data, ['habitat']) ?? '',
            'latitude' => $this->parseFloat($this->value($data, ['latitude'])),
            'longitude' => $this->parseFloat($this->value($data, ['longitude'])),
            'altitude' => $this->parseFloat($this->value($data, ['altitude'])),
            'insee_code' => trim($this->value($data, ['code_insee', 'insee_code']) ?? ''),
            'department' => $this->value($data, ['departement', 'department']) ?? '',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t"];
        $bestDelimiter = ',';
        $bestCount = -1;

        foreach ($candidates as $candidate) {
            $count = count(str_getcsv($line, $candidate));
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter = $candidate;
            }
        }

        return $bestDelimiter;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/([a-z])([A-Z])/', '$1_$2', $header);
        $header = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) ?: $header;
        $header = strtolower($header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim($header, '_');
    }

    private function value(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return null;
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value || trim($value) === '') return null;
        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function parseFloat(?string $value): ?float
    {
        if ($value === null || trim($value) === '') return null;
        $value = str_replace(',', '.', trim($value));
        return is_numeric($value) ? (float) $value : null;
    }
}
