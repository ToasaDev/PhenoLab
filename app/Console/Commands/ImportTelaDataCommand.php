<?php

namespace App\Console\Commands;

use App\Models\TelaObservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTelaDataCommand extends Command
{
    protected $signature = 'tela:import {csv_file} {--batch-size=1000} {--skip-duplicates}';
    protected $description = 'Importer les observations Tela Botanica depuis un fichier CSV';

    public function handle(): int
    {
        $file = $this->argument('csv_file');
        $batchSize = (int) $this->option('batch-size');
        $skipDuplicates = $this->option('skip-duplicates');

        if (!file_exists($file)) {
            $this->error("Fichier non trouvé : {$file}");
            return Command::FAILURE;
        }

        $this->info("Import Tela Botanica depuis : {$file}");

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Impossible d'ouvrir le fichier");
            return Command::FAILURE;
        }

        // Detect and handle BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read headers
        $headers = fgetcsv($handle, 0, ';');
        if (!$headers) {
            $this->error("Impossible de lire les en-têtes CSV");
            fclose($handle);
            return Command::FAILURE;
        }

        $headers = array_map(fn ($h) => trim($h, "\"\t\n\r\0\x0B"), $headers);
        $this->info("Colonnes détectées : " . count($headers));

        $batch = [];
        $total = 0;
        $created = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $total++;
            $bar->advance();

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            $data = array_combine($headers, array_slice($row, 0, count($headers)));

            try {
                $record = $this->mapRow($data);
                $batch[] = $record;

                if (count($batch) >= $batchSize) {
                    $inserted = $this->insertBatch($batch, $skipDuplicates);
                    $created += $inserted;
                    $skipped += count($batch) - $inserted;
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $errors++;
                if ($errors <= 10) {
                    $this->warn("Ligne {$total}: {$e->getMessage()}");
                }
            }
        }

        // Insert remaining
        if (!empty($batch)) {
            $inserted = $this->insertBatch($batch, $skipDuplicates);
            $created += $inserted;
            $skipped += count($batch) - $inserted;
        }

        fclose($handle);
        $bar->finish();
        $this->newLine(2);

        $this->info("Import terminé !");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total lignes traitées', $total],
                ['Enregistrements créés', $created],
                ['Doublons ignorés', $skipped],
                ['Erreurs', $errors],
            ]
        );

        return Command::SUCCESS;
    }

    private function mapRow(array $data): array
    {
        $dateStr = $data['date'] ?? $data['Date'] ?? null;
        $date = $this->parseDate($dateStr);

        return [
            'date' => $date,
            'year' => $date ? (int) date('Y', strtotime($date)) : 0,
            'day_of_year' => $date ? (int) date('z', strtotime($date)) + 1 : 0,
            'data_source' => $data['source.donnees'] ?? $data['data_source'] ?? 'ODS Tela Botanica',
            'site_id_tela' => $data['station.id'] ?? $data['site_id'] ?? '',
            'site_name' => $data['station.nom'] ?? $data['site_name'] ?? '',
            'site_latitude' => $this->parseFloat($data['station.latitude'] ?? $data['site_latitude'] ?? null),
            'site_longitude' => $this->parseFloat($data['station.longitude'] ?? $data['site_longitude'] ?? null),
            'site_altitude' => $this->parseFloat($data['station.altitude'] ?? $data['site_altitude'] ?? null),
            'site_altitude_from_ign' => $this->parseFloat($data['station.altitude.ign'] ?? $data['site_altitude_from_ign'] ?? null),
            'taxon_id_tela' => $data['taxon.id'] ?? $data['taxon_id'] ?? '',
            'binomial_name' => $data['taxon.nom.binomial'] ?? $data['binomial_name'] ?? '',
            'kingdom' => $data['taxon.regne'] ?? $data['kingdom'] ?? '',
            'genus' => $data['taxon.genre'] ?? $data['genus'] ?? '',
            'species' => $data['taxon.espece'] ?? $data['species'] ?? '',
            'subspecies' => $data['taxon.sous.espece'] ?? $data['subspecies'] ?? '',
            'variety' => $data['taxon.variete'] ?? $data['variety'] ?? '',
            'taxon_clone_or_accession_code' => $data['taxon.clone.ou.code.accession'] ?? '',
            'phenological_scale_id' => (int) ($data['echelle.id'] ?? $data['phenological_scale_id'] ?? 0),
            'phenological_scale' => $data['echelle.nom'] ?? $data['phenological_scale'] ?? '',
            'stage_code' => $data['stade.code'] ?? $data['stage_code'] ?? '',
            'stage_description' => $data['stade.description'] ?? $data['stage_description'] ?? '',
            'phenological_main_event_code' => (int) ($data['evenement.id'] ?? $data['phenological_main_event_code'] ?? 0),
            'phenological_main_event_description' => $data['evenement.description'] ?? $data['phenological_main_event_description'] ?? '',
            'data_license_acronym' => $data['licence.acronyme'] ?? $data['data_license_acronym'] ?? '',
            'data_license' => $data['licence.nom'] ?? $data['data_license'] ?? '',
            'data_license_url' => $data['licence.url'] ?? $data['data_license_url'] ?? '',
            'contact_name' => $data['contact.nom'] ?? $data['contact_name'] ?? '',
            'contact_email_address' => $data['contact.email'] ?? $data['contact_email_address'] ?? '',
            'contact_organisation' => $data['contact.organisme'] ?? $data['contact_organisation'] ?? '',
            'environment' => $data['environnement'] ?? $data['environment'] ?? '',
            'private_station' => $this->parseBool($data['station.privee'] ?? $data['private_station'] ?? 'Non'),
            'observation_number' => $data['observation.numero'] ?? $data['observation_number'] ?? '',
            'observer_number' => $data['observateur.numero'] ?? $data['observer_number'] ?? '',
            'aggregation' => $data['agregation'] ?? $data['aggregation'] ?? '',
            'drias_cell_number' => $data['maille.drias'] ?? $data['drias_cell_number'] ?? '',
            'safran_cell_number' => $data['maille.safran'] ?? $data['safran_cell_number'] ?? '',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function insertBatch(array $batch, bool $skipDuplicates): int
    {
        if ($skipDuplicates) {
            // Use insert and count - MariaDB doesn't have insertOrIgnore easily, use chunks
            try {
                DB::table('tela_observations')->insert($batch);
                return count($batch);
            } catch (\Throwable) {
                // Fallback: insert one by one
                $count = 0;
                foreach ($batch as $record) {
                    try {
                        DB::table('tela_observations')->insert($record);
                        $count++;
                    } catch (\Throwable) {
                        // duplicate, skip
                    }
                }
                return $count;
            }
        }

        DB::table('tela_observations')->insert($batch);
        return count($batch);
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value || trim($value) === '') return null;
        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'] as $format) {
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

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        $value = strtolower(trim((string) $value));
        return in_array($value, ['oui', 'true', '1', 'yes', 'vrai']);
    }
}
