<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhenologicalStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage_code',
        'stage_description',
        'main_event_code',
        'main_event_description',
        'phenological_scale',
    ];

    /**
     * BBCH main phenological events.
     */
    public const MAIN_EVENTS = [
        1 => 'Développement des feuilles (pousse principale)',
        2 => 'Formation des pousses latérales',
        3 => 'Développement de la tige/allongement de la pousse',
        4 => 'Développement des organes reproducteurs',
        5 => "Épiaison/émergence de l'inflorescence",
        6 => 'Floraison',
        7 => 'Fructification',
        8 => 'Maturation des fruits et graines',
        9 => 'Sénescence et dormance',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }
}
