// PhenoLab Vue.js Application

const { createApp } = Vue;

createApp({
    data() {
        return {
            // Current view state
            currentView: 'dashboard',
        
        // Site detailed mapping
        siteMapData: null,
        siteMapVisible: false,
        selectedPlantOnMap: null,
        mapInstance: null,
            
            // Site detail view
            currentSite: null,
            
            // Plant navigation and detail
            currentPlant: null,
            plantDetail: {
                plant: null,
                loading: false,
                observations: [],
                photos: [],
                statistics: null
            },
            
            // User data
            user: {
                username: 'Utilisateur',
                isAuthenticated: false,
                id: null,
                email: '',
                isStaff: false,
                isSuperuser: false
            },
            
            // Statistics data
            statistics: {
                totalSites: 0,
                totalPlants: 0,
                totalObservations: 0,
                currentYearObservations: 0
            },
            
            // Recent activities
            recentActivities: [],
            
            // Sites data
            sites: [],
            filteredSites: [],
            sitesViewMode: 'grid', // 'grid' or 'map'
            siteFilters: {
                search: '',
                environment: '',
                showPrivate: false
            },
            
            // Site detail data
            siteDetail: {
                site: null,
                plants: [],
                plantsCount: 0,
                totalObservations: 0,
                loading: false,
                pagination: {
                    count: 0,
                    next: null,
                    previous: null,
                    current_page: 1,
                    total_pages: 1
                },
                filters: {
                    search: '',
                    category: '',
                    status: '',
                    health_status: '',
                    has_observations: null,
                    has_photos: null,
                    planting_date_after: '',
                    planting_date_before: '',
                    ordering: 'name',
                    page_size: 25
                }
            },

            // Site Map Editor data
            siteMapEditor: {
                active: false,
                editMode: false,
                site: null,
                plants: [],
                selectedPlant: null,
                draggingPlant: null,
                dragStartX: 0,
                dragStartY: 0,
                svgDimensions: { width: 800, height: 600 },
                zoom: 1,
                pan: { x: 0, y: 0 },
                unsavedChanges: false,
                loading: false,
                // Layer management
                layers: [],
                selectedLayer: null,
                showCreateLayerModal: false,
                newLayerData: {
                    name: '',
                    start_date: '',
                    end_date: '',
                    notes: ''
                },
                // Drawing tools
                drawingMode: 'select', // 'select' | 'rect' | 'circle' | 'polyline' | 'text'
                drawingShapes: [],
                currentShape: null,
                shapeStartX: 0,
                shapeStartY: 0,
                polylinePoints: [],
                selectedShape: null,
                drawingUnsavedChanges: false,
                // Repeat pattern tool
                showRepeatPatternModal: false,
                repeatPattern: { cols: 4, rows: 3, marginX: 10, marginY: 10 }
            },
            
            // Plants data (List Page Contract)
            plantsList: {
                items: [],
                loading: false,
                pagination: {
                    count: 0,
                    next: null,
                    previous: null,
                    current_page: 1,
                    total_pages: 1
                },
                filters: {
                    q: '',
                    site: '',
                    category: '',
                    status: '',
                    health_status: '',
                    has_observations: null,
                    has_photos: null,
                    ordering: 'name',
                    page_size: 25
                }
            },

            // Legacy plants data (for backward compatibility with existing code)
            plants: [],
            filteredPlants: [],

            // Plant Positions data (for succession tracking)
            plantPositions: [],
            filteredPositions: [],
            currentPosition: null,
            positionDetail: {
                position: null,
                successionHistory: [],
                loading: false
            },
            
            // Modal states
            showAddSiteModal: false,
            showEditSiteModal: false,
            showAddPlantModal: false,
            showEditPlantModal: false,
            showAddObservationModal: false,
            showEditObservationModal: false,
            showDeleteObservationModal: false,
            showDeletePlantModal: false,
            showAddPhotoModal: false,
            showEditPhotoModal: false,
            showLoginModal: false,
            showTestSiteModal: false,
            showAddPositionModal: false,
            showMarkDeadModal: false,
            showReplacePlantModal: false,
            showSiteMapEditorModal: false,

            // Form data
            newSite: {
                name: '',
                description: '',
                latitude: null,
                longitude: null,
                altitude: null,
                environment: 'garden',
                soil_type: '',
                exposure: '',
                climate_zone: '',
                is_private: false
            },
            
            // Edit site form data  
            editSite: {
                id: null,
                name: '',
                description: '',
                latitude: null,
                longitude: null,
                altitude: null,
                environment: 'garden',
                soil_type: '',
                exposure: '',
                climate_zone: '',
                is_private: false
            },
            
            // Taxon autocomplete state
            taxonAutocomplete: {
                query: '',
                results: [],
                loading: false,
                showDropdown: false,
                debounceTimer: null,
                selectedTaxon: null,
                cache: {} // Cache search results
            },
            taxonAutocompleteReplace: {
                query: '',
                results: [],
                loading: false,
                showDropdown: false,
                debounceTimer: null,
                selectedTaxon: null,
                cache: {}
            },
            taxonAutocompleteEdit: {
                query: '',
                results: [],
                loading: false,
                showDropdown: false,
                debounceTimer: null,
                selectedTaxon: null,
                cache: {}
            },

            newPlant: {
                name: '',
                description: '',
                taxon: null,
                category: null,
                site: null,
                position: null, // Plant position (for succession tracking)
                planting_date: null,
                age_years: null,
                height_category: '',
                exact_height: null,
                health_status: 'good',
                clone_or_accession: '',
                is_private: false,
                notes: '',
                anecdotes: '',
                cultural_significance: '',
                ecological_notes: '',
                care_notes: '',
                // GPS fields
                latitude: null,
                longitude: null,
                gps_accuracy: null
            },

            // Edit plant form data
            editPlantData: {
                id: null,
                name: '',
                description: '',
                taxon: null,
                category: null,
                site: null,
                position: null, // Plant position (for succession tracking)
                planting_date: null,
                age_years: null,
                height_category: '',
                exact_height: null,
                health_status: 'good',
                clone_or_accession: '',
                is_private: false,
                notes: '',
                anecdotes: '',
                cultural_significance: '',
                ecological_notes: '',
                care_notes: '',
                // GPS fields
                latitude: null,
                longitude: null,
                gps_accuracy: null
            },
            
            newTaxon: {
                taxon_id: '',
                genus: '',
                species: '',
                kingdom: 'Plantae',
                phylum: '',
                class_name: '',
                order: '',
                family: '',
                subspecies: '',
                variety: '',
                cultivar: '',
                common_name_fr: '',
                common_name_it: '',
                common_name_en: '',
                author: '',
                publication_year: null
            },
            
            newObservation: {
                plant: null,
                phenological_stage: null,
                observation_date: new Date().toISOString().split('T')[0],
                intensity: 1,
                notes: '',
                weather_conditions: '',
                temperature: null,
                is_public: true
            },
            
            newPhoto: {
                plant: null,
                title: '',
                description: '',
                photo_type: 'general',
                is_public: true
            },

            editPhoto: {
                id: null,
                title: '',
                description: '',
                photo_type: 'general',
                is_public: true
            },

            photoOperationLoading: false,

            // Edit observation
            editObservation: {
                id: null,
                plant: null,
                phenological_stage: null,
                observation_date: '',
                time_of_day: '',
                intensity: 1,
                notes: '',
                weather_condition: '',
                temperature: null,
                humidity: null,
                wind_speed: null,
                confidence_level: 3,
                is_public: true
            },

            // Delete observation
            observationToDelete: null,

            // Delete plant
            plantToDelete: null,
            deletingPlant: false,

            // Photo management
            showPhotoGalleryModal: false,
            showUploadPhotoModal: false,
            observationPhotos: [],

            newPhoto: {
                observation: null,
                title: '',
                description: '',
                photo_type: 'phenological_state',
                is_public: true,
                imagePreview: null
            },

            photoFile: null,
            uploadingPhoto: false,
            selectedPhotoIndex: 0,

            // Analysis page
            analysisYear: new Date().getFullYear(),
            availableYears: [],
            monthlyChart: null,
            stageChart: null,
            categoryChart: null,
            siteChart: null,
            mainEventChart: null,
            analysisData: {
                monthly: null,
                byStage: [],
                topPlants: [],
                bySite: [],
                byCategory: [],
                byIntensity: [],
                byWeather: [],
                byMainEvent: [],
                recent: []
            },
            analysisStats: {
                totalObservations: 0,
                uniquePlants: 0,
                uniqueSites: 0,
                validatedCount: 0,
                withPhotosCount: 0
            },

            // Login form
            loginForm: {
                username: '',
                password: '',
                error: ''
            },
            
            // Test site form
            testSiteForm: {
                name: '',
                latitude: null,
                longitude: null
            },

            // Plant position forms
            newPosition: {
                site: null,
                label: '',
                description: '',
                latitude: null,
                longitude: null,
                gps_accuracy: null,
                soil_notes: '',
                exposure_notes: '',
                microclimate_notes: '',
                is_active: true
            },

            // Mark plant as dead form
            markDeadForm: {
                plant_id: null,
                death_date: new Date().toISOString().split('T')[0],
                death_cause: '',
                death_notes: ''
            },

            // Replace plant form
            replacePlantForm: {
                old_plant_id: null,
                new_plant: {
                    name: '',
                    taxon: null,
                    category: null,
                    planting_date: new Date().toISOString().split('T')[0],
                    is_private: false,
                    description: '',
                    notes: ''
                }
            },

            // Data for form options
            categories: [],
            phenologicalStages: [],
            plants: [],
            taxons: [],
            selectedTaxonFamily: null,

            // Loading states
            loading: {
                sites: false,
                statistics: false,
                plants: false,
                map: false,
                observations: false
            },

            // Observations data (List Page Contract)
            observationsList: {
                items: [],
                loading: false,
                availableYears: [],
                pagination: {
                    count: 0,
                    next: null,
                    previous: null,
                    current_page: 1,
                    total_pages: 1
                },
                filters: {
                    q: '',
                    year: '',
                    date_from: '',
                    date_to: '',
                    site: '',
                    plant: '',
                    taxon: '',
                    category: '',
                    stage: '',
                    has_photos: null,
                    is_validated: null,
                    ordering: '-observation_date',
                    page_size: 25
                }
            },

            // Legacy observations data (for backward compatibility)
            observations: [],
            currentObservation: null,
            telaComparison: null,

            // GPS functionality
            gpsValidation: {
                latitude: null,
                longitude: null
            },
            showGpsPreview: false,
            gpsMap: null,
            
            // General Map functionality
            mapViewMode: 'both', // 'sites', 'plants', 'both'
            generalMap: null,
            mapLayers: {
                sites: null,
                plants: null
            },
            mapStats: {
                sites: 0,
                plants: 0,
                precision: 0,
                visible: 0
            },
            selectedMapItem: null,
            
            // Map instance
            map: null,
            sitesLayer: null,

            // Charts
            observationsChart: null,
            odsEvolutionChart: null,

            // ODS Chart Data
            odsChartData: {
                chart_data: null,
                summary: null,
                loading: false,
                error: null
            },

            // Global Search
            globalSearch: {
                query: '',
                results: null,
                loading: false,
                error: null,
                showModal: false
            },

            // Dedicated Search Page
            searchPage: {
                query: '',
                results: [],
                count: 0,
                loading: false,
                error: null,
                hasSearched: false,
                selectedIndex: 0,
                filters: {
                    type: 'all',
                    mine: false,
                    date_from: null,
                    date_to: null
                },
                history: []
            },

            // Admin page data
            admin: {
                activeTab: 'dashboard',
                dashboard: null,
                loading: false,
                message: null,
                messageType: 'info',
                // Categories
                categories: [],
                newCategory: { name: '', description: '', icon: '', category_type: 'plants' },
                editingCategory: null,
                // Phenological Stages
                stages: [],
                newStage: { stage_code: '', stage_description: '', main_event_code: 1, main_event_description: '', phenological_scale: 'BBCH Tela Botanica' },
                editingStage: null,
                // Taxons GBIF
                gbifSync: { mode: 'backbone_match', query: '', limit: 20, strict: false, fetchVernacular: true },
                gbifResults: null,
                // Import CSV
                importType: 'ods',
                importClear: false,
                importFile: null,
                importResult: null
            }
        }
    },

    computed: {
        // Filter observations based on filters
        filteredObservationsList() {
            let filtered = this.observations;

            if (this.observationFilters.startDate) {
                filtered = filtered.filter(obs => obs.observation_date >= this.observationFilters.startDate);
            }
            if (this.observationFilters.endDate) {
                filtered = filtered.filter(obs => obs.observation_date <= this.observationFilters.endDate);
            }
            if (this.observationFilters.plant) {
                filtered = filtered.filter(obs => obs.plant && obs.plant.id === this.observationFilters.plant);
            }
            if (this.observationFilters.stage) {
                filtered = filtered.filter(obs => obs.phenological_stage && obs.phenological_stage.id === this.observationFilters.stage);
            }

            return filtered.sort((a, b) => new Date(b.observation_date) - new Date(a.observation_date));
        },

        // Year range for analysis selector (from database)
        yearRange() {
            if (this.availableYears.length > 0) {
                return this.availableYears;
            }
            // Fallback to last 10 years if not loaded yet
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let i = 0; i < 10; i++) {
                years.push(currentYear - i);
            }
            return years;
        },

        // Filter sites based on search and filters
        filteredSitesComputed() {
            let filtered = Array.isArray(this.sites) ? this.sites : [];
            
            // Search filter
            if (this.siteFilters.search) {
                const search = this.siteFilters.search.toLowerCase();
                filtered = filtered.filter(site => 
                    site.name.toLowerCase().includes(search) ||
                    (site.description && site.description.toLowerCase().includes(search))
                );
            }
            
            // Environment filter
            if (this.siteFilters.environment) {
                filtered = filtered.filter(site => 
                    site.environment === this.siteFilters.environment
                );
            }
            
            // Privacy filter
            if (!this.siteFilters.showPrivate) {
                filtered = filtered.filter(site => !site.is_private);
            }
            
            return filtered;
        },
        
        // Filter plants based on current filters
        filteredPlantsComputed() {
            let filtered = Array.isArray(this.plants) ? this.plants : [];
            
            if (this.plantFilters.search) {
                const search = this.plantFilters.search.toLowerCase();
                filtered = filtered.filter(plant => 
                    plant.name.toLowerCase().includes(search) ||
                    (plant.description && plant.description.toLowerCase().includes(search)) ||
                    (plant.taxon && plant.taxon.binomial_name && plant.taxon.binomial_name.toLowerCase().includes(search)) ||
                    (plant.taxon && plant.taxon.genus && plant.taxon.genus.toLowerCase().includes(search)) ||
                    (plant.taxon && plant.taxon.species && plant.taxon.species.toLowerCase().includes(search)) ||
                    (plant.taxon && plant.taxon.common_name_fr && plant.taxon.common_name_fr.toLowerCase().includes(search))
                );
            }
            
            if (this.plantFilters.category) {
                filtered = filtered.filter(plant => plant.category && plant.category.id == this.plantFilters.category);
            }
            
            if (this.plantFilters.site) {
                filtered = filtered.filter(plant => plant.site_id == this.plantFilters.site);
            }
            
            if (this.plantFilters.health) {
                filtered = filtered.filter(plant => plant.health_status === this.plantFilters.health);
            }
            
            if (this.plantFilters.family) {
                filtered = filtered.filter(plant => plant.taxon && plant.taxon.family === this.plantFilters.family);
            }
            
            if (this.plantFilters.genus) {
                filtered = filtered.filter(plant => plant.taxon && plant.taxon.genus === this.plantFilters.genus);
            }
            
            if (this.plantFilters.hasPhotos) {
                filtered = filtered.filter(plant => plant.photos_count > 0);
            }

            if (this.plantFilters.hasGPS) {
                filtered = filtered.filter(plant => plant.latitude && plant.longitude);
            }

            if (!this.plantFilters.showPrivate) {
                filtered = filtered.filter(plant => !plant.is_private);
            }

            if (this.plantFilters.onlyMine && this.user.isAuthenticated) {
                filtered = filtered.filter(plant => plant.owner_id === this.user.id);
            }

            // Status filter (alive, dead, replaced, removed)
            if (this.plantFilters.status) {
                filtered = filtered.filter(plant => plant.status === this.plantFilters.status);
            }

            return filtered;
        },

        // Filter positions based on site
        filteredPositionsForSite() {
            if (!this.newPlant.site && !this.editPlantData.site) {
                return [];
            }
            const siteId = this.newPlant.site || this.editPlantData.site;
            return this.plantPositions.filter(pos => pos.site === siteId && pos.is_active);
        },
        
        // Get unique families from plants
        uniqueFamilies() {
            const families = new Set();
            this.plants.forEach(plant => {
                if (plant.taxon && plant.taxon.family) {
                    families.add(plant.taxon.family);
                }
            });
            return Array.from(families).sort();
        },
        
        // Get unique genera from plants
        uniqueGenera() {
            const genera = new Set();
            this.plants.forEach(plant => {
                if (plant.taxon && plant.taxon.genus) {
                    genera.add(plant.taxon.genus);
                }
            });
            return Array.from(genera).sort();
        },
        
        // GPS validation computed properties
        hasValidGpsCoordinates() {
            return this.isValidLatitude(this.newPlant.latitude) && this.isValidLongitude(this.newPlant.longitude);
        }
    },
    
    watch: {
        // Watch for changes in current view
        currentView(newView, oldView) {
            console.log(`🔄 View changed: ${oldView} → ${newView}`);
            // Sync hash with currentView (skip detail views handled elsewhere)
            if (['dashboard', 'sites', 'plants', 'observations', 'analysis', 'search', 'map', 'admin'].includes(newView)) {
                const expectedHash = '#' + newView;
                if (window.location.hash !== expectedHash) {
                    window.location.hash = expectedHash;
                }
            }
            this.handleViewChange(newView);

            // Initialize general map when switching to map view
            if (newView === 'map') {
                this.$nextTick(() => {
                    this.initGeneralMap();
                });
            } else if (newView === 'plants') {
                // Load plants list when switching to plants view
                this.loadPlantsList();
            } else if (newView === 'observations') {
                // Load observations when switching to observations view
                this.loadObservations();
                this.loadObservationsYears();
                this.loadObservationsList();
            } else if (newView === 'analysis') {
                // Load available years and analysis data when switching to analysis view
                this.loadAvailableYears();
                this.loadAnalysisData();
            } else if (newView === 'dashboard') {
                // When navigating to dashboard, ensure ODS chart renders
                this.ensureDashboardChartRendered();
            } else if (newView === 'admin') {
                this.loadAdminDashboard();
            }
        },

        // Watch for year changes in analysis
        analysisYear() {
            if (this.currentView === 'analysis') {
                this.loadAnalysisData();
            }
        },
        
        // Watch for changes in map view mode
        mapViewMode(newMode) {
            this.updateMapLayers();
        },
        
        // Watch for changes in site filters
        siteFilters: {
            handler(newFilters) {
                this.filteredSites = this.filteredSitesComputed;
                if (this.map && this.sitesViewMode === 'map') {
                    this.updateMapMarkers();
                }
            },
            deep: true
        },
        
        // Watch for changes in sites view mode
        sitesViewMode(newMode) {
            if (newMode === 'map') {
                this.$nextTick(() => {
                    this.initializeMap();
                });
            }
        },

        // Watch for modal visibility - manage body scroll lock
        showAddSiteModal(show) {
            document.body.classList.toggle('modal-open', show);
        },

        showEditSiteModal(show) {
            document.body.classList.toggle('modal-open', show);
        },

        showAddPlantModal(show) {
            document.body.classList.toggle('modal-open', show);
        },

        showAddObservationModal(show) {
            document.body.classList.toggle('modal-open', show);
        },

        showAddPhotoModal(show) {
            document.body.classList.toggle('modal-open', show);
        },

        showLoginModal(show) {
            document.body.classList.toggle('modal-open', show);
        },

        showTestSiteModal(show) {
            document.body.classList.toggle('modal-open', show);
        }
    },
    
    mounted() {
        // Initialize the application
        console.log('🚀 Vue.js app mounted');
        this.initializeApp();

        // Load search history from localStorage
        this.loadSearchHistory();

        // Handle URL hash for direct navigation
        this.handleHashChange();
        window.addEventListener('hashchange', this.handleHashChange);

        // Set up Bootstrap modal event listeners for photo modal
        const photoModalElement = document.getElementById('addPhotoModal');
        if (photoModalElement) {
            // Cleanup on modal hidden
            photoModalElement.addEventListener('hidden.bs.modal', () => {
                // Failsafe: remove any orphan backdrops
                this.cleanupModalArtifacts();
            });
        }
    },
    
    methods: {
        // Initialize application
        initializeApp() {
            // Check auth status first
            this.checkAuthStatus()
                .then(() => {
                    return Promise.all([
                        this.loadStatistics(),
                        this.loadSites(),
                        this.loadRecentActivities(),
                        this.loadFormData(),
                        this.loadODSChartData()  // Load ODS chart data for homepage
                    ]);
                })
                .then(() => {
                    this.initializeCharts();
                })
                .catch(error => {
                    console.error('Error initializing app:', error);
                    this.showAlert('Erreur lors du chargement de l\'application', 'danger');
                });
        },

        extractCollection(payload) {
            if (Array.isArray(payload)) {
                return payload;
            }

            if (Array.isArray(payload?.results)) {
                return payload.results;
            }

            if (Array.isArray(payload?.data)) {
                return payload.data;
            }

            return [];
        },

        extractTotal(payload) {
            if (typeof payload?.total === 'number') {
                return payload.total;
            }

            if (typeof payload?.count === 'number') {
                return payload.count;
            }

            return this.extractCollection(payload).length;
        },

        toNullableNumber(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            const parsedValue = Number(value);

            return Number.isFinite(parsedValue) ? parsedValue : null;
        },

        normalizeSite(site) {
            if (!site || typeof site !== 'object') {
                return site;
            }

            return {
                ...site,
                latitude: this.toNullableNumber(site.latitude),
                longitude: this.toNullableNumber(site.longitude),
                altitude: this.toNullableNumber(site.altitude),
                plan_width_meters: this.toNullableNumber(site.plan_width_meters),
                plan_height_meters: this.toNullableNumber(site.plan_height_meters)
            };
        },

        normalizeActivity(activity) {
            return {
                ...activity,
                actor: activity?.actor ? {
                    ...activity.actor,
                    username: activity.actor.username || activity.actor.name || 'Utilisateur'
                } : null,
                color: activity?.color || 'secondary',
                icon: activity?.icon || 'fa-clock',
                is_system: Boolean(activity?.is_system || !activity?.actor),
                timestamp: activity?.timestamp || activity?.created_at || null
            };
        },
        
        // Handle view changes
        handleViewChange(view) {
            console.log('🔄 View changed to:', view);
            
            // Clean up previous view resources
            if (this.map && view !== 'sites') {
                // Keep map instance but hide it
            }
            
            // Initialize new view
            switch (view) {
                case 'dashboard':
                    this.loadStatistics();
                    this.$nextTick(() => this.initializeCharts());
                    break;
                case 'sites':
                    console.log('📍 Loading sites view...');
                    this.loadSites();
                    break;
                case 'site-map':
                    // Handled by showSiteMap method
                    break;
                case 'plant-detail':
                    // Handled by showPlantDetail method
                    break;
                case 'plants':
                    console.log('🌱 Loading plants view...');
                    this.loadPlants();
                    break;
                case 'observations':
                    // TODO: Load observations data
                    break;
                case 'analysis':
                    // TODO: Load analysis data
                    break;
            }
        },
        
        // Load application statistics
        async loadStatistics() {
            this.loading.statistics = true;
            try {
                const response = await axios.get('/api/v1/statistics');
                const global = response.data.global || {};
                this.statistics = {
                    totalSites: global.total_sites || 0,
                    totalPlants: global.total_plants || 0,
                    totalObservations: global.total_observations || 0,
                    currentYearObservations: 0
                };
                console.log('📊 Statistics loaded:', this.statistics);
            } catch (error) {
                console.error('Error loading statistics:', error);
                this.statistics = {
                    totalSites: 0,
                    totalPlants: 0,
                    totalObservations: 0,
                    currentYearObservations: 0
                };
            } finally {
                this.loading.statistics = false;
            }
        },
        
        // Load sites data
        async loadSites() {
            this.loading.sites = true;
            try {
                const response = await axios.get('/api/v1/sites');
                this.sites = this.extractCollection(response.data).map(site => this.normalizeSite(site));
                this.filteredSites = this.filteredSitesComputed;

                // Log success for debugging
                console.log(`✅ ${this.sites.length} sites chargés depuis l'API`);

                // Warn about sites with missing names
                const emptySites = this.sites.filter(s => !s.name);
                if (emptySites.length > 0) {
                    console.warn(`⚠️ ${emptySites.length} sites have empty names:`, emptySites.map(s => s.id));
                }
            } catch (error) {
                console.error('Error loading sites:', error);

                // Show specific error to user
                if (error.response) {
                    this.showAlert(`Erreur API ${error.response.status}: ${error.response.statusText}`, 'warning');
                } else if (error.request) {
                    this.showAlert('Impossible de contacter le serveur API', 'danger');
                } else {
                    this.showAlert('Erreur de configuration API', 'danger');
                }

                // Initialize empty sites array instead of fallback data
                this.sites = [];
                this.filteredSites = [];
            } finally {
                this.loading.sites = false;
            }
        },
        
        // Load recent activities
        async loadRecentActivities() {
            try {
                const response = await axios.get('/api/v1/activity/?per_page=10');
                this.recentActivities = this.extractCollection(response.data).map(activity => this.normalizeActivity(activity));
                console.log('📋 Recent activities loaded:', this.recentActivities.length);
            } catch (error) {
                console.error('Error loading recent activities:', error);
                this.recentActivities = [];
            }
        },

        getRelativeTime(timestamp) {
            const now = new Date();
            const past = new Date(timestamp);
            const seconds = Math.floor((now - past) / 1000);

            if (seconds < 60) return 'à l\'instant';
            if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`;
            if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)} h`;
            if (seconds < 604800) return `il y a ${Math.floor(seconds / 86400)} j`;
            return `il y a ${Math.floor(seconds / 604800)} sem`;
        },
        
        // Initialize charts
        initializeCharts() {
            // Initialize ODS Evolution Chart (hero chart on homepage)
            this.initializeODSChart();

            // Old observations chart (kept for backward compatibility)
            const ctx = document.getElementById('observationsChart');
            if (ctx && !this.observationsChart) {
                this.observationsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                        datasets: [{
                            label: 'Observations 2025',
                            data: [12, 19, 25, 42, 67, 89, 95, 78, 65, 45, 32, 18],
                            borderColor: 'rgb(25, 135, 84)',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        },

        // Load ODS evolution data
        async loadODSChartData() {
            this.odsChartData.loading = true;
            this.odsChartData.error = null;

            try {
                const response = await axios.get('/api/v1/ods-evolution');
                this.odsChartData.chart_data = response.data.chart_data;
                this.odsChartData.summary = response.data.summary;

                console.log(`✅ ODS chart data loaded: ${this.odsChartData.summary.total_observations} observations`);

                // Render chart after data is loaded
                this.$nextTick(() => {
                    this.initializeODSChart();
                });
            } catch (error) {
                console.error('❌ Error loading ODS chart data:', error);
                this.odsChartData.error = error.message;
            } finally {
                this.odsChartData.loading = false;
            }
        },

        // Initialize ODS Evolution Chart
        initializeODSChart() {
            const ctx = document.getElementById('odsEvolutionChart');
            if (!ctx) return;

            // Destroy existing chart
            if (this.odsEvolutionChart) {
                this.odsEvolutionChart.destroy();
            }

            // Check if data is loaded
            if (!this.odsChartData.chart_data || !this.odsChartData.chart_data.years) {
                console.log('⏳ ODS chart data not yet loaded');
                return;
            }

            const { years, counts } = this.odsChartData.chart_data;

            this.odsEvolutionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: years,
                    datasets: [{
                        label: 'Observations ODS',
                        data: counts,
                        borderColor: 'rgb(25, 135, 84)',
                        backgroundColor: 'rgba(25, 135, 84, 0.15)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: 'rgb(25, 135, 84)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    size: 13,
                                    family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto'
                                },
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            },
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.parsed.y.toLocaleString('fr-FR') + ' observations';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Année',
                                font: {
                                    size: 13,
                                    weight: '600'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: 'Nombre d\'observations',
                                font: {
                                    size: 13,
                                    weight: '600'
                                }
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                callback: function(value) {
                                    return value.toLocaleString('fr-FR');
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            console.log('✅ ODS Evolution Chart initialized');
        },

        // Ensure ODS chart renders when navigating to dashboard
        async ensureDashboardChartRendered(retryCount = 0) {
            const maxRetries = 2;

            console.log(`📊 Ensuring dashboard chart rendered (attempt ${retryCount + 1}/${maxRetries + 1})`);

            // Wait for Vue to render the dashboard DOM
            await this.$nextTick();

            // Additional DOM settling time for v-if rendering
            await new Promise(resolve => requestAnimationFrame(resolve));

            // Check if canvas exists
            const canvas = document.getElementById('odsEvolutionChart');

            if (!canvas) {
                console.warn('⚠️ Canvas #odsEvolutionChart not found in DOM');

                // Retry if we haven't exceeded max attempts
                if (retryCount < maxRetries) {
                    console.log(`🔄 Retrying in 50ms...`);
                    await new Promise(resolve => setTimeout(resolve, 50));
                    return this.ensureDashboardChartRendered(retryCount + 1);
                } else {
                    console.error('❌ Failed to find canvas after max retries');
                    return;
                }
            }

            // Check if data is available
            if (!this.odsChartData.chart_data || !this.odsChartData.chart_data.years) {
                console.log('⏳ ODS chart data not yet loaded, fetching...');

                // Data not loaded yet - trigger load if needed
                if (!this.odsChartData.loading && !this.odsChartData.error) {
                    await this.loadODSChartData();
                } else {
                    console.log('⏳ Data is currently loading or errored, skipping chart init');
                }
                return;
            }

            // Canvas exists and data is ready - initialize chart
            console.log('✅ Canvas found and data ready, initializing chart');
            this.initializeODSChart();
        },

        // Format number with thousands separator
        formatNumber(num) {
            if (!num && num !== 0) return '0';
            return num.toLocaleString('fr-FR');
        },

        // ========================================
        // GLOBAL SEARCH METHODS
        // ========================================

        // Perform global search
        async performGlobalSearch() {
            const query = this.globalSearch.query.trim();

            if (query.length < 2) {
                this.showAlert('Veuillez entrer au moins 2 caractères', 'warning');
                return;
            }

            this.globalSearch.loading = true;
            this.globalSearch.error = null;
            this.globalSearch.results = null;
            this.globalSearch.showModal = true;

            try {
                const response = await axios.get('/api/v1/search', {
                    params: { q: query, limit: 10 }
                });

                this.globalSearch.results = response.data;
                console.log(`✅ Global search completed: ${response.data.total_results} results for "${query}"`);
            } catch (error) {
                console.error('❌ Global search error:', error);
                this.globalSearch.error = error.response?.data?.error || 'Erreur lors de la recherche';
            } finally {
                this.globalSearch.loading = false;
            }
        },

        // Clear global search
        clearGlobalSearch() {
            this.globalSearch.query = '';
            this.globalSearch.results = null;
            this.globalSearch.error = null;
        },

        // Navigate to plant from search results
        navigateToPlantFromSearch(plantId) {
            this.globalSearch.showModal = false;
            this.currentView = 'plants';
            this.$nextTick(() => {
                this.navigateToPlant(plantId);
            });
        },

        // Navigate to site from search results
        navigateToSiteFromSearch(siteId) {
            this.globalSearch.showModal = false;
            this.currentView = 'sites';
            this.$nextTick(() => {
                // Load site details (you can implement this similarly to plant details)
                console.log(`Navigate to site ${siteId}`);
            });
        },

        // Navigate to observation from search results
        navigateToObservationFromSearch(obsId) {
            this.globalSearch.showModal = false;
            this.currentView = 'observations';
            this.$nextTick(() => {
                // Load observation details
                console.log(`Navigate to observation ${obsId}`);
            });
        },

        // ========== Dedicated Search Page Methods ==========

        // Perform search on dedicated Search page
        async performSearchPageSearch() {
            const query = this.searchPage.query.trim();

            if (query.length < 2) {
                this.showAlert('Veuillez entrer au moins 2 caractères', 'warning');
                return;
            }

            this.searchPage.loading = true;
            this.searchPage.error = null;
            this.searchPage.hasSearched = true;
            this.searchPage.selectedIndex = 0;

            try {
                const params = {
                    q: query,
                    type: this.searchPage.filters.type,
                    limit: 50
                };

                if (this.searchPage.filters.mine) {
                    params.mine = true;
                }
                if (this.searchPage.filters.date_from) {
                    params.date_from = this.searchPage.filters.date_from;
                }
                if (this.searchPage.filters.date_to) {
                    params.date_to = this.searchPage.filters.date_to;
                }

                const response = await axios.get('/api/v1/search', { params });
                const data = response.data;

                // Flatten grouped results into a unified list
                const results = [];

                if (data.plants) {
                    data.plants.forEach(p => results.push({
                        id: p.id,
                        entity: 'plant',
                        title: p.name,
                        snippet: [p.binomial_name, p.common_name, p.site_name ? `Site: ${p.site_name}` : null].filter(Boolean).join(' — '),
                        status: p.status
                    }));
                }
                if (data.sites) {
                    data.sites.forEach(s => results.push({
                        id: s.id,
                        entity: 'site',
                        title: s.name,
                        snippet: s.environment ? `Environnement: ${s.environment}` : ''
                    }));
                }
                if (data.observations) {
                    data.observations.forEach(o => results.push({
                        id: o.id,
                        entity: 'observation',
                        title: o.plant_name || `Observation #${o.id}`,
                        snippet: [o.stage_description || o.stage_code, o.observation_date ? this.formatDate(o.observation_date) : null].filter(Boolean).join(' — ')
                    }));
                }
                if (data.taxons) {
                    data.taxons.forEach(t => results.push({
                        id: t.id,
                        entity: 'taxon',
                        title: t.binomial_name,
                        snippet: [t.common_name, t.family ? `Famille: ${t.family}` : null].filter(Boolean).join(' — ')
                    }));
                }

                this.searchPage.results = results;
                this.searchPage.count = results.length;

                // Save to search history
                this.addToSearchHistory(query);

                console.log(`Search: found ${results.length} results for "${query}"`);
            } catch (error) {
                console.error('Search error:', error);
                this.searchPage.error = error.response?.data?.error || error.response?.data?.message || 'Erreur lors de la recherche';
            } finally {
                this.searchPage.loading = false;
            }
        },

        // Clear search page
        clearSearchPage() {
            this.searchPage.query = '';
            this.searchPage.results = [];
            this.searchPage.count = 0;
            this.searchPage.error = null;
            this.searchPage.hasSearched = false;
            this.searchPage.selectedIndex = 0;
        },

        // Navigate to a search result
        navigateToSearchResult(result) {
            if (!result) return;

            switch (result.entity) {
                case 'plant':
                    this.viewPlantDetail(result.id);
                    break;
                case 'site':
                    this.viewSiteDetail(result.id);
                    break;
                case 'observation':
                    if (typeof this.viewObservationDetail === 'function') {
                        this.viewObservationDetail(result.id);
                    } else {
                        this.currentView = 'observations';
                    }
                    break;
                case 'taxon':
                    // Search plants with this taxon
                    this.currentView = 'plants';
                    this.$nextTick(() => {
                        this.showAlert(`Taxon: ${result.title}`, 'info');
                    });
                    break;
            }
        },

        // Highlight search text in results
        highlightSearchText(text, query) {
            if (!text || !query) return text;

            const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`(${escapedQuery})`, 'gi');
            return text.replace(regex, '<mark class="bg-warning">$1</mark>');
        },

        // Get entity label for display
        getEntityLabel(entityType) {
            const labels = {
                'plant': 'Plante',
                'site': 'Site',
                'observation': 'Observation',
                'taxon': 'Taxon'
            };
            return labels[entityType] || entityType;
        },

        // Add query to search history
        addToSearchHistory(query) {
            if (!query || query.length < 2) return;

            // Remove duplicate if exists
            const index = this.searchPage.history.indexOf(query);
            if (index > -1) {
                this.searchPage.history.splice(index, 1);
            }

            // Add to beginning
            this.searchPage.history.unshift(query);

            // Keep only last 10
            if (this.searchPage.history.length > 10) {
                this.searchPage.history = this.searchPage.history.slice(0, 10);
            }

            // Save to localStorage
            this.saveSearchHistory();
        },

        // Save search history to localStorage
        saveSearchHistory() {
            try {
                localStorage.setItem('phenolab_search_history', JSON.stringify(this.searchPage.history));
            } catch (error) {
                console.error('Failed to save search history:', error);
            }
        },

        // Load search history from localStorage
        loadSearchHistory() {
            try {
                const saved = localStorage.getItem('phenolab_search_history');
                if (saved) {
                    this.searchPage.history = JSON.parse(saved);
                }
            } catch (error) {
                console.error('Failed to load search history:', error);
                this.searchPage.history = [];
            }
        },

        // Clear search history
        clearSearchHistory() {
            this.searchPage.history = [];
            try {
                localStorage.removeItem('phenolab_search_history');
            } catch (error) {
                console.error('Failed to clear search history:', error);
            }
        },

        // ========== End Search Page Methods ==========

        // Initialize map
        initializeMap() {
            if (!document.getElementById('sitesMap')) return;
            
            if (this.map) {
                this.map.invalidateSize();
                return;
            }
            
            // Initialize Leaflet map
            this.map = L.map('sitesMap').setView([46.2044, 6.1432], 10);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);
            
            // Add sites markers
            this.updateMapMarkers();
        },
        
        // Update map markers
        updateMapMarkers() {
            if (!this.map) return;
            
            // Clear existing markers
            if (this.sitesLayer) {
                this.map.removeLayer(this.sitesLayer);
            }
            
            // Create new layer group
            this.sitesLayer = L.layerGroup();
            
            // Add markers for filtered sites
            this.filteredSites.forEach(site => {
                if (site.latitude && site.longitude) {
                    const marker = L.marker([site.latitude, site.longitude])
                        .bindPopup(`
                            <div>
                                <h6>${site.name}</h6>
                                <p>${site.description || 'Aucune description'}</p>
                                <small>
                                    <strong>${site.plants_count || 0}</strong> plantes, 
                                    <strong>${site.observations_count || 0}</strong> observations
                                </small>
                                <br><small class="text-muted">
                                    ${site.latitude.toFixed(4)}, ${site.longitude.toFixed(4)}
                                </small>
                            </div>
                        `);
                    this.sitesLayer.addLayer(marker);
                }
            });
            
            // Add layer to map
            this.map.addLayer(this.sitesLayer);
        },
        
        // Site management methods
        viewSite(site) {
            window.location.hash = `#site/${site.id}`;
        },
        
        async viewSiteDetail(siteId) {
            console.log('🏠 Loading site detail:', siteId);
            this.currentView = 'site-detail';
            this.siteDetail.loading = true;

            try {
                // Load site basic info
                const siteResponse = await axios.get(`/api/v1/sites/${siteId}`);
                this.siteDetail.site = this.normalizeSite(siteResponse.data);

                // Load statistics for summary cards
                const statsResponse = await axios.get(`/api/v1/sites/${siteId}/statistics`);
                this.siteDetail.plantsCount = statsResponse.data.plants_count || 0;
                this.siteDetail.totalObservations = statsResponse.data.observations_count || 0;

                // Load plants table data
                await this.loadSitePlants(siteId);

                console.log('✅ Site detail loaded:', this.siteDetail.site.name);
            } catch (error) {
                console.error('Error loading site detail:', error);

                if (error.response && error.response.status === 404) {
                    this.showAlert('Site non trouvé', 'danger');
                    window.location.hash = '#sites';
                } else if (error.response && error.response.status === 403) {
                    this.showAlert('Vous n\'avez pas accès à ce site', 'warning');
                    window.location.hash = '#sites';
                } else {
                    this.showAlert('Erreur lors du chargement du site', 'danger');
                }
            } finally {
                this.siteDetail.loading = false;
            }
        },

        async loadSitePlants(siteId, page = 1) {
            if (!siteId && this.siteDetail.site) {
                siteId = this.siteDetail.site.id;
            }

            try {
                const filters = this.siteDetail.filters;
                const params = new URLSearchParams();

                // Add filters to query params (use 'q' for search to avoid DRF conflict)
                if (filters.search) params.append('q', filters.search);
                if (filters.category) params.append('category', filters.category);
                if (filters.status) params.append('status', filters.status);
                if (filters.health_status) params.append('health_status', filters.health_status);
                if (filters.has_observations !== null) params.append('has_observations', filters.has_observations);
                if (filters.has_photos !== null) params.append('has_photos', filters.has_photos);
                if (filters.planting_date_after) params.append('planting_date_after', filters.planting_date_after);
                if (filters.planting_date_before) params.append('planting_date_before', filters.planting_date_before);
                if (filters.ordering) params.append('ordering', filters.ordering);
                params.append('page_size', filters.page_size);
                params.append('page', page);

                const response = await axios.get(`/api/v1/sites/${siteId}/plants/?${params.toString()}`);

                this.siteDetail.plants = this.extractCollection(response.data);
                this.siteDetail.pagination = {
                    count: response.data.count,
                    next: response.data.next,
                    previous: response.data.previous,
                    current_page: page,
                    total_pages: Math.ceil(response.data.count / filters.page_size)
                };

                console.log('✅ Site plants loaded:', this.siteDetail.plants.length, 'plants');
            } catch (error) {
                console.error('Error loading site plants:', error);
                this.showAlert('Erreur lors du chargement des plantes', 'danger');
            }
        },

        applySiteDetailFilters() {
            this.loadSitePlants(this.siteDetail.site.id, 1);
        },

        resetSiteDetailFilters() {
            this.siteDetail.filters = {
                search: '',
                category: '',
                status: '',
                health_status: '',
                has_observations: null,
                has_photos: null,
                planting_date_after: '',
                planting_date_before: '',
                ordering: 'name',
                page_size: 25
            };
            this.loadSitePlants(this.siteDetail.site.id, 1);
        },

        changeSiteDetailPage(page) {
            this.loadSitePlants(this.siteDetail.site.id, page);
        },

        sortSitePlants(field) {
            const currentOrdering = this.siteDetail.filters.ordering;

            // Toggle sort direction if clicking same field
            if (currentOrdering === field) {
                this.siteDetail.filters.ordering = '-' + field;
            } else if (currentOrdering === '-' + field) {
                this.siteDetail.filters.ordering = field;
            } else {
                this.siteDetail.filters.ordering = field;
            }

            this.loadSitePlants(this.siteDetail.site.id, 1);
        },

        getSortIcon(field) {
            const ordering = this.siteDetail.filters.ordering;
            if (ordering === field) {
                return 'fa-sort-up';
            } else if (ordering === '-' + field) {
                return 'fa-sort-down';
            }
            return 'fa-sort';
        },

        // ==================== SITE MAP EDITOR ====================

        async openSiteMapEditor(site) {
            console.log('🗺️ Opening Site Map Editor for site:', site);
            this.siteMapEditor.site = site;
            this.siteMapEditor.active = true;
            this.siteMapEditor.editMode = false;
            this.siteMapEditor.drawingMode = 'select';
            this.showSiteMapEditorModal = true;
            console.log('📍 Modal should be visible now, showSiteMapEditorModal =', this.showSiteMapEditorModal);

            // Load layers first (which will load plants for the selected layer)
            await this.loadLayers();
        },

        closeSiteMapEditor() {
            if (this.siteMapEditor.unsavedChanges) {
                if (!confirm('Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir fermer ?')) {
                    return;
                }
            }
            this.showSiteMapEditorModal = false;
            this.siteMapEditor.active = false;
            this.siteMapEditor.editMode = false;
            this.siteMapEditor.plants = [];
            this.siteMapEditor.selectedPlant = null;
            this.siteMapEditor.unsavedChanges = false;
        },

        async loadSiteMapPlants(siteId, layerId = null) {
            this.siteMapEditor.loading = true;
            try {
                // Load ALL plants for this site (no layer filter)
                // so unpositioned plants appear in the sidebar for placement
                const params = {
                    site: siteId,
                    page_size: 1000
                };

                const response = await axios.get(`/api/v1/plants`, {
                    params: params
                });

                this.siteMapEditor.plants = this.extractCollection(response.data);
                console.log('📍 Loaded plants for map:', this.siteMapEditor.plants.length);
            } catch (error) {
                console.error('Error loading site map plants:', error);
                this.showAlert('Erreur lors du chargement des plantes', 'danger');
            } finally {
                this.siteMapEditor.loading = false;
            }
        },

        toggleMapEditMode() {
            if (this.siteMapEditor.editMode && this.siteMapEditor.unsavedChanges) {
                if (confirm('Sauvegarder les modifications ?')) {
                    this.saveSiteMapPositions();
                }
            }
            this.siteMapEditor.editMode = !this.siteMapEditor.editMode;
        },

        startDragPlant(plant, event) {
            if (!this.siteMapEditor.editMode) return;
            event.preventDefault();
            event.stopPropagation();

            this.siteMapEditor.draggingPlant = plant;
            this.siteMapEditor.dragStartX = event.clientX;
            this.siteMapEditor.dragStartY = event.clientY;
            this.siteMapEditor._dragMoved = false;

            // Use arrow functions to preserve `this` context
            this.siteMapEditor._onDragMove = (e) => {
                if (!this.siteMapEditor.draggingPlant) return;
                e.preventDefault();

                this.siteMapEditor._dragMoved = true;

                const svg = document.querySelector('#siteMapSvg');
                if (!svg) return;
                const rect = svg.getBoundingClientRect();

                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;

                this.siteMapEditor.draggingPlant.map_position_x = Math.max(0, Math.min(100, x));
                this.siteMapEditor.draggingPlant.map_position_y = Math.max(0, Math.min(100, y));
                this.siteMapEditor.unsavedChanges = true;
            };

            this.siteMapEditor._onDragEnd = () => {
                this.siteMapEditor.draggingPlant = null;
                document.removeEventListener('mousemove', this.siteMapEditor._onDragMove);
                document.removeEventListener('mouseup', this.siteMapEditor._onDragEnd);
            };

            document.addEventListener('mousemove', this.siteMapEditor._onDragMove);
            document.addEventListener('mouseup', this.siteMapEditor._onDragEnd);
        },

        selectPlant(plant) {
            // Don't select if we just finished dragging
            if (this.siteMapEditor._dragMoved) {
                this.siteMapEditor._dragMoved = false;
                return;
            }
            this.siteMapEditor.selectedPlant = plant;
        },

        addPlantToMap(plant) {
            // Place plant at center of map if it doesn't have a position yet
            if (plant.map_position_x === null || plant.map_position_y === null) {
                plant.map_position_x = 50; // Center horizontally
                plant.map_position_y = 50; // Center vertically
                this.siteMapEditor.unsavedChanges = true;
                this.siteMapEditor.selectedPlant = plant;
                console.log(`📍 Placed ${plant.name} at center (50%, 50%)`);
            }
        },

        async saveSiteMapPositions() {
            if (!this.siteMapEditor.selectedLayer) {
                this.showAlert('Aucune couche sélectionnée', 'warning');
                return;
            }

            this.siteMapEditor.loading = true;
            try {
                // Collect all plants with positions (positioned ones)
                const positions = this.siteMapEditor.plants
                    .filter(p => p.map_position_x !== null && p.map_position_y !== null)
                    .map(p => ({
                        plant_id: p.id,
                        map_position_x: Math.round(p.map_position_x * 100) / 100,
                        map_position_y: Math.round(p.map_position_y * 100) / 100
                    }));

                const response = await axios.post(
                    '/api/v1/plants/bulk-update-map-positions',
                    {
                        site_id: this.siteMapEditor.site.id,
                        layer_id: this.siteMapEditor.selectedLayer.id,
                        positions: positions
                    }
                );

                this.siteMapEditor.unsavedChanges = false;
                this.showAlert(`${response.data.updated_count} positions sauvegardées dans "${this.siteMapEditor.selectedLayer.name}"`, 'success');
                console.log('✅ Positions saved to layer:', this.siteMapEditor.selectedLayer.id, response.data.updated_count);
            } catch (error) {
                console.error('Error saving positions:', error);
                this.showAlert('Erreur lors de la sauvegarde des positions', 'danger');
            } finally {
                this.siteMapEditor.loading = false;
            }
        },

        removePlantFromMap(plant) {
            if (confirm(`Retirer "${plant.name}" du plan du site ?`)) {
                plant.map_position_x = null;
                plant.map_position_y = null;
                this.siteMapEditor.unsavedChanges = true;
                if (this.siteMapEditor.selectedPlant?.id === plant.id) {
                    this.siteMapEditor.selectedPlant = null;
                }
            }
        },

        getUnpositionedPlantsCount() {
            return this.siteMapEditor.plants.filter(p => p.map_position_x === null || p.map_position_y === null).length;
        },

        openRepeatPatternDialog() {
            const unpositioned = this.getUnpositionedPlantsCount();
            if (unpositioned === 0) {
                this.showAlert('Toutes les plantes sont déjà positionnées', 'info');
                return;
            }
            // Auto-size grid to fit unpositioned plants
            const cols = Math.ceil(Math.sqrt(unpositioned));
            const rows = Math.ceil(unpositioned / cols);
            this.siteMapEditor.repeatPattern.cols = cols;
            this.siteMapEditor.repeatPattern.rows = rows;
            this.siteMapEditor.showRepeatPatternModal = true;
        },

        applyRepeatPattern() {
            const { cols, rows, marginX, marginY } = this.siteMapEditor.repeatPattern;
            const unpositioned = this.siteMapEditor.plants.filter(p => p.map_position_x === null || p.map_position_y === null);

            if (unpositioned.length === 0) return;

            const usableWidth = 100 - 2 * marginX;
            const usableHeight = 100 - 2 * marginY;
            const stepX = cols > 1 ? usableWidth / (cols - 1) : 0;
            const stepY = rows > 1 ? usableHeight / (rows - 1) : 0;

            let idx = 0;
            for (let r = 0; r < rows && idx < unpositioned.length; r++) {
                for (let c = 0; c < cols && idx < unpositioned.length; c++) {
                    unpositioned[idx].map_position_x = marginX + c * stepX;
                    unpositioned[idx].map_position_y = marginY + r * stepY;
                    idx++;
                }
            }

            this.siteMapEditor.unsavedChanges = true;
            this.siteMapEditor.showRepeatPatternModal = false;
            this.showAlert(`${idx} plante(s) disposées en grille ${cols}x${rows}`, 'success');
        },

        getPlantMarkerColor(plant) {
            // Color based on status
            if (plant.status === 'dead' || plant.status === 'removed') {
                return '#6c757d';  // gray
            }
            if (plant.health_status === 'excellent') {
                return '#28a745';  // green
            }
            if (plant.health_status === 'good') {
                return '#5cb85c';  // light green
            }
            if (plant.health_status === 'fair') {
                return '#ffc107';  // yellow
            }
            if (plant.health_status === 'poor') {
                return '#dc3545';  // red
            }
            return '#007bff';  // default blue
        },

        getPositionedPlantsCount() {
            return this.siteMapEditor.plants.filter(p => p.map_position_x !== null && p.map_position_y !== null).length;
        },

        getPositionedPlants() {
            return this.siteMapEditor.plants.filter(p => p.map_position_x !== null && p.map_position_y !== null);
        },

        // ==================== DRAWING TOOLS ====================

        setDrawingMode(mode) {
            this.siteMapEditor.drawingMode = mode;
            this.siteMapEditor.currentShape = null;
            this.siteMapEditor.polylinePoints = [];
            console.log('🎨 Drawing mode:', mode);
        },

        handleSvgMouseDown(event) {
            if (this.siteMapEditor.drawingMode === 'select' || !this.siteMapEditor.editMode) return;

            const svg = document.querySelector('#siteMapSvg');
            const rect = svg.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            this.siteMapEditor.shapeStartX = x;
            this.siteMapEditor.shapeStartY = y;

            if (this.siteMapEditor.drawingMode === 'polyline') {
                this.siteMapEditor.polylinePoints.push({x, y});
            } else if (this.siteMapEditor.drawingMode === 'text') {
                const content = prompt('Entrez le texte:');
                if (content) {
                    this.siteMapEditor.drawingShapes.push({
                        type: 'text',
                        x: x,
                        y: y,
                        content: content,
                        fontSize: 16,
                        fill: '#000000'
                    });
                    this.siteMapEditor.drawingUnsavedChanges = true;
                }
            } else {
                // Start rect or circle
                this.siteMapEditor.currentShape = {
                    type: this.siteMapEditor.drawingMode,
                    x: x,
                    y: y,
                    width: 0,
                    height: 0,
                    r: 0,
                    stroke: '#000000',
                    strokeWidth: 2,
                    fill: 'none'
                };
            }
        },

        handleSvgMouseMove(event) {
            if (!this.siteMapEditor.currentShape || this.siteMapEditor.drawingMode === 'select') return;

            const svg = document.querySelector('#siteMapSvg');
            const rect = svg.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            if (this.siteMapEditor.drawingMode === 'rect') {
                this.siteMapEditor.currentShape.width = Math.abs(x - this.siteMapEditor.shapeStartX);
                this.siteMapEditor.currentShape.height = Math.abs(y - this.siteMapEditor.shapeStartY);
                this.siteMapEditor.currentShape.x = Math.min(x, this.siteMapEditor.shapeStartX);
                this.siteMapEditor.currentShape.y = Math.min(y, this.siteMapEditor.shapeStartY);
            } else if (this.siteMapEditor.drawingMode === 'circle') {
                const dx = x - this.siteMapEditor.shapeStartX;
                const dy = y - this.siteMapEditor.shapeStartY;
                this.siteMapEditor.currentShape.r = Math.sqrt(dx * dx + dy * dy);
                this.siteMapEditor.currentShape.cx = this.siteMapEditor.shapeStartX;
                this.siteMapEditor.currentShape.cy = this.siteMapEditor.shapeStartY;
            }
        },

        handleSvgMouseUp(event) {
            if (!this.siteMapEditor.currentShape || this.siteMapEditor.drawingMode === 'select') return;

            // Finalize shape
            if (this.siteMapEditor.drawingMode === 'rect' && this.siteMapEditor.currentShape.width > 5) {
                this.siteMapEditor.drawingShapes.push({...this.siteMapEditor.currentShape});
                this.siteMapEditor.drawingUnsavedChanges = true;
            } else if (this.siteMapEditor.drawingMode === 'circle' && this.siteMapEditor.currentShape.r > 5) {
                this.siteMapEditor.drawingShapes.push({...this.siteMapEditor.currentShape});
                this.siteMapEditor.drawingUnsavedChanges = true;
            }

            this.siteMapEditor.currentShape = null;
        },

        finishPolyline() {
            if (this.siteMapEditor.polylinePoints.length >= 2) {
                const points = this.siteMapEditor.polylinePoints.map(p => `${p.x},${p.y}`).join(' ');
                this.siteMapEditor.drawingShapes.push({
                    type: 'polyline',
                    points: points,
                    stroke: '#0000ff',
                    strokeWidth: 2,
                    fill: 'none'
                });
                this.siteMapEditor.drawingUnsavedChanges = true;
            }
            this.siteMapEditor.polylinePoints = [];
        },

        deleteSelectedShape() {
            if (this.siteMapEditor.selectedShape !== null) {
                this.siteMapEditor.drawingShapes.splice(this.siteMapEditor.selectedShape, 1);
                this.siteMapEditor.selectedShape = null;
                this.siteMapEditor.drawingUnsavedChanges = true;
            }
        },

        async saveDrawingOverlay() {
            if (!this.siteMapEditor.selectedLayer) {
                this.showAlert('Aucune couche sélectionnée', 'warning');
                return;
            }

            this.siteMapEditor.loading = true;
            try {
                // Save to the selected layer
                const response = await axios.patch(
                    `/api/v1/sites/${this.siteMapEditor.site.id}/layers/${this.siteMapEditor.selectedLayer.id}`,
                    {
                        drawing_overlay: this.siteMapEditor.drawingShapes
                    }
                );

                // Update local layer data
                this.siteMapEditor.selectedLayer.drawing_overlay = response.data.drawing_overlay;
                this.siteMapEditor.drawingUnsavedChanges = false;
                this.showAlert(`Dessin sauvegardé dans "${this.siteMapEditor.selectedLayer.name}"`, 'success');
                console.log('✅ Drawing overlay saved to layer:', response.data);
            } catch (error) {
                console.error('Error saving drawing overlay:', error);
                this.showAlert('Erreur lors de la sauvegarde de la superposition', 'danger');
            } finally {
                this.siteMapEditor.loading = false;
            }
        },

        async loadDrawingOverlay() {
            // Load from selected layer if available, otherwise from site
            if (this.siteMapEditor.selectedLayer && this.siteMapEditor.selectedLayer.drawing_overlay) {
                this.siteMapEditor.drawingShapes = this.siteMapEditor.selectedLayer.drawing_overlay || [];
                console.log('📐 Loaded drawing overlay from layer:', this.siteMapEditor.drawingShapes.length, 'shapes');
            } else if (this.siteMapEditor.site && this.siteMapEditor.site.drawing_overlay) {
                this.siteMapEditor.drawingShapes = this.siteMapEditor.site.drawing_overlay || [];
                console.log('📐 Loaded drawing overlay from site:', this.siteMapEditor.drawingShapes.length, 'shapes');
            }
        },

        // ==================== LAYER MANAGEMENT ====================

        async loadLayers() {
            if (!this.siteMapEditor.site) return;

            this.siteMapEditor.loading = true;
            try {
                const response = await axios.get(
                    `/api/v1/sites/${this.siteMapEditor.site.id}/layers`
                );
                this.siteMapEditor.layers = response.data;
                console.log('🎨 Loaded layers:', this.siteMapEditor.layers.length);

                // Auto-select first active layer or create default if none exist
                if (this.siteMapEditor.layers.length > 0) {
                    const activeLayer = this.siteMapEditor.layers.find(l => l.is_active);
                    this.siteMapEditor.selectedLayer = activeLayer || this.siteMapEditor.layers[0];
                } else {
                    // No layers exist - create a default one
                    await this.createDefaultLayer();
                }

                // Reload plants for selected layer
                await this.loadSiteMapPlants(this.siteMapEditor.site.id, this.siteMapEditor.selectedLayer.id);

                // Reload drawing overlay from selected layer
                await this.loadDrawingOverlay();
            } catch (error) {
                console.error('Error loading layers:', error);
                this.showAlert('Erreur lors du chargement des couches', 'danger');
            } finally {
                this.siteMapEditor.loading = false;
            }
        },

        async createDefaultLayer() {
            try {
                const today = new Date().toISOString().split('T')[0];
                const response = await axios.post(
                    `/api/v1/sites/${this.siteMapEditor.site.id}/layers`,
                    {
                        name: 'Couche par défaut',
                        start_date: today,
                        is_active: true,
                        notes: 'Couche créée automatiquement'
                    }
                );
                this.siteMapEditor.layers.push(response.data);
                this.siteMapEditor.selectedLayer = response.data;
                console.log('✅ Created default layer');
            } catch (error) {
                console.error('Error creating default layer:', error);
            }
        },

        async switchLayer(layer) {
            this.siteMapEditor.selectedLayer = layer;
            console.log('🔄 Switched to layer:', layer.name);

            // Reload plants for this layer (only show plants associated with this layer)
            await this.loadSiteMapPlants(this.siteMapEditor.site.id, layer.id);

            // Reload drawing overlay for this layer
            await this.loadDrawingOverlay();
        },

        openCreateLayerModal() {
            const today = new Date().toISOString().split('T')[0];
            this.siteMapEditor.newLayerData = {
                name: '',
                start_date: today,
                end_date: '',
                notes: ''
            };
            this.siteMapEditor.showCreateLayerModal = true;
        },

        closeCreateLayerModal() {
            this.siteMapEditor.showCreateLayerModal = false;
        },

        async createNewLayer() {
            this.siteMapEditor.loading = true;
            try {
                const response = await axios.post(
                    `/api/v1/sites/${this.siteMapEditor.site.id}/layers`,
                    {
                        ...this.siteMapEditor.newLayerData,
                        is_active: true
                    }
                );
                this.siteMapEditor.layers.push(response.data);
                this.siteMapEditor.selectedLayer = response.data;
                this.siteMapEditor.drawingShapes = []; // Clear shapes for new layer
                this.closeCreateLayerModal();
                this.showAlert(`Couche "${response.data.name}" créée`, 'success');
                console.log('✅ Created new layer:', response.data);
            } catch (error) {
                console.error('Error creating layer:', error);
                this.showAlert('Erreur lors de la création de la couche', 'danger');
            } finally {
                this.siteMapEditor.loading = false;
            }
        },

        async deleteLayer(layer) {
            if (!confirm(`Supprimer la couche "${layer.name}" ?`)) return;

            this.siteMapEditor.loading = true;
            try {
                await axios.delete(
                    `/api/v1/sites/${this.siteMapEditor.site.id}/layers/${layer.id}`
                );

                // Remove from list
                const index = this.siteMapEditor.layers.findIndex(l => l.id === layer.id);
                if (index !== -1) {
                    this.siteMapEditor.layers.splice(index, 1);
                }

                // Switch to another layer if we deleted the selected one
                if (this.siteMapEditor.selectedLayer?.id === layer.id) {
                    this.siteMapEditor.selectedLayer = this.siteMapEditor.layers[0] || null;
                    await this.loadDrawingOverlay();
                }

                this.showAlert('Couche supprimée', 'success');
                console.log('🗑️ Deleted layer:', layer.name);
            } catch (error) {
                console.error('Error deleting layer:', error);
                this.showAlert('Erreur lors de la suppression de la couche', 'danger');
            } finally {
                this.siteMapEditor.loading = false;
            }
        },

        // ==================== PLANTS LIST (List Page Contract) ====================

        async loadPlantsList(page = 1) {
            this.plantsList.loading = true;

            try {
                const filters = this.plantsList.filters;
                const params = new URLSearchParams();

                // Add filters to query params
                if (filters.q) params.append('q', filters.q);
                if (filters.site) params.append('site', filters.site);
                if (filters.category) params.append('category', filters.category);
                if (filters.status) params.append('status', filters.status);
                if (filters.health_status) params.append('health_status', filters.health_status);
                if (filters.has_observations !== null) params.append('has_observations', filters.has_observations);
                if (filters.has_photos !== null) params.append('has_photos', filters.has_photos);
                if (filters.ordering) params.append('ordering', filters.ordering);
                params.append('page_size', filters.page_size);
                params.append('page', page);

                const response = await axios.get(`/api/v1/plants/?${params.toString()}`);

                this.plantsList.items = this.extractCollection(response.data);
                this.plantsList.pagination = {
                    count: response.data.count,
                    next: response.data.next,
                    previous: response.data.previous,
                    current_page: page,
                    total_pages: Math.ceil(response.data.count / filters.page_size)
                };

                console.log('✅ Plants list loaded:', this.plantsList.items.length, 'plants');
            } catch (error) {
                console.error('Error loading plants list:', error);
                this.showAlert('Erreur lors du chargement des plantes', 'danger');
            } finally {
                this.plantsList.loading = false;
            }
        },

        applyPlantsFilters() {
            this.loadPlantsList(1);
        },

        resetPlantsFilters() {
            this.plantsList.filters = {
                q: '',
                site: '',
                category: '',
                status: '',
                health_status: '',
                has_observations: null,
                has_photos: null,
                ordering: 'name',
                page_size: 25
            };
            this.loadPlantsList(1);
        },

        changePlantsPage(page) {
            this.loadPlantsList(page);
        },

        sortPlantsList(field) {
            const currentOrdering = this.plantsList.filters.ordering;

            // Toggle sort direction if clicking same field
            if (currentOrdering === field) {
                this.plantsList.filters.ordering = '-' + field;
            } else if (currentOrdering === '-' + field) {
                this.plantsList.filters.ordering = field;
            } else {
                this.plantsList.filters.ordering = field;
            }

            this.loadPlantsList(1);
        },

        getPlantsListSortIcon(field) {
            const ordering = this.plantsList.filters.ordering;
            if (ordering === field) {
                return 'fa-sort-up';
            } else if (ordering === '-' + field) {
                return 'fa-sort-down';
            }
            return 'fa-sort';
        },

        // ==================== OBSERVATIONS LIST (List Page Contract) ====================

        async loadObservationsYears() {
            try {
                const response = await axios.get('/api/v1/observations/years-available');
                this.observationsList.availableYears = response.data.years || [];
            } catch (error) {
                console.error('Error loading available years:', error);
            }
        },

        async loadObservationsList(page = 1) {
            this.observationsList.loading = true;

            try {
                const filters = this.observationsList.filters;
                const params = new URLSearchParams();

                // Add filters to query params
                if (filters.q) params.append('q', filters.q);
                if (filters.year) params.append('year', filters.year);
                if (filters.date_from) params.append('date_from', filters.date_from);
                if (filters.date_to) params.append('date_to', filters.date_to);
                if (filters.site) params.append('site', filters.site);
                if (filters.plant) params.append('plant', filters.plant);
                if (filters.taxon) params.append('taxon', filters.taxon);
                if (filters.category) params.append('category', filters.category);
                if (filters.stage) params.append('stage', filters.stage);
                if (filters.has_photos !== null) params.append('has_photos', filters.has_photos);
                if (filters.is_validated !== null) params.append('is_validated', filters.is_validated);
                if (filters.ordering) params.append('ordering', filters.ordering);
                params.append('page_size', filters.page_size);
                params.append('page', page);

                const response = await axios.get(`/api/v1/observations/?${params.toString()}`);

                this.observationsList.items = this.extractCollection(response.data);
                this.observationsList.pagination = {
                    count: response.data.count,
                    next: response.data.next,
                    previous: response.data.previous,
                    current_page: page,
                    total_pages: Math.ceil(response.data.count / filters.page_size)
                };

                console.log('✅ Observations list loaded:', this.observationsList.items.length, 'observations');
            } catch (error) {
                console.error('Error loading observations list:', error);
                this.showAlert('Erreur lors du chargement des observations', 'danger');
            } finally {
                this.observationsList.loading = false;
            }
        },

        applyObservationsFilters() {
            this.loadObservationsList(1);
        },

        resetObservationsFilters() {
            this.observationsList.filters = {
                q: '',
                year: '',
                date_from: '',
                date_to: '',
                site: '',
                plant: '',
                taxon: '',
                category: '',
                stage: '',
                has_photos: null,
                is_validated: null,
                ordering: '-observation_date',
                page_size: 25
            };
            this.loadObservationsList(1);
        },

        changeObservationsPage(page) {
            this.loadObservationsList(page);
        },

        sortObservationsList(field) {
            const currentOrdering = this.observationsList.filters.ordering;

            // Toggle sort direction if clicking same field
            if (currentOrdering === field) {
                this.observationsList.filters.ordering = '-' + field;
            } else if (currentOrdering === '-' + field) {
                this.observationsList.filters.ordering = field;
            } else {
                this.observationsList.filters.ordering = field;
            }

            this.loadObservationsList(1);
        },

        getObservationsListSortIcon(field) {
            const ordering = this.observationsList.filters.ordering;
            if (ordering === field) {
                return 'fa-sort-up';
            } else if (ordering === '-' + field) {
                return 'fa-sort-down';
            }
            return 'fa-sort';
        },

        // ==================== END LIST PAGE CONTRACT METHODS ====================

        backToSites() {
            window.location.hash = '#sites';
        },
        
        editSiteAction(site) {
            console.log('🔧 Editing site:', site);
            
            // Load site data into edit form
            this.editSite = {
                id: site.id,
                name: site.name,
                description: site.description || '',
                latitude: site.latitude,
                longitude: site.longitude,
                altitude: site.altitude,
                environment: site.environment,
                soil_type: site.soil_type || '',
                exposure: site.exposure || '',
                climate_zone: site.climate_zone || '',
                is_private: site.is_private
            };
            
            // Open edit modal
            this.showEditSiteModal = true;
        },
        
        // Add new site
        async addSite() {
            try {
                // Validate form
                if (!this.newSite.name || !this.newSite.environment || 
                    !this.newSite.latitude || !this.newSite.longitude) {
                    this.showAlert('Veuillez remplir tous les champs obligatoires', 'warning');
                    return;
                }
                
                // Prepare data for API - convert empty strings to null
                const siteData = {};
                for (const [key, value] of Object.entries(this.newSite)) {
                    siteData[key] = (value === '') ? null : value;
                }

                // API call to create site
                const response = await axios.post('/api/v1/sites', siteData);
                
                // Add to local sites array
                this.sites.push(response.data);
                this.filteredSites = this.filteredSitesComputed;
                
                // Close modal and reset form
                this.closeModal();
                this.resetNewSiteForm();
                
                // Show success message
                this.showAlert('Site ajouté avec succès', 'success');
                
                // Update map if visible
                if (this.sitesViewMode === 'map' && this.map) {
                    this.updateMapMarkers();
                }
                
            } catch (error) {
                console.error('Error adding site:', error);
                
                // Check for specific error types
                if (error.response && error.response.status === 403) {
                    this.showAlert('Vous devez être connecté pour ajouter un site', 'warning');
                } else if (error.response && error.response.status === 401) {
                    this.showAlert('Authentification requise. Veuillez vous connecter.', 'warning');
                } else if (error.response && error.response.status === 400) {
                    // Validation errors
                    const errors = error.response.data;
                    let errorMessage = 'Erreurs de validation:\n';
                    for (const [field, messages] of Object.entries(errors)) {
                        errorMessage += `• ${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}\n`;
                    }
                    this.showAlert(errorMessage, 'warning');
                } else if (error.response && error.response.data && error.response.data.detail) {
                    this.showAlert(`Erreur: ${error.response.data.detail}`, 'danger');
                } else {
                    this.showAlert('Erreur lors de l\'ajout du site', 'danger');
                }
            }
        },
        
        // Update existing site
        async updateSite() {
            try {
                // Validate form
                if (!this.editSite.name || !this.editSite.environment || 
                    !this.editSite.latitude || !this.editSite.longitude) {
                    this.showAlert('Veuillez remplir tous les champs obligatoires', 'warning');
                    return;
                }
                
                // Prepare data for API - convert empty strings to null
                const siteData = {};
                for (const [key, value] of Object.entries(this.editSite)) {
                    if (key === 'id') continue;
                    siteData[key] = (value === '') ? null : value;
                }
                
                console.log('🔄 Updating site:', this.editSite.id, siteData);
                
                // API call to update site
                const response = await axios.put(`/api/v1/sites/${this.editSite.id}`, siteData);
                
                console.log('✅ Site updated:', response.data);
                
                // Update local sites array
                const siteIndex = this.sites.findIndex(s => s.id === this.editSite.id);
                if (siteIndex !== -1) {
                    this.sites[siteIndex] = response.data;
                    this.filteredSites = this.filteredSitesComputed;
                }
                
                // Close modal and reset form
                this.closeModal();
                this.resetEditSiteForm();
                
                // Show success message
                this.showAlert(`Site "${response.data.name}" modifié avec succès`, 'success');
                
                // Update map if visible
                if (this.sitesViewMode === 'map' && this.map) {
                    this.updateMapMarkers();
                }
                
            } catch (error) {
                console.error('Error updating site:', error);
                
                // Check for specific error types
                if (error.response && error.response.status === 403) {
                    this.showAlert('Vous n\'avez pas l\'autorisation de modifier ce site', 'warning');
                } else if (error.response && error.response.status === 401) {
                    this.showAlert('Authentification requise. Veuillez vous connecter.', 'warning');
                } else if (error.response && error.response.status === 400) {
                    // Validation errors
                    const errors = error.response.data;
                    let errorMessage = 'Erreurs de validation:\n';
                    for (const [field, messages] of Object.entries(errors)) {
                        errorMessage += `• ${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}\n`;
                    }
                    this.showAlert(errorMessage, 'warning');
                } else if (error.response && error.response.status === 404) {
                    this.showAlert('Site non trouvé', 'danger');
                } else if (error.response && error.response.status === 419) {
                    this.showAlert('Token CSRF expiré. Rechargez la page et reconnectez-vous.', 'warning');
                } else if (error.response && error.response.data && error.response.data.detail) {
                    this.showAlert(`Erreur: ${error.response.data.detail}`, 'danger');
                } else {
                    const status = error.response?.status || 'unknown';
                    const msg = error.response?.data?.message || error.message || 'Erreur inconnue';
                    this.showAlert(`Erreur ${status}: ${msg}`, 'danger');
                }
            }
        },
        
        // Reset new site form
        resetNewSiteForm() {
            this.newSite = {
                name: '',
                description: '',
                latitude: null,
                longitude: null,
                altitude: null,
                environment: 'garden',
                soil_type: '',
                exposure: '',
                climate_zone: '',
                is_private: false
            };
        },
        
        // Reset edit site form
        resetEditSiteForm() {
            this.editSite = {
                id: null,
                name: '',
                description: '',
                latitude: null,
                longitude: null,
                altitude: null,
                environment: 'garden',
                soil_type: '',
                exposure: '',
                climate_zone: '',
                is_private: false
            };
        },
        
        // Open modal with proper body handling
        openModal(modalType, context = null) {
            // Close all modals first
            this.closeModal();

            // Open specific modal
            switch(modalType) {
                case 'site':
                    this.showAddSiteModal = true;
                    break;
                case 'editSite':
                    this.showEditSiteModal = true;
                    break;
                case 'plant':
                    this.showAddPlantModal = true;
                    break;
                case 'observation':
                    // Pre-fill plant if context provided (from plant detail page)
                    if (context && context.plantId) {
                        this.newObservation.plant = context.plantId;
                    } else {
                        // Reset plant selection when opening without context
                        this.newObservation.plant = '';
                    }
                    this.showAddObservationModal = true;
                    break;
                case 'photo':
                    // Reset for plant photo context
                    this.newPhoto.photo_type = 'general';
                    this.newPhoto.title = '';
                    this.newPhoto.description = '';
                    this.newPhoto.is_public = true;
                    // Pre-fill plant if context provided (from plant detail page)
                    if (context && context.plantId) {
                        this.newPhoto.plant = context.plantId;
                    } else {
                        this.newPhoto.plant = '';
                    }
                    // Use Bootstrap Modal API for photo modal
                    const photoModalElement = document.getElementById('addPhotoModal');
                    if (photoModalElement) {
                        const photoModal = bootstrap.Modal.getOrCreateInstance(photoModalElement);
                        photoModal.show();
                    }
                    break;
                case 'login':
                    this.showLoginModal = true;
                    break;
                case 'test':
                    this.showTestSiteModal = true;
                    break;
            }
        },
        
        // Utility: Clean up all modal artifacts (backdrops, body classes, overflow)
        cleanupModalArtifacts() {
            // Remove all modal backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());

            // Reset body styling completely
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        },

        // Close modal and clean up backdrop
        closeModal() {
            // Close photo modal using Bootstrap API (if exists)
            const photoModalElement = document.getElementById('addPhotoModal');
            if (photoModalElement) {
                const photoModal = bootstrap.Modal.getInstance(photoModalElement);
                if (photoModal) {
                    photoModal.hide();
                }
            }

            // Close all other modals (Vue state)
            this.showAddSiteModal = false;
            this.showEditSiteModal = false;
            this.showAddPlantModal = false;
            this.showEditPlantModal = false;
            this.showAddObservationModal = false;
            this.showEditObservationModal = false;
            this.showDeleteObservationModal = false;
            this.showDeletePlantModal = false;
            this.showEditPhotoModal = false;
            this.showLoginModal = false;
            this.showTestSiteModal = false;
            this.showMarkDeadModal = false;
            this.showReplacePlantModal = false;

            // Failsafe cleanup (only for non-Bootstrap modals)
            setTimeout(() => {
                this.cleanupModalArtifacts();
            }, 200);
        },
        
        // ===== AUTHENTICATION METHODS =====
        async login() {
            this.loginForm.error = '';
            try {
                // Fetch CSRF cookie from Sanctum before login
                await axios.get('/sanctum/csrf-cookie');

                const username = this.loginForm.username.trim();
                const response = await axios.post('/api/v1/auth/login', {
                    username,
                    password: this.loginForm.password
                });
                
                if (response.data.success) {
                    this.user.username = response.data.user.username;
                    this.user.isAuthenticated = true;
                    this.user.id = response.data.user.id;
                    this.user.email = response.data.user.email;
                    this.user.isStaff = response.data.user.is_staff;
                    this.user.isSuperuser = response.data.user.is_superuser;

                    // Refresh CSRF cookie after session regeneration
                    await axios.get('/sanctum/csrf-cookie');

                    this.showLoginModal = false;
                    this.showAlert(response.data.message || 'Connexion réussie !', 'success');

                    // Reset form
                    this.loginForm = { username: '', password: '', error: '' };
                } else {
                    this.loginForm.error = response.data.error || 'Erreur de connexion';
                }
            } catch (error) {
                if (error.response?.data?.error) {
                    this.loginForm.error = error.response.data.error;
                } else if (error.response?.data?.message) {
                    this.loginForm.error = error.response.data.message;
                } else {
                    this.loginForm.error = 'Erreur de connexion. Vérifiez vos identifiants.';
                }
                console.error('Login error:', error);
            }
        },
        
        async logout() {
            try {
                const response = await axios.post('/api/v1/auth/logout');
                
                if (response.data.success) {
                    this.user.isAuthenticated = false;
                    this.user.username = 'Utilisateur';
                    this.user.id = null;
                    this.user.email = '';
                    this.user.isStaff = false;
                    this.user.isSuperuser = false;
                    
                    this.showAlert(response.data.message || 'Déconnexion réussie', 'info');
                } else {
                    this.showAlert('Erreur lors de la déconnexion', 'warning');
                }
            } catch (error) {
                console.error('Logout error:', error);
                // Force logout on client side even if server fails
                this.user.isAuthenticated = false;
                this.user.username = 'Utilisateur';
                this.user.id = null;
                this.user.email = '';
                this.user.isStaff = false;
                this.user.isSuperuser = false;
                this.showAlert('Déconnexion effectuée', 'info');
            }
        },
        
        async checkAuthStatus() {
            try {
                const response = await axios.get('/api/v1/auth/status');

                const isAuthenticated = response.data.isAuthenticated ?? response.data.authenticated ?? false;

                if (isAuthenticated) {
                    this.user.username = response.data.user.username;
                    this.user.isAuthenticated = true;
                    this.user.id = response.data.user.id;
                    this.user.email = response.data.user.email;
                    this.user.isStaff = response.data.user.is_staff;
                    this.user.isSuperuser = response.data.user.is_superuser;
                } else {
                    this.user.isAuthenticated = false;
                    this.user.username = 'Utilisateur';
                    this.user.id = null;
                    this.user.email = '';
                    this.user.isStaff = false;
                    this.user.isSuperuser = false;
                }
            } catch (error) {
                console.error('Auth status check error:', error);
                // Default to not authenticated
                this.user.isAuthenticated = false;
                this.user.username = 'Utilisateur';
            }
        },
        
        // ===== SITE DETAILED MAPPING METHODS =====
        async showSiteMap(site) {
            this.loading.sites = true;
            try {
                const response = await fetch(`/api/v1/plants/site-map?site_id=${site.id}`);
                if (response.ok) {
                    this.siteMapData = await response.json();
                    this.siteMapVisible = true;
                    this.currentView = 'site-map';
                    
                    // Initialize map after Vue renders the element
                    this.$nextTick(() => {
                        this.initializeSiteMap();
                    });
                } else {
                    console.error('Erreur lors du chargement de la carte du site');
                }
            } catch (error) {
                console.error('Erreur:', error);
            } finally {
                this.loading.sites = false;
            }
        },
        
        initializeSiteMap() {
            if (!this.siteMapData || !this.siteMapData.site) return;
            
            const site = this.siteMapData.site;
            const plants = this.siteMapData.plants;
            
            // Initialize Leaflet map
            const mapContainer = document.getElementById('site-detailed-map');
            if (!mapContainer) return;
            
            // Clear existing map
            if (this.mapInstance) {
                this.mapInstance.remove();
            }
            
            // Create new map centered on site
            const siteCoords = site.coordinates || [46.603354, 1.888334]; // Default center of France
            this.mapInstance = L.map('site-detailed-map').setView(siteCoords, 18); // High zoom for detail
            
            // Add satellite imagery for precision
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '© Esri, Maxar, GeoEye',
                maxZoom: 20
            }).addTo(this.mapInstance);
            
            // Add site center marker
            const siteMarker = L.marker(siteCoords, {
                icon: L.divIcon({
                    className: 'site-center-marker',
                    html: '<div class="site-center-icon">🏛️</div>',
                    iconSize: [30, 30]
                })
            }).addTo(this.mapInstance);
            siteMarker.bindPopup(`
                <strong>${site.name}</strong><br>
                Centre du site<br>
                ${site.area_hectares ? `Superficie: ${site.area_hectares} ha<br>` : ''}
                ${site.altitude ? `Altitude: ${site.altitude}m` : ''}
            `);
            
            // Add plant markers with precise positioning
            plants.forEach(plant => {
                if (plant.coordinates) {
                    const plantIcon = this.getPlantIcon(plant);
                    const marker = L.marker(plant.coordinates, { icon: plantIcon })
                        .addTo(this.mapInstance);
                    
                    // Detailed popup with plant info
                    const popupContent = `
                        <div class="plant-popup">
                            <h6>${plant.name}</h6>
                            <p><em>${plant.taxon.binomial_name}</em></p>
                            <p>${plant.taxon.common_name_fr || ''}</p>
                            <div class="popup-details">
                                <small>
                                    Catégorie: ${plant.category.name}<br>
                                    État: ${this.getHealthLabel(plant.health_status)}<br>
                                    ${plant.exact_height ? `Hauteur: ${plant.exact_height}m<br>` : ''}
                                    ${plant.gps_accuracy ? `Précision GPS: ±${plant.gps_accuracy}m<br>` : ''}
                                    ${plant.distance_from_site_center ? `Distance centre: ${Math.round(plant.distance_from_site_center)}m<br>` : ''}
                                    Observations: ${plant.observations_count}
                                </small>
                            </div>
                            <button class="btn btn-sm btn-primary mt-1" onclick="app.selectPlantOnMap(${plant.id})">
                                Voir détails
                            </button>
                        </div>
                    `;
                    marker.bindPopup(popupContent);
                    
                    // Store plant reference on marker
                    marker.plantData = plant;
                }
            });
            
            // Add scale control
            L.control.scale({ metric: true, imperial: false }).addTo(this.mapInstance);
        },
        
        getPlantIcon(plant) {
            // Different icons based on category and health
            let iconHtml = '';
            let className = 'plant-marker';
            
            switch (plant.category.category_type) {
                case 'trees':
                    iconHtml = '🌳';
                    break;
                case 'shrubs':
                    iconHtml = '🌿';
                    break;
                case 'plants':
                    iconHtml = '🌱';
                    break;
                default:
                    iconHtml = '🌿';
            }
            
            // Add health status color
            switch (plant.health_status) {
                case 'excellent':
                    className += ' health-excellent';
                    break;
                case 'good':
                    className += ' health-good';
                    break;
                case 'fair':
                    className += ' health-fair';
                    break;
                case 'poor':
                    className += ' health-poor';
                    break;
                case 'dead':
                    className += ' health-dead';
                    break;
            }
            
            return L.divIcon({
                className: className,
                html: `<div class="plant-icon">${iconHtml}</div>`,
                iconSize: [24, 24]
            });
        },
        
        selectPlantOnMap(plantId) {
            this.selectedPlantOnMap = this.siteMapData.plants.find(p => p.id === plantId);
        },
        
        closeSiteMap() {
            this.siteMapVisible = false;
            this.currentView = 'sites';
            this.siteMapData = null;
            this.selectedPlantOnMap = null;
            if (this.mapInstance) {
                this.mapInstance.remove();
                this.mapInstance = null;
            }
        },
        
        groupPlantsByCategory(plants) {
            const categories = {};
            plants.forEach(plant => {
                const type = plant.category.category_type;
                if (!categories[type]) {
                    categories[type] = {
                        type: type,
                        name: plant.category.name,
                        count: 0
                    };
                }
                categories[type].count++;
            });
            return Object.values(categories);
        },
        
        // ===== PLANT NAVIGATION AND DETAIL METHODS =====
        async viewPlantDetail(plantId) {
            this.currentView = 'plant-detail';
            this.currentPlant = plantId;
            this.plantDetail.loading = true;
            
            try {
                // Load plant details
                const plantResponse = await fetch(`/api/v1/plants/${plantId}`);
                if (plantResponse.ok) {
                    this.plantDetail.plant = await plantResponse.json();

                    // Load plant observations
                    const obsResponse = await fetch(`/api/v1/plants/${plantId}/observations`);
                    if (obsResponse.ok) {
                        const obsData = await obsResponse.json();
                        this.plantDetail.observations = Array.isArray(obsData) ? obsData : (obsData.observations || []);
                    }

                    // Load plant photos
                    const photosResponse = await fetch(`/api/v1/plants/${plantId}/photos`);
                    if (photosResponse.ok) {
                        const photosData = await photosResponse.json();
                        this.plantDetail.photos = Array.isArray(photosData) ? photosData : (photosData.photos || []);
                    }

                    // Load plant statistics
                    const statsResponse = await fetch(`/api/v1/plants/${plantId}/statistics`);
                    if (statsResponse.ok) {
                        this.plantDetail.statistics = await statsResponse.json();
                    }
                    
                    console.log('🌱 Plant detail loaded:', this.plantDetail.plant);
                } else {
                    console.error('Plant not found');
                    this.plantDetail.plant = null;
                }
            } catch (error) {
                console.error('Error loading plant detail:', error);
                this.plantDetail.plant = null;
            } finally {
                this.plantDetail.loading = false;
            }
        },
        
        backToPlants() {
            window.location.hash = '#plants';
            this.currentView = 'plants';
            this.currentPlant = null;
            this.plantDetail.plant = null;
        },
        
        // Search for plants by name or scientific name
        async searchPlants(query) {
            try {
                const response = await fetch(`/api/v1/plants/?search=${encodeURIComponent(query)}`);
                if (response.ok) {
                    const data = await response.json();
                    return this.extractCollection(data);
                }
            } catch (error) {
                console.error('Error searching plants:', error);
            }
            return [];
        },
        
        // Navigate to specific plant
        navigateToPlant(plantId) {
            window.location.hash = `#plant/${plantId}`;
        },

        viewPlant(plant) {
            // Navigate to plant detail page (used by site detail table)
            this.navigateToPlant(plant.id);
        },

        // Navigate to plant from map
        selectPlantOnMap(plantId) {
            this.selectedPlantOnMap = this.siteMapData.plants.find(p => p.id === plantId);
            
            // Add navigation button to selected plant
            if (this.selectedPlantOnMap) {
                // Store for later navigation
                this.selectedPlantOnMap._navigable = true;
            }
        },
        
        // Navigate to plant detail from map selection
        navigateToSelectedPlant() {
            if (this.selectedPlantOnMap && this.selectedPlantOnMap.id) {
                this.navigateToPlant(this.selectedPlantOnMap.id);
            }
        },
        
        // Load plants data
        async loadPlants() {
            this.loading.plants = true;
            try {
                const response = await fetch('/api/v1/plants');
                if (response.ok) {
                    const data = await response.json();
                    this.plants = this.extractCollection(data);
                    console.log('🌱 Plants loaded:', this.plants.length);
                } else {
                    console.error('Error loading plants');
                    this.plants = [];
                }
            } catch (error) {
                console.error('Error loading plants:', error);
                this.plants = [];
            } finally {
                this.loading.plants = false;
            }
        },

        // Load plant positions data
        async loadPositions() {
            try {
                const response = await fetch('/api/v1/plant-positions');
                if (response.ok) {
                    const data = await response.json();
                    this.plantPositions = this.extractCollection(data);
                    console.log('📍 Plant positions loaded:', this.plantPositions.length);
                } else {
                    console.error('Error loading plant positions');
                    this.plantPositions = [];
                }
            } catch (error) {
                console.error('Error loading plant positions:', error);
                this.plantPositions = [];
            }
        },

        // View position detail with succession history
        async viewPositionDetail(positionId) {
            this.positionDetail.loading = true;
            try {
                // Load position details
                const posResponse = await fetch(`/api/v1/plant-positions/${positionId}`);
                if (posResponse.ok) {
                    this.positionDetail.position = await posResponse.json();
                }

                // Load succession history
                const successionResponse = await fetch(`/api/v1/plant-positions/${positionId}/succession`);
                if (successionResponse.ok) {
                    const data = await successionResponse.json();
                    this.positionDetail.successionHistory = data.succession || [];
                }

                this.currentPosition = positionId;
                this.currentView = 'position-detail';
                console.log('📍 Position detail loaded:', this.positionDetail.position);
            } catch (error) {
                console.error('Error loading position detail:', error);
                this.positionDetail.position = null;
                this.positionDetail.successionHistory = [];
            } finally {
                this.positionDetail.loading = false;
            }
        },

        // ===== FORM DATA LOADING METHODS =====
        async loadFormData() {
            // Load categories
            try {
                const categoriesResponse = await axios.get('/api/v1/categories');
                this.categories = this.extractCollection(categoriesResponse.data);
                console.log(`✅ ${this.categories.length} categories loaded for forms`);

                // Warn about categories with missing names
                const emptyCategories = this.categories.filter(c => !c.name);
                if (emptyCategories.length > 0) {
                    console.warn(`⚠️ ${emptyCategories.length} categories have empty names:`, emptyCategories.map(c => c.id));
                }
            } catch (error) {
                console.error('❌ Error loading categories:', error);
                this.categories = [];
            }

            // Load phenological stages
            try {
                const stagesResponse = await axios.get('/api/v1/phenological-stages');
                this.phenologicalStages = this.extractCollection(stagesResponse.data);
                console.log(`✅ ${this.phenologicalStages.length} phenological stages loaded`);
            } catch (error) {
                console.error('❌ Error loading stages:', error);
                this.phenologicalStages = [];
            }

            // Taxons are now loaded via autocomplete search (no need to load all upfront)
            console.log('✅ Taxon autocomplete ready (dynamic loading)');

            // Load plants
            try {
                const plantsResponse = await axios.get('/api/v1/plants');
                this.plants = this.extractCollection(plantsResponse.data);
            } catch (error) {
                console.error('Error loading plants:', error);
                this.plants = [];
            }
        },
        
        // ===== PLANT FORM METHODS =====
        updateFamilyFromTaxon() {
            // Update the selected taxon family when taxon changes
            if (this.newPlant.taxon) {
                const selectedTaxon = this.taxons.find(t => t.id === this.newPlant.taxon);
                this.selectedTaxonFamily = selectedTaxon?.family || null;
            } else {
                this.selectedTaxonFamily = null;
            }
        },

        async addPlant() {
            if (!this.user.isAuthenticated) {
                this.showAlert('Fonctionnalité de démonstration - Connectez-vous avec admin/admin123 pour enregistrer réellement', 'info');
                this.showAddPlantModal = false;
                this.resetNewPlantForm();
                return;
            }
            
            try {
                // Prepare data - map field names and convert empty strings to null
                const plantData = {};
                for (const [key, value] of Object.entries(this.newPlant)) {
                    plantData[key] = (value === '') ? null : value;
                }
                // Map frontend field names to API field names
                if (plantData.taxon) { plantData.taxon_id = plantData.taxon; delete plantData.taxon; }
                if (plantData.category) { plantData.category_id = plantData.category; delete plantData.category; }
                if (plantData.site) { plantData.site_id = plantData.site; delete plantData.site; }
                if (plantData.position) { plantData.position_id = plantData.position; delete plantData.position; }
                delete plantData.location;

                const response = await axios.post('/api/v1/plants', plantData);
                
                this.plants.push(response.data);
                this.showAddPlantModal = false;
                this.resetNewPlantForm();
                this.showAlert('Plante ajoutée avec succès !', 'success');
            } catch (error) {
                console.error('Error adding plant:', error);
                const status = error.response?.status || 'unknown';
                const msg = error.response?.data?.message || error.response?.data?.detail || JSON.stringify(error.response?.data?.errors || error.message);
                this.showAlert(`Erreur ${status}: ${msg}`, 'danger');
            }
        },
        
        resetNewPlantForm() {
            this.newPlant = {
                name: '',
                description: '',
                taxon: null,
                category: null,
                site: null,
                position: null,
                planting_date: null,
                age_years: null,
                height_category: '',
                exact_height: null,
                health_status: 'good',
                clone_or_accession: '',
                is_private: false,
                notes: '',
                anecdotes: '',
                cultural_significance: '',
                ecological_notes: '',
                care_notes: '',
                // GPS fields
                latitude: null,
                longitude: null,
                gps_accuracy: null
            };
            
            // Reset GPS validation and preview
            this.gpsValidation.latitude = null;
            this.gpsValidation.longitude = null;
            this.showGpsPreview = false;

            // Reset selected taxon family
            this.selectedTaxonFamily = null;

            if (this.gpsMap) {
                this.gpsMap.remove();
                this.gpsMap = null;
            }
        },
        
        // Format date for display
        formatDate(dateString) {
            if (!dateString) return 'Non définie';
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        // Get status badge class for plant
        getStatusBadgeClass(status) {
            const statusClasses = {
                'alive': 'bg-success',
                'dead': 'bg-danger',
                'replaced': 'bg-warning',
                'removed': 'bg-secondary'
            };
            return statusClasses[status] || 'bg-secondary';
        },

        // Format status for display
        formatStatus(status) {
            const statusLabels = {
                'alive': 'Vivant',
                'dead': 'Mort',
                'replaced': 'Remplacé',
                'removed': 'Retiré'
            };
            return statusLabels[status] || status;
        },

        // ===== TAXON AUTOCOMPLETE METHODS =====
        async searchTaxons(context = 'newPlant') {
            const autocomplete = context === 'newPlant' ? this.taxonAutocomplete : (context === 'editPlant' ? this.taxonAutocompleteEdit : this.taxonAutocompleteReplace);
            const query = autocomplete.query.trim();

            // Don't search if query too short
            if (query.length < 2) {
                autocomplete.results = [];
                autocomplete.showDropdown = false;
                return;
            }

            // Check cache first
            if (autocomplete.cache[query]) {
                autocomplete.results = autocomplete.cache[query];
                autocomplete.showDropdown = true;
                return;
            }

            // Debounce search requests
            if (autocomplete.debounceTimer) {
                clearTimeout(autocomplete.debounceTimer);
            }

            autocomplete.debounceTimer = setTimeout(async () => {
                autocomplete.loading = true;
                try {
                    const response = await axios.get('/api/v1/taxons', {
                        params: {
                            search: query,
                            page_size: 20
                        }
                    });
                    const results = this.extractCollection(response.data);

                    // Ensure display_name is always valid
                    autocomplete.results = results.map(taxon => ({
                        ...taxon,
                        display_name: taxon.display_name || taxon.binomial_name || `Taxon #${taxon.id}`
                    }));

                    // Cache results
                    autocomplete.cache[query] = autocomplete.results;
                    autocomplete.showDropdown = true;
                } catch (error) {
                    console.error('Error searching taxons:', error);
                    autocomplete.results = [];
                } finally {
                    autocomplete.loading = false;
                }
            }, 300); // 300ms debounce
        },

        selectTaxon(taxon, context = 'newPlant') {
            const autocomplete = context === 'newPlant' ? this.taxonAutocomplete : (context === 'editPlant' ? this.taxonAutocompleteEdit : this.taxonAutocompleteReplace);

            autocomplete.selectedTaxon = taxon;
            autocomplete.query = taxon.display_name;
            autocomplete.showDropdown = false;

            // Update form data
            if (context === 'newPlant') {
                this.newPlant.taxon = taxon.id;
                this.updateFamilyFromTaxon();
            } else if (context === 'editPlant') {
                this.editPlantData.taxon = taxon.id;
            } else if (context === 'replace') {
                this.replacePlantForm.new_plant.taxon = taxon.id;
            }
        },

        clearTaxonSelection(context = 'newPlant') {
            const autocomplete = context === 'newPlant' ? this.taxonAutocomplete : (context === 'editPlant' ? this.taxonAutocompleteEdit : this.taxonAutocompleteReplace);

            autocomplete.selectedTaxon = null;
            autocomplete.query = '';
            autocomplete.results = [];
            autocomplete.showDropdown = false;

            if (context === 'newPlant') {
                this.newPlant.taxon = null;
            } else if (context === 'editPlant') {
                this.editPlantData.taxon = null;
            } else if (context === 'replace') {
                this.replacePlantForm.new_plant.taxon = null;
            }
        },

        closeTaxonDropdown(context = 'newPlant') {
            const autocomplete = context === 'newPlant' ? this.taxonAutocomplete : (context === 'editPlant' ? this.taxonAutocompleteEdit : this.taxonAutocompleteReplace);
            setTimeout(() => {
                autocomplete.showDropdown = false;
            }, 200); // Delay to allow click events to fire
        },

        handleTaxonKeydown(event, context = 'newPlant') {
            const autocomplete = context === 'newPlant' ? this.taxonAutocomplete : (context === 'editPlant' ? this.taxonAutocompleteEdit : this.taxonAutocompleteReplace);

            if (!autocomplete.showDropdown || autocomplete.results.length === 0) {
                return;
            }

            // Arrow navigation (implement if needed - for v1, click-to-select is sufficient)
            if (event.key === 'Escape') {
                autocomplete.showDropdown = false;
            }
        },

        // ===== PLANT POSITION METHODS =====
        async createPosition() {
            if (!this.user.isAuthenticated) {
                this.showAlert('Vous devez être connecté pour créer une position', 'warning');
                return;
            }

            try {
                const positionData = {
                    ...this.newPosition,
                    location: this.newPosition.latitude && this.newPosition.longitude ? {
                        type: 'Point',
                        coordinates: [this.newPosition.longitude, this.newPosition.latitude]
                    } : null
                };

                const response = await axios.post('/api/v1/plant-positions', positionData);

                this.plantPositions.push(response.data);
                this.showAddPositionModal = false;
                this.resetNewPositionForm();
                this.showAlert('Position créée avec succès !', 'success');

                // Reload positions list
                await this.loadPositions();
            } catch (error) {
                console.error('Error creating position:', error);
                const errorMsg = error.response?.data?.label || error.response?.data?.detail || 'Erreur lors de la création de la position';
                this.showAlert(errorMsg, 'danger');
            }
        },

        resetNewPositionForm() {
            this.newPosition = {
                site: null,
                label: '',
                description: '',
                latitude: null,
                longitude: null,
                gps_accuracy: null,
                soil_notes: '',
                exposure_notes: '',
                microclimate_notes: '',
                is_active: true
            };
        },

        // ===== PLANT LIFECYCLE METHODS =====
        async markPlantAsDead() {
            if (!this.user.isAuthenticated) {
                this.showAlert('Vous devez être connecté pour marquer une plante comme morte', 'warning');
                return;
            }

            try {
                const response = await axios.post(
                    `/api/v1/plants/${this.markDeadForm.plant_id}/mark-dead`,
                    {
                        death_date: this.markDeadForm.death_date,
                        death_cause: this.markDeadForm.death_cause,
                        death_notes: this.markDeadForm.death_notes
                    }
                );

                this.showMarkDeadModal = false;
                this.showAlert(response.data.message || 'Plante marquée comme morte', 'success');

                // Reload plant detail if viewing
                if (this.currentView === 'plant-detail' && this.currentPlant) {
                    await this.viewPlantDetail(this.currentPlant);
                }

                // Reload plants list
                await this.loadPlants();

                this.resetMarkDeadForm();
            } catch (error) {
                console.error('Error marking plant as dead:', error);
                const errorMsg = error.response?.data?.error || error.response?.data?.detail || 'Erreur lors du marquage de la plante';
                this.showAlert(errorMsg, 'danger');
            }
        },

        resetMarkDeadForm() {
            this.markDeadForm = {
                plant_id: null,
                death_date: new Date().toISOString().split('T')[0],
                death_cause: '',
                death_notes: ''
            };
        },

        async replacePlant() {
            if (!this.user.isAuthenticated) {
                this.showAlert('Vous devez être connecté pour remplacer une plante', 'warning');
                return;
            }

            try {
                const response = await axios.post(
                    `/api/v1/plants/${this.replacePlantForm.old_plant_id}/replace`,
                    {
                        new_plant: this.replacePlantForm.new_plant
                    }
                );

                this.showReplacePlantModal = false;
                this.showAlert(response.data.message || 'Plante remplacée avec succès', 'success');

                // Navigate to new plant detail
                if (response.data.new_plant && response.data.new_plant.id) {
                    await this.viewPlantDetail(response.data.new_plant.id);
                }

                // Reload plants list
                await this.loadPlants();

                this.resetReplacePlantForm();
            } catch (error) {
                console.error('Error replacing plant:', error);
                const errorMsg = error.response?.data?.error || error.response?.data?.detail || 'Erreur lors du remplacement de la plante';
                this.showAlert(errorMsg, 'danger');
            }
        },

        resetReplacePlantForm() {
            this.replacePlantForm = {
                old_plant_id: null,
                new_plant: {
                    name: '',
                    taxon: null,
                    category: null,
                    planting_date: new Date().toISOString().split('T')[0],
                    is_private: false,
                    description: '',
                    notes: ''
                }
            };
        },

        openMarkDeadModal(plant) {
            this.markDeadForm.plant_id = plant.id;
            this.showMarkDeadModal = true;
        },

        openReplacePlantModal(plant) {
            this.replacePlantForm.old_plant_id = plant.id;
            this.replacePlantForm.new_plant.name = `Remplacement de ${plant.name}`;
            this.replacePlantForm.new_plant.is_private = plant.is_private;
            this.showReplacePlantModal = true;
        },

        // ===== OBSERVATION FORM METHODS =====
        addObservation() {
            if (!this.user.isAuthenticated) {
                this.showAlert('Fonctionnalité de démonstration - Connectez-vous avec admin/admin123 pour enregistrer réellement', 'info');
                this.showAddObservationModal = false;
                this.resetNewObservationForm();
                return;
            }
            
            const payload = { ...this.newObservation };
            // Map frontend field names to Laravel _id fields
            if (payload.plant) { payload.plant_id = payload.plant; delete payload.plant; }
            if (payload.phenological_stage) { payload.phenological_stage_id = payload.phenological_stage; delete payload.phenological_stage; }
            if (payload.weather_conditions) { payload.weather_condition = payload.weather_conditions; delete payload.weather_conditions; }
            // Convert empty strings to null
            for (const [key, value] of Object.entries(payload)) {
                if (value === '') payload[key] = null;
            }

            axios.post('/api/v1/observations', payload)
                .then(response => {
                    this.showAddObservationModal = false;
                    this.resetNewObservationForm();
                    this.showAlert('Observation ajoutée avec succès !', 'success');
                    // Reload observations if we're on the observations view
                    if (this.currentView === 'observations') {
                        this.loadObservations();
                    }
                    // Reload plant detail if viewing a plant
                    if (this.currentView === 'plant-detail' && this.plantDetail.plant) {
                        this.viewPlantDetail(this.plantDetail.plant.id);
                    }
                })
                .catch(error => {
                    console.error('Error adding observation:', error);
                    const msg = error.response ? `Erreur ${error.response.status}: ${error.response.data.message || JSON.stringify(error.response.data.errors || error.response.data)}` : error.message;
                    this.showAlert(msg, 'danger');
                });
        },
        
        resetNewObservationForm() {
            this.newObservation = {
                plant: null,
                phenological_stage: null,
                observation_date: new Date().toISOString().split('T')[0],
                intensity: 1,
                notes: '',
                weather_conditions: '',
                temperature: null,
                is_public: true
            };
        },

        // ===== OBSERVATION LIST METHODS =====
        async loadObservations() {
            if (!this.user.isAuthenticated) {
                this.observations = [];
                return;
            }

            this.loading.observations = true;
            try {
                const response = await fetch('/api/v1/observations/my-observations');
                if (response.ok) {
                    this.observations = await response.json();
                    console.log('📊 Observations loaded:', this.observations.length);
                } else {
                    console.error('Error loading observations:', response.status);
                    this.observations = [];
                }
            } catch (error) {
                console.error('Error loading observations:', error);
                this.showAlert('Erreur lors du chargement des observations', 'danger');
            } finally {
                this.loading.observations = false;
            }
        },

        resetObservationFilters() {
            this.observationFilters = {
                startDate: null,
                endDate: null,
                plant: null,
                stage: null
            };
        },

        async viewObservationDetail(obsId) {
            this.loading.observations = true;
            try {
                const response = await fetch(`/api/v1/observations/${obsId}`);
                if (response.ok) {
                    this.currentObservation = await response.json();
                    this.currentView = 'observation-detail';
                    this.telaComparison = null; // Reset comparison data

                    // Load photos for this observation
                    await this.loadObservationPhotos(obsId);

                    console.log('📊 Observation detail loaded:', this.currentObservation);
                } else {
                    console.error('Error loading observation detail:', response.status);
                    this.showAlert('Erreur lors du chargement de l\'observation', 'danger');
                }
            } catch (error) {
                console.error('Error loading observation detail:', error);
                this.showAlert('Erreur lors du chargement de l\'observation', 'danger');
            } finally {
                this.loading.observations = false;
            }
        },

        backToObservations() {
            this.currentView = 'observations';
            this.currentObservation = null;
            this.telaComparison = null;
        },

        async loadTelaComparison() {
            if (!this.currentObservation || !this.currentObservation.plant || !this.currentObservation.phenological_stage) {
                this.showAlert('Données insuffisantes pour la comparaison', 'warning');
                return;
            }

            try {
                const plantId = this.currentObservation.plant.id;
                const stageCode = this.currentObservation.phenological_stage.stage_code;
                const response = await fetch(`/api/v1/comparison/?plant_id=${plantId}&stage_code=${stageCode}`);

                if (response.ok) {
                    const data = await response.json();

                    // Extract national_comparison from response (ODS data)
                    if (data.national_comparison && data.national_comparison.comparison_possible) {
                        const ods = data.national_comparison;

                        // Transform ODS data to match existing UI structure
                        const stats = ods.national_statistics;
                        const byYear = data.ods_by_year || [];
                        const years = byYear.map(y => y.year);
                        this.telaComparison = {
                            source: 'ODS',
                            status: ods.comparison.status,
                            difference_days: Math.abs(ods.comparison.diff_from_mean_days),
                            status_label: ods.comparison.status_label,
                            national_stats: {
                                count: stats.total_observations,
                                mean_day: stats.avg_day_of_year,
                                median_day: stats.avg_day_of_year, // approx, no median from API
                                year_range: years.length ? `${Math.min(...years)}-${Math.max(...years)}` : '',
                            },
                            user_day: ods.user_observation.day_of_year,
                            sample_dates: ods.distribution?.sample_dates || []
                        };
                        console.log('📊 ODS comparison loaded:', this.telaComparison);
                    } else {
                        this.telaComparison = null;
                        this.showAlert(
                            data.national_comparison?.message || 'Aucune donnée ODS disponible',
                            'info'
                        );
                    }
                } else {
                    const errorData = await response.json();
                    this.showAlert(errorData.error || 'Aucune donnée de comparaison disponible', 'info');
                }
            } catch (error) {
                console.error('Error loading ODS comparison:', error);
                this.showAlert('Erreur lors de la comparaison avec les données nationales ODS', 'danger');
            }
        },

        openEditObservationModal(obs) {
            console.log('Opening edit modal for observation:', obs);

            // Pre-fill editObservation with current data
            // Extract date part (YYYY-MM-DD) from ISO datetime, using local date to avoid timezone shift
            let obsDate = obs.observation_date || '';
            if (obsDate && obsDate.includes('T')) {
                obsDate = new Date(obsDate).toLocaleDateString('en-CA'); // en-CA gives YYYY-MM-DD
            } else if (obsDate && obsDate.includes(' ')) {
                obsDate = obsDate.split(' ')[0];
            }
            this.editObservation = {
                id: obs.id,
                plant: obs.plant ? obs.plant.id : null,
                phenological_stage: obs.phenological_stage ? obs.phenological_stage.id : null,
                observation_date: obsDate,
                time_of_day: obs.time_of_day || '',
                intensity: obs.intensity || 1,
                notes: obs.notes || '',
                weather_condition: obs.weather_condition || '',
                temperature: obs.temperature,
                humidity: obs.humidity,
                wind_speed: obs.wind_speed,
                confidence_level: obs.confidence_level || 3,
                is_public: obs.is_public !== undefined ? obs.is_public : true
            };

            this.showEditObservationModal = true;
        },

        async updateObservation() {
            if (!this.user.isAuthenticated) {
                this.showAlert('Vous devez être connecté pour modifier une observation', 'warning');
                return;
            }

            try {
                console.log('Sending update with data:', this.editObservation);

                // Prepare update data - map field names to Laravel _id fields
                const updateData = {
                    observation_date: this.editObservation.observation_date,
                    plant_id: this.editObservation.plant,
                    phenological_stage_id: this.editObservation.phenological_stage,
                    intensity: this.editObservation.intensity,
                    temperature: this.editObservation.temperature,
                    weather_condition: this.editObservation.weather_condition,
                    humidity: this.editObservation.humidity,
                    wind_speed: this.editObservation.wind_speed,
                    notes: this.editObservation.notes,
                    confidence_level: this.editObservation.confidence_level || 3,
                    time_of_day: this.editObservation.time_of_day,
                    is_public: this.editObservation.is_public
                };
                // Convert empty strings to null
                for (const [key, value] of Object.entries(updateData)) {
                    if (value === '') updateData[key] = null;
                }

                console.log('Update payload:', updateData);

                const response = await axios.put(
                    `/api/v1/observations/${this.editObservation.id}`,
                    updateData
                );

                if (response.status === 200) {
                    this.showEditObservationModal = false;
                    this.showAlert('Observation modifiée avec succès !', 'success');
                    console.log('Observation updated:', this.editObservation.id);

                    // Update currentObservation if on detail view
                    if (this.currentView === 'observation-detail' && this.currentObservation && this.currentObservation.id === this.editObservation.id) {
                        await this.viewObservationDetail(this.editObservation.id);
                    }

                    // Reload observations list if needed
                    if (this.currentView === 'observations') {
                        await this.loadObservations();
                    }

                    this.resetEditObservationForm();
                }
            } catch (error) {
                console.error('Error updating observation:', error);
                console.error('Error response data:', error.response?.data);
                console.error('Error response status:', error.response?.status);

                // Display detailed validation errors
                let errorMsg = 'Erreur lors de la modification de l\'observation';
                if (error.response && error.response.data) {
                    if (typeof error.response.data === 'object') {
                        // Format validation errors
                        const errors = Object.entries(error.response.data)
                            .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                            .join('\n');
                        errorMsg = errors || errorMsg;
                    } else {
                        errorMsg = error.response.data.error || error.response.data;
                    }
                }
                this.showAlert(errorMsg, 'danger');
            }
        },

        closeEditObservationModal() {
            this.showEditObservationModal = false;
            this.resetEditObservationForm();
        },

        resetEditObservationForm() {
            this.editObservation = {
                id: null,
                plant: null,
                phenological_stage: null,
                observation_date: '',
                time_of_day: '',
                intensity: 1,
                notes: '',
                weather_condition: '',
                temperature: null,
                humidity: null,
                wind_speed: null,
                confidence_level: 3,
                is_public: true
            };
        },

        confirmDeleteObservation(obs) {
            console.log('Confirming delete for observation:', obs);
            this.observationToDelete = obs;
            this.showDeleteObservationModal = true;
        },

        async validateObservation(observationId) {
            if (!this.user.isAuthenticated) return;
            try {
                const response = await axios.post(`/api/v1/observations/${observationId}/validate`);
                this.showAlert('Observation validée avec succès !', 'success');
                // Reload observation detail
                await this.viewObservationDetail(observationId);
            } catch (error) {
                console.error('Error validating observation:', error);
                const msg = error.response?.data?.message || error.response?.data?.detail || 'Erreur lors de la validation';
                this.showAlert(msg, 'danger');
            }
        },

        async deleteObservation() {
            if (!this.user.isAuthenticated || !this.observationToDelete) {
                return;
            }

            try {
                const response = await axios.delete(
                    `/api/v1/observations/${this.observationToDelete.id}`
                );

                if (response.status === 204 || response.status === 200) {
                    this.showDeleteObservationModal = false;
                    this.showAlert('Observation supprimée avec succès !', 'success');

                    // Go back to list if on detail view
                    if (this.currentView === 'observation-detail') {
                        this.currentView = 'observations';
                        this.currentObservation = null;
                    }

                    // Reload observations
                    await this.loadObservations();

                    this.observationToDelete = null;
                }
            } catch (error) {
                console.error('Error deleting observation:', error);
                const errorMsg = error.response && error.response.data && error.response.data.error
                    ? error.response.data.error
                    : 'Erreur lors de la suppression de l\'observation';
                this.showAlert(errorMsg, 'danger');
            }
        },

        closeDeleteObservationModal() {
            this.showDeleteObservationModal = false;
            this.observationToDelete = null;
        },

        // ===== PLANT DELETE METHODS =====

        confirmDeletePlant(plant) {
            console.log('🗑️ Confirming delete for plant:', plant);
            this.plantToDelete = plant;
            this.showDeletePlantModal = true;
        },

        async deletePlant() {
            if (!this.plantToDelete || !this.user.isAuthenticated) {
                return;
            }

            this.deletingPlant = true;

            try {
                const response = await axios.delete(
                    `/api/v1/plants/${this.plantToDelete.id}`
                );

                if (response.status === 204 || response.status === 200) {
                    this.showDeletePlantModal = false;
                    this.showAlert('Plante supprimée avec succès !', 'success');

                    // Reload plants list
                    await this.loadPlants();

                    // If we're in plant detail view, navigate back to plants list
                    if (this.currentView === 'plant-detail') {
                        this.backToPlants();
                    }

                    this.plantToDelete = null;
                }
            } catch (error) {
                console.error('❌ Error deleting plant:', error);
                const errorMsg = error.response?.data?.detail
                    || error.response?.data?.error
                    || error.response?.data?.message
                    || 'Erreur lors de la suppression de la plante';
                this.showAlert(errorMsg, 'danger');
            } finally {
                this.deletingPlant = false;
            }
        },

        closeDeletePlantModal() {
            this.showDeletePlantModal = false;
            this.plantToDelete = null;
        },

        // ===== Photo Management Methods =====

        async loadObservationPhotos(observationId) {
            try {
                const response = await axios.get(
                    `/api/v1/observation-photos/by-observation?observation_id=${observationId}`
                );
                this.observationPhotos = response.data;
                console.log('📸 Photos loaded:', this.observationPhotos.length);
            } catch (error) {
                console.error('Error loading photos:', error);
                this.observationPhotos = [];
            }
        },

        openUploadPhotoModal(observationId) {
            this.newPhoto.observation = observationId;
            this.showUploadPhotoModal = true;
        },

        handlePhotoFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    this.showAlert('Format de fichier non supporté. Utilisez JPG, PNG ou WEBP.', 'warning');
                    return;
                }

                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    this.showAlert('Le fichier est trop volumineux (max 10MB).', 'warning');
                    return;
                }

                this.photoFile = file;

                // Generate preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.newPhoto.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        async uploadObservationPhoto() {
            if (!this.photoFile) {
                this.showAlert('Veuillez sélectionner une photo', 'warning');
                return;
            }

            if (!this.user.isAuthenticated) {
                this.showAlert('Vous devez être connecté pour ajouter une photo', 'warning');
                return;
            }

            this.uploadingPhoto = true;

            try {
                const formData = new FormData();
                formData.append('observation_id', this.newPhoto.observation);
                formData.append('image', this.photoFile);
                if (this.newPhoto.title) formData.append('title', this.newPhoto.title);
                if (this.newPhoto.description) formData.append('description', this.newPhoto.description);
                if (this.newPhoto.photo_type) formData.append('photo_type', this.newPhoto.photo_type);
                formData.append('is_public', this.newPhoto.is_public ? '1' : '0');

                console.log('Uploading photo for observation:', this.newPhoto.observation);

                const response = await axios.post(
                    '/api/v1/observation-photos',
                    formData
                    // Don't set Content-Type header - let browser set multipart boundary
                );

                if (response.status === 201) {
                    this.showAlert('Photo ajoutée avec succès !', 'success');
                    console.log('Photo uploaded:', response.data);

                    this.closeUploadPhotoModal();

                    // Reload photos for this observation
                    await this.loadObservationPhotos(this.newPhoto.observation);

                    // Refresh observation detail if viewing
                    if (this.currentView === 'observation-detail' &&
                        this.currentObservation?.id === this.newPhoto.observation) {
                        await this.viewObservationDetail(this.newPhoto.observation);
                    }
                }
            } catch (error) {
                console.error('Error uploading photo:', error);
                console.error('Error response:', error.response?.data);
                let errorMsg = 'Erreur lors de l\'ajout de la photo';
                if (error.response?.data?.message) {
                    errorMsg = error.response.data.message;
                    if (error.response.data.errors) {
                        const details = Object.values(error.response.data.errors).flat().join(', ');
                        errorMsg += ': ' + details;
                    }
                } else if (error.response?.data?.error) {
                    errorMsg = error.response.data.error;
                }
                this.showAlert(errorMsg, 'danger');
            } finally {
                this.uploadingPhoto = false;
            }
        },

        closeUploadPhotoModal() {
            this.showUploadPhotoModal = false;
            this.resetPhotoForm();
        },

        resetPhotoForm() {
            this.newPhoto = {
                observation: null,
                title: '',
                description: '',
                photo_type: 'phenological_state',
                is_public: true,
                imagePreview: null
            };
            this.photoFile = null;

            // Reset file input
            const fileInput = document.getElementById('photoFileInput');
            if (fileInput) fileInput.value = '';
        },

        openPhotoGallery(index = 0) {
            this.selectedPhotoIndex = index;
            this.showPhotoGalleryModal = true;
        },

        closePhotoGallery() {
            this.showPhotoGalleryModal = false;
            this.selectedPhotoIndex = 0;
        },

        nextPhoto() {
            if (this.selectedPhotoIndex < this.observationPhotos.length - 1) {
                this.selectedPhotoIndex++;
            }
        },

        prevPhoto() {
            if (this.selectedPhotoIndex > 0) {
                this.selectedPhotoIndex--;
            }
        },

        async deleteObservationPhoto(photoId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) {
                return;
            }

            try {
                const response = await axios.delete(
                    `/api/v1/observation-photos/${photoId}`
                );

                if (response.status === 204 || response.status === 200) {
                    this.showAlert('Photo supprimée avec succès', 'success');
                    console.log('Photo deleted:', photoId);

                    // Reload photos
                    if (this.currentObservation) {
                        await this.loadObservationPhotos(this.currentObservation.id);
                    }

                    // Close gallery if no more photos
                    if (this.observationPhotos.length === 0) {
                        this.closePhotoGallery();
                    } else if (this.selectedPhotoIndex >= this.observationPhotos.length) {
                        // Adjust index if we deleted the last photo
                        this.selectedPhotoIndex = this.observationPhotos.length - 1;
                    }
                }
            } catch (error) {
                console.error('Error deleting photo:', error);
                const errorMsg = error.response?.data?.error || 'Erreur lors de la suppression de la photo';
                this.showAlert(errorMsg, 'danger');
            }
        },

        // ===== Analysis Methods =====

        async loadAnalysisData() {
            if (this.currentView !== 'analysis') return;

            this.loading.observations = true;
            try {
                const params = { year: this.analysisYear };
                const response = await axios.get('/api/v1/observations/monthly-counts', { params });
                const data = response.data;

                // Update monthly data
                this.analysisData.monthly = data.monthly;

                // Update stage data
                this.analysisData.byStage = (data.by_stage || []).map(stage => ({
                    name: stage.phenological_stage__stage_description,
                    count: stage.count
                }));

                // Update additional data
                this.analysisData.topPlants = data.top_plants || [];
                this.analysisData.bySite = data.by_site || [];
                this.analysisData.byCategory = data.by_category || [];
                this.analysisData.byIntensity = data.by_intensity || [];
                this.analysisData.byWeather = data.by_weather || [];
                this.analysisData.byMainEvent = data.by_main_event || [];
                this.analysisData.recent = data.recent || [];

                // Update stats
                this.analysisStats = {
                    totalObservations: data.summary.total_observations,
                    uniquePlants: data.summary.unique_plants,
                    uniqueSites: data.summary.unique_sites,
                    validatedCount: data.summary.validated_count || 0,
                    withPhotosCount: data.summary.with_photos_count || 0
                };

                console.log('📊 Analysis data loaded:', data);

                // Wait a bit for DOM to render canvases
                this.$nextTick(() => {
                    this.initializeCharts();
                });

            } catch (error) {
                console.error('Error loading analysis data:', error);
                this.showAlert('Erreur lors du chargement des analyses', 'danger');
                // Set default values on error
                this.analysisData.monthly = { labels: [], data: [] };
                this.analysisData.byStage = [];
                this.analysisData.topPlants = [];
                this.analysisData.bySite = [];
                this.analysisData.byCategory = [];
                this.analysisData.byIntensity = [];
                this.analysisData.byWeather = [];
                this.analysisData.byMainEvent = [];
                this.analysisData.recent = [];
                this.analysisStats = {
                    totalObservations: 0,
                    uniquePlants: 0,
                    uniqueSites: 0,
                    validatedCount: 0,
                    withPhotosCount: 0
                };
            } finally {
                this.loading.observations = false;
            }
        },

        async loadAvailableYears() {
            try {
                const response = await axios.get('/api/v1/observations/years-available');
                this.availableYears = response.data.years;
                console.log('📅 Available years loaded:', this.availableYears);

                // Set analysisYear to most recent year if available
                if (this.availableYears.length > 0 && !this.availableYears.includes(this.analysisYear)) {
                    this.analysisYear = this.availableYears[0];
                }
            } catch (error) {
                console.error('Error loading available years:', error);
                this.availableYears = [];
            }
        },

        initializeCharts() {
            this.createMonthlyChart();
            this.createStageChart();
            this.createCategoryChart();
            this.createSiteChart();
            this.createMainEventChart();
        },

        createMonthlyChart() {
            // Destroy existing chart
            if (this.monthlyChart) {
                this.monthlyChart.destroy();
            }

            const ctx = document.getElementById('monthlyChart');
            if (!ctx || !this.analysisData.monthly) return;

            this.monthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.analysisData.monthly.labels,
                    datasets: [{
                        label: `Observations en ${this.analysisYear}`,
                        data: this.analysisData.monthly.data,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            console.log('Monthly chart created');
        },

        createStageChart() {
            // Destroy existing chart
            if (this.stageChart) {
                this.stageChart.destroy();
            }

            const ctx = document.getElementById('stageChart');
            if (!ctx || this.analysisData.byStage.length === 0) return;

            const labels = this.analysisData.byStage.map(s => s.name);
            const data = this.analysisData.byStage.map(s => s.count);

            // Generate colors
            const colors = [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)',
                'rgba(255, 159, 64, 0.6)',
                'rgba(199, 199, 199, 0.6)',
                'rgba(83, 102, 255, 0.6)'
            ];

            this.stageChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, data.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            console.log('Stage chart created');
        },

        createCategoryChart() {
            if (this.categoryChart) this.categoryChart.destroy();
            const ctx = document.getElementById('categoryChart');
            if (!ctx || this.analysisData.byCategory.length === 0) return;

            const colors = [
                'rgba(40, 167, 69, 0.7)',
                'rgba(0, 123, 255, 0.7)',
                'rgba(255, 193, 7, 0.7)',
                'rgba(220, 53, 69, 0.7)',
                'rgba(23, 162, 184, 0.7)',
                'rgba(108, 117, 125, 0.7)'
            ];

            this.categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: this.analysisData.byCategory.map(c => c.name),
                    datasets: [{
                        data: this.analysisData.byCategory.map(c => c.count),
                        backgroundColor: colors.slice(0, this.analysisData.byCategory.length),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                    }
                }
            });
        },

        createSiteChart() {
            if (this.siteChart) this.siteChart.destroy();
            const ctx = document.getElementById('siteChart');
            if (!ctx || this.analysisData.bySite.length === 0) return;

            this.siteChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.analysisData.bySite.map(s => s.name),
                    datasets: [{
                        label: 'Observations',
                        data: this.analysisData.bySite.map(s => s.count),
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } },
                    plugins: { legend: { display: false } }
                }
            });
        },

        createMainEventChart() {
            if (this.mainEventChart) this.mainEventChart.destroy();
            const ctx = document.getElementById('mainEventChart');
            if (!ctx || this.analysisData.byMainEvent.length === 0) return;

            const eventColors = {
                1: 'rgba(76, 175, 80, 0.7)',   // Feuilles - vert
                2: 'rgba(139, 195, 74, 0.7)',   // Pousses - vert clair
                3: 'rgba(156, 204, 101, 0.7)',  // Tige - vert lime
                4: 'rgba(255, 183, 77, 0.7)',   // Organes repro - orange
                5: 'rgba(255, 138, 101, 0.7)',  // Inflorescence - orange clair
                6: 'rgba(240, 98, 146, 0.7)',   // Floraison - rose
                7: 'rgba(186, 104, 200, 0.7)',  // Fructification - violet
                8: 'rgba(149, 117, 205, 0.7)',  // Maturation - violet foncé
                9: 'rgba(158, 158, 158, 0.7)',  // Sénescence - gris
            };

            this.mainEventChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.analysisData.byMainEvent.map(e => e.main_event_description || `Stade ${e.main_event_code}`),
                    datasets: [{
                        label: 'Observations',
                        data: this.analysisData.byMainEvent.map(e => e.count),
                        backgroundColor: this.analysisData.byMainEvent.map(e => eventColors[e.main_event_code] || 'rgba(158,158,158,0.7)'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                    plugins: { legend: { display: false } }
                }
            });
        },

        destroyCharts() {
            if (this.monthlyChart) {
                this.monthlyChart.destroy();
                this.monthlyChart = null;
            }
            if (this.stageChart) {
                this.stageChart.destroy();
                this.stageChart = null;
            }
            if (this.categoryChart) {
                this.categoryChart.destroy();
                this.categoryChart = null;
            }
            if (this.siteChart) {
                this.siteChart.destroy();
                this.siteChart = null;
            }
            if (this.mainEventChart) {
                this.mainEventChart.destroy();
                this.mainEventChart = null;
            }
        },

        getIntensityClass(intensity) {
            if (intensity <= 1) return 'bg-danger';
            if (intensity <= 2) return 'bg-warning';
            if (intensity <= 3) return 'bg-info';
            if (intensity <= 4) return 'bg-primary';
            return 'bg-success';
        },

        getWeatherIcon(weather) {
            const icons = {
                'ensoleillé': 'fas fa-sun text-warning',
                'nuageux': 'fas fa-cloud text-secondary',
                'pluvieux': 'fas fa-cloud-rain text-primary',
                'venteux': 'fas fa-wind text-info',
                'orageux': 'fas fa-bolt text-danger',
                // Legacy English keys
                'sunny': 'fas fa-sun text-warning',
                'partly_cloudy': 'fas fa-cloud-sun text-info',
                'cloudy': 'fas fa-cloud text-secondary',
                'overcast': 'fas fa-cloud text-secondary',
                'rainy': 'fas fa-cloud-rain text-primary',
                'stormy': 'fas fa-bolt text-danger',
                'snowy': 'fas fa-snowflake text-info',
                'foggy': 'fas fa-smog text-muted'
            };
            return icons[weather] || 'fas fa-cloud text-muted';
        },

        // ===== PHOTO FORM METHODS =====
        async addPhoto() {
            if (!this.user.isAuthenticated) {
                this.showAlert('Fonctionnalité de démonstration - Connectez-vous avec admin/admin123 pour enregistrer réellement', 'info');
                this.closeModal();
                this.resetNewPhotoForm();
                return;
            }

            const fileInput = document.getElementById('photo-file');
            if (!fileInput.files.length) {
                this.showAlert('Veuillez sélectionner une photo', 'warning');
                return;
            }

            this.photoOperationLoading = true;
            try {
                const formData = new FormData();
                formData.append('image', fileInput.files[0]);
                formData.append('plant_id', this.newPhoto.plant);
                if (this.newPhoto.title) formData.append('title', this.newPhoto.title);
                if (this.newPhoto.description) formData.append('description', this.newPhoto.description);
                if (this.newPhoto.photo_type) formData.append('photo_type', this.newPhoto.photo_type);
                formData.append('is_public', this.newPhoto.is_public ? '1' : '0');

                const response = await axios.post('/api/v1/plant-photos', formData
                    // Don't set Content-Type header - let browser set multipart boundary
                );

                // Close modal using Bootstrap API
                this.closeModal();
                this.resetNewPhotoForm();
                this.showAlert('Photo ajoutée avec succès !', 'success');

                // Reload plant detail if we're viewing a plant
                if (this.currentView === 'plant-detail' && this.currentPlant) {
                    await this.viewPlantDetail(this.currentPlant);
                }
            } catch (error) {
                console.error('Error adding photo:', error);
                const status = error.response?.status || 'unknown';
                const msg = error.response?.data?.message || error.response?.data?.detail || JSON.stringify(error.response?.data?.errors || error.message);
                this.showAlert(`Erreur ${status}: ${msg}`, 'danger');
            } finally {
                this.photoOperationLoading = false;
            }
        },

        async setAsMainPhoto(photoId) {
            if (!this.user.isAuthenticated) {
                return;
            }

            try {
                const response = await axios.post(`/api/v1/plant-photos/${photoId}/set-as-main`);
                this.showAlert('Photo principale définie !', 'success');

                // Reload plant detail
                if (this.currentView === 'plant-detail' && this.currentPlant) {
                    await this.viewPlantDetail(this.currentPlant);
                }
            } catch (error) {
                console.error('Error setting main photo:', error);
                this.showAlert('Erreur lors de la définition de la photo principale', 'danger');
            }
        },

        async deletePlantPhoto(photoId) {
            if (!this.user.isAuthenticated) {
                return;
            }

            if (!confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) {
                return;
            }

            try {
                await axios.delete(`/api/v1/plant-photos/${photoId}`);
                this.showAlert('Photo supprimée avec succès !', 'success');

                // Reload plant detail
                if (this.currentView === 'plant-detail' && this.currentPlant) {
                    await this.viewPlantDetail(this.currentPlant);
                }
            } catch (error) {
                console.error('Error deleting photo:', error);
                this.showAlert('Erreur lors de la suppression de la photo', 'danger');
            }
        },

        openPhotoGallery(index) {
            this.selectedPhotoIndex = index;
            this.showPhotoGalleryModal = true;
        },

        closePhotoGallery() {
            this.showPhotoGalleryModal = false;
            this.selectedPhotoIndex = 0;
        },

        nextPhoto() {
            const photos = this.currentView === 'plant-detail' ? this.plantDetail.photos : this.observationPhotos;
            if (this.selectedPhotoIndex < photos.length - 1) {
                this.selectedPhotoIndex++;
            }
        },

        previousPhoto() {
            if (this.selectedPhotoIndex > 0) {
                this.selectedPhotoIndex--;
            }
        },
        
        resetNewPhotoForm() {
            this.newPhoto = {
                plant: null,
                title: '',
                description: '',
                photo_type: 'general',
                is_public: true
            };
            // Reset file input
            const fileInput = document.getElementById('photo-file');
            if (fileInput) fileInput.value = '';
        },

        openEditPhotoModal(photo) {
            if (!this.user.isAuthenticated) {
                return;
            }
            this.editPhoto = {
                id: photo.id,
                title: photo.title || '',
                description: photo.description || '',
                photo_type: photo.photo_type,
                is_public: photo.is_public !== undefined ? photo.is_public : true
            };
            this.showEditPhotoModal = true;
        },

        async updatePhoto() {
            if (!this.user.isAuthenticated) {
                this.showEditPhotoModal = false;
                return;
            }

            this.photoOperationLoading = true;
            try {
                const response = await axios.patch(
                    `/api/v1/plant-photos/${this.editPhoto.id}`,
                    {
                        title: this.editPhoto.title,
                        description: this.editPhoto.description,
                        photo_type: this.editPhoto.photo_type,
                        is_public: this.editPhoto.is_public
                    }
                );

                this.showEditPhotoModal = false;
                this.showAlert('Photo mise à jour avec succès !', 'success');

                // Reload plant detail
                if (this.currentView === 'plant-detail' && this.currentPlant) {
                    await this.viewPlantDetail(this.currentPlant);
                }
            } catch (error) {
                console.error('Error updating photo:', error);
                const errorMsg = error.response?.data?.detail || error.response?.data?.error || 'Erreur lors de la mise à jour de la photo';
                this.showAlert(errorMsg, 'danger');
            } finally {
                this.photoOperationLoading = false;
            }
        },
        
        // Help alert method
        showHelpAlert() {
            this.showAlert('💡 Cliquez sur "Ajouter" pour créer du contenu. Connectez-vous avec admin/admin123 pour enregistrer réellement.', 'info');
        },
        
        // Test site form methods
        testSiteSubmit() {
            if (!this.testSiteForm.name || !this.testSiteForm.latitude || !this.testSiteForm.longitude) {
                this.showAlert('❌ Veuillez remplir tous les champs obligatoires', 'warning');
                return;
            }
            
            // Simulation d'envoi réussie
            const message = `✅ Test réussi!<br><br><strong>Site:</strong> ${this.testSiteForm.name}<br><strong>Coordonnées:</strong> ${this.testSiteForm.latitude}, ${this.testSiteForm.longitude}<br><br>💡 Pour un vrai enregistrement, connectez-vous avec admin/admin123`;
            
            this.showAlert(message, 'success');
            
            // Fermer la modal et reset le formulaire
            this.showTestSiteModal = false;
            this.resetTestSiteForm();
        },
        
        resetTestSiteForm() {
            this.testSiteForm = {
                name: '',
                latitude: null,
                longitude: null
            };
        },
        
        // Utility methods
        showAlert(message, type = 'info') {
            // Create and show Bootstrap alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert at top of main content
            const mainContent = document.querySelector('#app main') || document.querySelector('main');
            if (mainContent) {
                mainContent.insertBefore(alertDiv, mainContent.firstChild);
            } else {
                document.body.prepend(alertDiv);
            }
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        },

        // Format numbers with commas
        formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(num);
        },
        
        // Get environment label in French
        getEnvironmentLabel(environment) {
            const labels = {
                'urban': 'Urbain',
                'suburban': 'Périurbain', 
                'rural': 'Rural',
                'forest': 'Forêt',
                'garden': 'Jardin/Parc',
                'natural': 'Naturel',
                'agricultural': 'Agricole'
            };
            return labels[environment] || environment;
        },
        
        // Handle URL hash changes for navigation
        handleHashChange() {
            const hash = window.location.hash.replace('#', '');
            
            // Check for site detail view (format: #site/123)
            if (hash.startsWith('site/')) {
                const siteId = hash.split('/')[1];
                if (siteId && !isNaN(siteId)) {
                    console.log('📍 Navigating to site detail:', siteId);
                    this.viewSiteDetail(parseInt(siteId));
                    return;
                }
            }
            
            // Check for plant detail view (format: #plant/123)
            if (hash.startsWith('plant/')) {
                const plantId = hash.split('/')[1];
                if (plantId && !isNaN(plantId)) {
                    console.log('🌱 Navigating to plant detail:', plantId);
                    this.viewPlantDetail(parseInt(plantId));
                    return;
                }
            }
            
            // Standard views
            if (hash && ['dashboard', 'sites', 'plants', 'observations', 'analysis', 'search', 'map', 'admin'].includes(hash)) {
                console.log('📍 Navigating to:', hash);
                this.currentView = hash;
                this.currentSite = null;
                this.currentPlant = null;
            }
        },
        
        // Get health status label
        getStatusLabel(status) {
            const labels = {
                'alive': 'Vivant',
                'dead': 'Mort',
                'replaced': 'Remplacé',
                'removed': 'Retiré'
            };
            return labels[status] || status || '-';
        },

        computePlantAge(plant) {
            if (!plant) return '-';
            const ageAtPlanting = plant.age_years || 0;
            const plantingDate = plant.planting_date;

            if (!plantingDate && !ageAtPlanting) return '-';
            if (!plantingDate && ageAtPlanting) return '~' + ageAtPlanting + ' ans';

            const planted = new Date(plantingDate);
            const now = new Date();
            const diffMs = now - planted;
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            const sincePlantingMonths = Math.floor(diffDays / 30.44);
            const sincePlantingYears = Math.floor(sincePlantingMonths / 12);

            const totalYears = ageAtPlanting + sincePlantingYears;
            const remainingMonths = sincePlantingMonths % 12;

            if (ageAtPlanting > 0) {
                // Âge déclaré + temps écoulé
                return '~' + totalYears + ' ans';
            }
            // Pas d'âge déclaré, seulement temps depuis plantation
            if (diffDays < 30) return diffDays + ' j';
            if (sincePlantingMonths < 12) return sincePlantingMonths + ' mois';
            return remainingMonths > 0 ? totalYears + ' ans ' + remainingMonths + ' mois' : totalYears + ' ans';
        },

        getHealthLabel(status) {
            const labels = {
                'excellent': 'Excellent',
                'good': 'Bon',
                'fair': 'Correct',
                'poor': 'Mauvais',
                'dead': 'Mort'
            };
            return labels[status] || status;
        },
        
        // Get health status badge CSS class
        getHealthBadgeClass(status) {
            const classes = {
                'excellent': 'bg-success',
                'good': 'bg-primary',
                'fair': 'bg-warning',
                'poor': 'bg-danger', 
                'dead': 'bg-dark'
            };
            return classes[status] || 'bg-secondary';
        },

        // Get height category label
        getHeightCategoryLabel(category) {
            const labels = {
                'seedling': 'Plantule (<30cm)',
                'young': 'Jeune (30cm-1m)',
                'medium': 'Moyen (1-3m)',
                'mature': 'Mature (3-10m)',
                'large': 'Grand (>10m)'
            };
            return labels[category] || category || '-';
        },

        // Get death cause label
        getDeathCauseLabel(cause) {
            const labels = {
                'disease': 'Maladie',
                'pests': 'Ravageurs',
                'frost': 'Gel',
                'drought': 'Sécheresse',
                'flooding': 'Inondation',
                'wind': 'Vent/Tempête',
                'age': 'Vieillesse',
                'accident': 'Accident',
                'human': 'Intervention humaine',
                'unknown': 'Cause inconnue',
                'other': 'Autre'
            };
            return labels[cause] || cause || '-';
        },

        // Plant management methods
        editPlant(plant) {
            console.log('✏️ Editing plant:', plant);

            // Pre-fill the edit form with plant data
            this.editPlantData = {
                id: plant.id,
                name: plant.name || '',
                description: plant.description || '',
                taxon: plant.taxon?.id || null,
                category: plant.category?.id || null,
                site: plant.site?.id || plant.site_id || null,
                planting_date: plant.planting_date ? new Date(plant.planting_date).toLocaleDateString('en-CA') : null,
                age_years: plant.age_years || null,
                height_category: plant.height_category || '',
                exact_height: plant.exact_height || null,
                health_status: plant.health_status || 'good',
                clone_or_accession: plant.clone_or_accession || '',
                is_private: plant.is_private || false,
                notes: plant.notes || '',
                anecdotes: plant.anecdotes || '',
                cultural_significance: plant.cultural_significance || '',
                ecological_notes: plant.ecological_notes || '',
                care_notes: plant.care_notes || '',
                latitude: plant.latitude || null,
                longitude: plant.longitude || null,
                gps_accuracy: plant.gps_accuracy || null
            };

            // Pre-fill the taxon autocomplete for editing
            if (plant.taxon) {
                this.taxonAutocompleteEdit.selectedTaxon = plant.taxon;
                this.taxonAutocompleteEdit.query = plant.taxon.display_name || plant.taxon.binomial_name || '';
            } else {
                this.taxonAutocompleteEdit.selectedTaxon = null;
                this.taxonAutocompleteEdit.query = '';
            }
            this.taxonAutocompleteEdit.results = [];
            this.taxonAutocompleteEdit.showDropdown = false;

            // Open the edit modal
            this.showEditPlantModal = true;
        },

        async updatePlant() {
            if (!this.user.isAuthenticated) {
                this.showAlert('Vous devez être connecté pour modifier une plante', 'warning');
                return;
            }

            try {
                const payload = {
                    name: this.editPlantData.name,
                    description: this.editPlantData.description || null,
                    planting_date: this.editPlantData.planting_date || null,
                    age_years: this.editPlantData.age_years ? parseInt(this.editPlantData.age_years) : null,
                    height_category: this.editPlantData.height_category || null,
                    exact_height: this.editPlantData.exact_height ? parseFloat(this.editPlantData.exact_height) : null,
                    health_status: this.editPlantData.health_status,
                    clone_or_accession: this.editPlantData.clone_or_accession || null,
                    is_private: this.editPlantData.is_private,
                    notes: this.editPlantData.notes || null,
                    anecdotes: this.editPlantData.anecdotes || null,
                    cultural_significance: this.editPlantData.cultural_significance || null,
                    ecological_notes: this.editPlantData.ecological_notes || null,
                    care_notes: this.editPlantData.care_notes || null,
                    latitude: this.editPlantData.latitude ? parseFloat(this.editPlantData.latitude) : null,
                    longitude: this.editPlantData.longitude ? parseFloat(this.editPlantData.longitude) : null,
                    gps_accuracy: this.editPlantData.gps_accuracy ? parseFloat(this.editPlantData.gps_accuracy) : null
                };
                // Only include required fields if they have a value (avoids 'sometimes|required' validation failure)
                if (this.editPlantData.taxon) payload.taxon_id = this.editPlantData.taxon;
                if (this.editPlantData.category) payload.category_id = this.editPlantData.category;
                if (this.editPlantData.site) payload.site_id = this.editPlantData.site;

                const response = await axios.patch(
                    `/api/v1/plants/${this.editPlantData.id}`,
                    payload
                );

                this.closeModal();
                this.showAlert('Plante modifiée avec succès !', 'success');

                // Refresh the appropriate view
                if (this.currentView === 'site-detail' && this.siteDetail.site) {
                    await this.loadSitePlants(this.siteDetail.site.id);
                } else if (this.currentView === 'plant-detail' && this.currentPlant === this.editPlantData.id) {
                    await this.viewPlantDetail(this.editPlantData.id);
                } else {
                    await this.loadPlants();
                }
            } catch (error) {
                console.error('Error updating plant:', error);
                console.error('Error response data:', error.response?.data);
                let errorMsg = 'Erreur lors de la modification de la plante';
                if (error.response?.data?.message) {
                    errorMsg = error.response.data.message;
                    if (error.response.data.errors) {
                        const details = Object.values(error.response.data.errors).flat().join(', ');
                        errorMsg += ': ' + details;
                    }
                } else if (error.response?.data?.detail) {
                    errorMsg = error.response.data.detail;
                }
                this.showAlert(errorMsg, 'danger');
            }
        },
        
        // GPS Validation Methods
        isValidLatitude(lat) {
            const num = parseFloat(lat);
            return !isNaN(num) && num >= -90 && num <= 90;
        },
        
        isValidLongitude(lng) {
            const num = parseFloat(lng);
            return !isNaN(num) && num >= -180 && num <= 180;
        },
        
        getGpsValidationClass(type, value) {
            if (!value) return '';
            
            if (type === 'latitude') {
                return this.isValidLatitude(value) ? 'gps-valid' : 'gps-invalid';
            } else if (type === 'longitude') {
                return this.isValidLongitude(value) ? 'gps-valid' : 'gps-invalid';
            }
            return '';
        },
        
        validateGpsCoordinates() {
            this.gpsValidation.latitude = null;
            this.gpsValidation.longitude = null;
            
            if (this.newPlant.latitude && !this.isValidLatitude(this.newPlant.latitude)) {
                this.gpsValidation.latitude = 'Latitude invalide (-90 à 90)';
            }
            
            if (this.newPlant.longitude && !this.isValidLongitude(this.newPlant.longitude)) {
                this.gpsValidation.longitude = 'Longitude invalide (-180 à 180)';
            }
            
            // Update GPS preview if coordinates are valid
            if (this.hasValidGpsCoordinates && this.showGpsPreview) {
                this.updateGpsMap();
            }
        },
        
        getGpsPrecisionClass(accuracy) {
            if (!accuracy) return 'medium';
            const acc = parseFloat(accuracy);
            if (acc < 1) return 'ultra-high';
            if (acc < 5) return 'high';
            return 'medium';
        },
        
        getGpsPrecisionLabel(accuracy) {
            if (!accuracy) return 'Non définie';
            const acc = parseFloat(accuracy);
            if (acc < 1) return `Ultra-précis (±${acc}m)`;
            if (acc < 5) return `Très précis (±${acc}m)`;
            return `Précis (±${acc}m)`;
        },
        
        // GPS Control Methods
        async getCurrentLocation() {
            if (!navigator.geolocation) {
                this.showAlert('Géolocalisation non supportée', 'error');
                return;
            }
            
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        resolve,
                        reject,
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 60000
                        }
                    );
                });
                
                this.newPlant.latitude = position.coords.latitude.toFixed(6);
                this.newPlant.longitude = position.coords.longitude.toFixed(6);
                this.newPlant.gps_accuracy = position.coords.accuracy ? position.coords.accuracy.toFixed(1) : null;
                
                this.validateGpsCoordinates();
                this.showAlert(
                    `Position obtenue: précision ±${position.coords.accuracy.toFixed(1)}m`,
                    'success'
                );
                
            } catch (error) {
                console.error('Erreur géolocalisation:', error);
                this.showAlert('Impossible d\'obtenir la position', 'error');
            }
        },
        
        showGpsMap() {
            if (!this.hasValidGpsCoordinates) return;
            
            this.showGpsPreview = true;
            this.$nextTick(() => {
                this.initGpsMap();
            });
        },
        
        initGpsMap() {
            if (this.gpsMap) {
                this.gpsMap.remove();
            }
            
            const lat = parseFloat(this.newPlant.latitude);
            const lng = parseFloat(this.newPlant.longitude);
            
            this.gpsMap = L.map('plantGpsMap').setView([lat, lng], 18);
            
            // High-resolution satellite tiles
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 22,
                attribution: '© Esri'
            }).addTo(this.gpsMap);
            
            // Plant marker
            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'plant-marker health-excellent',
                    html: '<i class="fas fa-leaf plant-icon" style="color: white;"></i>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                })
            }).addTo(this.gpsMap);
            
            // Accuracy circle if available
            if (this.newPlant.gps_accuracy) {
                L.circle([lat, lng], {
                    radius: parseFloat(this.newPlant.gps_accuracy),
                    color: '#28a745',
                    fillColor: '#28a745',
                    fillOpacity: 0.1,
                    weight: 2
                }).addTo(this.gpsMap);
            }
            
            marker.bindPopup(`
                <strong>Position de la plante</strong><br>
                Lat: ${lat.toFixed(6)}<br>
                Lng: ${lng.toFixed(6)}<br>
                ${this.newPlant.gps_accuracy ? `Précision: ±${this.newPlant.gps_accuracy}m` : ''}
            `);
        },
        
        updateGpsMap() {
            if (this.gpsMap && this.hasValidGpsCoordinates) {
                this.initGpsMap();
            }
        },
        
        clearGpsCoordinates() {
            this.newPlant.latitude = null;
            this.newPlant.longitude = null;
            this.newPlant.gps_accuracy = null;
            this.gpsValidation.latitude = null;
            this.gpsValidation.longitude = null;
            this.showGpsPreview = false;
            
            if (this.gpsMap) {
                this.gpsMap.remove();
                this.gpsMap = null;
            }
        },
        
        // General Map Methods
        async initGeneralMap() {
            this.loading.map = true;

            try {
                // Load map data
                await this.loadMapData();

                // Hide loading spinner to show map container
                this.loading.map = false;

                // Wait for Vue to render the #generalMap element
                await this.$nextTick();

                // Initialize map if not already done
                if (!this.generalMap) {
                    this.generalMap = L.map('generalMap').setView([43.7102, 7.2620], 10);

                    // Add tile layer
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        maxZoom: 22,
                        attribution: '© Esri | PhenoLab'
                    }).addTo(this.generalMap);

                    // Initialize layer groups
                    this.mapLayers.sites = L.layerGroup().addTo(this.generalMap);
                    this.mapLayers.plants = L.layerGroup().addTo(this.generalMap);

                    // Add zoom event listener for dynamic clustering
                    this.generalMap.on('zoomend', () => {
                        this.updateMapLayersWithClustering();
                    });

                    // Add move event listener for updating visible area
                    this.generalMap.on('moveend', () => {
                        this.updateZoomPrecisionIndicator();
                    });
                }

                this.updateMapLayers();
                this.centerMapOnData();

            } catch (error) {
                console.error('Error initializing general map:', error);
                this.showAlert('Erreur lors du chargement de la carte', 'error');
                this.loading.map = false;
            }
        },
        
        async loadMapData() {
            try {
                const [sitesResponse, plantsResponse] = await Promise.all([
                    axios.get('/api/v1/sites'),
                    axios.get('/api/v1/plants')
                ]);

                // Extract arrays from paginated responses
                const sitesData = this.extractCollection(sitesResponse.data);
                const plantsData = this.extractCollection(plantsResponse.data);

                // Update statistics
                const sitesWithGps = sitesData.filter(site => site.latitude && site.longitude);
                const plantsWithGps = plantsData.filter(plant => plant.latitude && plant.longitude);
                
                this.mapStats.sites = sitesWithGps.length;
                this.mapStats.plants = plantsWithGps.length;
                
                // Calculate average precision
                const accuracies = plantsWithGps
                    .filter(plant => plant.gps_accuracy)
                    .map(plant => parseFloat(plant.gps_accuracy));
                
                this.mapStats.precision = accuracies.length > 0 
                    ? (accuracies.reduce((a, b) => a + b, 0) / accuracies.length).toFixed(1)
                    : 0;
                
            } catch (error) {
                console.error('Error loading map data:', error);
                throw error;
            }
        },
        
        updateMapLayers() {
            // Use the enhanced clustering version for better precision
            this.updateMapLayersWithClustering();
        },
        
        centerMapOnData() {
            if (!this.generalMap) return;
            
            const bounds = [];
            
            // Collect all coordinates
            if (this.mapViewMode === 'sites' || this.mapViewMode === 'both') {
                this.sites.forEach(site => {
                    if (site.latitude && site.longitude) {
                        bounds.push([site.latitude, site.longitude]);
                    }
                });
            }
            
            if (this.mapViewMode === 'plants' || this.mapViewMode === 'both') {
                this.plants.forEach(plant => {
                    if (plant.latitude && plant.longitude) {
                        bounds.push([plant.latitude, plant.longitude]);
                    }
                });
            }
            
            if (bounds.length > 0) {
                this.generalMap.fitBounds(bounds, { padding: [20, 20] });
            } else {
                // Default to Nice, France if no data
                this.generalMap.setView([43.7102, 7.2620], 10);
            }
        },
        
        refreshMapData() {
            this.selectedMapItem = null;
            this.initGeneralMap();
        },
        
        toggleMapFullscreen() {
            const mapContainer = document.getElementById('generalMap');
            if (mapContainer) {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else {
                    mapContainer.requestFullscreen();
                }
            }
        },
        
        exportMapData() {
            const mapData = {
                sites: this.sites.filter(site => site.latitude && site.longitude),
                plants: this.plants.filter(plant => plant.latitude && plant.longitude),
                exportDate: new Date().toISOString(),
                statistics: this.mapStats
            };
            
            const blob = new Blob([JSON.stringify(mapData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `phenolab-map-data-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);
            
            this.showAlert('Données cartographiques exportées', 'success');
        },
        
        showMapLegend() {
            this.showAlert(`
                <strong>Légende de la carte:</strong><br>
                <i class="fas fa-map-marker-alt text-primary"></i> Sites géographiques<br>
                <i class="fas fa-leaf text-success"></i> Plantes avec GPS<br>
                <br>
                <strong>Couleurs état de santé:</strong><br>
                <span class="badge bg-success">Vert</span> Excellent<br>
                <span class="badge bg-primary">Bleu</span> Bon<br>
                <span class="badge bg-warning">Jaune</span> Correct<br>
                <span class="badge bg-danger">Rouge</span> Mauvais<br>
                <span class="badge bg-dark">Gris</span> Mort
            `, 'info');
        },
        
        // Ultra-Precise Mapping Methods (10cm precision)
        calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371000; // Earth radius in meters
            const dLat = this.toRadians(lat2 - lat1);
            const dLng = this.toRadians(lng2 - lng1);
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                     Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c; // Distance in meters
        },
        
        toRadians(degrees) {
            return degrees * (Math.PI / 180);
        },
        
        // Cluster plants that are very close together (within 10cm - 5m)
        clusterNearbyPlants(plants, maxDistance = 5) {
            const clusters = [];
            const processed = new Set();
            
            plants.forEach((plant, index) => {
                if (processed.has(index)) return;
                
                const cluster = {
                    center: {
                        lat: plant.latitude,
                        lng: plant.longitude
                    },
                    plants: [plant],
                    precision: plant.gps_accuracy ? parseFloat(plant.gps_accuracy) : null,
                    isUltraPrecise: plant.gps_accuracy && parseFloat(plant.gps_accuracy) < 1
                };
                
                // Find nearby plants
                plants.forEach((otherPlant, otherIndex) => {
                    if (otherIndex === index || processed.has(otherIndex)) return;
                    
                    const distance = this.calculateDistance(
                        plant.latitude, plant.longitude,
                        otherPlant.latitude, otherPlant.longitude
                    );
                    
                    if (distance <= maxDistance) {
                        cluster.plants.push(otherPlant);
                        processed.add(otherIndex);
                        
                        // Update cluster center (weighted average)
                        const totalPlants = cluster.plants.length;
                        cluster.center.lat = cluster.plants.reduce((sum, p) => sum + p.latitude, 0) / totalPlants;
                        cluster.center.lng = cluster.plants.reduce((sum, p) => sum + p.longitude, 0) / totalPlants;
                        
                        // Update precision (take the best precision available)
                        if (otherPlant.gps_accuracy) {
                            const otherAccuracy = parseFloat(otherPlant.gps_accuracy);
                            if (!cluster.precision || otherAccuracy < cluster.precision) {
                                cluster.precision = otherAccuracy;
                            }
                            if (otherAccuracy < 1) {
                                cluster.isUltraPrecise = true;
                            }
                        }
                    }
                });
                
                processed.add(index);
                clusters.push(cluster);
            });
            
            return clusters;
        },
        
        // Enhanced map layer update with clustering
        updateMapLayersWithClustering() {
            if (!this.generalMap) return;
            
            // Clear existing layers
            this.mapLayers.sites.clearLayers();
            this.mapLayers.plants.clearLayers();
            
            let visibleCount = 0;
            
            // Add sites (unchanged)
            if (this.mapViewMode === 'sites' || this.mapViewMode === 'both') {
                this.sites.forEach(site => {
                    if (site.latitude && site.longitude) {
                        const marker = L.marker([site.latitude, site.longitude], {
                            icon: L.divIcon({
                                className: 'site-center-marker',
                                html: '<div class="site-center-icon"><i class="fas fa-map-marker-alt" style="color: white;"></i></div>',
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            })
                        });
                        
                        marker.on('click', () => {
                            this.selectedMapItem = {
                                ...site,
                                type: 'site',
                                coordinates: [site.latitude, site.longitude]
                            };
                        });
                        
                        marker.bindPopup(`
                            <strong>${site.name}</strong><br>
                            ${this.getEnvironmentLabel(site.environment)}<br>
                            ${site.description || 'Pas de description'}<br>
                            <small>Cliquez pour plus de détails</small>
                        `);
                        
                        this.mapLayers.sites.addLayer(marker);
                        visibleCount++;
                    }
                });
            }
            
            // Add plants with ultra-precise clustering
            if (this.mapViewMode === 'plants' || this.mapViewMode === 'both') {
                const plantsWithGps = this.plants.filter(plant => plant.latitude && plant.longitude);
                const currentZoom = this.generalMap.getZoom();
                
                // Determine clustering distance based on zoom level
                let clusterDistance;
                if (currentZoom >= 20) {
                    clusterDistance = 0.5; // Ultra-precise: 50cm
                } else if (currentZoom >= 18) {
                    clusterDistance = 2; // Very precise: 2m
                } else if (currentZoom >= 16) {
                    clusterDistance = 10; // Precise: 10m
                } else {
                    clusterDistance = 50; // Standard: 50m
                }
                
                const clusters = this.clusterNearbyPlants(plantsWithGps, clusterDistance);
                
                clusters.forEach(cluster => {
                    if (cluster.plants.length === 1) {
                        // Single plant
                        const plant = cluster.plants[0];
                        const marker = L.marker([plant.latitude, plant.longitude], {
                            icon: L.divIcon({
                                className: `plant-marker health-${plant.health_status} ${cluster.isUltraPrecise ? 'ultra-precise-marker' : ''}`,
                                html: '<i class="fas fa-leaf plant-icon" style="color: white;"></i>',
                                iconSize: cluster.isUltraPrecise ? [28, 28] : [25, 25],
                                iconAnchor: cluster.isUltraPrecise ? [14, 14] : [12, 12]
                            })
                        });
                        
                        marker.on('click', () => {
                            this.selectedMapItem = {
                                ...plant,
                                type: 'plant',
                                coordinates: [plant.latitude, plant.longitude]
                            };
                        });
                        
                        marker.bindPopup(`
                            <strong>${plant.name}</strong><br>
                            <em>${plant.taxon?.binomial_name || 'Non classifiée'}</em><br>
                            Site: ${plant.site_name}<br>
                            État: ${this.getHealthLabel(plant.health_status)}<br>
                            ${plant.gps_accuracy ? `Précision: ±${plant.gps_accuracy}m` : ''}<br>
                            <small>Cliquez pour plus de détails</small>
                        `);
                        
                        // Add ultra-precise accuracy circle
                        if (cluster.isUltraPrecise && currentZoom >= 18) {
                            L.circle([plant.latitude, plant.longitude], {
                                radius: cluster.precision,
                                color: '#28a745',
                                fillColor: '#28a745',
                                fillOpacity: 0.1,
                                weight: 2,
                                dashArray: '5, 5'
                            }).addTo(this.mapLayers.plants);
                        }
                        
                        this.mapLayers.plants.addLayer(marker);
                        
                    } else {
                        // Clustered plants
                        const clusterMarker = L.marker([cluster.center.lat, cluster.center.lng], {
                            icon: L.divIcon({
                                className: `precision-cluster ${cluster.isUltraPrecise ? 'ultra' : ''}`,
                                html: `<span>${cluster.plants.length}</span>`,
                                iconSize: [35, 35],
                                iconAnchor: [17, 17]
                            })
                        });
                        
                        clusterMarker.on('click', () => {
                            if (currentZoom < 20) {
                                // Zoom in to show individual plants
                                this.generalMap.setView([cluster.center.lat, cluster.center.lng], Math.min(currentZoom + 3, 22));
                            } else {
                                // Show cluster details
                                this.showClusterDetails(cluster);
                            }
                        });
                        
                        const plantsList = cluster.plants.map(p => `<li>${p.name} (${this.getHealthLabel(p.health_status)})</li>`).join('');
                        
                        clusterMarker.bindPopup(`
                            <strong>Groupe de ${cluster.plants.length} plantes</strong><br>
                            ${cluster.precision ? `Précision max: ±${cluster.precision}m` : ''}<br>
                            <small>Distance max: ${clusterDistance}m</small><br>
                            <br>
                            <strong>Plantes:</strong>
                            <ul style="margin: 5px 0; padding-left: 15px; max-height: 100px; overflow-y: auto;">
                                ${plantsList}
                            </ul>
                            <small>${currentZoom < 20 ? 'Cliquez pour zoomer' : 'Cliquez pour détails'}</small>
                        `);
                        
                        // Add precision area for ultra-precise clusters
                        if (cluster.isUltraPrecise && currentZoom >= 16) {
                            L.circle([cluster.center.lat, cluster.center.lng], {
                                radius: Math.max(cluster.precision || 1, clusterDistance),
                                color: '#007bff',
                                fillColor: '#007bff',
                                fillOpacity: 0.1,
                                weight: 2,
                                dashArray: '10, 5'
                            }).addTo(this.mapLayers.plants);
                        }
                        
                        this.mapLayers.plants.addLayer(clusterMarker);
                    }
                    
                    visibleCount += cluster.plants.length;
                });
            }
            
            this.mapStats.visible = visibleCount;
            
            // Update layer visibility
            if (this.mapViewMode === 'sites') {
                this.generalMap.removeLayer(this.mapLayers.plants);
                this.generalMap.addLayer(this.mapLayers.sites);
            } else if (this.mapViewMode === 'plants') {
                this.generalMap.removeLayer(this.mapLayers.sites);
                this.generalMap.addLayer(this.mapLayers.plants);
            } else {
                this.generalMap.addLayer(this.mapLayers.sites);
                this.generalMap.addLayer(this.mapLayers.plants);
            }
            
            // Add zoom level indicator
            this.updateZoomPrecisionIndicator();
        },
        
        updateZoomPrecisionIndicator() {
            if (!this.generalMap) return;
            
            const zoom = this.generalMap.getZoom();
            let precision, label, className;
            
            if (zoom >= 20) {
                precision = '10-50cm';
                label = 'Précision Ultra';
                className = 'ultra-precise';
            } else if (zoom >= 18) {
                precision = '1-5m';
                label = 'Très Précis';
                className = 'high-precise';
            } else if (zoom >= 16) {
                precision = '5-20m';
                label = 'Précis';
                className = '';
            } else {
                precision = '20m+';
                label = 'Vue Générale';
                className = '';
            }
            
            // Remove existing indicator
            const existingIndicator = document.querySelector('.zoom-precision-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }
            
            // Add new indicator
            const indicator = document.createElement('div');
            indicator.className = `zoom-precision-indicator ${className}`;
            indicator.innerHTML = `
                <i class="fas fa-crosshairs me-1"></i>
                ${label}<br>
                <small>±${precision}</small>
            `;
            
            const mapContainer = document.getElementById('generalMap');
            if (mapContainer) {
                mapContainer.appendChild(indicator);
            }
        },
        
        showClusterDetails(cluster) {
            this.selectedMapItem = {
                type: 'cluster',
                name: `Groupe de ${cluster.plants.length} plantes`,
                plants: cluster.plants,
                coordinates: [cluster.center.lat, cluster.center.lng],
                precision: cluster.precision,
                isUltraPrecise: cluster.isUltraPrecise
            };
        },
        
        // Enhanced Plants Page Methods
        resetPlantFilters() {
            this.plantFilters = {
                search: '',
                category: '',
                site: '',
                health: '',
                family: '',
                genus: '',
                hasPhotos: false,
                hasGPS: false,
                showPrivate: false,
                onlyMine: false
            };
        },
        
        sortPlants(criteria) {
            const sorted = [...this.filteredPlantsComputed];
            
            switch(criteria) {
                case 'name':
                    sorted.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                case 'created_at':
                    sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    break;
                case 'health_status':
                    const healthOrder = { 'excellent': 5, 'good': 4, 'fair': 3, 'poor': 2, 'dead': 1 };
                    sorted.sort((a, b) => (healthOrder[b.health_status] || 0) - (healthOrder[a.health_status] || 0));
                    break;
                case 'observations_count':
                    sorted.sort((a, b) => (b.observations_count || 0) - (a.observations_count || 0));
                    break;
            }
            
            // Temporarily update the computed property result
            // In a real app, you'd want to update the actual data source or add a sort state
            this.plants = this.plants.sort((a, b) => {
                const aIndex = sorted.findIndex(s => s.id === a.id);
                const bIndex = sorted.findIndex(s => s.id === b.id);
                return aIndex - bIndex;
            });
        },
        
        async exportPlants(format) {
            // Fetch plants with observations from export endpoint
            this.showAlert('Préparation de l\'export...', 'info');

            try {
                const response = await fetch('/api/v1/plants/export');
                if (!response.ok) {
                    throw new Error('Failed to fetch export data');
                }

                const data = await response.json();
                const plantsData = data.plants || [];

                if (plantsData.length === 0) {
                    this.showAlert('Aucune plante à exporter', 'warning');
                    return;
                }

                let content, filename, mimeType;

                switch(format) {
                    case 'csv':
                        content = this.generateCSVWithObservations(plantsData);
                        filename = `phenolab-plantes-observations-${new Date().toISOString().split('T')[0]}.csv`;
                        mimeType = 'text/csv;charset=utf-8;';
                        break;

                    case 'json':
                        content = JSON.stringify({
                            export_date: new Date().toISOString(),
                            total_plants: plantsData.length,
                            total_observations: plantsData.reduce((sum, p) => sum + (p.observations?.length || 0), 0),
                            plants: plantsData
                        }, null, 2);
                        filename = `phenolab-plantes-observations-${new Date().toISOString().split('T')[0]}.json`;
                        mimeType = 'application/json';
                        break;

                    case 'gps':
                        const gpsData = plantsData
                            .filter(p => p.latitude && p.longitude)
                            .map(p => ({
                                name: p.name,
                                scientific_name: p.taxon?.binomial_name,
                                latitude: p.latitude,
                                longitude: p.longitude,
                                accuracy: p.gps_accuracy,
                                site: p.site_name
                            }));
                        content = JSON.stringify(gpsData, null, 2);
                        filename = `phenolab-gps-${new Date().toISOString().split('T')[0]}.json`;
                        mimeType = 'application/json';
                        break;
                }

                const blob = new Blob([content], { type: mimeType });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                a.click();
                URL.revokeObjectURL(url);

                const obsCount = plantsData.reduce((sum, p) => sum + (p.observations?.length || 0), 0);
                this.showAlert(
                    `Export ${format.toUpperCase()} terminé: ${plantsData.length} plantes, ${obsCount} observations`,
                    'success'
                );
            } catch (error) {
                console.error('Export error:', error);
                this.showAlert('Erreur lors de l\'export', 'danger');
            }
        },
        
        generateCSVWithObservations(data) {
            /**
             * Generate CSV with denormalized format:
             * One row per observation, with plant fields repeated.
             * If plant has no observations, one row with plant data only.
             */
            const headers = [
                // Plant columns
                'plante_id', 'plante_nom', 'nom_scientifique', 'famille', 'site',
                'categorie', 'sante', 'hauteur', 'latitude', 'longitude',
                'precision_gps', 'date_creation_plante', 'date_plantation',
                // Observation columns
                'observation_id', 'date_observation', 'heure', 'stade_code',
                'stade_description', 'intensite', 'meteo', 'temperature',
                'humidite', 'vent', 'confiance', 'notes_observation', 'observateur'
            ];

            const csvContent = [headers.join(',')];

            data.forEach(plant => {
                // Plant base data
                const plantData = [
                    plant.id || '',
                    `"${(plant.name || '').replace(/"/g, '""')}"`,
                    `"${(plant.taxon?.binomial_name || '').replace(/"/g, '""')}"`,
                    `"${(plant.taxon?.family || '').replace(/"/g, '""')}"`,
                    `"${(plant.site_name || '').replace(/"/g, '""')}"`,
                    `"${(plant.category?.name || '').replace(/"/g, '""')}"`,
                    `"${this.getHealthLabel(plant.health_status)}"`,
                    plant.exact_height || '',
                    plant.latitude || '',
                    plant.longitude || '',
                    plant.gps_accuracy || '',
                    `"${plant.created_at || ''}"`,
                    `"${plant.planting_date || ''}"`
                ];

                // If plant has observations, create one row per observation
                if (plant.observations && plant.observations.length > 0) {
                    plant.observations.forEach(obs => {
                        const row = [
                            ...plantData,
                            obs.id || '',
                            `"${obs.observation_date || ''}"`,
                            `"${obs.time_of_day || ''}"`,
                            `"${obs.phenological_stage_code || ''}"`,
                            `"${(obs.phenological_stage_description || '').replace(/"/g, '""')}"`,
                            obs.intensity || '',
                            `"${obs.weather_condition || ''}"`,
                            obs.temperature || '',
                            obs.humidity || '',
                            obs.wind_speed || '',
                            obs.confidence_level || '',
                            `"${(obs.notes || '').replace(/"/g, '""')}"`,
                            `"${obs.observer || ''}"`
                        ];
                        csvContent.push(row.join(','));
                    });
                } else {
                    // No observations - create one row with empty observation fields
                    const row = [
                        ...plantData,
                        '', '', '', '', '', '', '', '', '', '', '', '', ''
                    ];
                    csvContent.push(row.join(','));
                }
            });

            return csvContent.join('\n');
        },
        
        navigateToPlant(plantId) {
            window.location.hash = `plant/${plantId}`;
        },
        
        showPlantOnMap(plant) {
            // Switch to map view and center on plant
            this.currentView = 'map';
            this.mapViewMode = 'plants';

            // After map is initialized, center on the plant
            this.$nextTick(() => {
                setTimeout(() => {
                    if (this.generalMap) {
                        this.generalMap.setView([plant.latitude, plant.longitude], 18);
                        this.selectedMapItem = {
                            ...plant,
                            type: 'plant',
                            coordinates: [plant.latitude, plant.longitude]
                        };
                    }
                }, 500);
            });
        },

        // ── Admin Methods ────────────────────────────────────────────

        async ensureCsrf() {
            await axios.get('/sanctum/csrf-cookie');
        },

        async loadAdminDashboard() {
            this.admin.loading = true;
            try {
                await this.ensureCsrf();
                const { data } = await axios.get('/api/v1/admin/dashboard');
                this.admin.dashboard = data;
            } catch (e) {
                console.error('Admin dashboard error:', e);
            }
            this.admin.loading = false;
        },

        setAdminMessage(msg, type = 'info') {
            this.admin.message = msg;
            this.admin.messageType = type;
            setTimeout(() => { this.admin.message = null; }, 6000);
        },

        // ── Categories CRUD ──

        async loadAdminCategories() {
            try {
                const { data } = await axios.get('/api/v1/categories');
                this.admin.categories = data;
            } catch (e) { console.error(e); }
        },

        async saveCategory() {
            const cat = this.admin.newCategory;
            if (!cat.name || !cat.category_type) return;
            try {
                await this.ensureCsrf();
                await axios.post('/api/v1/categories', cat);
                this.admin.newCategory = { name: '', description: '', icon: '', category_type: 'plants' };
                this.setAdminMessage('Catégorie créée avec succès', 'success');
                this.loadAdminCategories();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur lors de la création', 'danger');
            }
        },

        editCategory(cat) {
            this.admin.editingCategory = { ...cat };
        },

        cancelEditCategory() {
            this.admin.editingCategory = null;
        },

        async updateCategory() {
            const cat = this.admin.editingCategory;
            if (!cat) return;
            try {
                await this.ensureCsrf();
                await axios.put(`/api/v1/categories/${cat.id}`, cat);
                this.admin.editingCategory = null;
                this.setAdminMessage('Catégorie mise à jour', 'success');
                this.loadAdminCategories();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur lors de la mise à jour', 'danger');
            }
        },

        async deleteCategory(id) {
            if (!confirm('Supprimer cette catégorie ?')) return;
            try {
                await this.ensureCsrf();
                await axios.delete(`/api/v1/categories/${id}`);
                this.setAdminMessage('Catégorie supprimée', 'success');
                this.loadAdminCategories();
            } catch (e) {
                this.setAdminMessage('Erreur réseau', 'danger');
            }
        },

        // ── Phenological Stages CRUD ──

        async loadAdminStages() {
            try {
                const { data } = await axios.get('/api/v1/phenological-stages');
                this.admin.stages = data;
            } catch (e) { console.error(e); }
        },

        async saveStage() {
            const st = this.admin.newStage;
            if (!st.stage_code || !st.stage_description) return;
            try {
                await this.ensureCsrf();
                await axios.post('/api/v1/phenological-stages', st);
                this.admin.newStage = { stage_code: '', stage_description: '', main_event_code: 1, main_event_description: '', phenological_scale: 'BBCH Tela Botanica' };
                this.setAdminMessage('Stade phénologique créé', 'success');
                this.loadAdminStages();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur lors de la création', 'danger');
            }
        },

        editStage(stage) {
            this.admin.editingStage = { ...stage };
        },

        cancelEditStage() {
            this.admin.editingStage = null;
        },

        async updateStage() {
            const st = this.admin.editingStage;
            if (!st) return;
            try {
                await this.ensureCsrf();
                await axios.put(`/api/v1/phenological-stages/${st.id}`, st);
                this.admin.editingStage = null;
                this.setAdminMessage('Stade mis à jour', 'success');
                this.loadAdminStages();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur lors de la mise à jour', 'danger');
            }
        },

        async deleteStage(id) {
            if (!confirm('Supprimer ce stade phénologique ?')) return;
            try {
                await this.ensureCsrf();
                await axios.delete(`/api/v1/phenological-stages/${id}`);
                this.setAdminMessage('Stade supprimé', 'success');
                this.loadAdminStages();
            } catch (e) {
                this.setAdminMessage('Erreur réseau', 'danger');
            }
        },

        async seedStages() {
            if (!confirm('Charger les 16 stades BBCH par défaut ?')) return;
            this.admin.loading = true;
            try {
                await this.ensureCsrf();
                const { data } = await axios.post('/api/v1/admin/seed-stages');
                this.setAdminMessage(data.message || 'Stades BBCH chargés', 'success');
                this.loadAdminStages();
                this.loadAdminDashboard();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur', 'danger');
            }
            this.admin.loading = false;
        },

        async seedCategories() {
            if (!confirm('Charger les catégories par défaut ?')) return;
            this.admin.loading = true;
            try {
                await this.ensureCsrf();
                const { data } = await axios.post('/api/v1/admin/seed-categories');
                this.setAdminMessage(data.message || 'Catégories chargées', 'success');
                this.loadAdminCategories();
                this.loadAdminDashboard();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur', 'danger');
            }
            this.admin.loading = false;
        },

        // ── GBIF Sync ──

        async syncGbif() {
            const s = this.admin.gbifSync;
            if (!s.query || s.query.length < 2) {
                this.setAdminMessage('Saisissez au moins 2 caractères', 'warning');
                return;
            }
            this.admin.loading = true;
            this.admin.gbifResults = null;
            try {
                await this.ensureCsrf();
                const { data } = await axios.post('/api/v1/taxons/sync-gbif', {
                    sync_mode: s.mode,
                    search_query: s.query,
                    import_limit: s.limit,
                    strict_mode: s.strict,
                    fetch_vernacular: s.fetchVernacular
                });
                this.admin.gbifResults = data;
                if (data.synced_count > 0) {
                    this.setAdminMessage(`${data.synced_count} taxon(s) synchronisé(s)`, 'success');
                } else if (data.error_count > 0) {
                    this.setAdminMessage(`Erreurs: ${data.errors[0]}`, 'warning');
                } else {
                    this.setAdminMessage('Aucun résultat trouvé', 'info');
                }
                this.loadAdminDashboard();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur réseau', 'danger');
            }
            this.admin.loading = false;
        },

        async importGbifFamily() {
            const s = this.admin.gbifSync;
            if (!s.query || s.query.length < 2) {
                this.setAdminMessage('Saisissez un nom de famille', 'warning');
                return;
            }
            this.admin.loading = true;
            this.admin.gbifResults = null;
            try {
                await this.ensureCsrf();
                const { data } = await axios.post('/api/v1/taxons/import-family', {
                    family_name: s.query,
                    accepted_only: true,
                    import_limit: s.limit
                });
                this.admin.gbifResults = data;
                this.setAdminMessage(`Import famille terminé: ${data.imported_count || 0} taxon(s)`, 'success');
                this.loadAdminDashboard();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur réseau', 'danger');
            }
            this.admin.loading = false;
        },

        // ── CSV Import ──

        onImportFileChange(event) {
            this.admin.importFile = event.target.files[0] || null;
        },

        async importCsv() {
            if (!this.admin.importFile) {
                this.setAdminMessage('Sélectionnez un fichier CSV', 'warning');
                return;
            }
            this.admin.loading = true;
            this.admin.importResult = null;
            const formData = new FormData();
            formData.append('file', this.admin.importFile);
            if (this.admin.importClear) formData.append('clear_existing', '1');

            const url = this.admin.importType === 'ods' ? '/api/v1/admin/import-ods' : '/api/v1/admin/import-tela';
            try {
                await this.ensureCsrf();
                const { data } = await axios.post(url, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.admin.importResult = data;
                this.setAdminMessage(data.message || 'Import réussi', 'success');
                this.loadAdminDashboard();
            } catch (e) {
                this.admin.importResult = e.response?.data || null;
                this.setAdminMessage(e.response?.data?.message || 'Erreur import', 'danger');
            }
            this.admin.loading = false;
        }
    }
}).mount('#app');
