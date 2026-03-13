<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PhenoLab - Phénologie des Plantes</title>

    @vite(['resources/js/app.js'])

    <style>
        /* Hide Vue templates until mounted */
        [v-cloak] { display: none !important; }

        /* Modal backdrop fix */
        .modal {
            background-color: rgba(0,0,0,0.5);
        }
        
        /* Ensure modal shows properly */
        .modal[v-show] {
            display: block !important;
        }
        
        /* Custom modal styles */
        .modal-dialog {
            margin: 1.75rem auto;
            max-width: 500px;
        }
        
        .modal-lg {
            max-width: 800px;
        }
        
        /* Prevent body scroll issues */
        body.modal-open {
            overflow: hidden;
        }
        
        /* Site detailed map styles */
        .plant-marker {
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .plant-marker .plant-icon {
            font-size: 16px;
            line-height: 1;
        }
        
        .plant-marker.health-excellent {
            background-color: #28a745;
            border-color: #1e7e34;
        }
        
        .plant-marker.health-good {
            background-color: #17a2b8;
            border-color: #138496;
        }
        
        .plant-marker.health-fair {
            background-color: #ffc107;
            border-color: #e0a800;
        }
        
        .plant-marker.health-poor {
            background-color: #dc3545;
            border-color: #c82333;
        }
        
        .plant-marker.health-dead {
            background-color: #6c757d;
            border-color: #545b62;
        }
        
        .site-center-marker {
            text-align: center;
        }
        
        .site-center-icon {
            font-size: 24px;
            background-color: #007bff;
            border: 3px solid #fff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 12px rgba(0,0,0,0.4);
        }
        
        .plant-popup {
            min-width: 200px;
        }
        
        .plant-popup h6 {
            color: #007bff;
            margin-bottom: 8px;
        }
        
        .plant-popup .popup-details {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin: 8px 0;
        }
        
        /* Leaflet map controls custom styling */
        .leaflet-control-scale-line {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        
        /* Plant list styling */
        .cursor-pointer {
            cursor: pointer;
        }
        
        .cursor-pointer:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
    </style>
</head>
<body>
    <div id="app" v-cloak>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-seedling me-2"></i>
                    PhenoLab
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Primary Navigation -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#dashboard" @click="currentView = 'dashboard'" :class="{active: currentView === 'dashboard'}" aria-label="Tableau de bord">
                                <i class="fas fa-home me-1"></i>Accueil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#sites" @click="currentView = 'sites'" :class="{active: currentView === 'sites'}" aria-label="Sites">
                                <i class="fas fa-map-marker-alt me-1"></i>Sites
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#plants" @click="currentView = 'plants'" :class="{active: currentView === 'plants'}" aria-label="Plantes">
                                <i class="fas fa-leaf me-1"></i>Plantes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#observations" @click="currentView = 'observations'" :class="{active: currentView === 'observations'}" aria-label="Observations">
                                <i class="fas fa-eye me-1"></i>Observations
                            </a>
                        </li>

                        <!-- Visual separator -->
                        <li class="d-none d-lg-flex"><div class="nav-separator"></div></li>

                        <!-- Secondary Navigation -->
                        <li class="nav-item">
                            <a class="nav-link" href="#map" @click="currentView = 'map'" :class="{active: currentView === 'map'}" aria-label="Carte générale">
                                <i class="fas fa-globe me-1"></i>Carte
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#analysis" @click="currentView = 'analysis'" :class="{active: currentView === 'analysis'}" aria-label="Analyses">
                                <i class="fas fa-chart-line me-1"></i>Analyses
                            </a>
                        </li>
                    </ul>

                    <!-- Action Buttons -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Add dropdown -->
                        <li class="nav-item dropdown btn-action">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ajouter des données">
                                <i class="fas fa-plus me-1"></i>Ajouter
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" @click.prevent="openModal('site')">
                                    <i class="fas fa-map-marker-alt me-2"></i>Nouveau site
                                </a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="openModal('plant')">
                                    <i class="fas fa-leaf me-2"></i>Nouvelle plante
                                </a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="openModal('observation')">
                                    <i class="fas fa-eye me-2"></i>Nouvelle observation
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="openModal('photo')">
                                    <i class="fas fa-camera me-2"></i>Nouvelle photo
                                </a></li>
                            </ul>
                        </li>

                        <!-- Tools dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle icon-only" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Outils">
                                <i class="fas fa-tools"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Outils</h6></li>
                                <li><a class="dropdown-item" href="#search" @click="currentView = 'search'">
                                    <i class="fas fa-search me-2"></i>Recherche Globale
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/observations-ods" target="_blank">
                                    <i class="fas fa-database me-2"></i>Données ODS
                                    <i class="fas fa-external-link-alt ms-1 text-muted" style="font-size: 0.75em;"></i>
                                </a></li>
                                <li v-if="user.isStaff || user.isSuperuser"><hr class="dropdown-divider"></li>
                                <li v-if="user.isStaff || user.isSuperuser">
                                    <h6 class="dropdown-header">Administration</h6>
                                </li>
                                <li v-if="user.isStaff || user.isSuperuser">
                                    <a class="dropdown-item" href="#admin" @click="currentView = 'admin'">
                                        <i class="fas fa-cogs me-2"></i>Gestion des données
                                    </a>
                                </li>
                                <li v-if="user.isStaff || user.isSuperuser">
                                    <a class="dropdown-item" href="/admin" target="_blank">
                                        <i class="fas fa-shield-alt me-2"></i>Filament Admin
                                        <i class="fas fa-external-link-alt ms-1 text-muted" style="font-size: 0.75em;"></i>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="showHelpAlert()">
                                    <i class="fas fa-question-circle me-2"></i>Aide
                                </a></li>
                            </ul>
                        </li>

                        <!-- User menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu utilisateur">
                                <i class="fas fa-user me-1"></i>
                                <span class="d-none d-md-inline" v-text="user.username"></span>
                                <span v-if="user.isAuthenticated && user.isStaff" class="badge bg-warning ms-1 d-none d-lg-inline">Staff</span>
                                <span v-if="user.isAuthenticated && user.isSuperuser" class="badge bg-danger ms-1 d-none d-lg-inline">Admin</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end user-menu">
                                <!-- User not authenticated -->
                                <template v-if="!user.isAuthenticated">
                                    <li>
                                        <h6 class="dropdown-header">
                                            <i class="fas fa-user-slash me-1"></i>Non connecté
                                        </h6>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" @click.prevent="showLoginModal = true">
                                            <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <small class="dropdown-item-text text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Connectez-vous pour enregistrer vos données
                                        </small>
                                    </li>
                                </template>

                                <!-- User authenticated -->
                                <template v-if="user.isAuthenticated">
                                    <li>
                                        <h6 class="dropdown-header">
                                            <i class="fas fa-user-check me-1"></i>
                                            <span v-text="user.username"></span>
                                            <span v-if="user.isStaff" class="badge bg-warning ms-1">Staff</span>
                                            <span v-if="user.isSuperuser" class="badge bg-danger ms-1">Admin</span>
                                        </h6>
                                    </li>
                                    <li v-if="user.email">
                                        <span class="dropdown-item-text text-muted">
                                            <i class="fas fa-envelope me-2"></i>
                                            <span v-text="user.email"></span>
                                        </span>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="#" @click.prevent="">
                                            <i class="fas fa-cog me-2"></i>Paramètres
                                        </a>
                                    </li>
                                    <li v-if="user.isStaff">
                                        <a class="dropdown-item" href="/admin/" target="_blank">
                                            <i class="fas fa-shield-alt me-2"></i>Administration
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" @click.prevent="logout()">
                                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container-fluid py-4">
            <!-- Dashboard View - Redesigned Homepage -->
            <div v-if="currentView === 'dashboard'" class="dashboard">
                <!-- Hero Section: ODS Evolution Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <h1 class="h4 mb-2">
                                        <i class="fas fa-chart-line text-success me-2"></i>
                                        Observatoire National de Phénologie — Évolution depuis 2006
                                    </h1>
                                    <p class="text-muted mb-0">
                                        <span v-if="odsChartData.summary">
                                            <strong v-text="formatNumber(odsChartData.summary.total_observations)"></strong> observations phénologiques
                                            enregistrées en France entre @{{ odsChartData.summary.first_year }} et @{{ odsChartData.summary.last_year }}
                                        </span>
                                        <span v-else>
                                            Chargement des données nationales...
                                        </span>
                                    </p>
                                </div>
                                <div style="position: relative; height: 350px;">
                                    <canvas id="odsEvolutionChart"></canvas>
                                </div>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <p class="mb-0 small text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        La phénologie étudie les événements saisonniers chez les êtres vivants (floraison, feuillaison, etc.).
                                        Ce graphique montre l'évolution des observations citoyennes à travers la France.
                                        <strong>Rejoignez l'effort en ajoutant vos observations !</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Your Phenology Footprint -->
                <div class="row mb-4" v-if="user.isAuthenticated">
                    <div class="col-12">
                        <h2 class="h5 mb-3">
                            <i class="fas fa-user-circle text-primary me-2"></i>
                            Votre empreinte phénologique
                        </h2>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-success h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-eye fa-2x text-success mb-2"></i>
                                <h3 class="mb-1" v-text="statistics.totalObservations || 0"></h3>
                                <p class="text-muted mb-0 small">Observations enregistrées</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-success h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-leaf fa-2x text-success mb-2"></i>
                                <h3 class="mb-1" v-text="statistics.totalPlants || 0"></h3>
                                <p class="text-muted mb-0 small">Plantes suivies</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-success h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-2x text-success mb-2"></i>
                                <h3 class="mb-1" v-text="statistics.totalSites || 0"></h3>
                                <p class="text-muted mb-0 small">Sites surveillés</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="h5 mb-3">
                            <i class="fas fa-tasks text-info me-2"></i>
                            Actions rapides
                        </h2>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white h-100 clickable" @click="openModal('observation')" style="cursor: pointer;">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-plus-circle fa-3x mb-3"></i>
                                <h5 class="card-title">Nouvelle observation</h5>
                                <p class="card-text small">Enregistrez un stade phénologique</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white h-100 clickable" @click="currentView = 'plants'" style="cursor: pointer;">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-seedling fa-3x mb-3"></i>
                                <h5 class="card-title">Gérer mes plantes</h5>
                                <p class="card-text small">Ajoutez et suivez vos spécimens</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-info text-white h-100 clickable" @click="currentView = 'sites'" style="cursor: pointer;">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-map fa-3x mb-3"></i>
                                <h5 class="card-title">Explorer les sites</h5>
                                <p class="card-text small">Découvrez les lieux d'observation</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row" v-if="recentActivities && recentActivities.length > 0">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2 text-warning"></i>
                                    Activité récente
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <div v-for="activity in recentActivities.slice(0, 8)" :key="activity.id"
                                         class="list-group-item list-group-item-action border-0">
                                        <div class="d-flex align-items-start">
                                            <!-- Icon with color -->
                                            <div class="flex-shrink-0 me-3">
                                                <i :class="['fas', activity.icon, `text-${activity.color}`]" style="font-size: 1.2rem; width: 24px;"></i>
                                            </div>

                                            <!-- Activity content -->
                                            <div class="flex-grow-1 min-width-0">
                                                <!-- Entity label -->
                                                <div class="mb-1">
                                                    <span class="text-dark" v-text="activity.entity_label"></span>
                                                </div>

                                                <!-- Actor info -->
                                                <div class="small text-muted">
                                                    <span v-if="activity.is_system">
                                                        <i class="fas fa-robot me-1"></i>
                                                        Système
                                                    </span>
                                                    <span v-else-if="activity.actor">
                                                        <i class="fas fa-user me-1"></i>
                                                        <span v-text="activity.actor.username"></span>
                                                    </span>
                                                    <span class="mx-1">•</span>
                                                    <span v-text="getRelativeTime(activity.timestamp)"></span>
                                                </div>
                                            </div>

                                            <!-- Badge for action type (optional) -->
                                            <div class="flex-shrink-0 ms-2">
                                                <span v-if="activity.action === 'validated'" class="badge bg-primary rounded-pill">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white text-center border-top" v-if="recentActivities.length > 8">
                                <a href="#observations" @click="currentView = 'observations'" class="text-primary text-decoration-none small">
                                    <i class="fas fa-list me-1"></i>
                                    Voir toutes les activités →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state for no activities -->
                <div class="row" v-else-if="recentActivities && recentActivities.length === 0">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Aucune activité récente</p>
                                <small class="text-muted">Les nouvelles observations apparaîtront ici</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sites View -->
            <div v-if="currentView === 'sites'" class="sites-view">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h1 class="h3 mb-0">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                            Gestion des Sites
                        </h1>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-primary" @click="openModal('site')">
                            <i class="fas fa-plus me-1"></i>Ajouter un site
                        </button>
                        <button class="btn btn-success ms-2" @click="openModal('test')">
                            <i class="fas fa-vial me-1"></i>Test Formulaire
                        </button>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <input v-model="siteFilters.search" type="text" class="form-control" placeholder="Rechercher un site...">
                    </div>
                    <div class="col-md-3">
                        <select v-model="siteFilters.environment" class="form-select">
                            <option value="">Tous les environnements</option>
                            <option value="urban">Urbain</option>
                            <option value="suburban">Périurbain</option>
                            <option value="rural">Rural</option>
                            <option value="forest">Forêt</option>
                            <option value="garden">Jardin/Parc</option>
                            <option value="natural">Naturel</option>
                            <option value="agricultural">Agricole</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-2">
                            <input v-model="siteFilters.showPrivate" class="form-check-input" type="checkbox" id="showPrivate">
                            <label class="form-check-label" for="showPrivate">
                                Sites privés
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Sites Grid/Map Toggle -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" 
                                    :class="{active: sitesViewMode === 'grid'}"
                                    @click="sitesViewMode = 'grid'">
                                <i class="fas fa-th me-1"></i>Grille
                            </button>
                            <button type="button" class="btn btn-outline-primary"
                                    :class="{active: sitesViewMode === 'map'}"
                                    @click="sitesViewMode = 'map'">
                                <i class="fas fa-map me-1"></i>Carte
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sites Content -->
                <div v-if="sitesViewMode === 'grid'" class="row">
                    <!-- Loading State -->
                    <div v-if="loading.sites" class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3 text-muted">Chargement des sites...</p>
                    </div>
                    
                    <!-- No Sites Found -->
                    <div v-else-if="filteredSites.length === 0" class="col-12 text-center py-5">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                        <h4>Aucun site trouvé</h4>
                        <p class="text-muted mb-4">Aucun site ne correspond à vos critères de recherche.</p>
                        <button class="btn btn-primary" @click="openModal('site')">
                            <i class="fas fa-plus me-1"></i>Ajouter le premier site
                        </button>
                    </div>
                    
                    <!-- Sites Grid -->
                    <div v-else v-for="site in filteredSites" :key="site.id" class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5 class="card-title">@{{ site.name }}</h5>
                                    <span v-if="site.is_private" class="badge bg-warning">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                                <p class="card-text text-muted" v-text="site.description || 'Aucune description'"></p>
                                
                                <!-- Site Statistics -->
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <strong v-text="site.plants_count || 0"></strong><br>
                                        <small class="text-muted">Plantes</small>
                                    </div>
                                    <div class="col-4">
                                        <strong v-text="site.observations_count || 0"></strong><br>
                                        <small class="text-muted">Observations</small>
                                    </div>
                                    <div class="col-4">
                                        <strong><span v-text="site.altitude || 'N/A'"></span><span v-if="site.altitude">m</span></strong><br>
                                        <small class="text-muted">Altitude</small>
                                    </div>
                                </div>
                                
                                <!-- Site Info -->
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Créé le <span v-text="formatDate(site.created_at)"></span>
                                    </small>
                                    <br>
                                    <small class="text-muted" v-if="site.latitude && site.longitude">
                                        <i class="fas fa-globe me-1"></i>
                                        <span v-text="parseFloat(site.latitude).toFixed(4)"></span>, <span v-text="parseFloat(site.longitude).toFixed(4)"></span>
                                    </small>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary btn-sm flex-fill" @click="viewSite(site)">
                                        <i class="fas fa-eye me-1"></i>Voir
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" @click="editSiteAction(site)" v-if="user.isAuthenticated && (user.id === site.owner?.id || user.isStaff)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <small>
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <span v-text="getEnvironmentLabel(site.environment)"></span>
                                    <span v-if="site.owner && site.owner.username" class="float-end">
                                        <i class="fas fa-user me-1"></i><span v-text="site.owner.username"></span>
                                    </span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="sitesViewMode === 'map'" class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <!-- Loading State for Map -->
                                <div v-if="loading.sites" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                    <p class="mt-3 text-muted">Chargement de la carte...</p>
                                </div>
                                
                                <!-- Map Container -->
                                <div v-else id="sitesMap" style="height: 600px;"></div>
                                
                                <!-- No Sites Message for Map -->
                                <div v-if="!loading.sites && filteredSites.length === 0" class="text-center py-5">
                                    <i class="fas fa-map fa-3x text-muted mb-3"></i>
                                    <h4>Aucun site à afficher</h4>
                                    <p class="text-muted">Ajoutez des sites pour les voir sur la carte.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Site Detail View -->
            <div v-if="currentView === 'site-detail'" class="site-detail">
                <!-- Loading State -->
                <div v-if="siteDetail.loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3 text-muted">Chargement des détails du site...</p>
                </div>
                
                <!-- Site Detail Content -->
                <div v-else-if="siteDetail.site" class="site-detail-content">
                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="#sites" @click="backToSites">
                                            <i class="fas fa-map-marker-alt me-1"></i>Sites
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <span v-text="siteDetail.site.name"></span>
                                    </li>
                                </ol>
                            </nav>
                            <h1 class="h2 mb-2">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <span v-text="siteDetail.site.name"></span>
                                <span v-if="siteDetail.site.is_private" class="badge bg-warning ms-2">
                                    <i class="fas fa-lock me-1"></i>Privé
                                </span>
                            </h1>
                            <p class="text-muted mb-0" v-if="siteDetail.site.description" v-text="siteDetail.site.description"></p>
                            <p class="text-muted mb-0" v-else><em>Aucune description</em></p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-outline-secondary me-2" @click="backToSites">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </button>
                            <button class="btn btn-success me-2" @click="showSiteMap(siteDetail.site)" title="Carte détaillée avec GPS des plantes">
                                <i class="fas fa-map-marked-alt me-1"></i>Carte GPS détaillée
                            </button>
                            <button class="btn btn-info me-2" @click="openSiteMapEditor(siteDetail.site)" title="Éditeur de plan du site">
                                <i class="fas fa-drawing-compass me-1"></i>Plan du Site
                            </button>
                            <button class="btn btn-primary" @click="editSiteAction(siteDetail.site)" v-if="user.isAuthenticated && (user.id === siteDetail.site.owner?.id || user.isStaff)">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </button>
                        </div>
                    </div>

                    <!-- Site Information Cards -->
                    <div class="row mb-4">
                        <!-- Basic Information -->
                        <div class="col-lg-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Informations générales
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Environnement:</strong></div>
                                        <div class="col-8">
                                            <span class="badge bg-secondary" v-text="getEnvironmentLabel(siteDetail.site.environment)"></span>
                                        </div>
                                    </div>
                                    <div class="row mb-2" v-if="siteDetail.site.soil_type">
                                        <div class="col-4"><strong>Sol:</strong></div>
                                        <div class="col-8" v-text="siteDetail.site.soil_type"></div>
                                    </div>
                                    <div class="row mb-2" v-if="siteDetail.site.exposure">
                                        <div class="col-4"><strong>Exposition:</strong></div>
                                        <div class="col-8" v-text="siteDetail.site.exposure"></div>
                                    </div>
                                    <div class="row mb-2" v-if="siteDetail.site.climate_zone">
                                        <div class="col-4"><strong>Zone climatique:</strong></div>
                                        <div class="col-8" v-text="siteDetail.site.climate_zone"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Propriétaire:</strong></div>
                                        <div class="col-8">
                                            <i class="fas fa-user me-1"></i>
                                            <span v-text="siteDetail.site.owner?.username || 'Non défini'"></span>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Créé le:</strong></div>
                                        <div class="col-8" v-text="formatDate(siteDetail.site.created_at)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location Information -->
                        <div class="col-lg-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-globe me-2"></i>Localisation
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Latitude:</strong></div>
                                        <div class="col-8">
                                            <code v-text="siteDetail.site.latitude ? parseFloat(siteDetail.site.latitude).toFixed(6) : 'N/A'"></code>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Longitude:</strong></div>
                                        <div class="col-8">
                                            <code v-text="siteDetail.site.longitude ? parseFloat(siteDetail.site.longitude).toFixed(6) : 'N/A'"></code>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Altitude:</strong></div>
                                        <div class="col-8">
                                            <span v-if="siteDetail.site.altitude">
                                                <span v-text="siteDetail.site.altitude"></span> m
                                            </span>
                                            <span v-else class="text-muted">Non définie</span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <!-- Mini carte pourrait être ajoutée ici -->
                                        <small class="text-muted">
                                            <i class="fas fa-map me-1"></i>
                                            Coordonnées GPS disponibles pour cartographie
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-leaf fa-2x text-success mb-2"></i>
                                    <h3 class="mb-1" v-text="siteDetail.plantsCount || 0"></h3>
                                    <p class="text-muted mb-0">Plantes totales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-eye fa-2x text-info mb-2"></i>
                                    <h3 class="mb-1" v-text="siteDetail.totalObservations || 0"></h3>
                                    <p class="text-muted mb-0">Observations totales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-table fa-2x text-primary mb-2"></i>
                                    <h3 class="mb-1" v-text="siteDetail.pagination.count || 0"></h3>
                                    <p class="text-muted mb-0">Plantes affichées</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plants Management Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-table me-2"></i>Gestion des plantes
                                            </h5>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <button class="btn btn-sm btn-primary" @click="openModal('plant')" v-if="user.isAuthenticated">
                                                <i class="fas fa-plus me-1"></i>Ajouter une plante
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filters -->
                                <div class="card-body border-bottom">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <input type="text"
                                                   class="form-control form-control-sm"
                                                   v-model="siteDetail.filters.search"
                                                   @keyup.enter="applySiteDetailFilters"
                                                   placeholder="Rechercher...">
                                        </div>
                                        <div class="col-md-2">
                                            <select class="form-select form-select-sm" v-model="siteDetail.filters.category">
                                                <option value="">Toutes catégories</option>
                                                <option v-for="cat in categories" :key="cat.id" :value="cat.id" v-text="cat.name"></option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select class="form-select form-select-sm" v-model="siteDetail.filters.status">
                                                <option value="">Tous statuts</option>
                                                <option value="alive">Vivante</option>
                                                <option value="dead">Morte</option>
                                                <option value="replaced">Remplacée</option>
                                                <option value="removed">Retirée</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select class="form-select form-select-sm" v-model="siteDetail.filters.health_status">
                                                <option value="">Toutes santés</option>
                                                <option value="excellent">Excellente</option>
                                                <option value="good">Bonne</option>
                                                <option value="fair">Moyenne</option>
                                                <option value="poor">Mauvaise</option>
                                                <option value="dead">Morte</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <button type="button" class="btn btn-outline-primary" @click="applySiteDetailFilters">
                                                    <i class="fas fa-filter me-1"></i>Filtrer
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" @click="resetSiteDetailFilters">
                                                    <i class="fas fa-times me-1"></i>Réinitialiser
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body p-0">
                                    <!-- Empty State -->
                                    <div v-if="siteDetail.plants.length === 0 && !siteDetail.loading" class="text-center py-5">
                                        <i class="fas fa-seedling fa-3x text-muted mb-3"></i>
                                        <h5>Aucune plante trouvée</h5>
                                        <p class="text-muted">Aucune plante ne correspond à vos critères de recherche.</p>
                                        <button class="btn btn-primary" @click="openModal('plant')" v-if="user.isAuthenticated">
                                            <i class="fas fa-plus me-1"></i>Ajouter une plante
                                        </button>
                                    </div>

                                    <!-- Plants Table -->
                                    <div v-else class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th @click="sortSitePlants('name')" style="cursor: pointer;">
                                                        Nom <i class="fas" :class="getSortIcon('name')"></i>
                                                    </th>
                                                    <th>Taxon</th>
                                                    <th @click="sortSitePlants('category')" style="cursor: pointer;">
                                                        Catégorie <i class="fas" :class="getSortIcon('category')"></i>
                                                    </th>
                                                    <th @click="sortSitePlants('status')" style="cursor: pointer;">
                                                        Statut <i class="fas" :class="getSortIcon('status')"></i>
                                                    </th>
                                                    <th @click="sortSitePlants('health_status')" style="cursor: pointer;">
                                                        Santé <i class="fas" :class="getSortIcon('health_status')"></i>
                                                    </th>
                                                    <th>Position</th>
                                                    <th @click="sortSitePlants('planting_date')" style="cursor: pointer;">
                                                        Plantation <i class="fas" :class="getSortIcon('planting_date')"></i>
                                                    </th>
                                                    <th>Âge</th>
                                                    <th @click="sortSitePlants('observations_count')" style="cursor: pointer;">
                                                        Obs. <i class="fas" :class="getSortIcon('observations_count')"></i>
                                                    </th>
                                                    <th @click="sortSitePlants('photos_count')" style="cursor: pointer;">
                                                        Photos <i class="fas" :class="getSortIcon('photos_count')"></i>
                                                    </th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="plant in siteDetail.plants" :key="plant.id">
                                                    <td>
                                                        <strong v-text="plant.name"></strong>
                                                        <span v-if="plant.is_private" class="badge bg-warning badge-sm ms-1" title="Privé">
                                                            <i class="fas fa-lock"></i>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <em v-if="plant.taxon" v-text="plant.taxon.binomial_name" class="text-muted small"></em>
                                                        <span v-else class="text-muted">-</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary" v-text="plant.category ? plant.category.name : '-'"></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge"
                                                              :class="{
                                                                  'bg-success': plant.status === 'alive',
                                                                  'bg-danger': plant.status === 'dead',
                                                                  'bg-warning': plant.status === 'replaced',
                                                                  'bg-secondary': plant.status === 'removed'
                                                              }"
                                                              v-text="getStatusLabel(plant.status)"></span>
                                                    </td>
                                                    <td>
                                                        <span v-if="plant.health_status"
                                                              class="badge"
                                                              :class="{
                                                                  'bg-success': plant.health_status === 'excellent',
                                                                  'bg-info': plant.health_status === 'good',
                                                                  'bg-warning': plant.health_status === 'fair',
                                                                  'bg-danger': plant.health_status === 'poor' || plant.health_status === 'dead'
                                                              }"
                                                              v-text="getHealthLabel(plant.health_status)"></span>
                                                        <span v-else class="text-muted">-</span>
                                                    </td>
                                                    <td>
                                                        <span v-if="plant.position && plant.position.label" class="small">
                                                            <i class="fas fa-map-pin me-1"></i>
                                                            <span v-text="plant.position.label"></span>
                                                        </span>
                                                        <span v-else class="text-muted">-</span>
                                                    </td>
                                                    <td>
                                                        <span v-if="plant.planting_date" v-text="formatDate(plant.planting_date)" class="small"></span>
                                                        <span v-else class="text-muted">-</span>
                                                    </td>
                                                    <td>
                                                        <span v-if="plant.planting_date || plant.age_years" class="small" v-text="computePlantAge(plant)"></span>
                                                        <span v-else class="text-muted">-</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-info" v-text="plant.observations_count || 0"></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary" v-text="plant.photos_count || 0"></span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button class="btn btn-outline-primary btn-sm"
                                                                    @click="viewPlant(plant)"
                                                                    title="Voir les détails">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-outline-secondary btn-sm"
                                                                    @click="editPlant(plant)"
                                                                    v-if="user.isAuthenticated && (user.id === plant.owner_id || user.isStaff)"
                                                                    title="Modifier">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Pagination -->
                                <div v-if="siteDetail.pagination.total_pages > 1" class="card-footer">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <p class="mb-0 small text-muted">
                                                Affichage de <strong v-text="((siteDetail.pagination.current_page - 1) * siteDetail.filters.page_size) + 1"></strong>
                                                à <strong v-text="Math.min(siteDetail.pagination.current_page * siteDetail.filters.page_size, siteDetail.pagination.count)"></strong>
                                                sur <strong v-text="siteDetail.pagination.count"></strong> plantes
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <nav aria-label="Plants pagination">
                                                <ul class="pagination pagination-sm justify-content-end mb-0">
                                                    <li class="page-item" :class="{disabled: !siteDetail.pagination.previous}">
                                                        <button class="page-link"
                                                                @click="changeSiteDetailPage(siteDetail.pagination.current_page - 1)"
                                                                :disabled="!siteDetail.pagination.previous">
                                                            Précédent
                                                        </button>
                                                    </li>
                                                    <li class="page-item"
                                                        v-for="page in Math.min(5, siteDetail.pagination.total_pages)"
                                                        :key="page"
                                                        :class="{active: page === siteDetail.pagination.current_page}">
                                                        <button class="page-link"
                                                                @click="changeSiteDetailPage(page)"
                                                                v-text="page"></button>
                                                    </li>
                                                    <li class="page-item" :class="{disabled: !siteDetail.pagination.next}">
                                                        <button class="page-link"
                                                                @click="changeSiteDetailPage(siteDetail.pagination.current_page + 1)"
                                                                :disabled="!siteDetail.pagination.next">
                                                            Suivant
                                                        </button>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Error State -->
                <div v-else class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h4>Site non trouvé</h4>
                    <p class="text-muted mb-4">Le site demandé n'existe pas ou n'est pas accessible.</p>
                    <button class="btn btn-primary" @click="backToSites">
                        <i class="fas fa-arrow-left me-1"></i>Retour aux sites
                    </button>
                </div>
            </div>

            <!-- Plants View -->
            <div v-if="currentView === 'plants'">
                <!-- Header -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <h2><i class="fas fa-leaf text-success me-2"></i>Gestion des Plantes</h2>
                        <p class="text-muted mb-0">
                            Découvrez et gérez votre collection botanique avec précision GPS
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <button class="btn btn-outline-primary btn-sm" @click="currentView = 'map'" title="Voir sur carte">
                                <i class="fas fa-map me-1"></i>Carte
                            </button>
                            <button class="btn btn-success" @click="openModal('plant')">
                                <i class="fas fa-plus me-1"></i>Ajouter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres de Recherche</h6>
                        <button class="btn btn-sm btn-outline-secondary" @click="resetPlantsFilters()" title="Réinitialiser les filtres">
                            <i class="fas fa-undo me-1"></i>Reset
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-4">
                                <label class="form-label">Recherche</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input v-model="plantsList.filters.q" type="text" class="form-control"
                                           placeholder="Nom, taxon, site..." @input="applyPlantsFilters">
                                    <button class="btn btn-outline-secondary" type="button"
                                            @click="plantsList.filters.q = ''; applyPlantsFilters()"
                                            v-if="plantsList.filters.q">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Site Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Site</label>
                                <select v-model="plantsList.filters.site" class="form-select" @change="applyPlantsFilters">
                                    <option value="">Tous les sites</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id" v-text="site.name"></option>
                                </select>
                            </div>

                            <!-- Category Filter -->
                            <div class="col-md-2">
                                <label class="form-label">Catégorie</label>
                                <select v-model="plantsList.filters.category" class="form-select" @change="applyPlantsFilters">
                                    <option value="">Toutes</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id" v-text="category.name"></option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <label class="form-label">Statut</label>
                                <select v-model="plantsList.filters.status" class="form-select" @change="applyPlantsFilters">
                                    <option value="">Tous</option>
                                    <option value="alive">Vivant</option>
                                    <option value="dead">Mort</option>
                                    <option value="removed">Retiré</option>
                                    <option value="replaced">Remplacé</option>
                                </select>
                            </div>

                            <!-- Health Status Filter -->
                            <div class="col-md-2">
                                <label class="form-label">Santé</label>
                                <select v-model="plantsList.filters.health_status" class="form-select" @change="applyPlantsFilters">
                                    <option value="">Tous</option>
                                    <option value="excellent">Excellent</option>
                                    <option value="good">Bon</option>
                                    <option value="fair">Correct</option>
                                    <option value="poor">Mauvais</option>
                                    <option value="dead">Mort</option>
                                </select>
                            </div>

                            <!-- Has Observations Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Observations</label>
                                <select v-model="plantsList.filters.has_observations" class="form-select" @change="applyPlantsFilters">
                                    <option :value="null">Toutes</option>
                                    <option :value="true">Avec observations</option>
                                    <option :value="false">Sans observations</option>
                                </select>
                            </div>

                            <!-- Has Photos Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Photos</label>
                                <select v-model="plantsList.filters.has_photos" class="form-select" @change="applyPlantsFilters">
                                    <option :value="null">Toutes</option>
                                    <option :value="true">Avec photos</option>
                                    <option :value="false">Sans photos</option>
                                </select>
                            </div>

                            <!-- Page Size -->
                            <div class="col-md-2">
                                <label class="form-label">Par page</label>
                                <select v-model="plantsList.filters.page_size" class="form-select" @change="applyPlantsFilters">
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Summary -->
                <div class="row align-items-center mb-3">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <span class="badge bg-success fs-6"><span v-text="plantsList.pagination.count"></span></span>
                            plante(s) trouvée(s)
                        </h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            Page <span v-text="plantsList.pagination.current_page"></span> / <span v-text="plantsList.pagination.total_pages"></span>
                        </small>
                    </div>
                </div>

                <!-- Plants Table -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="min-width: 150px; cursor: pointer;" @click="sortPlantsList('name')">
                                            Nom <i :class="getPlantsListSortIcon('name')"></i>
                                        </th>
                                        <th style="min-width: 180px;">Taxon</th>
                                        <th style="min-width: 120px;">Site</th>
                                        <th style="min-width: 100px;">Catégorie</th>
                                        <th style="min-width: 80px;">Statut</th>
                                        <th style="min-width: 80px;">Santé</th>
                                        <th style="min-width: 100px; cursor: pointer;" @click="sortPlantsList('planting_date')">
                                            Planté <i :class="getPlantsListSortIcon('planting_date')"></i>
                                        </th>
                                        <th class="text-center" style="width: 60px; cursor: pointer;" @click="sortPlantsList('observations_count')">
                                            Obs. <i :class="getPlantsListSortIcon('observations_count')"></i>
                                        </th>
                                        <th style="min-width: 100px; cursor: pointer;" @click="sortPlantsList('last_observation_date')">
                                            Dernière Obs. <i :class="getPlantsListSortIcon('last_observation_date')"></i>
                                        </th>
                                        <th class="text-center" style="width: 60px; cursor: pointer;" @click="sortPlantsList('photos_count')">
                                            Photos <i :class="getPlantsListSortIcon('photos_count')"></i>
                                        </th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Loading State -->
                                    <tr v-if="plantsList.loading">
                                        <td colspan="11" class="text-center py-5">
                                            <div class="spinner-border text-success" role="status">
                                                <span class="visually-hidden">Chargement...</span>
                                            </div>
                                            <p class="mt-3 text-muted mb-0">Chargement des plantes...</p>
                                        </td>
                                    </tr>

                                    <!-- Empty State -->
                                    <tr v-else-if="plantsList.items.length === 0">
                                        <td colspan="11" class="text-center py-5">
                                            <i class="fas fa-seedling fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Aucune plante trouvée</h5>
                                            <p class="text-muted mb-3">
                                                Aucune plante ne correspond à vos critères de recherche.
                                            </p>
                                            <button class="btn btn-outline-secondary btn-sm" @click="resetPlantsFilters()">
                                                <i class="fas fa-undo me-1"></i>Réinitialiser les filtres
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Plants Rows -->
                                    <tr v-else v-for="plant in plantsList.items" :key="plant.id"
                                        :class="{
                                            'table-danger': plant.status === 'dead',
                                            'table-warning': plant.status === 'removed'
                                        }"
                                        style="cursor: pointer;"
                                        @click="navigateToPlant(plant.id)">

                                        <!-- Nom -->
                                        <td>
                                            <strong v-text="plant.name"></strong>
                                        </td>

                                        <!-- Taxon -->
                                        <td>
                                            <em class="text-primary" v-text="plant.taxon ? plant.taxon.binomial_name : '-'"></em><br>
                                            <small class="text-muted" v-text="plant.taxon ? (plant.taxon.common_name_fr || '') : ''"></small>
                                        </td>

                                        <!-- Site -->
                                        <td v-text="plant.site ? plant.site.name : '-'"></td>

                                        <!-- Catégorie -->
                                        <td v-text="plant.category ? plant.category.name : '-'"></td>

                                        <!-- Statut -->
                                        <td>
                                            <span class="badge" :class="{
                                                'bg-success': plant.status === 'alive',
                                                'bg-danger': plant.status === 'dead',
                                                'bg-warning text-dark': plant.status === 'removed',
                                                'bg-info': plant.status === 'replaced'
                                            }" v-text="plant.status_display || plant.status"></span>
                                        </td>

                                        <!-- Santé -->
                                        <td>
                                            <span v-if="plant.health_status" class="badge" :class="{
                                                'bg-success': plant.health_status === 'excellent',
                                                'bg-primary': plant.health_status === 'good',
                                                'bg-warning text-dark': plant.health_status === 'fair',
                                                'bg-danger': plant.health_status === 'poor' || plant.health_status === 'dead'
                                            }" v-text="plant.health_status_display || plant.health_status"></span>
                                            <span v-else class="text-muted">-</span>
                                        </td>

                                        <!-- Planté -->
                                        <td>
                                            <span v-if="plant.planting_date" v-text="formatDate(plant.planting_date)"></span>
                                            <span v-else class="text-muted">-</span>
                                        </td>

                                        <!-- Observations Count -->
                                        <td class="text-center">
                                            <span class="badge bg-info" v-text="plant.observations_count || 0"></span>
                                        </td>

                                        <!-- Dernière Observation -->
                                        <td>
                                            <span v-if="plant.last_observation_date" v-text="formatDate(plant.last_observation_date)"></span>
                                            <span v-else class="text-muted">-</span>
                                        </td>

                                        <!-- Photos Count -->
                                        <td class="text-center">
                                            <span v-if="plant.photos_count > 0" class="badge bg-secondary">
                                                <i class="fas fa-camera me-1"></i><span v-text="plant.photos_count"></span>
                                            </span>
                                            <span v-else class="text-muted">0</span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-center" @click.stop>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary btn-sm" @click="viewPlant(plant)" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button v-if="user.isAuthenticated && (user.id === plant.owner_id || user.isStaff)"
                                                        class="btn btn-outline-secondary btn-sm" @click="editPlant(plant)" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button v-if="user.isAuthenticated && (user.id === plant.owner_id || user.isStaff)"
                                                        class="btn btn-outline-danger btn-sm" @click="confirmDeletePlant(plant)" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="card-footer bg-light" v-if="plantsList.pagination.total_pages > 1">
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-outline-secondary btn-sm"
                                    @click="changePlantsPage(plantsList.pagination.current_page - 1)"
                                    :disabled="!plantsList.pagination.previous">
                                <i class="fas fa-chevron-left me-1"></i>Précédent
                            </button>
                            <span class="text-muted">
                                Page <span v-text="plantsList.pagination.current_page"></span> / <span v-text="plantsList.pagination.total_pages"></span>
                            </span>
                            <button class="btn btn-outline-secondary btn-sm"
                                    @click="changePlantsPage(plantsList.pagination.current_page + 1)"
                                    :disabled="!plantsList.pagination.next">
                                Suivant<i class="fas fa-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- General Map View -->
            <div v-if="currentView === 'map'">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-globe text-primary me-2"></i>Carte Générale</h2>
                        <p class="text-muted mb-0">
                            Visualisation interactive de tous les sites et plantes avec GPS
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn" :class="mapViewMode === 'sites' ? 'btn-primary' : 'btn-outline-primary'" @click="mapViewMode = 'sites'">
                                <i class="fas fa-map-marker-alt me-1"></i>Sites
                            </button>
                            <button type="button" class="btn" :class="mapViewMode === 'plants' ? 'btn-primary' : 'btn-outline-primary'" @click="mapViewMode = 'plants'">
                                <i class="fas fa-leaf me-1"></i>Plantes
                            </button>
                            <button type="button" class="btn" :class="mapViewMode === 'both' ? 'btn-primary' : 'btn-outline-primary'" @click="mapViewMode = 'both'">
                                <i class="fas fa-layer-group me-1"></i>Tout
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Map Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title text-primary">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <span v-text="mapStats.sites || 0"></span>
                                </h5>
                                <p class="card-text small mb-0">Sites géolocalisés</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h5 class="card-title text-success">
                                    <i class="fas fa-leaf me-1"></i>
                                    <span v-text="mapStats.plants || 0"></span>
                                </h5>
                                <p class="card-text small mb-0">Plantes avec GPS</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h5 class="card-title text-info">
                                    <i class="fas fa-crosshairs me-1"></i>
                                    <span v-text="mapStats.precision || '0'"></span>
                                </h5>
                                <p class="card-text small mb-0">Précision moy. (m)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h5 class="card-title text-warning">
                                    <i class="fas fa-search me-1"></i>
                                    <span v-text="mapStats.visible || 0"></span>
                                </h5>
                                <p class="card-text small mb-0">Visible sur carte</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Container -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-satellite me-2"></i>
                            Carte interactive - Vue <span v-text="mapViewMode === 'sites' ? 'Sites' : mapViewMode === 'plants' ? 'Plantes' : 'Complète'"></span>
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" @click="centerMapOnData()" title="Centrer sur les données">
                                <i class="fas fa-crosshairs"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" @click="toggleMapFullscreen()" title="Plein écran">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button class="btn btn-outline-success btn-sm" @click="refreshMapData()" title="Actualiser">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Loading State for Map -->
                        <div v-if="loading.map" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-3 text-muted">Chargement de la carte et des données GPS...</p>
                        </div>
                        
                        <!-- Map Container -->
                        <div v-else id="generalMap" style="height: 600px; width: 100%;"></div>
                    </div>
                    <div class="card-footer">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Navigation:</strong> Molette = Zoom | Glisser = Déplacer | Clic = Détails
                                    <br>
                                    <strong>Précision GPS:</strong> 
                                    <span class="precision-indicator ultra-high me-1"><i class="fas fa-circle"></i> &lt;1m</span>
                                    <span class="precision-indicator high me-1"><i class="fas fa-circle"></i> 1-5m</span>
                                    <span class="precision-indicator medium"><i class="fas fa-circle"></i> &gt;5m</span>
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-secondary" @click="exportMapData()" title="Exporter données">
                                        <i class="fas fa-download me-1"></i>Export
                                    </button>
                                    <button class="btn btn-outline-info" @click="showMapLegend()" title="Légende">
                                        <i class="fas fa-question-circle me-1"></i>Aide
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected item details -->
                <div v-if="selectedMapItem" class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i :class="selectedMapItem.type === 'site' ? 'fas fa-map-marker-alt text-primary' : 'fas fa-leaf text-success'" class="me-2"></i>
                            Détails - <span v-text="selectedMapItem.name"></span>
                            <button class="btn btn-sm btn-outline-secondary float-end" @click="selectedMapItem = null">
                                <i class="fas fa-times"></i>
                            </button>
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Site Details -->
                        <div v-if="selectedMapItem.type === 'site'">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Description:</strong> <span v-text="selectedMapItem.description || 'Aucune description'"></span></p>
                                    <p><strong>Environnement:</strong> <span v-text="getEnvironmentLabel(selectedMapItem.environment)"></span></p>
                                    <p><strong>Altitude:</strong> <span v-text="selectedMapItem.altitude || 'Non définie'"></span>m</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Coordonnées:</strong> <span v-text="selectedMapItem.coordinates[0].toFixed(6)"></span>, <span v-text="selectedMapItem.coordinates[1].toFixed(6)"></span></p>
                                    <p><strong>Plantes:</strong> <span v-text="selectedMapItem.plants_count || 0"></span> plante(s)</p>
                                    <p><strong>Propriétaire:</strong> <span v-text="selectedMapItem.owner?.username || 'Non défini'"></span></p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-primary btn-sm" @click="viewSiteDetail(selectedMapItem.id)">
                                    <i class="fas fa-eye me-1"></i>Voir détails
                                </button>
                                <button class="btn btn-success btn-sm" @click="showSiteMap(selectedMapItem)">
                                    <i class="fas fa-map me-1"></i>Carte détaillée
                                </button>
                            </div>
                        </div>
                        
                        <!-- Plant Details -->
                        <div v-else-if="selectedMapItem.type === 'plant'">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nom latin:</strong> <em><span v-text="selectedMapItem.taxon?.binomial_name || 'Non classifiée'"></span></em></p>
                                    <p><strong>Famille:</strong> <span v-text="selectedMapItem.taxon?.family || 'Non définie'"></span></p>
                                    <p><strong>Site:</strong> <span v-text="selectedMapItem.site_name"></span></p>
                                    <p><strong>État de santé:</strong> 
                                        <span class="badge" :class="getHealthBadgeClass(selectedMapItem.health_status)" v-text="getHealthLabel(selectedMapItem.health_status)"></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Coordonnées GPS:</strong> <span v-text="selectedMapItem.coordinates[0].toFixed(6)"></span>, <span v-text="selectedMapItem.coordinates[1].toFixed(6)"></span></p>
                                    <p><strong>Précision:</strong> <span v-text="selectedMapItem.gps_accuracy ? '±' + selectedMapItem.gps_accuracy + 'm' : 'Non définie'"></span></p>
                                    <p><strong>Hauteur:</strong> <span v-text="selectedMapItem.exact_height ? selectedMapItem.exact_height + 'm' : 'Non définie'"></span></p>
                                    <p><strong>Observations:</strong> <span v-text="selectedMapItem.observations_count || 0"></span></p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-primary btn-sm" @click="viewPlantDetail(selectedMapItem.id)">
                                    <i class="fas fa-eye me-1"></i>Voir détails
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" @click="editPlant(selectedMapItem)">
                                    <i class="fas fa-edit me-1"></i>Éditer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observations View (FULL IMPLEMENTATION) -->
            <div v-if="currentView === 'observations'" class="container-fluid py-4">
                <!-- Header -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <h2><i class="fas fa-eye text-info me-2"></i>Mes Observations</h2>
                        <p class="text-muted mb-0">
                            Suivez l'évolution phénologique de vos plantes
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button v-if="user.isAuthenticated" class="btn btn-primary" @click="showAddObservationModal = true">
                            <i class="fas fa-plus me-2"></i>Nouvelle Observation
                        </button>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres de Recherche</h6>
                        <button class="btn btn-sm btn-outline-secondary" @click="resetObservationsFilters()" title="Réinitialiser les filtres">
                            <i class="fas fa-undo me-1"></i>Reset
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-4">
                                <label class="form-label">Recherche</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input v-model="observationsList.filters.q" type="text" class="form-control"
                                           placeholder="Notes, plante, stade..." @input="applyObservationsFilters">
                                    <button class="btn btn-outline-secondary" type="button"
                                            @click="observationsList.filters.q = ''; applyObservationsFilters()"
                                            v-if="observationsList.filters.q">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Year Filter -->
                            <div class="col-md-2">
                                <label class="form-label">Année</label>
                                <select v-model="observationsList.filters.year" class="form-select" @change="applyObservationsFilters">
                                    <option value="">Toutes</option>
                                    <option v-for="year in observationsList.availableYears" :key="year" :value="year" v-text="year"></option>
                                </select>
                            </div>

                            <!-- Date From -->
                            <div class="col-md-2">
                                <label class="form-label">Date de début</label>
                                <input type="date" v-model="observationsList.filters.date_from" class="form-control" @change="applyObservationsFilters">
                            </div>

                            <!-- Date To -->
                            <div class="col-md-2">
                                <label class="form-label">Date de fin</label>
                                <input type="date" v-model="observationsList.filters.date_to" class="form-control" @change="applyObservationsFilters">
                            </div>

                            <!-- Site Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Site</label>
                                <select v-model="observationsList.filters.site" class="form-select" @change="applyObservationsFilters">
                                    <option value="">Tous les sites</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id" v-text="site.name"></option>
                                </select>
                            </div>

                            <!-- Category Filter -->
                            <div class="col-md-2">
                                <label class="form-label">Catégorie</label>
                                <select v-model="observationsList.filters.category" class="form-select" @change="applyObservationsFilters">
                                    <option value="">Toutes</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id" v-text="category.name"></option>
                                </select>
                            </div>

                            <!-- Stage Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Stade</label>
                                <select v-model="observationsList.filters.stage" class="form-select" @change="applyObservationsFilters">
                                    <option value="">Tous les stades</option>
                                    <option v-for="stage in phenologicalStages" :key="stage.id" :value="stage.stage_code" v-text="stage.stage_code + ' - ' + stage.stage_description"></option>
                                </select>
                            </div>

                            <!-- Has Photos Filter -->
                            <div class="col-md-2">
                                <label class="form-label">Photos</label>
                                <select v-model="observationsList.filters.has_photos" class="form-select" @change="applyObservationsFilters">
                                    <option :value="null">Toutes</option>
                                    <option :value="true">Avec photos</option>
                                    <option :value="false">Sans photos</option>
                                </select>
                            </div>

                            <!-- Is Validated Filter -->
                            <div class="col-md-2">
                                <label class="form-label">Validation</label>
                                <select v-model="observationsList.filters.is_validated" class="form-select" @change="applyObservationsFilters">
                                    <option :value="null">Toutes</option>
                                    <option :value="true">Validées</option>
                                    <option :value="false">Non validées</option>
                                </select>
                            </div>

                            <!-- Page Size -->
                            <div class="col-md-2">
                                <label class="form-label">Par page</label>
                                <select v-model="observationsList.filters.page_size" class="form-select" @change="applyObservationsFilters">
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Summary -->
                <div class="row align-items-center mb-3">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <span class="badge bg-info fs-6"><span v-text="observationsList.pagination.count"></span></span>
                            observation(s) trouvée(s)
                        </h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            Page <span v-text="observationsList.pagination.current_page"></span> / <span v-text="observationsList.pagination.total_pages"></span>
                        </small>
                    </div>
                </div>

                <!-- Observations Table -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="min-width: 100px; cursor: pointer;" @click="sortObservationsList('observation_date')">
                                            Date <i :class="getObservationsListSortIcon('observation_date')"></i>
                                        </th>
                                        <th style="min-width: 150px;">Plante</th>
                                        <th style="min-width: 120px;">Site</th>
                                        <th style="min-width: 180px;">Stade phénologique</th>
                                        <th style="min-width: 80px;">Intensité</th>
                                        <th style="min-width: 100px;">Météo</th>
                                        <th class="text-center" style="width: 60px;">Photos</th>
                                        <th class="text-center" style="width: 80px;">Validée</th>
                                        <th class="text-center" style="width: 80px;">Public</th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Loading State -->
                                    <tr v-if="observationsList.loading">
                                        <td colspan="10" class="text-center py-5">
                                            <div class="spinner-border text-info" role="status">
                                                <span class="visually-hidden">Chargement...</span>
                                            </div>
                                            <p class="mt-3 text-muted mb-0">Chargement des observations...</p>
                                        </td>
                                    </tr>

                                    <!-- Empty State -->
                                    <tr v-else-if="observationsList.items.length === 0">
                                        <td colspan="10" class="text-center py-5">
                                            <i class="fas fa-eye-slash fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Aucune observation trouvée</h5>
                                            <p class="text-muted mb-3">
                                                Aucune observation ne correspond à vos critères de recherche.
                                            </p>
                                            <button class="btn btn-outline-secondary btn-sm" @click="resetObservationsFilters()">
                                                <i class="fas fa-undo me-1"></i>Réinitialiser les filtres
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Observations Rows -->
                                    <tr v-else v-for="obs in observationsList.items" :key="obs.id"
                                        style="cursor: pointer;"
                                        @click="viewObservationDetail(obs.id)">

                                        <!-- Date -->
                                        <td v-text="formatDate(obs.observation_date)"></td>

                                        <!-- Plante -->
                                        <td>
                                            <strong v-text="obs.plant ? obs.plant.name : '-'"></strong><br>
                                            <small class="text-muted" v-text="obs.plant && obs.plant.taxon ? obs.plant.taxon.binomial_name : ''"></small>
                                        </td>

                                        <!-- Site -->
                                        <td v-text="obs.plant && obs.plant.site ? obs.plant.site.name : '-'"></td>

                                        <!-- Stade phénologique -->
                                        <td>
                                            <span class="badge bg-info me-1" v-text="obs.phenological_stage ? obs.phenological_stage.stage_code : '-'"></span>
                                            <span v-text="obs.phenological_stage ? obs.phenological_stage.stage_description : '-'"></span>
                                        </td>

                                        <!-- Intensité -->
                                        <td>
                                            <div v-if="obs.intensity" class="progress" style="width: 80px; height: 20px;">
                                                <div class="progress-bar" :style="{ width: (obs.intensity * 20) + '%' }"
                                                     :class="getIntensityClass(obs.intensity)">
                                                    <span v-text="obs.intensity + '/5'"></span>
                                                </div>
                                            </div>
                                            <span v-else class="text-muted">-</span>
                                        </td>

                                        <!-- Météo -->
                                        <td>
                                            <span v-if="obs.weather_condition">
                                                <i :class="getWeatherIcon(obs.weather_condition)"></i>
                                                <span v-text="obs.weather_condition"></span>
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                            <br>
                                            <small v-if="obs.temperature" class="text-muted" v-text="obs.temperature + '°C'"></small>
                                        </td>

                                        <!-- Photos Count -->
                                        <td class="text-center">
                                            <span v-if="obs.photos_count > 0" class="badge bg-secondary">
                                                <i class="fas fa-camera me-1"></i><span v-text="obs.photos_count"></span>
                                            </span>
                                            <span v-else class="text-muted">0</span>
                                        </td>

                                        <!-- Validée -->
                                        <td class="text-center">
                                            <span v-if="obs.is_validated" class="badge bg-success" title="Validée">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <span v-else class="badge bg-warning" title="Non validée">
                                                <i class="fas fa-clock"></i>
                                            </span>
                                        </td>

                                        <!-- Public -->
                                        <td class="text-center">
                                            <span v-if="obs.is_public" class="badge bg-success" title="Public">
                                                <i class="fas fa-globe"></i>
                                            </span>
                                            <span v-else class="badge bg-secondary" title="Privé">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-center" @click.stop>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary btn-sm" @click="viewObservationDetail(obs.id)" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button v-if="user.isAuthenticated && (obs.observer && obs.observer.name === user.username || user.isStaff)"
                                                        class="btn btn-outline-secondary btn-sm" @click="openEditObservationModal(obs)" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button v-if="user.isAuthenticated && (obs.observer && obs.observer.name === user.username || user.isStaff)"
                                                        class="btn btn-outline-danger btn-sm" @click="confirmDeleteObservation(obs)" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="card-footer bg-light" v-if="observationsList.pagination.total_pages > 1">
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-outline-secondary btn-sm"
                                    @click="changeObservationsPage(observationsList.pagination.current_page - 1)"
                                    :disabled="!observationsList.pagination.previous">
                                <i class="fas fa-chevron-left me-1"></i>Précédent
                            </button>
                            <span class="text-muted">
                                Page <span v-text="observationsList.pagination.current_page"></span> / <span v-text="observationsList.pagination.total_pages"></span>
                            </span>
                            <button class="btn btn-outline-secondary btn-sm"
                                    @click="changeObservationsPage(observationsList.pagination.current_page + 1)"
                                    :disabled="!observationsList.pagination.next">
                                Suivant<i class="fas fa-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observation Detail View -->
            <div v-if="currentView === 'observation-detail' && currentObservation" class="container-fluid py-4">
                <div class="row mb-4">
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button class="btn btn-outline-secondary btn-sm mb-2" @click="backToObservations">
                                    <i class="fas fa-arrow-left me-1"></i>Retour aux observations
                                </button>
                                <h2><i class="fas fa-eye me-2 text-info"></i>Détail de l'observation</h2>
                            </div>
                            <div>
                                <button v-if="user.isAuthenticated && user.isStaff && !currentObservation.is_validated" class="btn btn-success me-2" @click="validateObservation(currentObservation.id)">
                                    <i class="fas fa-check-circle me-1"></i>Valider
                                </button>
                                <button v-if="user.isAuthenticated && currentObservation.observer && (currentObservation.observer.name === user.username || user.isStaff)" class="btn btn-warning me-2" @click="openEditObservationModal(currentObservation)">
                                    <i class="fas fa-edit me-1"></i>Modifier
                                </button>
                                <button v-if="user.isAuthenticated && currentObservation.observer && (currentObservation.observer.name === user.username || user.isStaff)" class="btn btn-danger" @click="confirmDeleteObservation(currentObservation)">
                                    <i class="fas fa-trash me-1"></i>Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Main Information Card -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations principales</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Plante observée</h6>
                                        <p class="mb-1"><strong v-text="currentObservation.plant ? currentObservation.plant.name : 'N/A'"></strong></p>
                                        <p class="text-muted small" v-if="currentObservation.plant && currentObservation.plant.taxon">
                                            <em v-text="currentObservation.plant.taxon.binomial_name"></em>
                                        </p>
                                        <p class="text-muted small" v-if="currentObservation.plant && currentObservation.plant.site">
                                            <i class="fas fa-map-marker-alt me-1"></i><span v-text="currentObservation.plant.site.name"></span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Date d'observation</h6>
                                        <p><i class="fas fa-calendar me-2"></i><span v-text="formatDate(currentObservation.observation_date)"></span></p>
                                        <p class="text-muted small" v-if="currentObservation.time_of_day">
                                            <i class="fas fa-clock me-1"></i><span v-text="currentObservation.time_of_day"></span>
                                        </p>
                                    </div>
                                </div>

                                <hr>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Stade phénologique</h6>
                                        <p>
                                            <span class="badge bg-info me-2" v-text="currentObservation.phenological_stage ? currentObservation.phenological_stage.stage_code : '-'"></span>
                                            <span v-text="currentObservation.phenological_stage ? currentObservation.phenological_stage.stage_description : 'N/A'"></span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Intensité du phénomène</h6>
                                        <div v-if="currentObservation.intensity" class="progress" style="height: 25px;">
                                            <div class="progress-bar" :style="{ width: (currentObservation.intensity * 20) + '%' }"
                                                 :class="getIntensityClass(currentObservation.intensity)"
                                                 v-text="currentObservation.intensity + '/5'">
                                            </div>
                                        </div>
                                        <p v-else>-</p>
                                    </div>
                                </div>

                                <div v-if="currentObservation.notes" class="row mb-3">
                                    <div class="col-12">
                                        <h6 class="text-muted">Notes</h6>
                                        <p class="border-start border-info border-3 ps-3" v-text="currentObservation.notes"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Weather Conditions Card -->
                        <div class="card mb-4" v-if="currentObservation.weather_condition || currentObservation.temperature || currentObservation.humidity || currentObservation.wind_speed">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-cloud-sun me-2"></i>Conditions météorologiques</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3" v-if="currentObservation.weather_condition">
                                        <h6 class="text-muted">Conditions</h6>
                                        <p>
                                            <i :class="getWeatherIcon(currentObservation.weather_condition)" class="fa-2x"></i><br>
                                            <span v-text="currentObservation.weather_condition"></span>
                                        </p>
                                    </div>
                                    <div class="col-md-3" v-if="currentObservation.temperature">
                                        <h6 class="text-muted">Température</h6>
                                        <p><i class="fas fa-thermometer-half me-2 text-danger"></i><span v-text="currentObservation.temperature + '°C'"></span></p>
                                    </div>
                                    <div class="col-md-3" v-if="currentObservation.humidity">
                                        <h6 class="text-muted">Humidité</h6>
                                        <p><i class="fas fa-tint me-2 text-primary"></i><span v-text="currentObservation.humidity + '%'"></span></p>
                                    </div>
                                    <div class="col-md-3" v-if="currentObservation.wind_speed">
                                        <h6 class="text-muted">Vent</h6>
                                        <p><i class="fas fa-wind me-2 text-info"></i><span v-text="currentObservation.wind_speed + ' km/h'"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Photos Gallery Card -->
                        <div class="card mb-4" v-if="observationPhotos.length > 0">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-images me-2"></i>Photos (<span v-text="observationPhotos.length"></span>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div v-for="(photo, index) in observationPhotos" :key="photo.id" class="col-md-4 col-sm-6">
                                        <div class="card h-100 shadow-sm">
                                            <img :src="photo.image_url || photo.image" :alt="photo.title || 'Photo observation'"
                                                 class="card-img-top" style="height: 150px; object-fit: cover; cursor: pointer;"
                                                 @click="openPhotoGallery(index)" title="Cliquer pour agrandir">
                                            <div class="card-body p-2">
                                                <small v-if="photo.title" class="d-block text-truncate fw-bold" v-text="photo.title"></small>
                                                <small class="text-muted d-block text-truncate" v-text="photo.photo_type"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Photo Button -->
                        <div v-if="user.isAuthenticated && currentObservation.observer && (currentObservation.observer.name === user.username || user.isStaff)" class="mb-4">
                            <button class="btn btn-outline-primary w-100" @click="openUploadPhotoModal(currentObservation.id)">
                                <i class="fas fa-camera me-2"></i>Ajouter une photo à cette observation
                            </button>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Metadata Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Métadonnées</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="text-muted">Visibilité</h6>
                                    <span v-if="currentObservation.is_public" class="badge bg-success">
                                        <i class="fas fa-globe me-1"></i>Publique
                                    </span>
                                    <span v-else class="badge bg-secondary">
                                        <i class="fas fa-lock me-1"></i>Privée
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-muted">Observateur</h6>
                                    <p><i class="fas fa-user me-2"></i><span v-text="currentObservation.observer ? currentObservation.observer.name : 'N/A'"></span></p>
                                </div>

                                <div class="mb-3" v-if="currentObservation.confidence_level">
                                    <h6 class="text-muted">Niveau de confiance</h6>
                                    <p v-text="currentObservation.confidence_level + '/5'"></p>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-muted">Validation</h6>
                                    <p v-if="currentObservation.is_validated">
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Validée
                                        </span>
                                    </p>
                                    <p v-else>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-clock me-1"></i>Non validée
                                        </span>
                                    </p>
                                    <p class="text-muted small" v-if="currentObservation.validated_by">
                                        Par <span v-text="currentObservation.validated_by.name || currentObservation.validated_by"></span><br>
                                        le <span v-text="formatDate(currentObservation.validation_date)"></span>
                                    </p>
                                </div>

                                <hr>

                                <div class="mb-2">
                                    <h6 class="text-muted">Créée le</h6>
                                    <p class="small" v-text="formatDate(currentObservation.created_at)"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Comparison Card (Tela Botanica) -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Comparaison nationale</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Comparer cette observation avec les données nationales ODS (Observatoire Des Saisons)
                                </p>
                                <button class="btn btn-warning btn-sm" @click="loadTelaComparison">
                                    <i class="fas fa-search me-1"></i>Comparer avec ODS
                                </button>
                                <div v-if="telaComparison" class="mt-3">
                                    <p class="text-muted small">
                                        <strong>Comparaison avec données nationales ODS</strong>
                                        <br>
                                        <span v-if="telaComparison.national_stats">
                                            Basé sur <span v-text="telaComparison.national_stats.count"></span> observations
                                            (<span v-text="telaComparison.national_stats.year_range"></span>)
                                        </span>
                                    </p>
                                    <div class="alert" :class="{
                                        'alert-success': telaComparison.status === 'early',
                                        'alert-danger': telaComparison.status === 'late',
                                        'alert-info': telaComparison.status === 'normal'
                                    }">
                                        <p class="mb-0">
                                            <i class="fas" :class="{
                                                'fa-arrow-up': telaComparison.status === 'early',
                                                'fa-arrow-down': telaComparison.status === 'late',
                                                'fa-equals': telaComparison.status === 'normal'
                                            }"></i>
                                            <strong v-text="telaComparison.status_label"></strong>
                                            <span v-if="telaComparison.difference_days" class="ms-1">
                                                (<span v-text="(telaComparison.status === 'late' ? '+' : '-') + Math.round(telaComparison.difference_days)"></span> jours)
                                            </span>
                                        </p>
                                        <small v-if="telaComparison.national_stats" class="text-muted">
                                            Moyenne nationale : jour <span v-text="Math.round(telaComparison.national_stats.mean_day)"></span>
                                            | Votre observation : jour <span v-text="telaComparison.user_day"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analysis View -->
            <div v-if="currentView === 'analysis'" class="container-fluid py-4">
                <!-- Header -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><i class="fas fa-chart-line me-2 text-warning"></i>Analyses et Statistiques</h2>
                        <p class="text-muted mb-0">Visualisez vos observations sous forme de graphiques interactifs</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 fw-bold">Année :</label>
                        <select v-model.number="analysisYear" class="form-select" style="width: 120px; font-size: 1.1rem;">
                            <option v-for="y in yearRange" :key="y" :value="y" v-text="y"></option>
                        </select>
                    </div>
                </div>

                <!-- Statistics Cards Row -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center py-3">
                                <div class="text-primary mb-1"><i class="fas fa-eye fa-2x"></i></div>
                                <h3 class="mb-0" v-text="analysisStats.totalObservations"></h3>
                                <small class="text-muted">Observations</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center py-3">
                                <div class="text-success mb-1"><i class="fas fa-seedling fa-2x"></i></div>
                                <h3 class="mb-0" v-text="analysisStats.uniquePlants"></h3>
                                <small class="text-muted">Plantes suivies</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center py-3">
                                <div class="text-info mb-1"><i class="fas fa-map-marker-alt fa-2x"></i></div>
                                <h3 class="mb-0" v-text="analysisStats.uniqueSites"></h3>
                                <small class="text-muted">Sites observés</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center py-3">
                                <div class="text-warning mb-1"><i class="fas fa-check-circle fa-2x"></i></div>
                                <h3 class="mb-0" v-text="analysisStats.validatedCount"></h3>
                                <small class="text-muted">Validées</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center py-3">
                                <div class="text-danger mb-1"><i class="fas fa-camera fa-2x"></i></div>
                                <h3 class="mb-0" v-text="analysisStats.withPhotosCount"></h3>
                                <small class="text-muted">Avec photos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 1: Monthly + Stages -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Observations mensuelles (<span v-text="analysisYear"></span>)</h6>
                            </div>
                            <div class="card-body">
                                <div v-if="analysisData.monthly && analysisData.monthly.data.some(d => d > 0)" style="height: 350px;">
                                    <canvas id="monthlyChart"></canvas>
                                </div>
                                <div v-else class="text-center py-5 text-muted">
                                    <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                    <p>Aucune observation pour cette année</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-pie me-2 text-info"></i>Par stade phénologique</h6>
                            </div>
                            <div class="card-body">
                                <div v-if="analysisData.byStage.length > 0" style="height: 350px;">
                                    <canvas id="stageChart"></canvas>
                                </div>
                                <div v-else class="text-center py-5 text-muted">
                                    <i class="fas fa-chart-pie fa-3x mb-3"></i>
                                    <p>Aucune donnée</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Main Events + By Category -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-seedling me-2 text-success"></i>Phases phénologiques principales (BBCH)</h6>
                            </div>
                            <div class="card-body">
                                <div v-if="analysisData.byMainEvent.length > 0" style="height: 350px;">
                                    <canvas id="mainEventChart"></canvas>
                                </div>
                                <div v-else class="text-center py-5 text-muted">
                                    <i class="fas fa-leaf fa-3x mb-3"></i>
                                    <p>Aucune donnée de phase principale</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-layer-group me-2 text-warning"></i>Par catégorie de plante</h6>
                            </div>
                            <div class="card-body">
                                <div v-if="analysisData.byCategory.length > 0" style="height: 350px;">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                                <div v-else class="text-center py-5 text-muted">
                                    <i class="fas fa-th-large fa-3x mb-3"></i>
                                    <p>Aucune donnée</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: By Site + Top Plants -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Observations par site</h6>
                            </div>
                            <div class="card-body">
                                <div v-if="analysisData.bySite.length > 0" style="height: 300px;">
                                    <canvas id="siteChart"></canvas>
                                </div>
                                <div v-else class="text-center py-5 text-muted">
                                    <i class="fas fa-map fa-3x mb-3"></i>
                                    <p>Aucune donnée par site</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Top 10 plantes les plus observées</h6>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="analysisData.topPlants.length > 0" class="table-responsive">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">#</th>
                                                <th>Plante</th>
                                                <th>Taxon</th>
                                                <th class="text-end pe-3">Obs.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(p, idx) in analysisData.topPlants" :key="p.id"
                                                @click="viewPlantDetail(p.id)" style="cursor: pointer;">
                                                <td class="ps-3">
                                                    <span v-if="idx === 0" class="text-warning"><i class="fas fa-medal"></i></span>
                                                    <span v-else-if="idx === 1" class="text-secondary"><i class="fas fa-medal"></i></span>
                                                    <span v-else-if="idx === 2" class="text-danger"><i class="fas fa-medal"></i></span>
                                                    <span v-else v-text="idx + 1"></span>
                                                </td>
                                                <td><strong v-text="p.name"></strong></td>
                                                <td><em class="text-muted small" v-text="p.common_name_fr || p.binomial_name"></em></td>
                                                <td class="text-end pe-3">
                                                    <span class="badge bg-primary rounded-pill" v-text="p.count"></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="text-center py-5 text-muted">
                                    <i class="fas fa-leaf fa-3x mb-3"></i>
                                    <p>Aucune plante observée</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 4: Weather + Intensity + Recent -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-cloud-sun me-2 text-info"></i>Conditions météo</h6>
                            </div>
                            <div class="card-body">
                                <div v-if="analysisData.byWeather.length > 0">
                                    <div v-for="w in analysisData.byWeather" :key="w.weather_condition" class="d-flex align-items-center mb-2">
                                        <span class="me-2" style="width: 24px; text-align: center;">
                                            <i class="fas" :class="{
                                                'fa-sun text-warning': w.weather_condition === 'ensoleillé',
                                                'fa-cloud text-secondary': w.weather_condition === 'nuageux',
                                                'fa-cloud-rain text-primary': w.weather_condition === 'pluvieux',
                                                'fa-wind text-info': w.weather_condition === 'venteux',
                                                'fa-bolt text-danger': w.weather_condition === 'orageux'
                                            }"></i>
                                        </span>
                                        <span class="flex-grow-1 text-capitalize" v-text="w.weather_condition"></span>
                                        <div class="flex-grow-1 mx-2">
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-info" :style="{ width: (w.count / Math.max(...analysisData.byWeather.map(x => x.count)) * 100) + '%' }"></div>
                                            </div>
                                        </div>
                                        <span class="badge bg-secondary rounded-pill" v-text="w.count"></span>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-muted">
                                    <i class="fas fa-cloud fa-2x mb-2"></i>
                                    <p class="mb-0 small">Aucune donnée météo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-signal me-2 text-success"></i>Distribution d'intensité</h6>
                            </div>
                            <div class="card-body">
                                <div v-if="analysisData.byIntensity.length > 0">
                                    <div v-for="item in analysisData.byIntensity" :key="item.intensity" class="d-flex align-items-center mb-2">
                                        <span class="me-2 small" style="width: 120px;">
                                            <span v-if="item.intensity === 1">Très faible</span>
                                            <span v-else-if="item.intensity === 2">Faible</span>
                                            <span v-else-if="item.intensity === 3">Modéré</span>
                                            <span v-else-if="item.intensity === 4">Fort</span>
                                            <span v-else-if="item.intensity === 5">Très fort</span>
                                        </span>
                                        <div class="flex-grow-1 mx-2">
                                            <div class="progress" style="height: 12px;">
                                                <div class="progress-bar" :class="{
                                                    'bg-secondary': item.intensity === 1,
                                                    'bg-info': item.intensity === 2,
                                                    'bg-primary': item.intensity === 3,
                                                    'bg-warning': item.intensity === 4,
                                                    'bg-danger': item.intensity === 5
                                                }" :style="{ width: (item.count / Math.max(...analysisData.byIntensity.map(x => x.count)) * 100) + '%' }"></div>
                                            </div>
                                        </div>
                                        <span class="badge bg-secondary rounded-pill" v-text="item.count"></span>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-muted">
                                    <i class="fas fa-signal fa-2x mb-2"></i>
                                    <p class="mb-0 small">Aucune donnée d'intensité</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-clock me-2 text-primary"></i>Observations récentes</h6>
                            </div>
                            <div class="card-body p-0">
                                <div v-if="analysisData.recent.length > 0">
                                    <div v-for="obs in analysisData.recent" :key="obs.id"
                                         class="d-flex align-items-center px-3 py-2 border-bottom">
                                        <div class="flex-grow-1">
                                            <strong class="small" v-text="obs.plant_name"></strong><br>
                                            <small class="text-muted" v-text="obs.stage || obs.stage_code"></small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted" v-text="formatDate(obs.date)"></small><br>
                                            <small class="text-muted" v-if="obs.observer" v-text="obs.observer"></small>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0 small">Aucune observation récente</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Add Site Modal -->
        <div class="modal fade" :class="{ 'show': showAddSiteModal }" id="addSiteModal" tabindex="-1" v-show="showAddSiteModal" @click.self="closeModal()" :style="{ display: showAddSiteModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus me-2"></i>Ajouter un nouveau site
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="addSite">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="siteName" class="form-label">Nom du site *</label>
                                    <input v-model="newSite.name" type="text" class="form-control" id="siteName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="siteEnvironment" class="form-label">Environnement *</label>
                                    <select v-model="newSite.environment" class="form-select" id="siteEnvironment" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="urban">Urbain</option>
                                        <option value="suburban">Périurbain</option>
                                        <option value="rural">Rural</option>
                                        <option value="forest">Forêt</option>
                                        <option value="garden">Jardin/Parc</option>
                                        <option value="natural">Naturel</option>
                                        <option value="agricultural">Agricole</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="siteDescription" class="form-label">Description</label>
                                <textarea v-model="newSite.description" class="form-control" id="siteDescription" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="siteLatitude" class="form-label">Latitude *</label>
                                    <input v-model="newSite.latitude" type="number" step="any" class="form-control" id="siteLatitude" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="siteLongitude" class="form-label">Longitude *</label>
                                    <input v-model="newSite.longitude" type="number" step="any" class="form-control" id="siteLongitude" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="siteAltitude" class="form-label">Altitude (m)</label>
                                    <input v-model="newSite.altitude" type="number" class="form-control" id="siteAltitude">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="siteSoil" class="form-label">Type de sol</label>
                                    <select v-model="newSite.soil_type" class="form-select" id="siteSoil">
                                        <option value="">Non spécifié</option>
                                        <option value="argileux">Argileux</option>
                                        <option value="sableux">Sableux</option>
                                        <option value="limoneux">Limoneux</option>
                                        <option value="calcaire">Calcaire</option>
                                        <option value="humifère">Humifère</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="siteExposure" class="form-label">Exposition</label>
                                    <select v-model="newSite.exposure" class="form-select" id="siteExposure">
                                        <option value="">Non spécifiée</option>
                                        <option value="nord">Nord</option>
                                        <option value="sud">Sud</option>
                                        <option value="est">Est</option>
                                        <option value="ouest">Ouest</option>
                                        <option value="sud-est">Sud-Est</option>
                                        <option value="sud-ouest">Sud-Ouest</option>
                                        <option value="nord-est">Nord-Est</option>
                                        <option value="nord-ouest">Nord-Ouest</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="siteClimate" class="form-label">Zone climatique</label>
                                    <select v-model="newSite.climate_zone" class="form-select" id="siteClimate">
                                        <option value="">Non spécifiée</option>
                                        <option value="océanique">Océanique</option>
                                        <option value="continental">Continental</option>
                                        <option value="méditerranéen">Méditerranéen</option>
                                        <option value="montagnard">Montagnard</option>
                                        <option value="semi-continental">Semi-continental</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input v-model="newSite.is_private" class="form-check-input" type="checkbox" id="sitePrivate">
                                <label class="form-check-label" for="sitePrivate">
                                    Site privé
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="addSite">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search View (Dedicated Search Page) -->
        <div v-if="currentView === 'search'" class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <!-- Search Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">
                            <i class="fas fa-search text-success me-2"></i>
                            Recherche Globale
                        </h1>
                    </div>

                    <!-- Search Input and Filters -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Search Input -->
                                <div class="col-md-8">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-success text-white">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="searchPage.query"
                                            @keydown.enter.prevent="searchPage.results.length > 0 && searchPage.selectedIndex >= 0 ? navigateToSearchResult(searchPage.results[searchPage.selectedIndex]) : performSearchPageSearch()"
                                            @keydown.down.prevent="searchPage.selectedIndex = Math.min(searchPage.selectedIndex + 1, searchPage.results.length - 1)"
                                            @keydown.up.prevent="searchPage.selectedIndex = Math.max(searchPage.selectedIndex - 1, 0)"
                                            placeholder="Rechercher des plantes, sites, observations, taxons..."
                                            ref="searchPageInput"
                                        />
                                        <button
                                            class="btn btn-success"
                                            type="button"
                                            @click="performSearchPageSearch()"
                                            :disabled="searchPage.loading"
                                        >
                                            <i class="fas" :class="searchPage.loading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                                            Rechercher
                                        </button>
                                        <button
                                            v-if="searchPage.query"
                                            class="btn btn-outline-secondary"
                                            type="button"
                                            @click="clearSearchPage()"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-keyboard me-1"></i>
                                        Utilisez les flèches ↑↓ pour naviguer, Entrée pour ouvrir
                                    </small>
                                </div>

                                <!-- Entity Type Filter -->
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-1">Type d'entité</label>
                                    <select
                                        class="form-select"
                                        v-model="searchPage.filters.type"
                                        @change="searchPage.query.trim().length >= 2 && performSearchPageSearch()"
                                    >
                                        <option value="all">Tous les types</option>
                                        <option value="plants">Plantes</option>
                                        <option value="sites">Sites</option>
                                        <option value="observations">Observations</option>
                                        <option value="taxons">Taxons</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Advanced Filters (Future-ready stub) -->
                            <div class="mt-3 pt-3 border-top" v-if="user.isAuthenticated">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                v-model="searchPage.filters.mine"
                                                @change="searchPage.query.trim().length >= 2 && performSearchPageSearch()"
                                                id="searchFilterMine"
                                            >
                                            <label class="form-check-label" for="searchFilterMine">
                                                Mes données uniquement
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4" v-if="searchPage.filters.type === 'observations' || searchPage.filters.type === 'all'">
                                        <label class="form-label small text-muted mb-1">Date de début</label>
                                        <input
                                            type="date"
                                            class="form-control form-control-sm"
                                            v-model="searchPage.filters.date_from"
                                            @change="searchPage.query.trim().length >= 2 && performSearchPageSearch()"
                                        />
                                    </div>
                                    <div class="col-md-4" v-if="searchPage.filters.type === 'observations' || searchPage.filters.type === 'all'">
                                        <label class="form-label small text-muted mb-1">Date de fin</label>
                                        <input
                                            type="date"
                                            class="form-control form-control-sm"
                                            v-model="searchPage.filters.date_to"
                                            @change="searchPage.query.trim().length >= 2 && performSearchPageSearch()"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search History -->
                    <div v-if="searchPage.history.length > 0" class="mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <small class="text-muted">Recherches récentes:</small>
                            <span
                                v-for="(historyQuery, index) in searchPage.history"
                                :key="index"
                                class="badge bg-light text-dark border clickable"
                                @click="searchPage.query = historyQuery; performSearchPageSearch()"
                                style="cursor: pointer;"
                            >
                                <i class="fas fa-history me-1"></i>
                                <span v-text="historyQuery"></span>
                            </span>
                            <button
                                class="btn btn-sm btn-link text-muted p-0"
                                @click="clearSearchHistory()"
                                title="Effacer l'historique"
                            >
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div v-if="searchPage.hasSearched">
                        <!-- Loading State -->
                        <div v-if="searchPage.loading" class="text-center py-5">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Recherche en cours...</span>
                            </div>
                            <p class="mt-3 text-muted">Recherche en cours...</p>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="searchPage.error" class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span v-text="searchPage.error"></span>
                        </div>

                        <!-- Results -->
                        <div v-else-if="searchPage.results.length > 0">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <span v-text="searchPage.count"></span> résultat(s) trouvé(s)
                                </h5>
                                <!-- Future: Export button stub -->
                                <button class="btn btn-sm btn-outline-secondary" disabled title="Fonctionnalité à venir">
                                    <i class="fas fa-download me-1"></i>
                                    Exporter (bientôt disponible)
                                </button>
                            </div>

                            <div class="list-group">
                                <a
                                    v-for="(result, index) in searchPage.results"
                                    :key="result.entity + '-' + result.id"
                                    href="#"
                                    class="list-group-item list-group-item-action"
                                    :class="{ 'active': index === searchPage.selectedIndex }"
                                    @click.prevent="navigateToSearchResult(result)"
                                    @mouseenter="searchPage.selectedIndex = index"
                                >
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <span
                                                    class="badge me-2"
                                                    :class="{
                                                        'bg-success': result.entity === 'plant',
                                                        'bg-primary': result.entity === 'site',
                                                        'bg-info': result.entity === 'observation',
                                                        'bg-warning': result.entity === 'taxon'
                                                    }"
                                                >
                                                    <i class="fas" :class="{
                                                        'fa-seedling': result.entity === 'plant',
                                                        'fa-map-marker-alt': result.entity === 'site',
                                                        'fa-eye': result.entity === 'observation',
                                                        'fa-leaf': result.entity === 'taxon'
                                                    }"></i>
                                                    <span v-text="getEntityLabel(result.entity)"></span>
                                                </span>
                                                <h6 class="mb-0" v-html="highlightSearchText(result.title, searchPage.query)"></h6>
                                            </div>
                                            <p class="mb-0 text-muted small" v-html="highlightSearchText(result.snippet, searchPage.query)"></p>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- No Results -->
                        <div v-else class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun résultat trouvé</h5>
                            <p class="text-muted">
                                Essayez de modifier votre recherche ou d'utiliser des termes différents
                            </p>
                        </div>
                    </div>

                    <!-- Initial State (No search performed yet) -->
                    <div v-else class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Recherchez dans toutes vos données</h4>
                        <p class="text-muted">
                            Entrez un terme de recherche pour trouver des plantes, sites, observations ou taxons
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!-- ADMIN PAGE (staff/superuser only)                             -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <div v-if="currentView === 'admin' && (user.isStaff || user.isSuperuser)" class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-cogs text-primary me-2"></i>Gestion des données
                </h1>
                <span class="badge bg-secondary"><i class="fas fa-shield-alt me-1"></i>Administration</span>
            </div>

            <!-- Alert message -->
            <div v-if="admin.message" :class="'alert alert-' + admin.messageType + ' alert-dismissible fade show'" role="alert">
                @{{ admin.message }}
                <button type="button" class="btn-close" @click="admin.message = null"></button>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: admin.activeTab === 'dashboard' }" href="#" @click.prevent="admin.activeTab = 'dashboard'; loadAdminDashboard()">
                        <i class="fas fa-tachometer-alt me-1"></i>Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: admin.activeTab === 'categories' }" href="#" @click.prevent="admin.activeTab = 'categories'; loadAdminCategories()">
                        <i class="fas fa-tags me-1"></i>Catégories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: admin.activeTab === 'stages' }" href="#" @click.prevent="admin.activeTab = 'stages'; loadAdminStages()">
                        <i class="fas fa-leaf me-1"></i>Stades phénologiques
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: admin.activeTab === 'gbif' }" href="#" @click.prevent="admin.activeTab = 'gbif'">
                        <i class="fas fa-globe me-1"></i>Taxons / GBIF
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="{ active: admin.activeTab === 'import' }" href="#" @click.prevent="admin.activeTab = 'import'">
                        <i class="fas fa-file-csv me-1"></i>Import CSV
                    </a>
                </li>
            </ul>

            <!-- Loading -->
            <div v-if="admin.loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Chargement...</p>
            </div>

            <!-- ── Dashboard Tab ── -->
            <div v-if="admin.activeTab === 'dashboard' && !admin.loading">
                <div v-if="admin.dashboard" class="row g-3">
                    <div class="col-md-4 col-lg-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <i class="fas fa-dna fa-2x text-success mb-2"></i>
                                <h3 class="mb-0" v-text="admin.dashboard.taxons_count"></h3>
                                <small class="text-muted">Taxons</small>
                                <div class="text-success small mt-1">
                                    <i class="fas fa-globe me-1"></i>@{{ admin.dashboard.taxons_with_gbif }} avec GBIF
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <i class="fas fa-tags fa-2x text-info mb-2"></i>
                                <h3 class="mb-0" v-text="admin.dashboard.categories_count"></h3>
                                <small class="text-muted">Catégories</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <i class="fas fa-leaf fa-2x text-warning mb-2"></i>
                                <h3 class="mb-0" v-text="admin.dashboard.phenological_stages_count"></h3>
                                <small class="text-muted">Stades phénologiques</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x text-secondary mb-2"></i>
                                <h3 class="mb-0" v-text="admin.dashboard.users_count"></h3>
                                <small class="text-muted">Utilisateurs</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <i class="fas fa-seedling fa-2x text-success mb-2"></i>
                                <h3 class="mb-0" v-text="admin.dashboard.plants_count"></h3>
                                <small class="text-muted">Plantes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <i class="fas fa-eye fa-2x text-primary mb-2"></i>
                                <h3 class="mb-0" v-text="admin.dashboard.observations_count"></h3>
                                <small class="text-muted">Observations</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <i class="fas fa-chart-line fa-2x text-danger mb-2"></i>
                                <h3 class="mb-0" v-text="admin.dashboard.ods_observations_count"></h3>
                                <small class="text-muted">Observations ODS</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="card text-center shadow-sm border-0">
                            <div class="card-body">
                                <i class="fas fa-database fa-2x text-purple mb-2" style="color: #6f42c1;"></i>
                                <h3 class="mb-0" v-text="admin.dashboard.tela_observations_count"></h3>
                                <small class="text-muted">Observations Tela</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-header"><i class="fas fa-bolt me-2"></i>Actions rapides</div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-success" @click="seedCategories()" :disabled="admin.loading">
                            <i class="fas fa-tags me-1"></i>Charger catégories par défaut
                        </button>
                        <button class="btn btn-outline-warning" @click="seedStages()" :disabled="admin.loading">
                            <i class="fas fa-leaf me-1"></i>Charger stades BBCH par défaut
                        </button>
                        <a href="/admin" target="_blank" class="btn btn-outline-secondary">
                            <i class="fas fa-shield-alt me-1"></i>Ouvrir Filament Admin
                            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.75em;"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ── Categories Tab ── -->
            <div v-if="admin.activeTab === 'categories' && !admin.loading">
                <!-- Add category form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white"><i class="fas fa-plus me-2"></i>Ajouter une catégorie</div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" v-model="admin.newCategory.name" placeholder="Ex: Conifères">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" v-model="admin.newCategory.category_type">
                                    <option value="trees">Arbres</option>
                                    <option value="shrubs">Arbustes</option>
                                    <option value="plants">Plantes</option>
                                    <option value="animals">Animaux</option>
                                    <option value="insects">Insectes</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Icône</label>
                                <input type="text" class="form-control" v-model="admin.newCategory.icon" placeholder="fa-tree">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" v-model="admin.newCategory.description" placeholder="Description...">
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-info w-100" @click="saveCategory()"><i class="fas fa-save"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories list -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-tags me-2"></i>Catégories (@{{ admin.categories.length }})</span>
                        <button class="btn btn-sm btn-outline-success" @click="seedCategories()" :disabled="admin.loading">
                            <i class="fas fa-magic me-1"></i>Charger par défaut
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th>Icône</th>
                                    <th>Description</th>
                                    <th>Plantes</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cat in admin.categories" :key="cat.id">
                                    <template v-if="admin.editingCategory && admin.editingCategory.id === cat.id">
                                        <td><input type="text" class="form-control form-control-sm" v-model="admin.editingCategory.name"></td>
                                        <td>
                                            <select class="form-select form-select-sm" v-model="admin.editingCategory.category_type">
                                                <option value="trees">Arbres</option>
                                                <option value="shrubs">Arbustes</option>
                                                <option value="plants">Plantes</option>
                                                <option value="animals">Animaux</option>
                                                <option value="insects">Insectes</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="admin.editingCategory.icon"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="admin.editingCategory.description"></td>
                                        <td>@{{ cat.plants_count || 0 }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-success me-1" @click="updateCategory()"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-sm btn-secondary" @click="cancelEditCategory()"><i class="fas fa-times"></i></button>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td><strong v-text="cat.name"></strong></td>
                                        <td><span class="badge bg-secondary" v-text="cat.category_type"></span></td>
                                        <td><i v-if="cat.icon" :class="'fas ' + cat.icon"></i></td>
                                        <td class="text-muted small" v-text="cat.description"></td>
                                        <td><span class="badge bg-light text-dark" v-text="cat.plants_count || 0"></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" @click="editCategory(cat)"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" @click="deleteCategory(cat.id)"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </template>
                                </tr>
                                <tr v-if="admin.categories.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">Aucune catégorie. Cliquez sur "Charger par défaut" pour commencer.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── Stages Tab ── -->
            <div v-if="admin.activeTab === 'stages' && !admin.loading">
                <!-- Add stage form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark"><i class="fas fa-plus me-2"></i>Ajouter un stade phénologique</div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">Code</label>
                                <input type="text" class="form-control" v-model="admin.newStage.stage_code" placeholder="Ex: 11">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Description du stade</label>
                                <input type="text" class="form-control" v-model="admin.newStage.stage_description" placeholder="Ex: Début de feuillaison">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Code evt</label>
                                <input type="number" class="form-control" v-model.number="admin.newStage.main_event_code">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Description événement</label>
                                <input type="text" class="form-control" v-model="admin.newStage.main_event_description" placeholder="Ex: Feuillaison">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Échelle</label>
                                <input type="text" class="form-control" v-model="admin.newStage.phenological_scale" placeholder="BBCH Tela Botanica">
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-warning w-100" @click="saveStage()"><i class="fas fa-save"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stages list -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-leaf me-2"></i>Stades phénologiques (@{{ admin.stages.length }})</span>
                        <button class="btn btn-sm btn-outline-warning" @click="seedStages()" :disabled="admin.loading">
                            <i class="fas fa-magic me-1"></i>Charger BBCH par défaut
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Evt</th>
                                    <th>Événement principal</th>
                                    <th>Échelle</th>
                                    <th>Obs.</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="st in admin.stages" :key="st.id">
                                    <template v-if="admin.editingStage && admin.editingStage.id === st.id">
                                        <td><input type="text" class="form-control form-control-sm" v-model="admin.editingStage.stage_code" style="width:60px"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="admin.editingStage.stage_description"></td>
                                        <td><input type="number" class="form-control form-control-sm" v-model.number="admin.editingStage.main_event_code" style="width:60px"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="admin.editingStage.main_event_description"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="admin.editingStage.phenological_scale"></td>
                                        <td>@{{ st.observations_count || 0 }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-success me-1" @click="updateStage()"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-sm btn-secondary" @click="cancelEditStage()"><i class="fas fa-times"></i></button>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td><span class="badge bg-success" v-text="st.stage_code"></span></td>
                                        <td v-text="st.stage_description"></td>
                                        <td v-text="st.main_event_code"></td>
                                        <td v-text="st.main_event_description"></td>
                                        <td class="small text-muted" v-text="st.phenological_scale"></td>
                                        <td><span class="badge bg-light text-dark" v-text="st.observations_count || 0"></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" @click="editStage(st)"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" @click="deleteStage(st.id)"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </template>
                                </tr>
                                <tr v-if="admin.stages.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">Aucun stade. Cliquez sur "Charger BBCH par défaut" pour commencer.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── GBIF / Taxons Tab ── -->
            <div v-if="admin.activeTab === 'gbif' && !admin.loading">
                <div class="row g-4">
                    <!-- Sync GBIF -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success text-white"><i class="fas fa-sync me-2"></i>Synchroniser depuis GBIF</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Mode de recherche</label>
                                    <select class="form-select" v-model="admin.gbifSync.mode">
                                        <option value="backbone_match">Correspondance exacte (Backbone)</option>
                                        <option value="search">Recherche large</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nom scientifique ou recherche</label>
                                    <input type="text" class="form-control" v-model="admin.gbifSync.query" placeholder="Ex: Quercus robur" @keydown.enter.prevent="syncGbif()">
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <label class="form-label">Limite</label>
                                        <input type="number" class="form-control" v-model.number="admin.gbifSync.limit" min="1" max="500">
                                    </div>
                                    <div class="col-4 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" v-model="admin.gbifSync.strict" id="gbifStrict">
                                            <label class="form-check-label" for="gbifStrict">Mode strict</label>
                                        </div>
                                    </div>
                                    <div class="col-4 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" v-model="admin.gbifSync.fetchVernacular" id="gbifVernacular" checked>
                                            <label class="form-check-label" for="gbifVernacular">Noms vernaculaires</label>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-success w-100" @click="syncGbif()" :disabled="admin.loading">
                                    <i class="fas fa-sync me-1"></i>Synchroniser
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Import Family -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary text-white"><i class="fas fa-sitemap me-2"></i>Importer une famille GBIF</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Nom de la famille</label>
                                    <input type="text" class="form-control" v-model="admin.gbifSync.query" placeholder="Ex: Fagaceae" @keydown.enter.prevent="importGbifFamily()">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Limite d'import</label>
                                    <input type="number" class="form-control" v-model.number="admin.gbifSync.limit" min="1" max="5000">
                                </div>
                                <p class="text-muted small">Importe toutes les espèces acceptées d'une famille depuis GBIF Backbone.</p>
                                <button class="btn btn-primary w-100" @click="importGbifFamily()" :disabled="admin.loading">
                                    <i class="fas fa-download me-1"></i>Importer la famille
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GBIF Results -->
                <div v-if="admin.gbifResults" class="card shadow-sm mt-4">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i>Résultats GBIF
                        <span class="badge bg-success ms-2" v-if="admin.gbifResults.synced_count">@{{ admin.gbifResults.synced_count }} synchronisé(s)</span>
                        <span class="badge bg-danger ms-2" v-if="admin.gbifResults.error_count">@{{ admin.gbifResults.error_count }} erreur(s)</span>
                    </div>
                    <div class="card-body">
                        <div v-if="admin.gbifResults.synced && admin.gbifResults.synced.length" class="mb-3">
                            <h6 class="text-success"><i class="fas fa-check me-1"></i>Taxons synchronisés</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between" v-for="t in admin.gbifResults.synced" :key="t.taxon_id">
                                    <span v-text="t.name"></span>
                                    <span class="badge" :class="t.created ? 'bg-success' : 'bg-info'" v-text="t.created ? 'Créé' : 'Mis à jour'"></span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="admin.gbifResults.errors && admin.gbifResults.errors.length">
                            <h6 class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Erreurs</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item text-danger small" v-for="(err, i) in admin.gbifResults.errors" :key="i" v-text="err"></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Import CSV Tab ── -->
            <div v-if="admin.activeTab === 'import' && !admin.loading">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-dark text-white"><i class="fas fa-file-csv me-2"></i>Import de données CSV</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Type d'import</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" value="ods" v-model="admin.importType" id="importOds">
                                            <label class="form-check-label" for="importOds">
                                                <i class="fas fa-chart-line me-1 text-danger"></i>ODS (Observatoire des Saisons)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" value="tela" v-model="admin.importType" id="importTela">
                                            <label class="form-check-label" for="importTela">
                                                <i class="fas fa-database me-1" style="color: #6f42c1;"></i>Tela Botanica
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Fichier CSV</label>
                                    <input type="file" class="form-control" accept=".csv" @change="onImportFileChange($event)">
                                    <small class="text-muted">Formats acceptés : CSV (séparateur virgule ou point-virgule)</small>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" v-model="admin.importClear" id="importClear">
                                        <label class="form-check-label text-danger" for="importClear">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Supprimer les données existantes avant import
                                        </label>
                                    </div>
                                </div>
                                <button class="btn btn-dark w-100" @click="importCsv()" :disabled="admin.loading || !admin.importFile">
                                    <i class="fas fa-upload me-1"></i>Importer
                                </button>
                            </div>
                        </div>

                        <!-- Import Result -->
                        <div v-if="admin.importResult" class="card shadow-sm mt-4">
                            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Résultat de l'import</div>
                            <div class="card-body">
                                <pre class="bg-light p-3 rounded small mb-0" style="max-height: 300px; overflow-y: auto;" v-text="JSON.stringify(admin.importResult, null, 2)"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Search Results Modal -->
        <div class="modal fade" :class="{ 'show': globalSearch.showModal }" id="searchResultsModal" tabindex="-1" v-show="globalSearch.showModal" @click.self="globalSearch.showModal = false" :style="{ display: globalSearch.showModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-search me-2"></i>
                            Résultats de recherche
                            <span v-if="globalSearch.results" class="badge bg-light text-success ms-2" v-text="globalSearch.results.total_results + ' résultat(s)'"></span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="globalSearch.showModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Loading State -->
                        <div v-if="globalSearch.loading" class="text-center py-5">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Recherche en cours...</span>
                            </div>
                            <p class="mt-3 text-muted">Recherche de "<span v-text="globalSearch.query"></span>"...</p>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="globalSearch.error" class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><span v-text="globalSearch.error"></span>
                        </div>

                        <!-- No Results -->
                        <div v-else-if="globalSearch.results && globalSearch.results.total_results === 0" class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucun résultat trouvé pour "<span v-text="globalSearch.query"></span>"</p>
                        </div>

                        <!-- Results -->
                        <div v-else-if="globalSearch.results">
                            <!-- Plants Results -->
                            <div v-if="globalSearch.results.plants && globalSearch.results.plants.length > 0" class="mb-4">
                                <h6 class="text-success border-bottom pb-2">
                                    <i class="fas fa-seedling me-2"></i>Plantes (<span v-text="globalSearch.results.plants.length"></span>)
                                </h6>
                                <div class="list-group">
                                    <a
                                        v-for="plant in globalSearch.results.plants"
                                        :key="'plant-' + plant.id"
                                        href="#"
                                        class="list-group-item list-group-item-action"
                                        @click.prevent="navigateToPlantFromSearch(plant.id)"
                                    >
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1" v-text="plant.name"></h6>
                                                <p class="mb-1 small text-muted">
                                                    <em v-text="plant.taxon.binomial_name"></em>
                                                    <span v-if="plant.taxon.common_name_fr"> - <span v-text="plant.taxon.common_name_fr"></span></span>
                                                </p>
                                                <p class="mb-0 small">
                                                    <span class="badge bg-secondary me-1" v-text="plant.category.name"></span>
                                                    <span v-if="plant.site" class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i><span v-text="plant.site.name"></span>
                                                    </span>
                                                </p>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Sites Results -->
                            <div v-if="globalSearch.results.sites && globalSearch.results.sites.length > 0" class="mb-4">
                                <h6 class="text-success border-bottom pb-2">
                                    <i class="fas fa-map-marked-alt me-2"></i>Sites (<span v-text="globalSearch.results.sites.length"></span>)
                                </h6>
                                <div class="list-group">
                                    <a
                                        v-for="site in globalSearch.results.sites"
                                        :key="'site-' + site.id"
                                        href="#"
                                        class="list-group-item list-group-item-action"
                                        @click.prevent="navigateToSiteFromSearch(site.id)"
                                    >
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1" v-text="site.name"></h6>
                                                <p class="mb-1 small text-muted" v-text="site.description || 'Aucune description'"></p>
                                                <p class="mb-0 small">
                                                    <span class="badge bg-info text-dark me-1" v-text="site.environment"></span>
                                                    <span v-if="site.altitude" class="text-muted">
                                                        <i class="fas fa-mountain me-1"></i><span v-text="site.altitude"></span> m
                                                    </span>
                                                </p>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Observations Results -->
                            <div v-if="globalSearch.results.observations && globalSearch.results.observations.length > 0" class="mb-4">
                                <h6 class="text-success border-bottom pb-2">
                                    <i class="fas fa-eye me-2"></i>Observations (<span v-text="globalSearch.results.observations.length"></span>)
                                </h6>
                                <div class="list-group">
                                    <a
                                        v-for="obs in globalSearch.results.observations"
                                        :key="'obs-' + obs.id"
                                        href="#"
                                        class="list-group-item list-group-item-action"
                                        @click.prevent="navigateToObservationFromSearch(obs.id)"
                                    >
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <span v-text="obs.plant.name"></span> - <span v-text="formatDate(obs.observation_date)"></span>
                                                </h6>
                                                <p class="mb-1 small text-muted">
                                                    Stade: <span v-text="obs.phenological_stage.stage_description"></span>
                                                </p>
                                                <p v-if="obs.notes" class="mb-0 small text-muted" v-text="obs.notes.substring(0, 100) + (obs.notes.length > 100 ? '...' : '')"></p>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- Taxons Results -->
                            <div v-if="globalSearch.results.taxons && globalSearch.results.taxons.length > 0" class="mb-4">
                                <h6 class="text-success border-bottom pb-2">
                                    <i class="fas fa-dna me-2"></i>Taxons (<span v-text="globalSearch.results.taxons.length"></span>)
                                </h6>
                                <div class="list-group">
                                    <div
                                        v-for="taxon in globalSearch.results.taxons"
                                        :key="'taxon-' + taxon.id"
                                        class="list-group-item"
                                    >
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><em v-text="taxon.binomial_name"></em></h6>
                                                <p class="mb-1 small">
                                                    <span v-if="taxon.common_name_fr" class="text-muted" v-text="taxon.common_name_fr"></span>
                                                </p>
                                                <p class="mb-0 small text-muted">
                                                    <span v-text="taxon.family"></span> - <span v-text="taxon.genus"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="globalSearch.showModal = false">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Modal -->
        <div class="modal fade" :class="{ 'show': showLoginModal }" id="loginModal" tabindex="-1" v-show="showLoginModal" @click.self="showLoginModal = false" :style="{ display: showLoginModal ? 'block' : 'none' }">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-sign-in-alt me-2"></i>Connexion
                        </h5>
                        <button type="button" class="btn-close" @click="showLoginModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <i class="fas fa-user-lock fa-3x text-primary mb-3"></i>
                            <p class="text-muted">Connectez-vous pour accéder à toutes les fonctionnalités</p>
                        </div>
                        
                        <form @submit.prevent="login">
                            <div class="mb-3">
                                <label for="loginUsername" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nom d'utilisateur
                                </label>
                                <input v-model="loginForm.username" 
                                       type="text" 
                                       class="form-control" 
                                       :class="{'is-invalid': loginForm.error}"
                                       id="loginUsername" 
                                       placeholder="Votre nom d'utilisateur"
                                       required>
                            </div>
                            <div class="mb-3">
                                <label for="loginPassword" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Mot de passe
                                </label>
                                <input v-model="loginForm.password" 
                                       type="password" 
                                       class="form-control" 
                                       :class="{'is-invalid': loginForm.error}"
                                       id="loginPassword" 
                                       placeholder="Votre mot de passe"
                                       required>
                            </div>
                            
                            <div v-if="loginForm.error" class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                @{{ loginForm.error }}
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Compte de test :</strong><br>
                                Username: <code>admin</code><br>
                                Password: <code>admin123</code>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showLoginModal = false">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="login">
                            <i class="fas fa-sign-in-alt me-1"></i>Se connecter
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Plant Modal -->
        <div class="modal fade" :class="{ 'show': showAddPlantModal }" id="addPlantModal" tabindex="-1" v-show="showAddPlantModal" @click.self="closeModal()" :style="{ display: showAddPlantModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-leaf me-2"></i>Ajouter une nouvelle plante
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="addPlant">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="plantName" class="form-label">Nom de la plante *</label>
                                    <input v-model="newPlant.name" type="text" class="form-control" id="plantName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="plantCategory" class="form-label">Catégorie *</label>
                                    <select v-model="newPlant.category" class="form-select" id="plantCategory" required>
                                        <option value="">Sélectionner une catégorie</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id" v-text="category.name ? category.name : 'Unnamed category #' + category.id"></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="plantSite" class="form-label">Site *</label>
                                    <select v-model="newPlant.site" class="form-select" id="plantSite" required>
                                        <option value="">Sélectionner un site</option>
                                        <option v-for="site in sites" :key="site.id" :value="site.id" v-text="site.name ? site.name : 'Unnamed site #' + site.id"></option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="plantTaxon" class="form-label">Taxon</label>
                                    <div class="position-relative">
                                        <input
                                            v-model="taxonAutocomplete.query"
                                            @input="searchTaxons('newPlant')"
                                            @focus="taxonAutocomplete.showDropdown = taxonAutocomplete.results.length > 0"
                                            @blur="closeTaxonDropdown('newPlant')"
                                            @keydown="handleTaxonKeydown($event, 'newPlant')"
                                            type="text"
                                            class="form-control"
                                            id="plantTaxon"
                                            placeholder="Rechercher un taxon (min. 2 caractères)..."
                                            autocomplete="off"
                                        >
                                        <button
                                            v-if="taxonAutocomplete.selectedTaxon"
                                            @click="clearTaxonSelection('newPlant')"
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary position-absolute"
                                            style="right: 5px; top: 5px;"
                                            title="Effacer la sélection"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div v-if="taxonAutocomplete.loading" class="position-absolute" style="right: 35px; top: 10px;">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </div>
                                        <ul
                                            v-show="taxonAutocomplete.showDropdown && taxonAutocomplete.results.length > 0"
                                            class="list-group position-absolute w-100 shadow-sm"
                                            style="z-index: 1050; max-height: 300px; overflow-y: auto;"
                                        >
                                            <li
                                                v-for="taxon in taxonAutocomplete.results"
                                                :key="taxon.id"
                                                @click="selectTaxon(taxon, 'newPlant')"
                                                class="list-group-item list-group-item-action"
                                                style="cursor: pointer;"
                                            >
                                                <strong v-text="taxon.binomial_name"></strong>
                                                <span v-if="taxon.common_name_fr" class="text-muted"> - <span v-text="taxon.common_name_fr"></span></span>
                                                <span v-if="taxon.family" class="badge bg-secondary ms-2" v-text="taxon.family"></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="row" v-if="selectedTaxonFamily">
                                <div class="col-12 mb-3">
                                    <div class="alert alert-info py-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Famille botanique:</strong> @{{ selectedTaxonFamily }}
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="plantDescription" class="form-label">Description</label>
                                <textarea v-model="newPlant.description" class="form-control" id="plantDescription" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="plantDate" class="form-label">Date de plantation</label>
                                    <input v-model="newPlant.planting_date" type="date" class="form-control" id="plantDate">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="plantHealth" class="form-label">État de santé</label>
                                    <select v-model="newPlant.health_status" class="form-select" id="plantHealth">
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Bon</option>
                                        <option value="fair">Correct</option>
                                        <option value="poor">Mauvais</option>
                                        <option value="dead">Mort</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="plantHeight" class="form-label">Taille</label>
                                    <select v-model="newPlant.height_category" class="form-select" id="plantHeight">
                                        <option value="seedling">Plantule (&lt;30cm)</option>
                                        <option value="young">Jeune (30cm-1m)</option>
                                        <option value="medium">Moyen (1-3m)</option>
                                        <option value="mature">Mature (3-10m)</option>
                                        <option value="large">Grand (&gt;10m)</option>
                                    </select>
                                </div>
                            </div>
                            <!-- GPS Location Section -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="mb-3">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        Localisation GPS précise
                                        <small class="text-muted ms-2">(optionnel - précision centimétrique possible)</small>
                                    </h6>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="plantLatitude" class="form-label">
                                        Latitude
                                        <i class="fas fa-info-circle text-muted ms-1" title="Coordonnée Nord-Sud (degrés décimaux)"></i>
                                    </label>
                                    <input 
                                        v-model="newPlant.latitude" 
                                        type="number" 
                                        step="0.000001" 
                                        min="-90" 
                                        max="90" 
                                        class="form-control" 
                                        :class="getGpsValidationClass('latitude', newPlant.latitude)"
                                        id="plantLatitude" 
                                        placeholder="ex: 43.710200"
                                        @input="validateGpsCoordinates"
                                    >
                                    <div v-if="gpsValidation.latitude" class="invalid-feedback d-block">
                                        @{{ gpsValidation.latitude }}
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="plantLongitude" class="form-label">
                                        Longitude
                                        <i class="fas fa-info-circle text-muted ms-1" title="Coordonnée Est-Ouest (degrés décimaux)"></i>
                                    </label>
                                    <input 
                                        v-model="newPlant.longitude" 
                                        type="number" 
                                        step="0.000001" 
                                        min="-180" 
                                        max="180" 
                                        class="form-control" 
                                        :class="getGpsValidationClass('longitude', newPlant.longitude)"
                                        id="plantLongitude" 
                                        placeholder="ex: 7.262000"
                                        @input="validateGpsCoordinates"
                                    >
                                    <div v-if="gpsValidation.longitude" class="invalid-feedback d-block">
                                        @{{ gpsValidation.longitude }}
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="plantAccuracy" class="form-label">
                                        Précision GPS (m)
                                        <i class="fas fa-info-circle text-muted ms-1" title="Précision en mètres (ex: 2.5 pour très précis)"></i>
                                    </label>
                                    <input 
                                        v-model="newPlant.gps_accuracy" 
                                        type="number" 
                                        step="0.1" 
                                        min="0" 
                                        max="1000" 
                                        class="form-control" 
                                        id="plantAccuracy" 
                                        placeholder="ex: 2.5"
                                    >
                                    <small class="form-text text-muted">
                                        &lt;1m: Ultra-précis | 1-5m: Très précis | 5-10m: Précis
                                    </small>
                                </div>
                            </div>
                            <!-- GPS Controls -->
                            <div class="row mb-3" v-if="newPlant.latitude && newPlant.longitude">
                                <div class="col-12">
                                    <div class="gps-coordinates">
                                        <strong>Coordonnées actuelles:</strong>
                                        <span v-text="parseFloat(newPlant.latitude).toFixed(6)"></span>, <span v-text="parseFloat(newPlant.longitude).toFixed(6)"></span>
                                        <span class="precision-indicator" :class="getGpsPrecisionClass(newPlant.gps_accuracy)">
                                            <i class="fas fa-crosshairs me-1"></i>
                                            <span v-text="getGpsPrecisionLabel(newPlant.gps_accuracy)"></span>
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" @click="getCurrentLocation()">
                                            <i class="fas fa-location-arrow me-1"></i>Position actuelle
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm" @click="showGpsMap()" v-if="hasValidGpsCoordinates">
                                            <i class="fas fa-map me-1"></i>Voir sur carte
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="clearGpsCoordinates()">
                                            <i class="fas fa-times me-1"></i>Effacer GPS
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Mini Map for GPS visualization -->
                            <div v-if="showGpsPreview && hasValidGpsCoordinates" class="row mb-3">
                                <div class="col-12">
                                    <div class="gps-map-mini" id="plantGpsMap"></div>
                                </div>
                            </div>
                            <!-- Additional plant details -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="plantExactHeight" class="form-label">Hauteur exacte (m)</label>
                                    <input v-model="newPlant.exact_height" type="number" step="0.1" min="0" max="100" class="form-control" id="plantExactHeight" placeholder="ex: 2.5">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="plantAge" class="form-label">Âge (années)</label>
                                    <input v-model="newPlant.age_years" type="number" min="0" max="1000" class="form-control" id="plantAge" placeholder="ex: 5">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="plantNotes" class="form-label">Notes techniques</label>
                                <textarea v-model="newPlant.notes" class="form-control" id="plantNotes" rows="2" placeholder="Notes d'observation, conditions de croissance..."></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input v-model="newPlant.is_private" class="form-check-input" type="checkbox" id="plantPrivate">
                                <label class="form-check-label" for="plantPrivate">
                                    Plante privée
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="addPlant">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Plant Modal -->
        <div class="modal fade" :class="{ 'show': showEditPlantModal }" id="editPlantModal" tabindex="-1" v-show="showEditPlantModal" @click.self="closeModal()" :style="{ display: showEditPlantModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Modifier la plante
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="updatePlant">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editPlantName" class="form-label">Nom de la plante *</label>
                                    <input v-model="editPlantData.name" type="text" class="form-control" id="editPlantName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editPlantCategory" class="form-label">Catégorie *</label>
                                    <select v-model="editPlantData.category" class="form-select" id="editPlantCategory" required>
                                        <option value="">Sélectionner une catégorie</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id" v-text="category.name ? category.name : 'Unnamed category #' + category.id"></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editPlantSite" class="form-label">Site *</label>
                                    <select v-model="editPlantData.site" class="form-select" id="editPlantSite" required>
                                        <option value="">Sélectionner un site</option>
                                        <option v-for="site in sites" :key="site.id" :value="site.id" v-text="site.name ? site.name : 'Unnamed site #' + site.id"></option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editPlantTaxon" class="form-label">Taxon</label>
                                    <div class="position-relative">
                                        <input
                                            v-model="taxonAutocompleteEdit.query"
                                            @input="searchTaxons('editPlant')"
                                            @focus="taxonAutocompleteEdit.showDropdown = taxonAutocompleteEdit.results.length > 0"
                                            @blur="closeTaxonDropdown('editPlant')"
                                            @keydown="handleTaxonKeydown($event, 'editPlant')"
                                            type="text"
                                            class="form-control"
                                            id="editPlantTaxon"
                                            placeholder="Rechercher un taxon (min. 2 caractères)..."
                                            autocomplete="off"
                                        >
                                        <button
                                            v-if="taxonAutocompleteEdit.selectedTaxon"
                                            @click="clearTaxonSelection('editPlant')"
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary position-absolute"
                                            style="right: 5px; top: 5px;"
                                            title="Effacer la sélection"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div v-if="taxonAutocompleteEdit.loading" class="position-absolute" style="right: 35px; top: 10px;">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </div>
                                        <ul
                                            v-show="taxonAutocompleteEdit.showDropdown && taxonAutocompleteEdit.results.length > 0"
                                            class="list-group position-absolute w-100 shadow-sm"
                                            style="z-index: 1050; max-height: 300px; overflow-y: auto;"
                                        >
                                            <li
                                                v-for="taxon in taxonAutocompleteEdit.results"
                                                :key="taxon.id"
                                                @click="selectTaxon(taxon, 'editPlant')"
                                                class="list-group-item list-group-item-action"
                                                style="cursor: pointer;"
                                            >
                                                <strong v-text="taxon.binomial_name"></strong>
                                                <span v-if="taxon.common_name_fr" class="text-muted"> - <span v-text="taxon.common_name_fr"></span></span>
                                                <span v-if="taxon.family" class="badge bg-secondary ms-2" v-text="taxon.family"></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editPlantDescription" class="form-label">Description</label>
                                <textarea v-model="editPlantData.description" class="form-control" id="editPlantDescription" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="editPlantDate" class="form-label">Date de plantation</label>
                                    <input v-model="editPlantData.planting_date" type="date" class="form-control" id="editPlantDate">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="editPlantAge" class="form-label">Âge à la plantation (ans)</label>
                                    <input v-model="editPlantData.age_years" type="number" min="0" max="1000" class="form-control" id="editPlantAge" placeholder="ex: 50">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="editPlantHealth" class="form-label">État de santé</label>
                                    <select v-model="editPlantData.health_status" class="form-select" id="editPlantHealth">
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Bon</option>
                                        <option value="fair">Correct</option>
                                        <option value="poor">Mauvais</option>
                                        <option value="dead">Mort</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editPlantHeight" class="form-label">Taille</label>
                                    <select v-model="editPlantData.height_category" class="form-select" id="editPlantHeight">
                                        <option value="seedling">Plantule (&lt;30cm)</option>
                                        <option value="young">Jeune (30cm-1m)</option>
                                        <option value="medium">Moyen (1-3m)</option>
                                        <option value="mature">Mature (3-10m)</option>
                                        <option value="large">Grand (&gt;10m)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="editPlantLatitude" class="form-label">Latitude</label>
                                    <input v-model="editPlantData.latitude" type="number" step="0.000001" min="-90" max="90" class="form-control" id="editPlantLatitude" placeholder="ex: 43.710200">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editPlantLongitude" class="form-label">Longitude</label>
                                    <input v-model="editPlantData.longitude" type="number" step="0.000001" min="-180" max="180" class="form-control" id="editPlantLongitude" placeholder="ex: 7.262000">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editPlantAccuracy" class="form-label">Précision GPS (m)</label>
                                    <input v-model="editPlantData.gps_accuracy" type="number" step="0.1" min="0" max="1000" class="form-control" id="editPlantAccuracy" placeholder="ex: 2.5">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editPlantNotes" class="form-label">Notes techniques</label>
                                <textarea v-model="editPlantData.notes" class="form-control" id="editPlantNotes" rows="2"></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input v-model="editPlantData.is_private" class="form-check-input" type="checkbox" id="editPlantPrivate">
                                <label class="form-check-label" for="editPlantPrivate">
                                    Plante privée
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="updatePlant">
                            <i class="fas fa-save me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Observation Modal -->
        <div class="modal fade" :class="{ 'show': showAddObservationModal }" id="addObservationModal" tabindex="-1" v-show="showAddObservationModal" @click.self="closeModal()" :style="{ display: showAddObservationModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye me-2"></i>Ajouter une nouvelle observation
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="addObservation">
                            <div class="row">
                                <div class="col-md-6 mb-3" v-if="!newObservation.plant">
                                    <label for="obsPlant" class="form-label">Plante *</label>
                                    <select v-model="newObservation.plant" class="form-select" id="obsPlant" required>
                                        <option value="">Sélectionner une plante</option>
                                        <option v-for="plant in plants" :key="plant.id" :value="plant.id" v-text="plant.name">
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" v-else>
                                    <label class="form-label">Plante</label>
                                    <div class="form-control-plaintext">
                                        <strong v-text="plants.find(p => p.id === newObservation.plant)?.name || 'Plante sélectionnée'"></strong>
                                        <small class="text-muted d-block">Observation pour cette plante</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="obsStage" class="form-label">Stade phénologique *</label>
                                    <select v-model="newObservation.phenological_stage" class="form-select" id="obsStage" required>
                                        <option value="">Sélectionner un stade</option>
                                        <option v-for="stage in phenologicalStages" :key="stage.id" :value="stage.id" v-text="stage.stage_code + ' - ' + stage.stage_description"></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="obsDate" class="form-label">Date d'observation *</label>
                                    <input v-model="newObservation.observation_date" type="date" class="form-control" id="obsDate" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="obsIntensity" class="form-label">Intensité (1-5)</label>
                                    <select v-model="newObservation.intensity" class="form-select" id="obsIntensity">
                                        <option value="1">1 - Très faible</option>
                                        <option value="2">2 - Faible</option>
                                        <option value="3">3 - Moyenne</option>
                                        <option value="4">4 - Forte</option>
                                        <option value="5">5 - Très forte</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="obsWeather" class="form-label">Conditions météo</label>
                                    <select v-model="newObservation.weather_conditions" class="form-select" id="obsWeather">
                                        <option value="">Non spécifié</option>
                                        <option value="ensoleillé">Ensoleillé</option>
                                        <option value="nuageux">Nuageux</option>
                                        <option value="pluvieux">Pluvieux</option>
                                        <option value="venteux">Venteux</option>
                                        <option value="orageux">Orageux</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="obsTemperature" class="form-label">Température (°C)</label>
                                    <input v-model="newObservation.temperature" type="number" step="0.1" class="form-control" id="obsTemperature">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="obsNotes" class="form-label">Notes</label>
                                <textarea v-model="newObservation.notes" class="form-control" id="obsNotes" rows="3"></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input v-model="newObservation.is_public" class="form-check-input" type="checkbox" id="obsPublic">
                                <label class="form-check-label" for="obsPublic">
                                    Observation publique
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="addObservation">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Observation Modal -->
        <div class="modal fade" :class="{ 'show': showEditObservationModal }" id="editObservationModal" tabindex="-1" v-show="showEditObservationModal" @click.self="closeEditObservationModal()" :style="{ display: showEditObservationModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg" @click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Modifier l'observation
                        </h5>
                        <button type="button" class="btn-close" @click="closeEditObservationModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="updateObservation">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editObsPlant" class="form-label">Plante *</label>
                                    <select v-model="editObservation.plant" class="form-select" id="editObsPlant" required>
                                        <option value="">Sélectionner une plante</option>
                                        <option v-for="plant in plants" :key="plant.id" :value="plant.id" v-text="plant.name">
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editObsStage" class="form-label">Stade phénologique *</label>
                                    <select v-model="editObservation.phenological_stage" class="form-select" id="editObsStage" required>
                                        <option value="">Sélectionner un stade</option>
                                        <option v-for="stage in phenologicalStages" :key="stage.id" :value="stage.id" v-text="stage.stage_code + ' - ' + stage.stage_description"></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editObsDate" class="form-label">Date d'observation *</label>
                                    <input v-model="editObservation.observation_date" type="date" class="form-control" id="editObsDate" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editObsTime" class="form-label">Heure</label>
                                    <input v-model="editObservation.time_of_day" type="time" class="form-control" id="editObsTime">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editObsIntensity" class="form-label">Intensité (1-5)</label>
                                    <select v-model="editObservation.intensity" class="form-select" id="editObsIntensity">
                                        <option value="1">1 - Très faible</option>
                                        <option value="2">2 - Faible</option>
                                        <option value="3">3 - Moyenne</option>
                                        <option value="4">4 - Forte</option>
                                        <option value="5">5 - Très forte</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editObsWeather" class="form-label">Conditions météo</label>
                                    <select v-model="editObservation.weather_condition" class="form-select" id="editObsWeather">
                                        <option value="">Non spécifié</option>
                                        <option value="ensoleillé">Ensoleillé</option>
                                        <option value="nuageux">Nuageux</option>
                                        <option value="pluvieux">Pluvieux</option>
                                        <option value="venteux">Venteux</option>
                                        <option value="orageux">Orageux</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="editObsTemperature" class="form-label">Température (°C)</label>
                                    <input v-model="editObservation.temperature" type="number" step="0.1" class="form-control" id="editObsTemperature">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editObsHumidity" class="form-label">Humidité (%)</label>
                                    <input v-model="editObservation.humidity" type="number" min="0" max="100" class="form-control" id="editObsHumidity">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editObsWind" class="form-label">Vent (km/h)</label>
                                    <input v-model="editObservation.wind_speed" type="number" step="0.1" min="0" class="form-control" id="editObsWind">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editObsNotes" class="form-label">Notes</label>
                                <textarea v-model="editObservation.notes" class="form-control" id="editObsNotes" rows="3"></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input v-model="editObservation.is_public" class="form-check-input" type="checkbox" id="editObsPublic">
                                <label class="form-check-label" for="editObsPublic">
                                    Observation publique
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeEditObservationModal()">Annuler</button>
                        <button type="button" class="btn btn-warning" @click="updateObservation">
                            <i class="fas fa-save me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Observation Confirmation Modal -->
        <div class="modal fade" :class="{ 'show': showDeleteObservationModal }" id="deleteObservationModal" tabindex="-1" v-show="showDeleteObservationModal" @click.self="closeDeleteObservationModal()" :style="{ display: showDeleteObservationModal ? 'block' : 'none' }">
            <div class="modal-dialog" @click.stop>
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="closeDeleteObservationModal()"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="observationToDelete">
                            <p class="fw-bold">Êtes-vous sûr de vouloir supprimer cette observation ?</p>
                            <div class="card">
                                <div class="card-body">
                                    <p class="mb-1"><strong>Plante:</strong> <span v-text="observationToDelete.plant ? observationToDelete.plant.name : 'N/A'"></span></p>
                                    <p class="mb-1"><strong>Stade:</strong> <span v-text="observationToDelete.phenological_stage ? observationToDelete.phenological_stage.stage_description : 'N/A'"></span></p>
                                    <p class="mb-0"><strong>Date:</strong> <span v-text="formatDate(observationToDelete.observation_date)"></span></p>
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                Cette action est irréversible. L'observation sera définitivement supprimée.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeDeleteObservationModal()">Annuler</button>
                        <button type="button" class="btn btn-danger" @click="deleteObservation">
                            <i class="fas fa-trash me-1"></i>Supprimer définitivement
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Plant Confirmation Modal -->
        <div class="modal fade" :class="{ 'show': showDeletePlantModal }" id="deletePlantModal" tabindex="-1" v-show="showDeletePlantModal" @click.self="closeDeletePlantModal()" :style="{ display: showDeletePlantModal ? 'block' : 'none' }">
            <div class="modal-dialog" @click.stop>
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="closeDeletePlantModal()"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="plantToDelete">
                            <p class="fw-bold">Êtes-vous sûr de vouloir supprimer cette plante ?</p>
                            <div class="card">
                                <div class="card-body">
                                    <p class="mb-1"><strong>Nom:</strong> <span v-text="plantToDelete.name"></span></p>
                                    <p class="mb-1" v-if="plantToDelete.taxon"><strong>Taxon:</strong> <em v-text="plantToDelete.taxon.binomial_name"></em></p>
                                    <p class="mb-1" v-if="plantToDelete.site_name"><strong>Site:</strong> <span v-text="plantToDelete.site_name"></span></p>
                                    <p class="mb-0" v-if="plantToDelete.observations_count > 0">
                                        <strong>Observations:</strong>
                                        <span class="badge bg-warning text-dark" v-text="plantToDelete.observations_count + ' observation(s)'"></span>
                                    </p>
                                    <p class="mb-0" v-if="plantToDelete.photos_count > 0">
                                        <strong>Photos:</strong>
                                        <span class="badge bg-info" v-text="plantToDelete.photos_count + ' photo(s)'"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <strong>Attention :</strong> Cette action est irréversible. La plante sera définitivement supprimée
                                <span v-if="plantToDelete.observations_count > 0 || plantToDelete.photos_count > 0">
                                    avec toutes ses observations et photos associées
                                </span>.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeDeletePlantModal()">Annuler</button>
                        <button type="button" class="btn btn-danger" @click="deletePlant" :disabled="deletingPlant">
                            <i class="fas" :class="deletingPlant ? 'fa-spinner fa-spin' : 'fa-trash'" me-1></i>
                            <span v-text="deletingPlant ? 'Suppression...' : 'Supprimer définitivement'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Backdrops - Manually managed for modals that don't use Bootstrap JS API -->
        <div v-if="showAddSiteModal" class="modal-backdrop fade show"></div>
        <div v-if="showLoginModal" class="modal-backdrop fade show"></div>
        <div v-if="showAddPlantModal" class="modal-backdrop fade show"></div>
        <div v-if="showAddObservationModal" class="modal-backdrop fade show"></div>
        <div v-if="showEditObservationModal" class="modal-backdrop fade show"></div>
        <div v-if="showDeleteObservationModal" class="modal-backdrop fade show"></div>
        <div v-if="showDeletePlantModal" class="modal-backdrop fade show"></div>
        <div v-if="showUploadPhotoModal" class="modal-backdrop fade show"></div>
        <div v-if="showPhotoGalleryModal" class="modal-backdrop fade show"></div>
        <div v-if="showAddPhotoModal" class="modal-backdrop fade show"></div>
        <div v-if="showEditSiteModal" class="modal-backdrop fade show"></div>
        <div v-if="showTestSiteModal" class="modal-backdrop fade show"></div>
        <!-- Site Map Editor (Full Screen Overlay) -->
        <div v-if="showSiteMapEditorModal" v-cloak
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1055; background: #fff; display: flex; flex-direction: column;">

            <!-- Header -->
            <div class="bg-success text-white d-flex align-items-center justify-content-between px-3" style="height: 48px; flex-shrink: 0;">
                <h5 class="mb-0">
                    <i class="fas fa-map-marked-alt me-2"></i>Plan du Site -
                    <span v-text="siteMapEditor.site?.name"></span>
                    <span v-if="siteMapEditor.unsavedChanges" class="badge bg-warning text-dark ms-2">
                        <i class="fas fa-exclamation-triangle"></i> Non sauvegardé
                    </span>
                </h5>
                <button type="button" class="btn-close btn-close-white" @click="closeSiteMapEditor()"></button>
            </div>

            <!-- Body -->
            <div style="flex: 1; overflow: hidden;">
                <div class="container-fluid h-100">
                    <div class="row h-100">
                                <!-- Left Sidebar - Plants List -->
                                <div class="col-md-3 bg-light border-end p-3" style="overflow-y: auto;">
                                    <h6 class="fw-bold mb-3">
                                        <i class="fas fa-list me-2"></i>Plantes
                                        <span class="badge bg-secondary ms-2" v-text="siteMapEditor.plants.length"></span>
                                    </h6>

                                    <!-- Plants List -->
                                    <div class="list-group">
                                        <div v-for="plant in siteMapEditor.plants" :key="plant.id"
                                             class="list-group-item list-group-item-action p-2"
                                             :class="{ 'active': siteMapEditor.selectedPlant?.id === plant.id }"
                                             @click="selectPlant(plant)"
                                             style="cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <!-- Color Indicator -->
                                                <div class="me-2" :style="{
                                                    width: '12px',
                                                    height: '12px',
                                                    borderRadius: '50%',
                                                    backgroundColor: getPlantMarkerColor(plant),
                                                    border: '2px solid white',
                                                    boxShadow: '0 0 3px rgba(0,0,0,0.3)'
                                                }"></div>

                                                <!-- Plant Info -->
                                                <div class="flex-grow-1">
                                                    <div class="small fw-bold" v-text="plant.name"></div>
                                                    <div class="small text-muted" v-text="plant.taxon ? plant.taxon.binomial_name : ''"></div>
                                                </div>

                                                <!-- Position Status Icon / Add Button -->
                                                <div class="ms-2">
                                                    <i v-if="plant.map_position_x !== null && plant.map_position_y !== null"
                                                       class="fas fa-map-pin text-success" title="Positionné"></i>
                                                    <button v-else
                                                            class="btn btn-sm btn-outline-primary py-0 px-1"
                                                            @click.stop="addPlantToMap(plant)"
                                                            title="Ajouter sur la carte">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Center - SVG Map Editor -->
                                <div class="col-md-9 p-0 position-relative bg-dark">
                                    <!-- Loading Overlay -->
                                    <div v-if="siteMapEditor.loading" class="position-absolute top-50 start-50 translate-middle">
                                        <div class="spinner-border text-light" style="width: 3rem; height: 3rem;"></div>
                                        <p class="text-white mt-2">Chargement...</p>
                                    </div>

                                    <!-- SVG Map -->
                                    <div v-else class="h-100 d-flex flex-column">
                                        <!-- Toolbar -->
                                        <div class="bg-white border-bottom p-2 d-flex gap-2 align-items-center">
                                            <button class="btn btn-sm"
                                                    :class="siteMapEditor.editMode ? 'btn-danger' : 'btn-primary'"
                                                    @click="toggleMapEditMode()">
                                                <i class="fas" :class="siteMapEditor.editMode ? 'fa-lock-open' : 'fa-lock'"></i>
                                                <span v-if="siteMapEditor.editMode">Mode Édition</span>
                                                <span v-else>Mode Lecture</span>
                                            </button>

                                            <button v-if="siteMapEditor.editMode"
                                                    class="btn btn-sm btn-success"
                                                    :disabled="!siteMapEditor.unsavedChanges"
                                                    @click="saveSiteMapPositions()">
                                                <i class="fas fa-save"></i>
                                                Positions
                                            </button>

                                            <button v-if="siteMapEditor.editMode"
                                                    class="btn btn-sm btn-info"
                                                    :disabled="!siteMapEditor.drawingUnsavedChanges"
                                                    @click="saveDrawingOverlay()">
                                                <i class="fas fa-save"></i>
                                                Dessin
                                            </button>

                                            <!-- Drawing Tools -->
                                            <div v-if="siteMapEditor.editMode" class="btn-group" role="group">
                                                <button class="btn btn-sm"
                                                        :class="siteMapEditor.drawingMode === 'select' ? 'btn-primary' : 'btn-outline-primary'"
                                                        @click="setDrawingMode('select')"
                                                        title="Sélectionner">
                                                    <i class="fas fa-mouse-pointer"></i>
                                                </button>
                                                <button class="btn btn-sm"
                                                        :class="siteMapEditor.drawingMode === 'rect' ? 'btn-primary' : 'btn-outline-primary'"
                                                        @click="setDrawingMode('rect')"
                                                        title="Rectangle">
                                                    <i class="far fa-square"></i>
                                                </button>
                                                <button class="btn btn-sm"
                                                        :class="siteMapEditor.drawingMode === 'circle' ? 'btn-primary' : 'btn-outline-primary'"
                                                        @click="setDrawingMode('circle')"
                                                        title="Cercle">
                                                    <i class="far fa-circle"></i>
                                                </button>
                                                <button class="btn btn-sm"
                                                        :class="siteMapEditor.drawingMode === 'polyline' ? 'btn-primary' : 'btn-outline-primary'"
                                                        @click="setDrawingMode('polyline')"
                                                        title="Polyligne">
                                                    <i class="fas fa-project-diagram"></i>
                                                </button>
                                                <button class="btn btn-sm"
                                                        :class="siteMapEditor.drawingMode === 'text' ? 'btn-primary' : 'btn-outline-primary'"
                                                        @click="setDrawingMode('text')"
                                                        title="Texte">
                                                    <i class="fas fa-font"></i>
                                                </button>
                                            </div>

                                            <button v-if="siteMapEditor.editMode && siteMapEditor.drawingMode === 'polyline' && siteMapEditor.polylinePoints.length >= 2"
                                                    class="btn btn-sm btn-warning"
                                                    @click="finishPolyline()">
                                                <i class="fas fa-check"></i> Terminer
                                            </button>

                                            <!-- Repeat Pattern Tool -->
                                            <button v-if="siteMapEditor.editMode"
                                                    class="btn btn-sm btn-outline-info"
                                                    @click="openRepeatPatternDialog()"
                                                    title="Répéter un schéma de plantation en grille">
                                                <i class="fas fa-th"></i> Grille
                                            </button>

                                            <div class="vr mx-2"></div>

                                            <!-- Layer Selector -->
                                            <div class="d-flex align-items-center gap-2">
                                                <label class="mb-0 small fw-bold">Couche:</label>
                                                <select v-model="siteMapEditor.selectedLayer"
                                                        @change="switchLayer(siteMapEditor.selectedLayer)"
                                                        class="form-select form-select-sm"
                                                        style="width: 200px;">
                                                    <option v-for="layer in siteMapEditor.layers"
                                                            :key="layer.id"
                                                            :value="layer">
                                                        @{{ layer.name }} (@{{ layer.start_date }})
                                                    </option>
                                                </select>
                                                <button v-if="siteMapEditor.editMode"
                                                        class="btn btn-sm btn-outline-success"
                                                        @click="openCreateLayerModal()"
                                                        title="Créer une nouvelle couche">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button v-if="siteMapEditor.editMode && siteMapEditor.layers.length > 1"
                                                        class="btn btn-sm btn-outline-danger"
                                                        @click="deleteLayer(siteMapEditor.selectedLayer)"
                                                        title="Supprimer la couche">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            <div class="ms-auto d-flex align-items-center gap-2">
                                                <small v-if="siteMapEditor.editMode" class="text-muted">
                                                    <i class="fas fa-info-circle"></i>
                                                    Cliquez <i class="fas fa-plus text-primary"></i> pour ajouter une plante, puis glissez-déposez pour positionner
                                                </small>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-map-pin"></i>
                                                    @{{ getPositionedPlantsCount }} / @{{ siteMapEditor.plants.length }} positionnés
                                                </span>
                                            </div>
                                        </div>

                                        <!-- SVG Container -->
                                        <div class="flex-grow-1 overflow-auto position-relative">
                                            <svg id="siteMapSvg"
                                                 :viewBox="`0 0 ${siteMapEditor.svgDimensions.width} ${siteMapEditor.svgDimensions.height}`"
                                                 class="w-100"
                                                 style="height: 100%; min-height: 500px; background: #f8f9fa; display: block;"
                                                 @mousedown="handleSvgMouseDown"
                                                 @mousemove="handleSvgMouseMove"
                                                 @mouseup="handleSvgMouseUp">

                                                <!-- Background Image (if site plan uploaded) -->
                                                <image v-if="siteMapEditor.site?.site_plan_image"
                                                       :href="siteMapEditor.site.site_plan_image"
                                                       x="0" y="0"
                                                       :width="siteMapEditor.svgDimensions.width"
                                                       :height="siteMapEditor.svgDimensions.height"
                                                       preserveAspectRatio="none" />

                                                <!-- Grid (if no background image) -->
                                                <defs v-if="!siteMapEditor.site?.site_plan_image">
                                                    <pattern id="smallGrid" width="20" height="20" patternUnits="userSpaceOnUse">
                                                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#dee2e6" stroke-width="0.5"/>
                                                    </pattern>
                                                    <pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse">
                                                        <rect width="100" height="100" fill="url(#smallGrid)"/>
                                                        <path d="M 100 0 L 0 0 0 100" fill="none" stroke="#adb5bd" stroke-width="1"/>
                                                    </pattern>
                                                </defs>
                                                <rect v-if="!siteMapEditor.site?.site_plan_image"
                                                      width="100%" height="100%" fill="url(#grid)" />

                                                <!-- Plant Markers -->
                                                <g v-for="plant in getPositionedPlants"
                                                   :key="plant.id"
                                                   :transform="`translate(${(plant.map_position_x / 100) * siteMapEditor.svgDimensions.width}, ${(plant.map_position_y / 100) * siteMapEditor.svgDimensions.height})`"
                                                   @mousedown="startDragPlant(plant, $event)"
                                                   @click="selectPlant(plant)"
                                                   :style="{ cursor: siteMapEditor.editMode ? 'grab' : 'pointer' }">

                                                    <!-- Hit area (invisible, larger for easier grab) -->
                                                    <circle r="18" fill="transparent" />

                                                    <!-- Marker Circle -->
                                                    <circle r="12"
                                                            :fill="getPlantMarkerColor(plant)"
                                                            :stroke="siteMapEditor.selectedPlant?.id === plant.id ? '#ffc107' : '#fff'"
                                                            :stroke-width="siteMapEditor.selectedPlant?.id === plant.id ? 4 : 2"
                                                            opacity="0.9" />

                                                    <!-- Plant Label -->
                                                    <text y="24"
                                                          text-anchor="middle"
                                                          font-size="11"
                                                          fill="#333"
                                                          font-weight="bold"
                                                          paint-order="stroke"
                                                          stroke="#fff"
                                                          stroke-width="3"
                                                          style="pointer-events: none; user-select: none;">
                                                        <tspan v-text="plant.name"></tspan>
                                                    </text>
                                                </g>

                                                <!-- Drawing Overlay Shapes -->
                                                <g v-for="(shape, index) in siteMapEditor.drawingShapes" :key="'shape-' + index">
                                                    <!-- Rectangle -->
                                                    <rect v-if="shape.type === 'rect'"
                                                          :x="shape.x"
                                                          :y="shape.y"
                                                          :width="shape.width"
                                                          :height="shape.height"
                                                          :stroke="shape.stroke || '#000'"
                                                          :stroke-width="shape.strokeWidth || 2"
                                                          :fill="shape.fill || 'none'"
                                                          @click="siteMapEditor.selectedShape = index"
                                                          style="cursor: pointer;" />

                                                    <!-- Circle -->
                                                    <circle v-if="shape.type === 'circle'"
                                                            :cx="shape.cx"
                                                            :cy="shape.cy"
                                                            :r="shape.r"
                                                            :stroke="shape.stroke || '#000'"
                                                            :stroke-width="shape.strokeWidth || 2"
                                                            :fill="shape.fill || 'none'"
                                                            @click="siteMapEditor.selectedShape = index"
                                                            style="cursor: pointer;" />

                                                    <!-- Polyline -->
                                                    <polyline v-if="shape.type === 'polyline'"
                                                              :points="shape.points"
                                                              :stroke="shape.stroke || '#000'"
                                                              :stroke-width="shape.strokeWidth || 2"
                                                              :fill="shape.fill || 'none'"
                                                              @click="siteMapEditor.selectedShape = index"
                                                              style="cursor: pointer;" />

                                                    <!-- Text -->
                                                    <text v-if="shape.type === 'text'"
                                                          :x="shape.x"
                                                          :y="shape.y"
                                                          :font-size="shape.fontSize || 16"
                                                          :fill="shape.fill || '#000'"
                                                          @click="siteMapEditor.selectedShape = index"
                                                          style="cursor: pointer;">
                                                        <tspan v-text="shape.content"></tspan>
                                                    </text>
                                                </g>

                                                <!-- Current shape being drawn -->
                                                <rect v-if="siteMapEditor.currentShape && siteMapEditor.currentShape.type === 'rect'"
                                                      :x="siteMapEditor.currentShape.x"
                                                      :y="siteMapEditor.currentShape.y"
                                                      :width="siteMapEditor.currentShape.width"
                                                      :height="siteMapEditor.currentShape.height"
                                                      :stroke="siteMapEditor.currentShape.stroke"
                                                      :stroke-width="siteMapEditor.currentShape.strokeWidth"
                                                      fill="none"
                                                      opacity="0.5" />

                                                <circle v-if="siteMapEditor.currentShape && siteMapEditor.currentShape.type === 'circle'"
                                                        :cx="siteMapEditor.currentShape.cx"
                                                        :cy="siteMapEditor.currentShape.cy"
                                                        :r="siteMapEditor.currentShape.r"
                                                        :stroke="siteMapEditor.currentShape.stroke"
                                                        :stroke-width="siteMapEditor.currentShape.strokeWidth"
                                                        fill="none"
                                                        opacity="0.5" />

                                                <!-- Polyline preview points -->
                                                <g v-if="siteMapEditor.drawingMode === 'polyline'">
                                                    <circle v-for="(point, idx) in siteMapEditor.polylinePoints"
                                                            :key="'poly-point-' + idx"
                                                            :cx="point.x"
                                                            :cy="point.y"
                                                            r="4"
                                                            fill="#0000ff" />
                                                    <polyline v-if="siteMapEditor.polylinePoints.length >= 2"
                                                              :points="siteMapEditor.polylinePoints.map(p => `${p.x},${p.y}`).join(' ')"
                                                              stroke="#0000ff"
                                                              stroke-width="2"
                                                              fill="none"
                                                              opacity="0.5" />
                                                </g>
                                            </svg>
                                        </div>

                                        <!-- Legend -->
                                        <div class="bg-white border-top p-2">
                                            <small class="text-muted">
                                                <span class="me-3"><span class="badge" style="background: #28a745;">●</span> Excellent</span>
                                                <span class="me-3"><span class="badge" style="background: #5cb85c;">●</span> Bon</span>
                                                <span class="me-3"><span class="badge" style="background: #ffc107;">●</span> Moyen</span>
                                                <span class="me-3"><span class="badge" style="background: #dc3545;">●</span> Mauvais</span>
                                                <span class="me-3"><span class="badge" style="background: #6c757d;">●</span> Mort</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

        <!-- Upload Observation Photo Modal -->
        <div class="modal fade" :class="{ 'show': showUploadPhotoModal }" id="uploadPhotoModal" tabindex="-1"
             v-show="showUploadPhotoModal" @click.self="closeUploadPhotoModal()"
             :style="{ display: showUploadPhotoModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg" @click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-camera me-2"></i>Ajouter une photo
                        </h5>
                        <button type="button" class="btn-close" @click="closeUploadPhotoModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="uploadObservationPhoto">
                            <!-- File Input -->
                            <div class="mb-3">
                                <label for="photoFileInput" class="form-label">Photo *</label>
                                <input type="file" class="form-control" id="photoFileInput"
                                       accept="image/jpeg,image/jpg,image/png,image/webp"
                                       @change="handlePhotoFileChange" required>
                                <small class="form-text text-muted">
                                    Formats acceptés: JPG, PNG, WEBP (max 10MB)
                                </small>
                            </div>

                            <!-- Image Preview -->
                            <div v-if="newPhoto.imagePreview" class="mb-3">
                                <img :src="newPhoto.imagePreview" alt="Aperçu"
                                     class="img-fluid rounded" style="max-height: 300px;">
                            </div>

                            <!-- Title -->
                            <div class="mb-3">
                                <label for="photoTitle" class="form-label">Titre</label>
                                <input v-model="newPhoto.title" type="text" class="form-control" id="photoTitle"
                                       placeholder="Ex: Floraison complète">
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label for="photoDescription" class="form-label">Description</label>
                                <textarea v-model="newPhoto.description" class="form-control" id="photoDescription" rows="2"
                                          placeholder="Décrivez ce que montre cette photo..."></textarea>
                            </div>

                            <!-- Photo Type -->
                            <div class="mb-3">
                                <label for="photoType" class="form-label">Type de photo</label>
                                <select v-model="newPhoto.photo_type" class="form-select" id="photoType">
                                    <option value="phenological_state">État phénologique</option>
                                    <option value="detail">Détail</option>
                                    <option value="comparison">Comparaison</option>
                                    <option value="context">Contexte</option>
                                    <option value="measurement">Mesure</option>
                                </select>
                            </div>

                            <!-- Public Checkbox -->
                            <div class="form-check mb-3">
                                <input v-model="newPhoto.is_public" class="form-check-input" type="checkbox" id="photoPublic">
                                <label class="form-check-label" for="photoPublic">
                                    Photo publique (visible par tous les utilisateurs)
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeUploadPhotoModal()">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="uploadObservationPhoto" :disabled="uploadingPhoto">
                            <span v-if="uploadingPhoto">
                                <i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...
                            </span>
                            <span v-else>
                                <i class="fas fa-upload me-1"></i>Télécharger
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Layer Modal -->
        <div class="modal fade" :class="{ 'show': siteMapEditor.showCreateLayerModal }" id="createLayerModal" tabindex="-1"
             v-show="siteMapEditor.showCreateLayerModal" @click.self="closeCreateLayerModal()"
             :style="{ display: siteMapEditor.showCreateLayerModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-dialog-centered" @click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-layer-group me-2"></i>Créer une nouvelle couche
                        </h5>
                        <button type="button" class="btn-close" @click="closeCreateLayerModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="createNewLayer()">
                            <div class="mb-3">
                                <label for="layerName" class="form-label">Nom de la couche *</label>
                                <input type="text"
                                       class="form-control"
                                       id="layerName"
                                       v-model="siteMapEditor.newLayerData.name"
                                       required
                                       placeholder="Ex: Printemps 2024, Configuration initiale">
                            </div>
                            <div class="mb-3">
                                <label for="layerStartDate" class="form-label">Date de début *</label>
                                <input type="date"
                                       class="form-control"
                                       id="layerStartDate"
                                       v-model="siteMapEditor.newLayerData.start_date"
                                       required>
                            </div>
                            <div class="mb-3">
                                <label for="layerEndDate" class="form-label">Date de fin (optionnel)</label>
                                <input type="date"
                                       class="form-control"
                                       id="layerEndDate"
                                       v-model="siteMapEditor.newLayerData.end_date">
                                <small class="form-text text-muted">Laissez vide si toujours active</small>
                            </div>
                            <div class="mb-3">
                                <label for="layerNotes" class="form-label">Notes</label>
                                <textarea class="form-control"
                                          id="layerNotes"
                                          v-model="siteMapEditor.newLayerData.notes"
                                          rows="3"
                                          placeholder="Description de cette version du plan..."></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit"
                                        class="btn btn-primary"
                                        :disabled="siteMapEditor.loading">
                                    <i class="fas fa-plus me-1"></i>
                                    <span v-if="siteMapEditor.loading">Création...</span>
                                    <span v-else>Créer la couche</span>
                                </button>
                                <button type="button"
                                        class="btn btn-secondary"
                                        @click="closeCreateLayerModal()">
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Repeat Pattern Modal -->
        <div class="modal fade" :class="{ 'show': siteMapEditor.showRepeatPatternModal }" id="repeatPatternModal" tabindex="-1"
             v-show="siteMapEditor.showRepeatPatternModal" @click.self="siteMapEditor.showRepeatPatternModal = false"
             :style="{ display: siteMapEditor.showRepeatPatternModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-dialog-centered" @click.stop>
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title"><i class="fas fa-th me-2"></i>Répéter en grille</h5>
                        <button type="button" class="btn-close btn-close-white" @click="siteMapEditor.showRepeatPatternModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Disposer les plantes non positionnées en grille sur le plan.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Colonnes</label>
                                <input v-model.number="siteMapEditor.repeatPattern.cols" type="number" class="form-control" min="1" max="20">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lignes</label>
                                <input v-model.number="siteMapEditor.repeatPattern.rows" type="number" class="form-control" min="1" max="20">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marge X (%)</label>
                                <input v-model.number="siteMapEditor.repeatPattern.marginX" type="number" class="form-control" min="5" max="40" step="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marge Y (%)</label>
                                <input v-model.number="siteMapEditor.repeatPattern.marginY" type="number" class="form-control" min="5" max="40" step="1">
                            </div>
                        </div>
                        <div class="alert alert-info small mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            <span v-text="getUnpositionedPlantsCount()"></span> plante(s) sans position seront disposées.
                            Grille <span v-text="siteMapEditor.repeatPattern.cols"></span> x <span v-text="siteMapEditor.repeatPattern.rows"></span>
                            = <span v-text="siteMapEditor.repeatPattern.cols * siteMapEditor.repeatPattern.rows"></span> emplacements.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="siteMapEditor.showRepeatPatternModal = false">Annuler</button>
                        <button type="button" class="btn btn-info" @click="applyRepeatPattern()"
                                :disabled="getUnpositionedPlantsCount() === 0">
                            <i class="fas fa-th me-1"></i>Appliquer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photo Gallery Modal (Lightbox) -->
        <div class="modal fade" :class="{ 'show': showPhotoGalleryModal }" id="photoGalleryModal" tabindex="-1"
             v-show="showPhotoGalleryModal" @click.self="closePhotoGallery()"
             :style="{ display: showPhotoGalleryModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-xl modal-dialog-centered" @click.stop>
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" v-if="currentView === 'plant-detail' && plantDetail.photos[selectedPhotoIndex]">
                            <span v-text="plantDetail.photos[selectedPhotoIndex].title || 'Photo'"></span>
                            <small class="ms-2 text-muted">
                                (<span v-text="selectedPhotoIndex + 1"></span> / <span v-text="plantDetail.photos.length"></span>)
                            </small>
                        </h5>
                        <h5 class="modal-title" v-else-if="observationPhotos[selectedPhotoIndex]">
                            <span v-text="observationPhotos[selectedPhotoIndex].title || 'Photo'"></span>
                            <small class="ms-2 text-muted">
                                (<span v-text="selectedPhotoIndex + 1"></span> / <span v-text="observationPhotos.length"></span>)
                            </small>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="closePhotoGallery()"></button>
                    </div>
                    <div class="modal-body text-center position-relative" style="min-height: 500px;">
                        <!-- Plant Photo Display -->
                        <div v-if="currentView === 'plant-detail' && plantDetail.photos[selectedPhotoIndex]">
                            <img :src="plantDetail.photos[selectedPhotoIndex].image_url || plantDetail.photos[selectedPhotoIndex].image"
                                 :alt="plantDetail.photos[selectedPhotoIndex].title || 'Photo'"
                                 class="img-fluid rounded" style="max-height: 70vh;">

                            <!-- Navigation Arrows -->
                            <button v-if="selectedPhotoIndex > 0"
                                    class="btn btn-light position-absolute top-50 start-0 translate-middle-y ms-3"
                                    @click="previousPhoto()" style="z-index: 10;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button v-if="selectedPhotoIndex < plantDetail.photos.length - 1"
                                    class="btn btn-light position-absolute top-50 end-0 translate-middle-y me-3"
                                    @click="nextPhoto()" style="z-index: 10;">
                                <i class="fas fa-chevron-right"></i>
                            </button>

                            <!-- Photo Info -->
                            <div class="mt-3">
                                <p v-if="plantDetail.photos[selectedPhotoIndex].description"
                                   class="text-white-50" v-text="plantDetail.photos[selectedPhotoIndex].description"></p>
                                <small class="text-muted d-block">
                                    Type: <span v-text="plantDetail.photos[selectedPhotoIndex].photo_type"></span>
                                </small>
                                <small class="text-muted d-block">
                                    Par: <span v-text="plantDetail.photos[selectedPhotoIndex].photographer"></span>
                                </small>
                            </div>
                        </div>

                        <!-- Observation Photo Display -->
                        <div v-else-if="observationPhotos[selectedPhotoIndex]">
                            <img :src="observationPhotos[selectedPhotoIndex].image_url || observationPhotos[selectedPhotoIndex].image"
                                 :alt="observationPhotos[selectedPhotoIndex].title || 'Photo'"
                                 class="img-fluid rounded" style="max-height: 70vh;">

                            <!-- Navigation Arrows -->
                            <button v-if="selectedPhotoIndex > 0"
                                    class="btn btn-light position-absolute top-50 start-0 translate-middle-y ms-3"
                                    @click="previousPhoto()" style="z-index: 10;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button v-if="selectedPhotoIndex < observationPhotos.length - 1"
                                    class="btn btn-light position-absolute top-50 end-0 translate-middle-y me-3"
                                    @click="nextPhoto()" style="z-index: 10;">
                                <i class="fas fa-chevron-right"></i>
                            </button>

                            <!-- Photo Info -->
                            <div class="mt-3">
                                <p v-if="observationPhotos[selectedPhotoIndex].description"
                                   class="text-white-50" v-text="observationPhotos[selectedPhotoIndex].description"></p>
                                <small class="text-muted d-block">
                                    Type: <span v-text="observationPhotos[selectedPhotoIndex].photo_type"></span>
                                </small>
                                <small class="text-muted d-block">
                                    Par: <span v-text="observationPhotos[selectedPhotoIndex].photographer"></span>
                                </small>
                            </div>

                            <!-- Delete Button (Owner Only) -->
                            <div v-if="observationPhotos[selectedPhotoIndex].photographer === user.username" class="mt-3">
                                <button class="btn btn-outline-danger btn-sm"
                                        @click="deleteObservationPhoto(observationPhotos[selectedPhotoIndex].id)">
                                    <i class="fas fa-trash me-1"></i>Supprimer cette photo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Photo Modal -->
        <div class="modal fade" id="addPhotoModal" tabindex="-1" aria-labelledby="addPhotoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPhotoModalLabel">
                            <i class="fas fa-camera me-2"></i>Ajouter une nouvelle photo
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="addPhoto">
                            <div class="mb-3">
                                <label for="photoFile" class="form-label">Photo *</label>
                                <input type="file" class="form-control" id="photo-file" accept="image/*" required>
                            </div>
                            <div class="mb-3" v-if="!newPhoto.plant">
                                <label for="photoPlant" class="form-label">Plante *</label>
                                <select v-model="newPhoto.plant" class="form-select" id="photoPlant" required>
                                    <option value="">Sélectionner une plante</option>
                                    <option v-for="plant in plants" :key="plant.id" :value="plant.id" v-text="plant.name">
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3" v-else>
                                <label class="form-label">Plante</label>
                                <div class="form-control-plaintext">
                                    <strong v-text="plants.find(p => p.id === newPhoto.plant)?.name || 'Plante sélectionnée'"></strong>
                                    <small class="text-muted d-block">Photo ajoutée à cette plante</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="plantPhotoTitle" class="form-label">Titre</label>
                                <input v-model="newPhoto.title" type="text" class="form-control" id="plantPhotoTitle">
                            </div>
                            <div class="mb-3">
                                <label for="plantPhotoDescription" class="form-label">Description</label>
                                <textarea v-model="newPhoto.description" class="form-control" id="plantPhotoDescription" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="plantPhotoType" class="form-label">Type de photo</label>
                                <select v-model="newPhoto.photo_type" class="form-select" id="plantPhotoType">
                                    <option value="general">Générale</option>
                                    <option value="leaves">Feuillage</option>
                                    <option value="flowers">Floraison</option>
                                    <option value="fruits">Fructification</option>
                                    <option value="bark">Écorce</option>
                                    <option value="habitat">Habitat</option>
                                    <option value="detail">Détail</option>
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input v-model="newPhoto.is_public" class="form-check-input" type="checkbox" id="plantPhotoPublic">
                                <label class="form-check-label" for="plantPhotoPublic">
                                    Photo publique
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" :disabled="photoOperationLoading">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="addPhoto" :disabled="photoOperationLoading">
                            <span v-if="photoOperationLoading">
                                <i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...
                            </span>
                            <span v-else>
                                <i class="fas fa-save me-1"></i>Enregistrer
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Photo Modal -->
        <div class="modal fade" :class="{ 'show': showEditPhotoModal }" id="editPhotoModal" tabindex="-1" v-show="showEditPhotoModal" @click.self="closeModal()" :style="{ display: showEditPhotoModal ? 'block' : 'none' }">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Modifier la photo
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="updatePhoto">
                            <div class="mb-3">
                                <label for="editPhotoTitle" class="form-label">Titre</label>
                                <input v-model="editPhoto.title" type="text" class="form-control" id="editPhotoTitle">
                            </div>
                            <div class="mb-3">
                                <label for="editPhotoDescription" class="form-label">Description</label>
                                <textarea v-model="editPhoto.description" class="form-control" id="editPhotoDescription" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editPhotoType" class="form-label">Type de photo</label>
                                <select v-model="editPhoto.photo_type" class="form-select" id="editPhotoType">
                                    <option value="general">Générale</option>
                                    <option value="leaves">Feuillage</option>
                                    <option value="flowers">Floraison</option>
                                    <option value="fruits">Fructification</option>
                                    <option value="bark">Écorce</option>
                                    <option value="habitat">Habitat</option>
                                    <option value="detail">Détail</option>
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input v-model="editPhoto.is_public" class="form-check-input" type="checkbox" id="editPhotoPublic">
                                <label class="form-check-label" for="editPhotoPublic">
                                    Photo publique
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()" :disabled="photoOperationLoading">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="updatePhoto" :disabled="photoOperationLoading">
                            <span v-if="photoOperationLoading">
                                <i class="fas fa-spinner fa-spin me-1"></i>Mise à jour...
                            </span>
                            <span v-else>
                                <i class="fas fa-save me-1"></i>Enregistrer les modifications
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Site Modal -->
        <div class="modal fade" :class="{ 'show': showEditSiteModal }" id="editSiteModal" tabindex="-1" v-show="showEditSiteModal" @click.self="closeModal()" :style="{ display: showEditSiteModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Modifier le site
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="updateSite">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editSiteName" class="form-label">Nom du site *</label>
                                    <input v-model="editSite.name" type="text" class="form-control" id="editSiteName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editSiteEnvironment" class="form-label">Environnement *</label>
                                    <select v-model="editSite.environment" class="form-select" id="editSiteEnvironment" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="urban">Urbain</option>
                                        <option value="suburban">Périurbain</option>
                                        <option value="rural">Rural</option>
                                        <option value="forest">Forêt</option>
                                        <option value="garden">Jardin/Parc</option>
                                        <option value="natural">Naturel</option>
                                        <option value="agricultural">Agricole</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editSiteDescription" class="form-label">Description</label>
                                <textarea v-model="editSite.description" class="form-control" id="editSiteDescription" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="editSiteLatitude" class="form-label">Latitude *</label>
                                    <input v-model="editSite.latitude" type="number" step="any" class="form-control" id="editSiteLatitude" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editSiteLongitude" class="form-label">Longitude *</label>
                                    <input v-model="editSite.longitude" type="number" step="any" class="form-control" id="editSiteLongitude" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editSiteAltitude" class="form-label">Altitude (m)</label>
                                    <input v-model="editSite.altitude" type="number" class="form-control" id="editSiteAltitude">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="editSiteSoil" class="form-label">Type de sol</label>
                                    <select v-model="editSite.soil_type" class="form-select" id="editSiteSoil">
                                        <option value="">Non spécifié</option>
                                        <option value="argileux">Argileux</option>
                                        <option value="sableux">Sableux</option>
                                        <option value="limoneux">Limoneux</option>
                                        <option value="calcaire">Calcaire</option>
                                        <option value="humifère">Humifère</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editSiteExposure" class="form-label">Exposition</label>
                                    <select v-model="editSite.exposure" class="form-select" id="editSiteExposure">
                                        <option value="">Non spécifiée</option>
                                        <option value="nord">Nord</option>
                                        <option value="sud">Sud</option>
                                        <option value="est">Est</option>
                                        <option value="ouest">Ouest</option>
                                        <option value="sud-est">Sud-Est</option>
                                        <option value="sud-ouest">Sud-Ouest</option>
                                        <option value="nord-est">Nord-Est</option>
                                        <option value="nord-ouest">Nord-Ouest</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editSiteClimate" class="form-label">Zone climatique</label>
                                    <select v-model="editSite.climate_zone" class="form-select" id="editSiteClimate">
                                        <option value="">Non spécifiée</option>
                                        <option value="océanique">Océanique</option>
                                        <option value="continental">Continental</option>
                                        <option value="méditerranéen">Méditerranéen</option>
                                        <option value="montagnard">Montagnard</option>
                                        <option value="semi-continental">Semi-continental</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input v-model="editSite.is_private" class="form-check-input" type="checkbox" id="editSitePrivate">
                                <label class="form-check-label" for="editSitePrivate">
                                    Site privé
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="updateSite">
                            <i class="fas fa-save me-1"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de test simple -->
        <div class="modal fade" :class="{ 'show': showTestSiteModal }" id="testSiteModal" tabindex="-1" v-show="showTestSiteModal" @click.self="showTestSiteModal = false" :style="{ display: showTestSiteModal ? 'block' : 'none' }">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-vial me-2"></i>Test Formulaire Site
                        </h5>
                        <button type="button" class="btn-close" @click="showTestSiteModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="testSiteSubmit">
                            <div class="mb-3">
                                <label for="testSiteName" class="form-label">Nom du site</label>
                                <input v-model="testSiteForm.name" type="text" class="form-control" id="testSiteName" placeholder="Ex: Mon jardin" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="testLat" class="form-label">Latitude</label>
                                    <input v-model="testSiteForm.latitude" type="number" step="any" class="form-control" id="testLat" placeholder="Ex: 45.7640" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="testLon" class="form-label">Longitude</label>
                                    <input v-model="testSiteForm.longitude" type="number" step="any" class="form-control" id="testLon" placeholder="Ex: 4.8574" required>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Ceci est un formulaire de test pour vérifier la fonctionnalité.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showTestSiteModal = false">Annuler</button>
                        <button type="button" class="btn btn-primary" @click="testSiteSubmit">
                            <i class="fas fa-vial me-1"></i>Tester Enregistrement
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark Plant as Dead Modal -->
        <div class="modal fade" :class="{ 'show': showMarkDeadModal }" id="markDeadModal" tabindex="-1" v-show="showMarkDeadModal" @click.self="closeModal()" :style="{ display: showMarkDeadModal ? 'block' : 'none' }">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-skull me-2"></i>Marquer la plante comme morte
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="markPlantAsDead">
                            <div class="mb-3">
                                <label for="deathDate" class="form-label">Date de mort *</label>
                                <input v-model="markDeadForm.death_date" type="date" class="form-control" id="deathDate" required>
                            </div>
                            <div class="mb-3">
                                <label for="deathCause" class="form-label">Cause de mort</label>
                                <select v-model="markDeadForm.death_cause" class="form-select" id="deathCause">
                                    <option value="">Sélectionner...</option>
                                    <option value="disease">Maladie</option>
                                    <option value="pests">Ravageurs</option>
                                    <option value="frost">Gel</option>
                                    <option value="drought">Sécheresse</option>
                                    <option value="flooding">Inondation</option>
                                    <option value="wind">Vent/Tempête</option>
                                    <option value="age">Vieillesse</option>
                                    <option value="accident">Accident</option>
                                    <option value="human">Intervention humaine</option>
                                    <option value="unknown">Cause inconnue</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="deathNotes" class="form-label">Notes</label>
                                <textarea v-model="markDeadForm.death_notes" class="form-control" id="deathNotes" rows="3" placeholder="Circonstances, symptômes observés..."></textarea>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Cette action marquera la plante comme morte. L'historique des observations sera préservé.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()">Annuler</button>
                        <button type="button" class="btn btn-warning" @click="markPlantAsDead">
                            <i class="fas fa-skull me-1"></i>Marquer comme morte
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Replace Plant Modal -->
        <div class="modal fade" :class="{ 'show': showReplacePlantModal }" id="replacePlantModal" tabindex="-1" v-show="showReplacePlantModal" @click.self="closeModal()" :style="{ display: showReplacePlantModal ? 'block' : 'none' }">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exchange-alt me-2"></i>Remplacer la plante
                        </h5>
                        <button type="button" class="btn-close" @click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="replacePlant">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                La nouvelle plante héritera de la position et du propriétaire de l'ancienne plante. L'historique complet sera préservé.
                            </div>

                            <h6 class="mb-3">Nouvelle plante</h6>

                            <div class="mb-3">
                                <label for="newPlantName" class="form-label">Nom de la plante *</label>
                                <input v-model="replacePlantForm.new_plant.name" type="text" class="form-control" id="newPlantName" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="newPlantTaxon" class="form-label">Taxon *</label>
                                    <div class="position-relative">
                                        <input
                                            v-model="taxonAutocompleteReplace.query"
                                            @input="searchTaxons('replace')"
                                            @focus="taxonAutocompleteReplace.showDropdown = taxonAutocompleteReplace.results.length > 0"
                                            @blur="closeTaxonDropdown('replace')"
                                            @keydown="handleTaxonKeydown($event, 'replace')"
                                            type="text"
                                            class="form-control"
                                            id="newPlantTaxon"
                                            placeholder="Rechercher un taxon (min. 2 caractères)..."
                                            autocomplete="off"
                                            required
                                        >
                                        <button
                                            v-if="taxonAutocompleteReplace.selectedTaxon"
                                            @click="clearTaxonSelection('replace')"
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary position-absolute"
                                            style="right: 5px; top: 5px;"
                                            title="Effacer la sélection"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div v-if="taxonAutocompleteReplace.loading" class="position-absolute" style="right: 35px; top: 10px;">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </div>
                                        <ul
                                            v-show="taxonAutocompleteReplace.showDropdown && taxonAutocompleteReplace.results.length > 0"
                                            class="list-group position-absolute w-100 shadow-sm"
                                            style="z-index: 1060; max-height: 300px; overflow-y: auto;"
                                        >
                                            <li
                                                v-for="taxon in taxonAutocompleteReplace.results"
                                                :key="taxon.id"
                                                @click="selectTaxon(taxon, 'replace')"
                                                class="list-group-item list-group-item-action"
                                                style="cursor: pointer;"
                                            >
                                                <strong v-text="taxon.binomial_name"></strong>
                                                <span v-if="taxon.common_name_fr" class="text-muted"> - <span v-text="taxon.common_name_fr"></span></span>
                                                <span v-if="taxon.family" class="badge bg-secondary ms-2" v-text="taxon.family"></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="newPlantCategory" class="form-label">Catégorie *</label>
                                    <select v-model="replacePlantForm.new_plant.category" class="form-select" id="newPlantCategory" required>
                                        <option value="">Sélectionner une catégorie...</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id" v-text="category.name"></option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="newPlantPlantingDate" class="form-label">Date de plantation *</label>
                                <input v-model="replacePlantForm.new_plant.planting_date" type="date" class="form-control" id="newPlantPlantingDate" required>
                            </div>

                            <div class="mb-3">
                                <label for="newPlantDescription" class="form-label">Description</label>
                                <textarea v-model="replacePlantForm.new_plant.description" class="form-control" id="newPlantDescription" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="newPlantNotes" class="form-label">Notes</label>
                                <textarea v-model="replacePlantForm.new_plant.notes" class="form-control" id="newPlantNotes" rows="2"></textarea>
                            </div>

                            <div class="form-check mb-3">
                                <input v-model="replacePlantForm.new_plant.is_private" class="form-check-input" type="checkbox" id="newPlantPrivate">
                                <label class="form-check-label" for="newPlantPrivate">
                                    Plante privée
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal()">Annuler</button>
                        <button type="button" class="btn btn-success" @click="replacePlant">
                            <i class="fas fa-exchange-alt me-1"></i>Remplacer la plante
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============= SITE DETAILED MAP VIEW ============= -->
        <div v-if="currentView === 'site-map' && siteMapData" class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-map-marked-alt text-success me-2"></i>
                        Carte détaillée : <span v-text="siteMapData.site.name"></span>
                    </h1>
                    <p class="text-muted mb-0">
                        <span v-text="siteMapData.total_plants"></span> plantes avec GPS
                        <span v-if="siteMapData.plants_without_gps" class="text-warning">
                            • <span v-text="siteMapData.plants_without_gps"></span> sans GPS
                        </span>
                    </p>
                </div>
                <button class="btn btn-outline-secondary" @click="closeSiteMap">
                    <i class="fas fa-arrow-left me-1"></i>Retour au site
                </button>
            </div>

            <div class="row">
                <!-- Map Container -->
                <div class="col-xl-9 col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-satellite me-2"></i>Carte satellite avec positions GPS
                            </h5>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="Centrer sur le site">
                                    <i class="fas fa-crosshairs"></i>
                                </button>
                                <button class="btn btn-outline-success" title="Plein écran">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Leaflet Map Container -->
                            <div id="site-detailed-map" style="height: 600px; width: 100%;"></div>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Cliquez sur les marqueurs pour voir les détails des plantes. 
                                Utilisez la molette pour zoomer et glisser pour naviguer.
                                <strong>Précision maximale</strong> : jusqu'à 20cm selon votre GPS.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Plant Details Panel -->
                <div class="col-xl-3 col-lg-4 mb-4">
                    <!-- Site Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-map-pin me-2"></i>Informations du site
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <small class="text-muted">Superficie</small><br>
                                <strong v-if="siteMapData.site.area_hectares">
                                    <span v-text="siteMapData.site.area_hectares"></span> hectares
                                </strong>
                                <span v-else class="text-muted">Non définie</span>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Altitude</small><br>
                                <strong v-if="siteMapData.site.altitude">
                                    <span v-text="siteMapData.site.altitude"></span> mètres
                                </strong>
                                <span v-else class="text-muted">Non définie</span>
                            </div>
                            <div>
                                <small class="text-muted">Coordonnées centre</small><br>
                                <code style="font-size: 0.8em;" v-if="siteMapData.site.coordinates">
                                    <span v-text="siteMapData.site.coordinates[0].toFixed(6)"></span>,<br>
                                    <span v-text="siteMapData.site.coordinates[1].toFixed(6)"></span>
                                </code>
                                <span v-else class="text-muted">Non disponibles</span>
                            </div>
                        </div>
                    </div>

                    <!-- Plants Statistics -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-seedling me-2"></i>Statistiques plantes
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-success mb-0" v-text="siteMapData.total_plants"></h4>
                                        <small class="text-muted">Avec GPS</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-warning mb-0" v-text="siteMapData.plants_without_gps || 0"></h4>
                                    <small class="text-muted">Sans GPS</small>
                                </div>
                            </div>
                            
                            <!-- Plants by Category -->
                            <div class="mt-3">
                                <small class="text-muted">Par catégorie :</small>
                                <div v-for="categoryGroup in groupPlantsByCategory(siteMapData.plants)" :key="categoryGroup.type">
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="small">
                                            <i class="fas" :class="{
                                                'fa-tree': categoryGroup.type === 'trees',
                                                'fa-leaf': categoryGroup.type === 'shrubs', 
                                                'fa-seedling': categoryGroup.type === 'plants'
                                            }"></i>
                                            <span v-text="categoryGroup.name"></span>
                                        </span>
                                        <span class="badge bg-secondary small" v-text="categoryGroup.count"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Plant Details -->
                    <div v-if="selectedPlantOnMap" class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Plante sélectionnée
                            </h6>
                        </div>
                        <div class="card-body">
                            <h6 v-text="selectedPlantOnMap.name" class="text-primary"></h6>
                            <p class="text-muted small mb-2">
                                <em v-text="selectedPlantOnMap.taxon.binomial_name"></em><br>
                                <span v-text="selectedPlantOnMap.taxon.common_name_fr"></span>
                            </p>
                            
                            <div class="mb-2">
                                <span class="badge" :class="{
                                    'bg-success': selectedPlantOnMap.health_status === 'excellent',
                                    'bg-primary': selectedPlantOnMap.health_status === 'good',
                                    'bg-warning': selectedPlantOnMap.health_status === 'fair',
                                    'bg-danger': selectedPlantOnMap.health_status === 'poor',
                                    'bg-dark': selectedPlantOnMap.health_status === 'dead'
                                }" v-text="getHealthLabel(selectedPlantOnMap.health_status)"></span>
                            </div>

                            <div class="small text-muted">
                                <div v-if="selectedPlantOnMap.exact_height" class="mb-1">
                                    <i class="fas fa-ruler-vertical me-1"></i>
                                    Hauteur : <span v-text="selectedPlantOnMap.exact_height"></span> m
                                </div>
                                <div v-if="selectedPlantOnMap.gps_accuracy" class="mb-1">
                                    <i class="fas fa-satellite-dish me-1"></i>
                                    Précision GPS : ±<span v-text="selectedPlantOnMap.gps_accuracy"></span> m
                                </div>
                                <div v-if="selectedPlantOnMap.distance_from_site_center" class="mb-1">
                                    <i class="fas fa-ruler-combined me-1"></i>
                                    Distance centre : <span v-text="Math.round(selectedPlantOnMap.distance_from_site_center)"></span> m
                                </div>
                                <div class="mb-1">
                                    <i class="fas fa-eye me-1"></i>
                                    Observations : <span v-text="selectedPlantOnMap.observations_count"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-sm btn-primary w-100" @click="navigateToSelectedPlant()">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    Voir détails de la plante
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Help/Instructions -->
                    <div v-else class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-question-circle me-2"></i>Instructions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <p><strong>Navigation :</strong></p>
                                <ul class="ps-3 mb-2">
                                    <li>Molette souris : zoomer/dézoomer</li>
                                    <li>Glisser : déplacer la carte</li>
                                    <li>Clic marqueur : informations plante</li>
                                </ul>
                                
                                <p><strong>Légende :</strong></p>
                                <ul class="ps-3 mb-0">
                                    <li>🏛️ Centre du site</li>
                                    <li>🌳 Arbres</li>
                                    <li>🌿 Arbustes</li>
                                    <li>🌱 Plantes</li>
                                </ul>
                                
                                <div class="mt-2 p-2 bg-light rounded">
                                    <strong>Précision GPS :</strong><br>
                                    Cette carte utilise des images satellites haute résolution. 
                                    La précision de localisation dépend de votre équipement GPS 
                                    (smartphone : ±3-5m, GPS professionnel : ±1m).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============= PLANT DETAIL VIEW ============= -->
        <div v-if="currentView === 'plant-detail' && plantDetail.plant" class="container-fluid px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div class="mb-2 mb-md-0">
                    <h1 class="h3 mb-0">
                        <span v-if="plantDetail.plant.category?.category_type === 'trees'">🌳</span>
                        <span v-else-if="plantDetail.plant.category?.category_type === 'shrubs'">🌿</span>
                        <span v-else>🌱</span>
                        <span v-text="plantDetail.plant.name"></span>
                    </h1>
                    <p class="text-muted mb-0">
                        <em v-text="plantDetail.plant.taxon?.binomial_name"></em>
                        <span v-if="plantDetail.plant.taxon?.common_name_fr"> • <span v-text="plantDetail.plant.taxon.common_name_fr"></span></span>
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-sm btn-outline-secondary" @click="backToPlants">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </button>
                    <button class="btn btn-sm btn-success" v-if="plantDetail.plant.coordinates"
                            @click="showSiteMap(plantDetail.plant.site)" title="Voir sur la carte">
                        <i class="fas fa-map-marked-alt me-1"></i>Carte
                    </button>
                    <button class="btn btn-sm btn-primary" @click="editPlant(plantDetail.plant)"
                            v-if="user.isAuthenticated && (user.id === plantDetail.plant.owner?.id || user.isStaff)">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </button>
                    <button class="btn btn-sm btn-warning" @click="openMarkDeadModal(plantDetail.plant)"
                            v-if="user.isAuthenticated && plantDetail.plant.status === 'alive' && (user.id === plantDetail.plant.owner?.id || user.isStaff)"
                            title="Marquer comme mort">
                        <i class="fas fa-skull me-1"></i>Mort
                    </button>
                    <button class="btn btn-sm btn-info" @click="openReplacePlantModal(plantDetail.plant)"
                            v-if="user.isAuthenticated && (plantDetail.plant.status === 'dead' || plantDetail.plant.status === 'alive') && (user.id === plantDetail.plant.owner?.id || user.isStaff)"
                            title="Remplacer cette plante">
                        <i class="fas fa-exchange-alt me-1"></i>Remplacer
                    </button>
                    <button class="btn btn-sm btn-danger" @click="confirmDeletePlant(plantDetail.plant)"
                            v-if="user.isAuthenticated && (user.id === plantDetail.plant.owner?.id || user.isStaff)">
                        <i class="fas fa-trash-alt me-1"></i>Supprimer
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Plant Information -->
                <div class="col-xl-8 col-lg-7 mb-4">
                    <!-- Basic Info Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Informations générales
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Catégorie:</strong></div>
                                        <div class="col-8" v-text="plantDetail.plant.category?.name"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Site:</strong></div>
                                        <div class="col-8" v-text="plantDetail.plant.site?.name"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>État:</strong></div>
                                        <div class="col-8">
                                            <span class="badge" :class="{
                                                'bg-success': plantDetail.plant.health_status === 'excellent',
                                                'bg-primary': plantDetail.plant.health_status === 'good',
                                                'bg-warning': plantDetail.plant.health_status === 'fair',
                                                'bg-danger': plantDetail.plant.health_status === 'poor',
                                                'bg-dark': plantDetail.plant.health_status === 'dead'
                                            }" v-text="getHealthLabel(plantDetail.plant.health_status)"></span>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Statut:</strong></div>
                                        <div class="col-8">
                                            <span class="badge" :class="getStatusBadgeClass(plantDetail.plant.status)" v-text="formatStatus(plantDetail.plant.status)"></span>
                                        </div>
                                    </div>
                                    <div class="row mb-2" v-if="plantDetail.plant.exact_height">
                                        <div class="col-4"><strong>Hauteur:</strong></div>
                                        <div class="col-8">
                                            <span v-text="plantDetail.plant.exact_height"></span> m
                                        </div>
                                    </div>
                                    <div class="row mb-2" v-if="plantDetail.plant.height_category">
                                        <div class="col-4"><strong>Catégorie hauteur:</strong></div>
                                        <div class="col-8" v-text="getHeightCategoryLabel(plantDetail.plant.height_category)"></div>
                                    </div>
                                    <div class="row mb-2" v-if="plantDetail.plant.position">
                                        <div class="col-4"><strong>Position:</strong></div>
                                        <div class="col-8" v-text="plantDetail.plant.position.label"></div>
                                    </div>
                                    <div class="row mb-2" v-if="plantDetail.plant.clone_or_accession">
                                        <div class="col-4"><strong>Clone/Accession:</strong></div>
                                        <div class="col-8" v-text="plantDetail.plant.clone_or_accession"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="row mb-2" v-if="plantDetail.plant.planting_date">
                                        <div class="col-4"><strong>Plantation:</strong></div>
                                        <div class="col-8" v-text="formatDate(plantDetail.plant.planting_date)"></div>
                                    </div>
                                    <div class="row mb-2" v-if="plantDetail.plant.planting_date || plantDetail.plant.age_years">
                                        <div class="col-4"><strong>Âge:</strong></div>
                                        <div class="col-8">
                                            <span v-text="computePlantAge(plantDetail.plant)"></span>
                                            <small v-if="plantDetail.plant.age_years" class="text-muted">(dont <span v-text="plantDetail.plant.age_years"></span> ans à la plantation)</small>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-4"><strong>Propriétaire:</strong></div>
                                        <div class="col-8" v-text="plantDetail.plant.owner?.name || 'Non défini'"></div>
                                    </div>
                                    <div class="row mb-2" v-if="plantDetail.plant.is_private">
                                        <div class="col-4"><strong>Visibilité:</strong></div>
                                        <div class="col-8"><span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Privée</span></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div v-if="plantDetail.plant.description" class="mt-3">
                                <h6>Description</h6>
                                <p class="text-muted" v-text="plantDetail.plant.description"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Death Info Card (for dead/replaced plants) -->
                    <div class="card mb-4 border-danger" v-if="plantDetail.plant.status === 'dead' || plantDetail.plant.status === 'replaced'">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-skull-crossbones me-2"></i>Informations de décès
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-2" v-if="plantDetail.plant.death_date">
                                    <small class="text-muted">Date de décès</small><br>
                                    <strong v-text="formatDate(plantDetail.plant.death_date)"></strong>
                                </div>
                                <div class="col-md-4 mb-2" v-if="plantDetail.plant.death_cause">
                                    <small class="text-muted">Cause</small><br>
                                    <strong v-text="getDeathCauseLabel(plantDetail.plant.death_cause)"></strong>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted">Statut</small><br>
                                    <span class="badge" :class="getStatusBadgeClass(plantDetail.plant.status)" v-text="formatStatus(plantDetail.plant.status)"></span>
                                </div>
                            </div>
                            <div v-if="plantDetail.plant.death_notes" class="mt-2">
                                <small class="text-muted">Notes</small><br>
                                <span v-text="plantDetail.plant.death_notes"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes & Additional Info Card -->
                    <div class="card mb-4" v-if="plantDetail.plant.notes || plantDetail.plant.anecdotes || plantDetail.plant.cultural_significance || plantDetail.plant.ecological_notes || plantDetail.plant.care_notes">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-sticky-note me-2"></i>Notes et informations complémentaires
                            </h5>
                        </div>
                        <div class="card-body">
                            <div v-if="plantDetail.plant.notes" class="mb-3">
                                <h6><i class="fas fa-pencil-alt me-1"></i>Notes</h6>
                                <p class="text-muted mb-0" v-text="plantDetail.plant.notes"></p>
                            </div>
                            <div v-if="plantDetail.plant.anecdotes" class="mb-3">
                                <h6><i class="fas fa-book me-1"></i>Anecdotes</h6>
                                <p class="text-muted mb-0" v-text="plantDetail.plant.anecdotes"></p>
                            </div>
                            <div v-if="plantDetail.plant.cultural_significance" class="mb-3">
                                <h6><i class="fas fa-landmark me-1"></i>Importance culturelle</h6>
                                <p class="text-muted mb-0" v-text="plantDetail.plant.cultural_significance"></p>
                            </div>
                            <div v-if="plantDetail.plant.ecological_notes" class="mb-3">
                                <h6><i class="fas fa-leaf me-1"></i>Notes écologiques</h6>
                                <p class="text-muted mb-0" v-text="plantDetail.plant.ecological_notes"></p>
                            </div>
                            <div v-if="plantDetail.plant.care_notes" class="mb-3">
                                <h6><i class="fas fa-hand-holding-heart me-1"></i>Notes d'entretien</h6>
                                <p class="text-muted mb-0" v-text="plantDetail.plant.care_notes"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Succession Card -->
                    <div class="card mb-4" v-if="plantDetail.plant.replaces_plant || plantDetail.plant.replaced_by">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exchange-alt me-2"></i>Succession de plantation
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Predecessor (plant this one replaces) -->
                            <div v-if="plantDetail.plant.replaces_plant" class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-arrow-left text-warning me-2"></i>
                                    <small class="text-muted">Cette plante a remplacé:</small>
                                </div>
                                <div class="card bg-light border-warning">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong v-text="plantDetail.plant.replaces_plant.name"></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <em v-if="plantDetail.plant.replaces_plant.taxon" v-text="plantDetail.plant.replaces_plant.taxon.binomial_name"></em>
                                                    <span v-if="plantDetail.plant.replaces_plant.taxon?.common_name_fr"> (<span v-text="plantDetail.plant.replaces_plant.taxon.common_name_fr"></span>)</span>
                                                    <span v-if="plantDetail.plant.replaces_plant.planting_date"> • Planté le <span v-text="formatDate(plantDetail.plant.replaces_plant.planting_date)"></span></span>
                                                    <span v-if="plantDetail.plant.replaces_plant.death_date"> • Mort le <span v-text="formatDate(plantDetail.plant.replaces_plant.death_date)"></span></span>
                                                    <span v-if="plantDetail.plant.replaces_plant.death_cause"> (<span v-text="getDeathCauseLabel(plantDetail.plant.replaces_plant.death_cause)"></span>)</span>
                                                </small>
                                                <br>
                                                <span class="badge mt-1" :class="getStatusBadgeClass(plantDetail.plant.replaces_plant.status)" v-text="formatStatus(plantDetail.plant.replaces_plant.status)"></span>
                                            </div>
                                            <div>
                                                <button
                                                    @click="viewPlantDetail(plantDetail.plant.replaces_plant.id)"
                                                    class="btn btn-sm btn-outline-warning"
                                                    title="Voir la plante remplacée"
                                                >
                                                    <i class="fas fa-eye me-1"></i>Voir
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Successor (plant that replaced this one) -->
                            <div v-if="plantDetail.plant.replaced_by">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-arrow-right text-success me-2"></i>
                                    <small class="text-muted">Cette plante a été remplacée par:</small>
                                </div>
                                <div class="card bg-light border-success">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong v-text="plantDetail.plant.replaced_by.name"></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <em v-if="plantDetail.plant.replaced_by.taxon" v-text="plantDetail.plant.replaced_by.taxon.binomial_name"></em>
                                                    <span v-if="plantDetail.plant.replaced_by.taxon?.common_name_fr"> (<span v-text="plantDetail.plant.replaced_by.taxon.common_name_fr"></span>)</span>
                                                    <span v-if="plantDetail.plant.replaced_by.planting_date"> • Planté le <span v-text="formatDate(plantDetail.plant.replaced_by.planting_date)"></span></span>
                                                </small>
                                                <br>
                                                <span class="badge mt-1" :class="getStatusBadgeClass(plantDetail.plant.replaced_by.status)" v-text="formatStatus(plantDetail.plant.replaced_by.status)"></span>
                                            </div>
                                            <div>
                                                <button
                                                    @click="viewPlantDetail(plantDetail.plant.replaced_by.id)"
                                                    class="btn btn-sm btn-outline-success"
                                                    title="Voir la plante remplaçante"
                                                >
                                                    <i class="fas fa-eye me-1"></i>Voir
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Position timeline link (if position exists) -->
                            <div v-if="plantDetail.plant.position && (plantDetail.plant.replaces_plant || plantDetail.plant.replaced_by)" class="mt-3 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Ces plantes partagent la même position: <strong v-text="plantDetail.plant.position.label"></strong>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- GPS Location Card -->
                    <div class="card mb-4" v-if="plantDetail.plant.coordinates">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i>Localisation GPS
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Latitude</small><br>
                                    <code v-text="plantDetail.plant.latitude ? parseFloat(plantDetail.plant.latitude).toFixed(6) : '-'"></code>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Longitude</small><br>
                                    <code v-text="plantDetail.plant.longitude ? parseFloat(plantDetail.plant.longitude).toFixed(6) : '-'"></code>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Précision</small><br>
                                    <span v-if="plantDetail.plant.gps_accuracy">±<span v-text="plantDetail.plant.gps_accuracy"></span>m</span>
                                    <span v-else class="text-muted">Non définie</span>
                                </div>
                            </div>
                            <div class="mt-3" v-if="plantDetail.plant.distance_from_site_center">
                                <small class="text-muted">Distance du centre du site:</small>
                                <strong><span v-text="Math.round(plantDetail.plant.distance_from_site_center)"></span> mètres</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Taxonomic Info Card -->
                    <div class="card mb-4" v-if="plantDetail.plant.taxon">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-dna me-2"></i>Taxonomie
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2" v-if="plantDetail.plant.taxon.family">
                                        <small class="text-muted">Famille:</small><br>
                                        <strong v-text="plantDetail.plant.taxon.family"></strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Genre:</small><br>
                                        <em v-text="plantDetail.plant.taxon.genus"></em>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Espèce:</small><br>
                                        <em v-text="plantDetail.plant.taxon.species"></em>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2" v-if="plantDetail.plant.taxon.author">
                                        <small class="text-muted">Auteur:</small><br>
                                        <span v-text="plantDetail.plant.taxon.author"></span>
                                    </div>
                                    <div class="mb-2" v-if="plantDetail.plant.taxon.common_name_fr">
                                        <small class="text-muted">Nom français:</small><br>
                                        <span v-text="plantDetail.plant.taxon.common_name_fr"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Photos Gallery Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-images me-2"></i>
                                    Photos (<span v-text="(plantDetail.photos || []).length"></span>)
                                </h5>
                                <button v-if="user.isAuthenticated" class="btn btn-sm btn-light" @click="openModal('photo', { plantId: currentPlant })">
                                    <i class="fas fa-plus me-1"></i>Ajouter
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div v-if="plantDetail.photos && plantDetail.photos.length > 0" class="row g-3">
                                <div v-for="(photo, index) in plantDetail.photos" :key="photo.id" class="col-md-4 col-sm-6">
                                    <div class="card h-100 shadow-sm photo-card position-relative">
                                        <span v-if="photo.is_main_photo" class="main-photo-badge">
                                            <i class="fas fa-star me-1"></i>Principale
                                        </span>
                                        <img :src="photo.image_url || photo.image" :alt="photo.title || 'Photo plante'"
                                             class="card-img-top photo-card-img"
                                             @click="openPhotoGallery(index)" title="Cliquer pour agrandir">
                                        <div class="card-body p-2 photo-card-body">
                                            <small v-if="photo.title" class="d-block text-truncate photo-card-title" v-text="photo.title"></small>
                                            <small class="photo-card-type text-truncate d-block" v-text="photo.photo_type"></small>
                                        </div>
                                        <div class="card-footer p-2 bg-light" v-if="user.isAuthenticated && (photo.photographer === user.username || user.isStaff)">
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <button v-if="!photo.is_main_photo" class="btn btn-outline-primary" @click="setAsMainPhoto(photo.id)" title="Définir comme photo principale">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary" @click="openEditPhotoModal(photo)" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" @click="deletePlantPhoto(photo.id)" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-muted text-center py-4">
                                <i class="fas fa-camera fa-3x mb-3 d-block"></i>
                                <p>Aucune photo pour cette plante</p>
                                <button v-if="user.isAuthenticated" class="btn btn-success" @click="openModal('photo', { plantId: currentPlant })">
                                    <i class="fas fa-plus me-1"></i>Ajouter la première photo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-xl-4 col-lg-5 mb-4">
                    <!-- Quick Stats -->
                    <div class="card mb-3">
                        <div class="card-body text-center">
                            <div class="row">
                                <div class="col-4">
                                    <h3 class="text-primary mb-0" v-text="plantDetail.statistics?.observations_count || 0"></h3>
                                    <small class="text-muted">Observations</small>
                                </div>
                                <div class="col-4">
                                    <h3 class="text-success mb-0" v-text="plantDetail.statistics?.photos_count || 0"></h3>
                                    <small class="text-muted">Photos</small>
                                </div>
                                <div class="col-4">
                                    <h3 class="text-warning mb-0">
                                        <span v-if="plantDetail.plant.planting_date || plantDetail.plant.age_years" v-text="computePlantAge(plantDetail.plant)"></span>
                                        <span v-else>-</span>
                                    </h3>
                                    <small class="text-muted">Âge</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Observations -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-eye me-2"></i>Observations récentes
                            </h6>
                        </div>
                        <div class="card-body">
                            <div v-if="plantDetail.observations.length > 0">
                                <div v-for="obs in plantDetail.observations.slice(0, 5)" :key="obs.id" 
                                     class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <small class="fw-bold" v-text="obs.phenological_stage?.stage_description || '-'"></small><br>
                                        <small class="text-muted" v-text="formatDate(obs.observation_date)"></small>
                                    </div>
                                    <span class="badge bg-secondary" v-text="obs.phenological_stage?.stage_code || '-'"></span>
                                </div>
                            </div>
                            <div v-else class="text-muted text-center">
                                <i class="fas fa-eye-slash fa-2x mb-2"></i><br>
                                Aucune observation
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-tools me-2"></i>Actions rapides
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" @click="openModal('observation', { plantId: currentPlant })">
                                    <i class="fas fa-plus me-2"></i>Nouvelle observation
                                </button>
                                <button class="btn btn-success" @click="openModal('photo', { plantId: currentPlant })">
                                    <i class="fas fa-camera me-2"></i>Ajouter photo
                                </button>
                                <button class="btn btn-info" v-if="plantDetail.plant.coordinates">
                                    <i class="fas fa-map-marked-alt me-2"></i>Mettre à jour GPS
                                </button>
                                <button class="btn btn-outline-secondary">
                                    <i class="fas fa-chart-line me-2"></i>Voir statistiques
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Loading Plant Detail -->
        <div v-if="currentView === 'plant-detail' && plantDetail.loading" class="container-fluid px-4">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <div class="spinner-border text-success" style="width: 3rem; height: 3rem;"></div>
                    <h4 class="mt-3">Chargement des détails de la plante...</h4>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
