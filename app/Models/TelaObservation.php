<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelaObservation extends Model
{
    use HasFactory;

    protected $table = 'tela_observations';

    protected $fillable = [
        'date',
        'year',
        'day_of_year',
        'data_source',
        'site_id',
        'site_name',
        'site_latitude',
        'site_longitude',
        'site_altitude',
        'site_altitude_from_ign',
        'taxon_id',
        'binomial_name',
        'kingdom',
        'genus',
        'species',
        'subspecies',
        'variety',
        'taxon_clone_or_accession_code',
        'phenological_scale_id',
        'phenological_scale',
        'stage_code',
        'stage_description',
        'phenological_main_event_code',
        'phenological_main_event_description',
        'data_license_acronym',
        'data_license',
        'data_license_url',
        'contact_name',
        'contact_email_address',
        'contact_organisation',
        'environment',
        'private_station',
        'observation_number',
        'observer_number',
        'aggregation',
        'drias_cell_number',
        'safran_cell_number',
    ];

    protected function casts(): array
    {
        return [
            'date'            => 'date',
            'private_station' => 'boolean',
        ];
    }
}
