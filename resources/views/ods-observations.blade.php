<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Observations ODS - Observatoire Des Saisons</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .observation-card {
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
        }
        .observation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .species-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        .stage-badge {
            background: linear-gradient(45deg, #007bff, #6610f2);
            color: white;
        }
        .location-badge {
            background: linear-gradient(45deg, #fd7e14, #e63946);
            color: white;
        }
        .search-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .stats-card {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            border: none;
        }
        .filter-badge {
            background-color: #007bff;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            margin-right: 0.5rem;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        .observation-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .pagination .page-link {
            border-radius: 50px;
            margin: 0 2px;
        }
        .navbar-brand {
            font-weight: 600;
            background: linear-gradient(45deg, #28a745, #20c997);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-seedling me-2"></i>PhenoLab
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home me-1"></i>Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('ods-observations') }}">
                            <i class="fas fa-database me-1"></i>Observations ODS
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- En-tete -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-4">
                    <i class="fas fa-database text-primary me-3"></i>
                    Observations ODS
                </h1>
                <p class="lead text-muted">
                    Explorez les donnees de l'Observatoire Des Saisons - Plus de 30 000 observations phenologiques
                </p>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3 class="mb-2">{{ number_format($totalObservations) }}</h3>
                    <p class="mb-0 text-muted">Observations trouvees</p>
                </div>
            </div>
        </div>

        <!-- Section de recherche -->
        <div class="search-section">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="q" class="form-label">Recherche generale</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="q" name="q"
                               value="{{ $searchQuery }}"
                               placeholder="Nom scientifique, station, nom vernaculaire...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="department" class="form-label">Departement</label>
                    <select class="form-select" id="department" name="department">
                        <option value="">Tous les departements</option>
                        @foreach($uniqueDepartments as $dept)
                            <option value="{{ $dept }}" @selected($dept == $department)>
                                {{ $dept }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="year" class="form-label">Annee</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">Toutes les annees</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" @selected((string)$y === $year)>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="scientific_name" class="form-label">Espece</label>
                    <select class="form-select" id="scientific_name" name="scientific_name">
                        <option value="">Toutes les especes</option>
                        @foreach($uniqueSpecies as $species)
                            <option value="{{ $species }}" @selected($species == $scientificName)>
                                {{ $species }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="phenological_stage" class="form-label">Stade phenologique</label>
                    <select class="form-select" id="phenological_stage" name="phenological_stage">
                        <option value="">Tous les stades</option>
                        @foreach($uniqueStages as $stage)
                            <option value="{{ $stage }}" @selected($stage == $phenologicalStage)>
                                {{ $stage }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Rechercher
                    </button>
                    <a href="{{ route('ods-observations') }}" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="fas fa-times me-2"></i>Reinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Filtres actifs -->
        @if($hasFilters)
        <div class="mb-4">
            <h6 class="mb-2">Filtres actifs :</h6>
            @if($searchQuery)
                <span class="filter-badge">Recherche: {{ $searchQuery }}</span>
            @endif
            @if($scientificName)
                <span class="filter-badge">Espece: {{ $scientificName }}</span>
            @endif
            @if($department)
                <span class="filter-badge">Departement: {{ $department }}</span>
            @endif
            @if($year)
                <span class="filter-badge">Annee: {{ $year }}</span>
            @endif
            @if($phenologicalStage)
                <span class="filter-badge">Stade: {{ $phenologicalStage }}</span>
            @endif
        </div>
        @endif

        <!-- Resultats -->
        <div class="row">
            @forelse($observations as $observation)
            <div class="col-12 mb-3">
                <div class="card observation-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5 class="card-title mb-2">
                                    <span class="species-badge badge fs-6">{{ $observation->scientific_name }}</span>
                                </h5>
                                @if($observation->vernacular_name)
                                <p class="card-text mb-1">
                                    <strong>{{ $observation->vernacular_name }}</strong>
                                </p>
                                @endif
                                <p class="observation-meta mb-0">
                                    <i class="fas fa-calendar me-1"></i>{{ $observation->date ? \Carbon\Carbon::parse($observation->date)->format('d/m/Y') : '' }}
                                    <i class="fas fa-clock ms-3 me-1"></i>{{ $observation->date ? \Carbon\Carbon::parse($observation->date)->format('Y') : '' }}
                                </p>
                            </div>

                            <div class="col-md-4">
                                @if($observation->phenological_stage)
                                <span class="stage-badge badge fs-6 mb-2">
                                    <i class="fas fa-leaf me-1"></i>{{ $observation->phenological_stage }}
                                </span>
                                @endif
                                @if($observation->bbch_code)
                                <p class="observation-meta mb-1">
                                    <strong>Code BBCH:</strong> {{ $observation->bbch_code }}
                                </p>
                                @endif
                                @if($observation->individual_name)
                                <p class="observation-meta mb-0">
                                    <strong>Individu:</strong> {{ $observation->individual_name }}
                                </p>
                                @endif
                            </div>

                            <div class="col-md-4">
                                <span class="location-badge badge fs-6 mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>{{ $observation->station_name }}
                                </span>
                                @if($observation->department)
                                <p class="observation-meta mb-1">
                                    <strong>Departement:</strong> {{ $observation->department }}
                                </p>
                                @endif
                                @if($observation->habitat)
                                <p class="observation-meta mb-0">
                                    <strong>Habitat:</strong> {{ $observation->habitat }}
                                </p>
                                @endif
                                @if($observation->latitude && $observation->longitude)
                                <p class="observation-meta mb-0">
                                    <small><i class="fas fa-globe me-1"></i>{{ number_format($observation->latitude, 4) }}, {{ number_format($observation->longitude, 4) }}</small>
                                </p>
                                @endif
                            </div>
                        </div>

                        @if($observation->details)
                        <div class="row mt-2">
                            <div class="col-12">
                                <p class="card-text"><em>{{ Str::words($observation->details, 20) }}</em></p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                    <h4>Aucune observation trouvee</h4>
                    <p class="mb-0">Essayez de modifier vos criteres de recherche ou de supprimer les filtres.</p>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($observations->hasPages())
        <nav aria-label="Navigation des observations" class="mt-4">
            {{ $observations->appends(request()->query())->links('pagination::bootstrap-5') }}

            <div class="text-center mt-3">
                <small class="text-muted">
                    Page {{ $observations->currentPage() }} sur {{ $observations->lastPage() }}
                    ({{ $observations->firstItem() }}-{{ $observations->lastItem() }} sur {{ $totalObservations }})
                </small>
            </div>
        </nav>
        @endif
    </div>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>A propos des donnees ODS</h6>
                    <p class="text-muted small">
                        Ces observations proviennent de l'Observatoire Des Saisons,
                        un programme de sciences participatives dedie a l'etude de la phenologie.
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="text-muted small">
                        <i class="fas fa-database me-1"></i>
                        {{ number_format($totalObservations) }} observations
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
