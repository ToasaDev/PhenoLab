// PhenoLab Vue.js Application

const { createApp } = Vue;

createApp({
    data() {
        return {
            // Current view state
            currentView: 'dashboard',
        
        // Site detailed mapping
        siteMapData: null,
        siteMapFocusPlant: null,
        siteMapVisible: false,
        selectedPlantOnMap: null,
        mapInstance: null,
            
            // Site detail view
            currentSite: null,
            
            // Plant navigation and detail
            currentPlant: null,
            observationReturnView: null,
            plantReturnView: null,
            siteReturnView: null,
            plantDetail: {
                plant: null,
                loading: false,
                observations: [],
                photos: [],
                actions: [],
                statistics: null,
                observationSortAsc: false, // false = newest first
                actionSortAsc: false,
                actionFilterType: '',
                actionFilterQ: '',
            },
            // Plant action types catalog
            plantActionTypes: [],
            // Action form modal state
            actionForm: {
                show: false,
                editing: null,
                loading: false,
                error: '',
                data: {
                    plant_id: null,
                    action_type_id: '',
                    action_date: '',
                    title: '',
                    notes: '',
                    product_name: '',
                    quantity: '',
                    unit: '',
                    dosage: '',
                    method: '',
                    performer_name: '',
                    cost: '',
                    weather_conditions: '',
                },
            },
            actionDetail: null,

            // User data
            user: {
                username: 'Utilisateur',
                isAuthenticated: false,
                id: null,
                email: '',
                isStaff: false,
                isSuperuser: false,
                groups: []
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
                site_category_id: '',
                showPrivate: false
            },

            // Site categories (user-managed, hierarchical)
            siteCategories: [],            // flat list with breadcrumb + depth
            siteCategoriesLoading: false,
            adminSiteCategoryForm: {
                id: null,
                name: '',
                slug: '',
                description: '',
                icon: '',
                color: '',
                parent_id: null,
                sort_order: 0,
                is_active: true,
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
                    q: '',
                    site: '',
                    site_category_id: '',
                    category: '',
                    status: '',
                    health_status: '',
                    has_observations: null,
                    has_photos: null,
                    has_actions: null,
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
                placementMode: false, // "click-to-place" mode
                plantToPlace: null,   // plant selected for click-to-place
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
                showHelp: false,
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
                    site_category_id: '',
                    category: '',
                    status: '',
                    health_status: '',
                    has_observations: null,
                    has_photos: null,
                    has_actions: null,
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
            showAddTaxonModal: false,
            showGbifSyncModal: false,
            showGbifImportFamilyModal: false,
            gbifModal: {
                loading: false,
                results: null,
                sync: { mode: 'backbone_match', query: '', limit: 20, strict: false, fetchVernacular: true, createPlant: true },
                importFamily: { family: '', limit: 100, acceptedOnly: true, dryRun: false }
            },
            showAddPlantModal: false,
            showEditPlantModal: false,
            showAddObservationModal: false,
            showUpdateGpsModal: false,
            updateGps: { latitude: null, longitude: null, gps_accuracy: null },
            updateGpsError: '',
            gpsLoading: false,
            geolocating: false,
            gpsSaving: false,
            showStatsModal: false,
            statsLoading: false,
            statsError: '',
            plantStats: null,
            statsChartInstance: null,
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
            showCultivationModal: false,
            cultivationFormSaving: false,
            cultivationForm: {
                plantId: null,
                plantName: '',
                planting_months: [],
                sowing_months: [],
                harvest_months: [],
                flowering_months: [],
                exposure: null,
                hardiness_min: '',
                usda_zone: '',
                suitable_environments: [],
                soil_types: [],
                soil_ph: '',
                soil_drainage: null,
                soil_fertility: null,
                mature_height_min: null,
                mature_height_max: null,
                mature_spread_min: null,
                mature_spread_max: null,
                watering_needs: null,
                watering_notes: '',
                fertilizing_frequency: '',
                fertilizing_notes: '',
                pruning_period: '',
                pruning_notes: '',
                mulching: '',
                winter_protection: '',
                pest_susceptibility: '',
                disease_susceptibility: '',
                companion_plants: '',
                avoid_near: '',
                propagation_methods: '',
                cultivation_difficulty: null,
                usage_types: [],
                is_edible: false,
                is_toxic: false,
                notes: '',
                source: ''
            },
            monthOptions: [
                { value: 1, label: 'Janv' }, { value: 2, label: 'Févr' },
                { value: 3, label: 'Mars' }, { value: 4, label: 'Avr' },
                { value: 5, label: 'Mai' }, { value: 6, label: 'Juin' },
                { value: 7, label: 'Juil' }, { value: 8, label: 'Août' },
                { value: 9, label: 'Sept' }, { value: 10, label: 'Oct' },
                { value: 11, label: 'Nov' }, { value: 12, label: 'Déc' },
            ],
            exposureOptions: {
                full_sun: 'Plein soleil',
                partial_shade: 'Mi-ombre',
                shade: 'Ombre',
                full_shade: 'Ombre dense',
            },
            wateringNeedsOptions: {
                low: 'Faible',
                moderate: 'Modéré',
                regular: 'Régulier',
                high: 'Élevé',
            },
            difficultyOptions: {
                easy: 'Facile',
                medium: 'Moyen',
                hard: 'Difficile',
                expert: 'Expert',
            },
            soilTypeOptions: {
                clay: 'Argileux',
                sandy: 'Sableux',
                loam: 'Limoneux',
                chalky: 'Calcaire',
                peaty: 'Tourbeux',
                silty: 'Limono-argileux',
            },
            soilDrainageOptions: {
                well_drained: 'Bien drainé',
                moist: 'Frais / humide',
                wet: 'Mouillé',
                dry: 'Sec',
            },
            soilFertilityOptions: {
                poor: 'Pauvre',
                average: 'Moyen',
                rich: 'Riche',
            },
            usageTypeOptions: {
                ornamental: 'Ornemental',
                edible: 'Comestible',
                medicinal: 'Médicinal',
                hedging: 'Haie / brise-vent',
                shade: 'Ombrage',
                fragrance: 'Parfum',
                wildlife: 'Faune / pollinisateurs',
                erosion: 'Anti-érosion',
                timber: "Bois d'œuvre",
                fodder: 'Fourrage',
            },
            environmentOptionsFlat: {
                urban: 'Urbain', suburban: 'Périurbain', rural: 'Rural',
                forest: 'Forêt', garden: 'Jardin/Parc', natural: 'Naturel',
                agricultural: 'Agricole',
                botanical_garden: 'Jardin botanique', arboretum: 'Arboretum',
                nursery: 'Pépinière', orchard: 'Verger', vegetable_garden: 'Potager',
                park: 'Parc public', private_garden: 'Jardin privé',
                school_garden: 'Jardin pédagogique', community_garden: 'Jardin partagé',
                experimental: 'Parcelle expérimentale', natural_reserve: 'Réserve naturelle',
                other: 'Autre',
            },

            // Form data
            newSite: {
                name: '',
                description: '',
                latitude: null,
                longitude: null,
                altitude: null,
                environment: 'garden',
                site_category_id: null,
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
                site_category_id: null,
                soil_type: '',
                exposure: '',
                climate_zone: '',
                is_private: false
            },
            
            // Taxon autocomplete state
            siteAutocomplete: {
                query: '',
                showDropdown: false,
                selectedSite: null,
            },
            cultivarSearch: {
                query: '',
                results: [],
                loading: false,
                showModal: false,
                target: 'newPlant', // 'newPlant' or 'editPlant'
            },
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
                abundance: null,
                initial_abundance: null,
                health_status: 'good',
                identification_certainty: 'certain',
                clone_or_accession: '',
                cultivar: '',
                variety: '',
                is_private: false,
                notes: '',
                anecdotes: '',
                cultural_significance: '',
                ecological_notes: '',
                care_notes: '',
                // GPS fields
                latitude: null,
                longitude: null,
                gps_accuracy: null,
                // Photo optionnelle
                _photoFile: null,
                _photoPreview: null,
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
                abundance: null,
                initial_abundance: null,
                health_status: 'good',
                identification_certainty: 'certain',
                clone_or_accession: '',
                cultivar: '',
                variety: '',
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
                binomial_name: '',
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
                is_public: true,
                _photoFile: null,
                _photoPreview: null,
            },
            
            // Plant picker state (shared by observation & photo modals)
            plantPicker: {
                query: '',
                siteFilter: '',
                results: [],
                loading: false,
                totalCount: 0,
                debounceTimer: null,
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
                is_public: true,
                _context: 'plant', // 'plant' or 'observation'
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

            // Export
            exportFilters: {
                year: 'all',
                site_id: '',
                category: '',
                status: '',
                taxon: '',
                format: 'full',
            },
            exportState: {
                loading: false,
                success: false,
                error: '',
            },
            hugoExportState: {
                loading: false,
                success: false,
                error: '',
            },

            // Login form
            loginForm: {
                username: '',
                password: '',
                error: ''
            },

            // Tags
            userTags: [],
            plantTags: [],
            showTagModal: false,
            newTagForm: { name: '', color: 'secondary', group_id: null },
            editingTag: null,
            tagFilter: '',

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

            submitting: {
                plant: false,
                observation: false,
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
                showCultivationFilters: false,
                filters: {
                    type: 'all',
                    mine: false,
                    date_from: null,
                    date_to: null,
                    cult_exposure: '',
                    cult_difficulty: '',
                    cult_watering: '',
                    cult_soil_type: '',
                    cult_soil_drainage: '',
                    cult_usage_type: '',
                    cult_usda_zone_min: '',
                    cult_usda_zone_max: '',
                    cult_temp_min: '',
                    cult_temp_max: '',
                    cult_is_edible: false,
                    cult_is_toxic: false,
                    tag_id: '',
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
                // Action Types
                actionTypes: [],
                newActionType: { name: '', slug: '', description: '', category: 'maintenance', icon: '', color: 'bg-secondary', applies_to: 'all', is_active: true, sort_order: 0 },
                editingActionType: null,
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

        // Observations on plant detail, sorted by date (default newest first).
        plantMainPhoto() {
            if (!this.plantDetail.photos) return null;
            return this.plantDetail.photos.find(p => p.is_main_photo) || null;
        },

        sortedPlantObservations() {
            const obs = [...this.plantDetail.observations];
            const asc = this.plantDetail.observationSortAsc;
            return obs.sort((a, b) => {
                const da = new Date(a.observation_date);
                const db = new Date(b.observation_date);
                return asc ? da - db : db - da;
            });
        },

        filteredPlantActions() {
            let actions = [...(this.plantDetail.actions || [])];
            if (this.plantDetail.actionFilterType) {
                actions = actions.filter(a => a.action_type_id == this.plantDetail.actionFilterType);
            }
            if (this.plantDetail.actionFilterQ) {
                const q = this.plantDetail.actionFilterQ.toLowerCase();
                actions = actions.filter(a =>
                    (a.title || '').toLowerCase().includes(q) ||
                    (a.notes || '').toLowerCase().includes(q) ||
                    (a.product_name || '').toLowerCase().includes(q) ||
                    (a.action_type?.name || '').toLowerCase().includes(q)
                );
            }
            actions.sort((a, b) => {
                const cmp = new Date(a.action_date) - new Date(b.action_date);
                return this.plantDetail.actionSortAsc ? cmp : -cmp;
            });
            return actions;
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

            // Site category filter (hierarchical: include descendants)
            if (this.siteFilters.site_category_id) {
                const targetIds = this.siteCategoryDescendantIds(parseInt(this.siteFilters.site_category_id, 10));
                filtered = filtered.filter(site =>
                    site.site_category_id != null && targetIds.includes(site.site_category_id)
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

        // Watch for sites view mode (grid / list / map)
        sitesViewMode(newMode) {
            if (newMode === 'map') {
                this.$nextTick(() => {
                    this.initializeMap();
                    if (this.map) {
                        this.map.invalidateSize();
                        this.fitSitesMapBounds();
                    }
                });
            }
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

        // Global axios interceptor for 401/403 responses
        axios.interceptors.response.use(
            response => response,
            error => {
                if (error.response?.status === 401) {
                    // Session expired or not authenticated
                    if (this.user.isAuthenticated) {
                        this.user.isAuthenticated = false;
                        this.user.id = null;
                        this.user.isStaff = false;
                        this.user.isSuperuser = false;
                        this.user.groups = [];
                        this.showAlert('Votre session a expiré. Veuillez vous reconnecter.', 'warning');
                        this.showLoginModal = true;
                    }
                } else if (error.response?.status === 403) {
                    this.showAlert('Accès refusé — vous n\'avez pas les droits nécessaires.', 'danger');
                }
                return Promise.reject(error);
            }
        );

        // Load search history from localStorage
        this.loadSearchHistory();

        // Auto-close hamburger menu on link click (mobile)
        const navbarCollapse = document.getElementById('navbarNav');
        if (navbarCollapse) {
            navbarCollapse.addEventListener('click', (e) => {
                if (e.target.closest('.dropdown-item, .nav-link') && !e.target.closest('.dropdown-toggle')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                    if (bsCollapse) bsCollapse.hide();
                }
            });
        }

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
        async initializeApp() {
            // Check auth status first
            await this.checkAuthStatus();

            // Always load public data + form data
            const publicLoads = [
                this.loadSites().catch(e => console.warn('Sites load failed:', e)),
                this.loadSiteCategories().catch(e => console.warn('Site categories load failed:', e)),
                this.loadFormData().catch(e => console.warn('Form data load failed:', e)),
                this.loadODSChartData().catch(e => console.warn('ODS data load failed:', e)),
            ];

            // Only load auth-required data if logged in
            if (this.user.isAuthenticated) {
                publicLoads.push(
                    this.loadStatistics().catch(e => console.warn('Stats load failed:', e)),
                    this.loadRecentActivities().catch(e => console.warn('Activities load failed:', e)),
                    this.loadUserTags().catch(e => console.warn('Tags load failed:', e)),
                );
            }

            await Promise.all(publicLoads);
            this.initializeCharts();
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
            // Icône et couleur selon entity_type + action
            const iconMap = {
                plant:       { icon: 'fa-seedling',    color: 'success' },
                observation: { icon: 'fa-eye',         color: 'info' },
                photo:       { icon: 'fa-camera',      color: 'purple' },
                taxon:       { icon: 'fa-dna',         color: 'warning' },
                site:        { icon: 'fa-map-marker-alt', color: 'primary' },
                position:    { icon: 'fa-map-pin',     color: 'secondary' },
                system:      { icon: 'fa-cog',         color: 'secondary' },
            };
            const actionOverrides = {
                created:     { icon: 'fa-plus-circle',      color: null },
                updated:     { icon: 'fa-pen',              color: null },
                deleted:     { icon: 'fa-trash',            color: 'danger' },
                replaced:    { icon: 'fa-exchange-alt',     color: 'warning' },
                marked_dead: { icon: 'fa-skull-crossbones', color: 'danger' },
                validated:   { icon: 'fa-check-circle',     color: 'primary' },
                uploaded:    { icon: 'fa-upload',            color: null },
                imported:    { icon: 'fa-file-import',       color: 'info' },
                synced:      { icon: 'fa-sync',              color: 'success' },
            };
            const entityStyle = iconMap[activity.entity_type] || { icon: 'fa-clock', color: 'secondary' };
            const actionStyle = actionOverrides[activity.action] || {};
            // Pour created/updated/uploaded : on garde l'icône de l'entité, sinon on prend celle de l'action
            const useEntityIcon = ['created', 'updated', 'uploaded'].includes(activity.action);
            return {
                ...activity,
                actor: activity?.actor ? {
                    ...activity.actor,
                    username: activity.actor.username || activity.actor.name || 'Utilisateur'
                } : null,
                color: actionStyle.color || entityStyle.color,
                icon: useEntityIcon ? entityStyle.icon : (actionStyle.icon || entityStyle.icon),
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
                    this.loadSites().then(() => {
                        if (this.sitesViewMode === 'map') {
                            this.$nextTick(() => this.initializeMap());
                        }
                    });
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
        
        // Navigate to the entity of an activity
        activityNavigate(activity) {
            if (!activity.entity_id) return;
            switch (activity.entity_type) {
                case 'plant':
                    this.viewPlantDetail(activity.entity_id);
                    break;
                case 'observation':
                    this.viewObservationDetail(activity.entity_id);
                    break;
                case 'site':
                    this.viewSiteDetail(activity.entity_id);
                    break;
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

        // True when at least one cultivation filter is active
        hasActiveCultivationFilters() {
            const f = this.searchPage.filters;
            return !!(f.cult_exposure || f.cult_difficulty || f.cult_watering ||
                f.cult_soil_type || f.cult_soil_drainage || f.cult_usage_type ||
                f.cult_usda_zone_min || f.cult_usda_zone_max ||
                f.cult_temp_min !== '' || f.cult_temp_max !== '' ||
                f.cult_is_edible || f.cult_is_toxic);
        },

        // Reset cultivation filters
        resetCultivationFilters() {
            const f = this.searchPage.filters;
            f.cult_exposure = '';
            f.cult_difficulty = '';
            f.cult_watering = '';
            f.cult_soil_type = '';
            f.cult_soil_drainage = '';
            f.cult_usage_type = '';
            f.cult_usda_zone_min = '';
            f.cult_usda_zone_max = '';
            f.cult_temp_min = '';
            f.cult_temp_max = '';
            f.cult_is_edible = false;
            f.cult_is_toxic = false;
        },

        // Perform search on dedicated Search page
        async performSearchPageSearch() {
            const query = this.searchPage.query.trim();
            const hasCult = this.hasActiveCultivationFilters();
            const hasTag = !!this.searchPage.filters.tag_id;

            if (query.length < 2 && !hasCult && !hasTag) {
                this.showAlert('Entrez au moins 2 caractères ou activez un filtre', 'warning');
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

                // Cultivation filters (only those with values)
                const cultMap = {
                    cult_exposure: this.searchPage.filters.cult_exposure,
                    cult_difficulty: this.searchPage.filters.cult_difficulty,
                    cult_watering: this.searchPage.filters.cult_watering,
                    cult_soil_type: this.searchPage.filters.cult_soil_type,
                    cult_soil_drainage: this.searchPage.filters.cult_soil_drainage,
                    cult_usage_type: this.searchPage.filters.cult_usage_type,
                };
                Object.entries(cultMap).forEach(([k, v]) => { if (v) params[k] = v; });
                // Range filters
                if (this.searchPage.filters.cult_usda_zone_min !== '') params.cult_usda_zone_min = this.searchPage.filters.cult_usda_zone_min;
                if (this.searchPage.filters.cult_usda_zone_max !== '') params.cult_usda_zone_max = this.searchPage.filters.cult_usda_zone_max;
                if (this.searchPage.filters.cult_temp_min !== '') params.cult_temp_min = this.searchPage.filters.cult_temp_min;
                if (this.searchPage.filters.cult_temp_max !== '') params.cult_temp_max = this.searchPage.filters.cult_temp_max;
                if (this.searchPage.filters.cult_is_edible) params.cult_is_edible = 1;
                if (this.searchPage.filters.cult_is_toxic) params.cult_is_toxic = 1;
                if (this.searchPage.filters.tag_id) params.tag_id = this.searchPage.filters.tag_id;

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
                        status: p.status,
                        cultivation: p.cultivation || null,
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

            const escapedText = String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`(${escapedQuery})`, 'gi');
            return escapedText.replace(regex, '<mark class="bg-warning">$1</mark>');
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

            // Destroy stale map instance if its container was removed by v-if
            if (this.map) {
                const oldContainer = this.map.getContainer();
                if (!oldContainer || !document.body.contains(oldContainer)) {
                    this.map.remove();
                    this.map = null;
                    this.sitesLayer = null;
                } else {
                    this.map.invalidateSize();
                    return;
                }
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

            // Auto-fit the view to all visible site markers
            this.fitSitesMapBounds();
        },

        fitSitesMapBounds() {
            if (!this.map) return;
            const pts = this.filteredSites
                .filter(s => s.latitude && s.longitude)
                .map(s => [parseFloat(s.latitude), parseFloat(s.longitude)]);
            if (pts.length === 0) return;
            if (pts.length === 1) {
                this.map.setView(pts[0], 13);
            } else {
                this.map.fitBounds(pts, { padding: [40, 40], maxZoom: 14 });
            }
        },
        
        // Site management methods
        viewSite(site) {
            window.location.hash = `#site/${site.id}`;
        },
        
        async viewSiteDetail(siteId) {
            console.log('🏠 Loading site detail:', siteId);
            this.siteReturnView = this.currentView;
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
                if (filters.has_actions !== null) params.append('has_actions', filters.has_actions);
                if (filters.planting_date_after) params.append('planting_date_after', filters.planting_date_after);
                if (filters.planting_date_before) params.append('planting_date_before', filters.planting_date_before);
                if (filters.ordering) params.append('ordering', filters.ordering);
                params.append('page_size', filters.page_size);
                params.append('page', page);

                const response = await axios.get(`/api/v1/sites/${siteId}/plants/?${params.toString()}`);

                this.siteDetail.plants = this.extractCollection(response.data);
                this.siteDetail.pagination = {
                    count: response.data.total ?? response.data.count ?? 0,
                    next: response.data.next_page_url ?? response.data.next,
                    previous: response.data.prev_page_url ?? response.data.previous,
                    current_page: response.data.current_page ?? page,
                    total_pages: response.data.last_page ?? Math.ceil((response.data.total ?? response.data.count ?? 0) / filters.page_size)
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

            // Bind keyboard handler for nudging / escape
            this._mapKeydownHandler = (e) => this.onMapEditorKeydown(e);
            document.addEventListener('keydown', this._mapKeydownHandler);

            // Load layers first (which will load plants for the selected layer)
            await this.loadLayers();
        },

        closeSiteMapEditor() {
            if (this.siteMapEditor.unsavedChanges) {
                if (!confirm('Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir fermer ?')) {
                    return;
                }
            }
            if (this._mapKeydownHandler) {
                document.removeEventListener('keydown', this._mapKeydownHandler);
                this._mapKeydownHandler = null;
            }
            this.showSiteMapEditorModal = false;
            this.siteMapEditor.active = false;
            this.siteMapEditor.editMode = false;
            this.siteMapEditor.plants = [];
            this.siteMapEditor.selectedPlant = null;
            this.siteMapEditor.unsavedChanges = false;
            this.siteMapEditor.placementMode = false;
            this.siteMapEditor.plantToPlace = null;
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

                const [plantsResponse, mapResponse] = await Promise.all([
                    axios.get(`/api/v1/plants`, { params }),
                    layerId
                        ? axios.get(`/api/v1/plants/site-map`, { params: { site_id: siteId, layer_id: layerId } })
                        : Promise.resolve(null),
                ]);

                const plants = this.extractCollection(plantsResponse.data);

                // Overlay layer-specific positions from the pivot. Plants not
                // present in this layer get their position cleared so they
                // appear as unplaced in the sidebar.
                if (mapResponse) {
                    const byId = {};
                    (mapResponse.data.plants || []).forEach(p => { byId[p.id] = p; });
                    plants.forEach(p => {
                        const layerPlant = byId[p.id];
                        if (layerPlant && layerPlant.map_position_x !== null && layerPlant.map_position_x !== undefined) {
                            p.map_position_x = layerPlant.map_position_x;
                            p.map_position_y = layerPlant.map_position_y;
                            p.layer_id = layerId;
                        } else {
                            p.map_position_x = null;
                            p.map_position_y = null;
                            p.layer_id = null;
                        }
                    });
                }

                this.siteMapEditor.plants = plants;
                this.siteMapEditor.unsavedChanges = false;
                console.log('📍 Loaded plants for map:', this.siteMapEditor.plants.length, 'layer:', layerId);
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

        // Convert a client (mouse/touch) point to SVG-percent coordinates
        // using the proper SVG CTM so clicks land exactly under the cursor,
        // regardless of zoom, resize or viewBox letterboxing.
        _svgPercentFromClient(clientX, clientY) {
            const svg = document.querySelector('#siteMapSvg');
            if (!svg) return null;
            const pt = svg.createSVGPoint();
            pt.x = clientX;
            pt.y = clientY;
            const ctm = svg.getScreenCTM();
            if (!ctm) return null;
            const svgP = pt.matrixTransform(ctm.inverse());
            const w = this.siteMapEditor.svgDimensions.width;
            const h = this.siteMapEditor.svgDimensions.height;
            return {
                x: Math.max(0, Math.min(100, (svgP.x / w) * 100)),
                y: Math.max(0, Math.min(100, (svgP.y / h) * 100)),
            };
        },

        startDragPlant(plant, event) {
            if (!this.siteMapEditor.editMode) return;
            // Don't start a drag if we're in placement mode
            if (this.siteMapEditor.placementMode) return;
            event.preventDefault();
            event.stopPropagation();

            this.siteMapEditor.draggingPlant = plant;
            this.siteMapEditor.selectedPlant = plant;
            this.siteMapEditor._dragMoved = false;

            const pointerId = event.pointerId;

            this.siteMapEditor._onDragMove = (e) => {
                if (!this.siteMapEditor.draggingPlant) return;
                e.preventDefault();
                this.siteMapEditor._dragMoved = true;
                const p = this._svgPercentFromClient(e.clientX, e.clientY);
                if (!p) return;
                this.siteMapEditor.draggingPlant.map_position_x = p.x;
                this.siteMapEditor.draggingPlant.map_position_y = p.y;
                this.siteMapEditor.unsavedChanges = true;
            };

            this.siteMapEditor._onDragEnd = () => {
                this.siteMapEditor.draggingPlant = null;
                document.removeEventListener('pointermove', this.siteMapEditor._onDragMove);
                document.removeEventListener('pointerup', this.siteMapEditor._onDragEnd);
                document.removeEventListener('pointercancel', this.siteMapEditor._onDragEnd);
            };

            document.addEventListener('pointermove', this.siteMapEditor._onDragMove, { passive: false });
            document.addEventListener('pointerup', this.siteMapEditor._onDragEnd);
            document.addEventListener('pointercancel', this.siteMapEditor._onDragEnd);
        },

        selectPlant(plant) {
            // Don't select if we just finished dragging
            if (this.siteMapEditor._dragMoved) {
                this.siteMapEditor._dragMoved = false;
                return;
            }
            this.siteMapEditor.selectedPlant = plant;
        },

        // Human-friendly "click-to-place": pick a plant in the sidebar,
        // then click anywhere on the map to drop it exactly there.
        addPlantToMap(plant) {
            if (!this.siteMapEditor.editMode) {
                this.showAlert('Activez le mode édition pour placer des plantes', 'info');
                return;
            }
            this.siteMapEditor.placementMode = true;
            this.siteMapEditor.plantToPlace = plant;
            this.siteMapEditor.selectedPlant = plant;
            this.showAlert(`Cliquez sur le plan pour placer "${plant.name}" (Échap pour annuler)`, 'info');
        },

        // Remove a plant's position so it can be re-placed.
        unplacePlant(plant) {
            if (!this.siteMapEditor.editMode) return;
            plant.map_position_x = null;
            plant.map_position_y = null;
            this.siteMapEditor.unsavedChanges = true;
            if (this.siteMapEditor.selectedPlant?.id === plant.id) {
                this.siteMapEditor.selectedPlant = null;
            }
        },

        cancelPlacement() {
            this.siteMapEditor.placementMode = false;
            this.siteMapEditor.plantToPlace = null;
        },

        // Called by the SVG click handler when in placement mode.
        placePlantAt(clientX, clientY) {
            const plant = this.siteMapEditor.plantToPlace;
            if (!plant) return false;
            const p = this._svgPercentFromClient(clientX, clientY);
            if (!p) return false;
            plant.map_position_x = p.x;
            plant.map_position_y = p.y;
            this.siteMapEditor.unsavedChanges = true;
            this.siteMapEditor.placementMode = false;
            this.siteMapEditor.plantToPlace = null;
            this.siteMapEditor.selectedPlant = plant;
            return true;
        },

        // Keyboard nudging for precise positioning of the selected plant.
        // Arrow keys = 0.5%, Shift+Arrow = 2%. Delete = remove from map.
        onMapEditorKeydown(event) {
            if (!this.siteMapEditor.active || !this.siteMapEditor.editMode) return;
            if (event.key === 'Escape' && this.siteMapEditor.placementMode) {
                this.cancelPlacement();
                event.preventDefault();
                return;
            }
            const plant = this.siteMapEditor.selectedPlant;
            if (!plant || plant.map_position_x === null) return;
            // Don't hijack typing in inputs
            const tag = (event.target?.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

            const step = event.shiftKey ? 2 : 0.5;
            let handled = true;
            switch (event.key) {
                case 'ArrowLeft':
                    plant.map_position_x = Math.max(0, plant.map_position_x - step); break;
                case 'ArrowRight':
                    plant.map_position_x = Math.min(100, plant.map_position_x + step); break;
                case 'ArrowUp':
                    plant.map_position_y = Math.max(0, plant.map_position_y - step); break;
                case 'ArrowDown':
                    plant.map_position_y = Math.min(100, plant.map_position_y + step); break;
                default:
                    handled = false;
            }
            if (handled) {
                this.siteMapEditor.unsavedChanges = true;
                event.preventDefault();
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
            // Click-to-place short-circuit: if a plant is waiting to be placed,
            // drop it exactly where the user clicked and stop here.
            if (this.siteMapEditor.placementMode && this.siteMapEditor.editMode) {
                if (this.placePlantAt(event.clientX, event.clientY)) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
            }
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

        editSelectedShapeText() {
            const idx = this.siteMapEditor.selectedShape;
            if (idx === null) return;
            const shape = this.siteMapEditor.drawingShapes[idx];
            if (!shape || shape.type !== 'text') return;
            const next = prompt('Modifier le texte :', shape.text || '');
            if (next === null) return;
            shape.text = next;
            this.siteMapEditor.drawingUnsavedChanges = true;
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

                // Update local layer data with a clone to keep refs decoupled.
                const saved = response.data.drawing_overlay;
                this.siteMapEditor.selectedLayer.drawing_overlay = Array.isArray(saved)
                    ? JSON.parse(JSON.stringify(saved))
                    : [];
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
            // Always deep-clone so editing the current layer doesn't mutate
            // the layer object stored in siteMapEditor.layers (which would
            // bleed changes across layers).
            const source = this.siteMapEditor.selectedLayer?.drawing_overlay;
            this.siteMapEditor.drawingShapes = Array.isArray(source)
                ? JSON.parse(JSON.stringify(source))
                : [];
            this.siteMapEditor.selectedShape = null;
            this.siteMapEditor.drawingUnsavedChanges = false;
            console.log('📐 Loaded drawing overlay:', this.siteMapEditor.drawingShapes.length, 'shapes');
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
                notes: '',
                copy_from_active: false
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
                        is_active: true,
                        source_layer_id: this.siteMapEditor.selectedLayer?.id || null,
                    }
                );
                // The new layer is now active — deactivate the others locally too.
                this.siteMapEditor.layers.forEach(l => { l.is_active = false; });
                this.siteMapEditor.layers.push(response.data);
                this.siteMapEditor.selectedLayer = response.data;
                // Reload plants + drawings for the new layer (will show copied
                // content if copy_from_active was checked, empty otherwise).
                await this.loadSiteMapPlants(this.siteMapEditor.site.id, response.data.id);
                await this.loadDrawingOverlay();
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
                if (filters.q) params.append('search', filters.q);
                if (filters.site) params.append('site', filters.site);
                if (filters.site_category_id) params.append('site_category_id', filters.site_category_id);
                if (filters.category) params.append('category', filters.category);
                if (filters.status) params.append('status', filters.status);
                if (filters.health_status) params.append('health_status', filters.health_status);
                if (filters.has_observations !== null) params.append('has_observations', filters.has_observations);
                if (filters.has_photos !== null) params.append('has_photos', filters.has_photos);
                if (filters.has_actions !== null) params.append('has_actions', filters.has_actions);
                if (this.tagFilter) params.append('tag_id', this.tagFilter);
                if (filters.ordering) params.append('ordering', filters.ordering);
                params.append('page_size', filters.page_size);
                params.append('page', page);

                const response = await axios.get(`/api/v1/plants/?${params.toString()}`);

                this.plantsList.items = this.extractCollection(response.data);
                this.plantsList.pagination = {
                    count: response.data.total ?? response.data.count ?? 0,
                    next: response.data.next_page_url ?? response.data.next,
                    previous: response.data.prev_page_url ?? response.data.previous,
                    current_page: response.data.current_page ?? page,
                    total_pages: response.data.last_page ?? Math.ceil((response.data.total ?? response.data.count ?? 0) / filters.page_size)
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
                site_category_id: '',
                category: '',
                status: '',
                health_status: '',
                has_observations: null,
                has_photos: null,
                has_actions: null,
                ordering: 'name',
                page_size: 25
            };
            this.tagFilter = '';
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
                if (filters.q) params.append('search', filters.q);
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
                    count: response.data.total ?? response.data.count ?? 0,
                    next: response.data.next_page_url ?? response.data.next,
                    previous: response.data.prev_page_url ?? response.data.previous,
                    current_page: response.data.current_page ?? page,
                    total_pages: response.data.last_page ?? Math.ceil((response.data.total ?? response.data.count ?? 0) / filters.page_size)
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
            const returnTo = this.siteReturnView || 'sites';
            this.siteReturnView = null;
            window.location.hash = '#' + returnTo;
            this.currentView = returnTo;
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
                site_category_id: site.site_category_id || null,
                soil_type: site.soil_type || '',
                exposure: site.exposure || '',
                climate_zone: site.climate_zone || '',
                is_private: site.is_private
            };
            
            // Open edit modal
            this.showEditSiteModal = true;
        },
        
        // Add new site
        async addTaxon() {
            try {
                if (!this.newTaxon.genus || !this.newTaxon.species) {
                    this.showAlert('Le genre et l\'espece sont obligatoires', 'warning');
                    return;
                }
                // Auto-generate taxon_id if empty
                if (!this.newTaxon.taxon_id) {
                    this.newTaxon.taxon_id = (this.newTaxon.genus.substring(0, 3) + this.newTaxon.species.substring(0, 3) + Date.now() % 100000).toUpperCase();
                }
                const payload = {};
                for (const [key, value] of Object.entries(this.newTaxon)) {
                    payload[key] = (value === '' || value === null) ? null : value;
                }
                payload.kingdom = payload.kingdom || 'Plantae';
                const response = await axios.post('/api/v1/taxons', payload);
                this.closeModal();
                this.showAlert('Taxon "' + (response.data.binomial_name || response.data.genus + ' ' + response.data.species) + '" cree avec succes', 'success');
            } catch (error) {
                console.error('Error adding taxon:', error);
                if (error.response?.data?.errors) {
                    const msgs = Object.values(error.response.data.errors).flat().join(', ');
                    this.showAlert(msgs, 'danger');
                } else {
                    this.showAlert(error.response?.data?.message || 'Erreur lors de la creation du taxon', 'danger');
                }
            }
        },

        async syncGbifFromModal() {
            const s = this.gbifModal.sync;
            if (!s.query || s.query.length < 2) {
                this.showAlert('Saisissez au moins 2 caracteres', 'warning');
                return;
            }
            this.gbifModal.loading = true;
            this.gbifModal.results = null;
            try {
                await this.ensureCsrf();
                const { data } = await axios.post('/api/v1/taxons/sync-gbif', {
                    sync_mode: s.mode,
                    search_query: s.query,
                    import_limit: s.limit,
                    strict_mode: s.strict,
                    fetch_vernacular: s.fetchVernacular
                });
                this.gbifModal.results = data;
                if (data.synced_count > 0) {
                    this.showAlert(`${data.synced_count} taxon(s) synchronise(s) depuis GBIF`, 'success');
                    // Si "Créer la plante" est coché, ouvrir le formulaire plante avec le taxon pré-rempli
                    if (this.gbifModal.sync.createPlant && data.synced && data.synced.length > 0) {
                        const syncedTaxon = data.synced[0];
                        // Chercher le taxon dans la base par son nom
                        let taxon = null;
                        try {
                            const resp = await axios.get('/api/v1/taxons', { params: { search: syncedTaxon.name, page_size: 5 } });
                            const results = this.extractCollection(resp.data);
                            taxon = results.find(t => t.taxon_id === syncedTaxon.taxon_id || t.binomial_name === syncedTaxon.name);
                        } catch (e) { console.error('Error fetching synced taxon:', e); }
                        this.closeModal();
                        this.$nextTick(() => {
                            this.openModal('plant');
                            if (taxon) {
                                this.newPlant.taxon = taxon.id;
                                this.taxonAutocomplete.selectedTaxon = taxon;
                                this.taxonAutocomplete.query = taxon.display_name || taxon.binomial_name || '';
                            }
                        });
                        return;
                    }
                } else if (data.error_count > 0) {
                    this.showAlert(`Erreurs: ${data.errors[0]}`, 'warning');
                } else {
                    this.showAlert('Aucun resultat trouve', 'info');
                }
            } catch (e) {
                this.showAlert(e.response?.data?.message || 'Erreur lors de la synchronisation GBIF', 'danger');
            }
            this.gbifModal.loading = false;
        },

        async importFamilyFromModal() {
            const f = this.gbifModal.importFamily;
            if (!f.family || f.family.length < 2) {
                this.showAlert('Saisissez un nom de famille', 'warning');
                return;
            }
            this.gbifModal.loading = true;
            this.gbifModal.results = null;
            try {
                await this.ensureCsrf();
                const { data } = await axios.post('/api/v1/taxons/import-family', {
                    family_name: f.family,
                    accepted_only: f.acceptedOnly,
                    import_limit: f.limit,
                    dry_run: f.dryRun
                });
                this.gbifModal.results = data;
                const prefix = f.dryRun ? '[SIMULATION] ' : '';
                this.showAlert(`${prefix}Import famille termine: ${data.imported_count || 0} taxon(s)`, 'success');
            } catch (e) {
                this.showAlert(e.response?.data?.message || 'Erreur lors de l\'import de famille', 'danger');
            }
            this.gbifModal.loading = false;
        },

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

                // Update site detail view if currently showing this site
                if (this.siteDetail.site && this.siteDetail.site.id === this.editSite.id) {
                    this.siteDetail.site = this.normalizeSite
                        ? this.normalizeSite(response.data)
                        : response.data;
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
            // Auth guard: creation modals require authentication
            const authRequired = ['site', 'plant', 'observation', 'photo', 'taxon', 'gbifSync', 'gbifImportFamily'];
            if (authRequired.includes(modalType) && !this.user.isAuthenticated) {
                this.showLoginModal = true;
                this.showAlert('Connectez-vous pour effectuer cette action.', 'warning');
                return;
            }

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
                case 'taxon':
                    this.newTaxon = {
                        taxon_id: '', binomial_name: '', genus: '', species: '', kingdom: 'Plantae',
                        phylum: '', class_name: '', order: '', family: '',
                        subspecies: '', variety: '', cultivar: '',
                        common_name_fr: '', common_name_it: '', common_name_en: '',
                        author: '', publication_year: null
                    };
                    this.showAddTaxonModal = true;
                    break;
                case 'gbifSync':
                    this.gbifModal.sync = { mode: 'backbone_match', query: '', limit: 20, strict: false, fetchVernacular: true, createPlant: true };
                    this.gbifModal.results = null;
                    this.gbifModal.loading = false;
                    this.showGbifSyncModal = true;
                    break;
                case 'gbifImportFamily':
                    this.gbifModal.importFamily = { family: '', limit: 100, acceptedOnly: true, dryRun: false };
                    this.gbifModal.results = null;
                    this.gbifModal.loading = false;
                    this.showGbifImportFamilyModal = true;
                    break;
                case 'plant':
                    if (context && context.siteId) {
                        this.newPlant.site = context.siteId;
                        const site = this.sites.find(s => s.id === context.siteId);
                        if (site) {
                            this.siteAutocomplete.selectedSite = site;
                            this.siteAutocomplete.query = site.name;
                        }
                    }
                    this.showAddPlantModal = true;
                    break;
                case 'observation':
                    this.resetPlantPicker();
                    // Pre-fill plant if context provided (from plant detail page)
                    if (context && context.plantId) {
                        this.newObservation.plant = context.plantId;
                        this.newObservation.plantLocked = true;
                    } else {
                        // Reset plant selection when opening without context
                        this.newObservation.plant = '';
                        this.newObservation.plantLocked = false;
                    }
                    // Ensure sites are loaded for the picker filter
                    if (!this.sites || this.sites.length === 0) this.loadSites();
                    // Load initial plant list via API
                    this.searchPlantsForPicker();
                    this.showAddObservationModal = true;
                    break;
                case 'photo':
                    // Reset for plant photo context
                    this.resetPlantPicker();
                    this.newPhoto.photo_type = 'general';
                    this.newPhoto.title = '';
                    this.newPhoto.description = '';
                    this.newPhoto.is_public = true;
                    // Pre-fill plant if context provided (from plant detail page)
                    if (context && context.plantId) {
                        this.newPhoto.plant = context.plantId;
                        this.newPhoto.plantLocked = true;
                    } else {
                        this.newPhoto.plant = '';
                        this.newPhoto.plantLocked = false;
                    }
                    // Ensure sites are loaded for the picker filter
                    if (!this.sites || this.sites.length === 0) this.loadSites();
                    // Load initial plant list via API
                    this.searchPlantsForPicker();
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
        async openStatsModal() {
            const plant = this.plantDetail.plant;
            if (!plant) return;
            this.showStatsModal = true;
            this.statsLoading = true;
            this.statsError = '';
            this.plantStats = null;
            if (this.statsChartInstance) {
                this.statsChartInstance.destroy();
                this.statsChartInstance = null;
            }
            try {
                const response = await axios.get(`/api/v1/plants/${plant.id}/statistics`);
                this.plantStats = response.data;
                this.statsLoading = false;
                this.$nextTick(() => this.renderStatsChart());
            } catch (error) {
                this.statsError = error.response?.data?.message || error.message || 'Erreur lors du chargement.';
                this.statsLoading = false;
            }
        },

        closeStatsModal() {
            this.showStatsModal = false;
            if (this.statsChartInstance) {
                this.statsChartInstance.destroy();
                this.statsChartInstance = null;
            }
        },

        renderStatsChart() {
            const canvas = document.getElementById('statsStageChart');
            if (!canvas || !this.plantStats || !this.plantStats.observations_by_stage.length) return;
            const data = this.plantStats.observations_by_stage;
            this.statsChartInstance = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.map(d => d.stage_code),
                    datasets: [{
                        label: 'Nombre d\'observations',
                        data: data.map(d => d.count),
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => {
                                    const idx = items[0].dataIndex;
                                    return data[idx].stage_code + ' - ' + data[idx].stage_description;
                                },
                            },
                        },
                    },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        },

        openUpdateGpsModal() {
            const plant = this.plantDetail.plant;
            if (!plant) return;
            this.updateGps = {
                latitude: plant.latitude ?? plant.coordinates?.latitude ?? null,
                longitude: plant.longitude ?? plant.coordinates?.longitude ?? null,
                gps_accuracy: plant.gps_accuracy ?? null,
            };
            this.updateGpsError = '';
            this.showUpdateGpsModal = true;
        },

        closeUpdateGpsModal() {
            this.showUpdateGpsModal = false;
            this.updateGpsError = '';
        },

        // Generic geolocation helper: fills lat/lon/altitude on a target object.
        // Usage: this.locateInto(this.newSite) or this.locateInto(this.editSite)
        async locateInto(target, onError) {
            if (!navigator.geolocation) {
                const msg = window.isSecureContext === false
                    ? 'Géolocalisation indisponible : une connexion HTTPS est requise sur mobile.'
                    : 'Géolocalisation non supportée par ce navigateur.';
                this.showAlert(msg, 'warning');
                if (onError) onError(msg);
                return;
            }
            if (!window.isSecureContext) {
                const msg = 'Géolocalisation indisponible : une connexion HTTPS est requise sur mobile.';
                this.showAlert(msg, 'warning');
                if (onError) onError(msg);
                return;
            }

            // Check permission state first — if previously denied, guide the
            // user instead of silently failing.
            if (navigator.permissions) {
                try {
                    const perm = await navigator.permissions.query({ name: 'geolocation' });
                    if (perm.state === 'denied') {
                        const msg = 'Géolocalisation bloquée par le navigateur. '
                            + 'Pour l\'activer : appuyez sur le cadenas 🔒 à côté de l\'URL → Autorisations → Position → Autoriser, '
                            + 'puis rechargez la page.';
                        this.showAlert(msg, 'warning');
                        if (onError) onError(msg);
                        return;
                    }
                } catch (e) {
                    // permissions API not fully supported, proceed anyway
                }
            }

            this.geolocating = true;

            // Keep a reference to the Vue instance so the async callback
            // always triggers Vue reactivity, even on mobile where the
            // GPS acquisition can be slow.
            const vm = this;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    // Use Object.assign so Vue's reactivity proxy detects the
                    // batch of changes in a single tick.
                    const updates = {
                        latitude: Number(pos.coords.latitude.toFixed(6)),
                        longitude: Number(pos.coords.longitude.toFixed(6)),
                    };
                    if (pos.coords.altitude != null && !isNaN(pos.coords.altitude)) {
                        updates.altitude = Math.round(pos.coords.altitude);
                    }
                    if (pos.coords.accuracy != null && 'gps_accuracy' in target) {
                        updates.gps_accuracy = Number(pos.coords.accuracy.toFixed(1));
                    }
                    Object.assign(target, updates);
                    vm.geolocating = false;
                    vm.showAlert(
                        `Position obtenue (précision ~${pos.coords.accuracy ? pos.coords.accuracy.toFixed(0) : '?'} m)`,
                        'success'
                    );
                },
                (err) => {
                    vm.geolocating = false;
                    let msg;
                    switch (err.code) {
                        case 1: msg = 'Géolocalisation refusée. Autorisez l\'accès dans les paramètres de votre navigateur.'; break;
                        case 2: msg = 'Position indisponible. Vérifiez que le GPS est activé sur votre appareil.'; break;
                        case 3: msg = 'Délai d\'attente GPS dépassé. Réessayez à l\'extérieur ou dans un endroit dégagé.'; break;
                        default: msg = 'Impossible d\'obtenir la position : ' + err.message;
                    }
                    vm.showAlert(msg, 'danger');
                    if (onError) onError(msg);
                },
                { enableHighAccuracy: true, timeout: 30000, maximumAge: 60000 }
            );
        },

        async useCurrentLocation() {
            if (!navigator.geolocation || !window.isSecureContext) {
                this.updateGpsError = !window.isSecureContext
                    ? 'Géolocalisation indisponible : HTTPS requis sur mobile.'
                    : 'Géolocalisation non supportée par ce navigateur.';
                return;
            }
            if (navigator.permissions) {
                try {
                    const perm = await navigator.permissions.query({ name: 'geolocation' });
                    if (perm.state === 'denied') {
                        this.updateGpsError = 'Géolocalisation bloquée. Appuyez sur le cadenas 🔒 → Autorisations → Position → Autoriser, puis rechargez.';
                        return;
                    }
                } catch (e) { /* proceed */ }
            }
            this.gpsLoading = true;
            this.updateGpsError = '';
            const vm = this;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    Object.assign(vm.updateGps, {
                        latitude: Number(pos.coords.latitude.toFixed(6)),
                        longitude: Number(pos.coords.longitude.toFixed(6)),
                        gps_accuracy: pos.coords.accuracy ? Number(pos.coords.accuracy.toFixed(1)) : null,
                    });
                    vm.gpsLoading = false;
                },
                (err) => {
                    switch (err.code) {
                        case 1: vm.updateGpsError = 'Géolocalisation refusée. Autorisez l\'accès dans les paramètres du navigateur.'; break;
                        case 2: vm.updateGpsError = 'Position indisponible. Vérifiez que le GPS est activé.'; break;
                        case 3: vm.updateGpsError = 'Délai GPS dépassé. Réessayez dehors ou dans un endroit dégagé.'; break;
                        default: vm.updateGpsError = 'Impossible d\'obtenir la position : ' + err.message;
                    }
                    vm.gpsLoading = false;
                },
                { enableHighAccuracy: true, timeout: 30000, maximumAge: 60000 }
            );
        },

        async saveUpdatedGps() {
            const plant = this.plantDetail.plant;
            if (!plant) return;
            if (this.updateGps.latitude == null || this.updateGps.longitude == null) {
                this.updateGpsError = 'Latitude et longitude requises.';
                return;
            }
            this.gpsSaving = true;
            this.updateGpsError = '';
            try {
                const response = await axios.patch(`/api/v1/plants/${plant.id}`, {
                    latitude: this.updateGps.latitude,
                    longitude: this.updateGps.longitude,
                    gps_accuracy: this.updateGps.gps_accuracy,
                });
                const fresh = response.data;
                plant.latitude = fresh.latitude;
                plant.longitude = fresh.longitude;
                plant.gps_accuracy = fresh.gps_accuracy;
                if (plant.coordinates) {
                    plant.coordinates.latitude = fresh.latitude;
                    plant.coordinates.longitude = fresh.longitude;
                } else {
                    plant.coordinates = { latitude: fresh.latitude, longitude: fresh.longitude };
                }
                this.showAlert('Position GPS mise à jour avec succès !', 'success');
                this.showUpdateGpsModal = false;
            } catch (error) {
                const errors = error.response?.data?.errors;
                this.updateGpsError = errors
                    ? Object.values(errors).flat().join(' ')
                    : (error.response?.data?.message || error.message);
            } finally {
                this.gpsSaving = false;
            }
        },

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

        // Close any currently open modal (only toggles the one that's actually open)
        closeModal() {
            // Close photo modal using Bootstrap API (if exists)
            const photoModalElement = document.getElementById('addPhotoModal');
            if (photoModalElement) {
                const photoModal = bootstrap.Modal.getInstance(photoModalElement);
                if (photoModal) {
                    photoModal.hide();
                }
            }

            // Only flip the modal flag that is actually true — flipping
            // already-false flags still triggers Vue patches and can corrupt the DOM.
            const modalFlags = [
                'showAddSiteModal', 'showEditSiteModal', 'showAddTaxonModal', 'showGbifSyncModal', 'showGbifImportFamilyModal',
                'showAddPlantModal', 'showEditPlantModal',
                'showAddObservationModal', 'showEditObservationModal', 'showDeleteObservationModal',
                'showDeletePlantModal', 'showEditPhotoModal', 'showLoginModal', 'showTestSiteModal',
                'showMarkDeadModal', 'showReplacePlantModal',
                'showCultivationModal',
                'showTagModal',
            ];
            for (const flag of modalFlags) {
                if (this[flag]) this[flag] = false;
            }
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
                    this.user.groups = response.data.user.groups || [];

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
                await axios.get('/sanctum/csrf-cookie');
                const response = await axios.post('/api/v1/auth/logout');
                
                if (response.data.success) {
                    this.user.isAuthenticated = false;
                    this.user.username = 'Utilisateur';
                    this.user.id = null;
                    this.user.email = '';
                    this.user.isStaff = false;
                    this.user.isSuperuser = false;
                    this.user.groups = [];

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
                this.user.groups = [];
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
                    this.user.groups = response.data.user.groups || [];
                } else {
                    this.user.isAuthenticated = false;
                    this.user.username = 'Utilisateur';
                    this.user.id = null;
                    this.user.email = '';
                    this.user.isStaff = false;
                    this.user.isSuperuser = false;
                    this.user.groups = [];
                }
            } catch (error) {
                console.error('Auth status check error:', error);
                // Default to not authenticated
                this.user.isAuthenticated = false;
                this.user.username = 'Utilisateur';
            }
        },
        
        // ===== SITE DETAILED MAPPING METHODS =====
        async showSiteMap(site, focusPlant = null) {
            if (!site || !site.id) {
                console.error('showSiteMap: site invalide', site);
                this.showAlert('Site introuvable pour cette plante.', 'warning');
                return;
            }
            this.loading.sites = true;
            // Remember which plant to focus on once the map is ready
            this.siteMapFocusPlant = focusPlant || null;
            try {
                const response = await fetch(`/api/v1/plants/site-map?site_id=${site.id}`);
                if (response.ok) {
                    this.siteMapData = await response.json();
                    this.siteMapVisible = true;
                    this.currentView = 'site-map';
                    // Update hash so the router doesn't override currentView on next event
                    if (window.location.hash !== `#site-map/${site.id}`) {
                        history.replaceState(null, '', `#site-map/${site.id}`);
                    }
                    this.$nextTick(() => {
                        this.initializeSiteMap();
                    });
                } else {
                    console.error('Erreur lors du chargement de la carte du site', response.status);
                    this.showAlert('Erreur lors du chargement de la carte.', 'danger');
                }
            } catch (error) {
                console.error('Erreur showSiteMap:', error);
                this.showAlert('Erreur réseau lors du chargement de la carte.', 'danger');
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
            const siteCoords = site.coordinates
                || (site.latitude != null && site.longitude != null
                    ? [parseFloat(site.latitude), parseFloat(site.longitude)]
                    : [46.603354, 1.888334]); // Default center of France
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

            // If we were asked to focus on a specific plant (e.g. arriving
            // from the plant detail page), recenter the map on its actual
            // coordinates, add a highlighted marker on top, and open popup.
            const focus = this.siteMapFocusPlant;
            if (focus) {
                // `focus.coordinates` can be either [lat, lon] (Plant::getCoordinates)
                // or { latitude, longitude } (PlantController::show). Normalize.
                let coords = null;
                if (Array.isArray(focus.coordinates) && focus.coordinates.length === 2) {
                    coords = [parseFloat(focus.coordinates[0]), parseFloat(focus.coordinates[1])];
                } else if (focus.coordinates && focus.coordinates.latitude && focus.coordinates.longitude) {
                    coords = [parseFloat(focus.coordinates.latitude), parseFloat(focus.coordinates.longitude)];
                } else if (focus.latitude && focus.longitude) {
                    coords = [parseFloat(focus.latitude), parseFloat(focus.longitude)];
                }
                if (coords) {
                    this.mapInstance.setView(coords, 20);

                    // Highlighted marker (large pulsing pin) so the user can
                    // immediately spot the plant they came from.
                    const highlightIcon = L.divIcon({
                        className: 'focus-plant-marker',
                        html: '<div style="position:relative;width:44px;height:44px;">'
                            + '<div style="position:absolute;inset:0;border-radius:50%;background:rgba(255,193,7,0.35);animation:focusPulse 1.6s infinite;"></div>'
                            + '<div style="position:absolute;inset:8px;border-radius:50%;background:#ffc107;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;font-size:16px;">📍</div>'
                            + '</div>',
                        iconSize: [44, 44],
                        iconAnchor: [22, 22],
                    });
                    const highlight = L.marker(coords, { icon: highlightIcon, zIndexOffset: 1000 })
                        .addTo(this.mapInstance);
                    highlight.bindPopup(
                        `<div class="plant-popup">`
                        + `<h6>📍 ${focus.name}</h6>`
                        + (focus.taxon?.binomial_name ? `<p><em>${focus.taxon.binomial_name}</em></p>` : '')
                        + `<small class="text-muted">Plante sélectionnée</small>`
                        + `</div>`
                    ).openPopup();

                    // Inject the pulse keyframes once
                    if (!document.getElementById('focus-plant-marker-style')) {
                        const style = document.createElement('style');
                        style.id = 'focus-plant-marker-style';
                        style.textContent = '@keyframes focusPulse{0%{transform:scale(.8);opacity:.9}70%{transform:scale(1.8);opacity:0}100%{transform:scale(.8);opacity:0}}';
                        document.head.appendChild(style);
                    }
                }
                this.siteMapFocusPlant = null;
            }
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
            if (this.currentView !== 'plant-detail') {
                this.plantReturnView = this.currentView;
            }
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

                    // Load plant actions
                    const actionsResponse = await fetch(`/api/v1/plants/${plantId}/actions`);
                    if (actionsResponse.ok) {
                        const actionsData = await actionsResponse.json();
                        this.plantDetail.actions = Array.isArray(actionsData) ? actionsData : [];
                    }

                    // Load plant statistics
                    const statsResponse = await fetch(`/api/v1/plants/${plantId}/statistics`);
                    if (statsResponse.ok) {
                        this.plantDetail.statistics = await statsResponse.json();
                    }

                    // Load action types if not loaded yet
                    if (this.plantActionTypes.length === 0) {
                        this.loadPlantActionTypes();
                    }

                    // Load tags for this plant
                    this.loadPlantTags(plantId);
                    if (this.userTags.length === 0) this.loadUserTags();

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
            const returnTo = this.plantReturnView || 'plants';
            this.plantReturnView = null;
            this.currentPlant = null;
            this.plantDetail.plant = null;
            this.plantDetail.actions = [];
            this.plantDetail.actionFilterType = '';
            this.plantDetail.actionFilterQ = '';

            if (returnTo === 'site-detail' && this.siteDetail.site) {
                window.location.hash = '#site/' + this.siteDetail.site.id;
                this.currentView = 'site-detail';
            } else {
                window.location.hash = '#' + returnTo;
                this.currentView = returnTo;
            }
        },
        
        // ===== PLANT ACTIONS CRUD =====
        async loadPlantActionTypes() {
            try {
                const resp = await fetch('/api/v1/plant-action-types');
                if (resp.ok) {
                    this.plantActionTypes = await resp.json();
                }
            } catch (e) { console.error('Error loading action types:', e); }
        },

        getActionCategoryLabel(cat) {
            const labels = {
                maintenance: 'Entretien', treatment: 'Traitement', fertilization: 'Fertilisation',
                irrigation: 'Irrigation', harvest: 'Récolte', planting: 'Plantation',
                protection: 'Protection', other: 'Autre'
            };
            return labels[cat] || cat;
        },

        openActionForm(action = null) {
            this.actionForm.editing = action;
            this.actionForm.error = '';
            if (action) {
                this.actionForm.data = {
                    plant_id: action.plant_id,
                    action_type_id: action.action_type_id,
                    action_date: action.action_date?.substring(0, 10) || '',
                    title: action.title || '',
                    notes: action.notes || '',
                    product_name: action.product_name || '',
                    quantity: action.quantity || '',
                    unit: action.unit || '',
                    dosage: action.dosage || '',
                    method: action.method || '',
                    performer_name: action.performer_name || '',
                    cost: action.cost || '',
                    weather_conditions: action.weather_conditions || '',
                };
            } else {
                this.actionForm.data = {
                    plant_id: this.currentPlant,
                    action_type_id: '',
                    action_date: new Date().toISOString().substring(0, 10),
                    title: '', notes: '', product_name: '', quantity: '',
                    unit: '', dosage: '', method: '', performer_name: '',
                    cost: '', weather_conditions: '',
                };
            }
            this.actionForm.show = true;
        },

        closeActionForm() {
            this.actionForm.show = false;
            this.actionForm.editing = null;
            this.actionForm.error = '';
        },

        async saveAction() {
            this.actionForm.loading = true;
            this.actionForm.error = '';
            try {
                const payload = { ...this.actionForm.data };
                // Clean empty strings to null
                Object.keys(payload).forEach(k => {
                    if (payload[k] === '') payload[k] = null;
                });
                payload.plant_id = this.currentPlant;

                let resp;
                if (this.actionForm.editing) {
                    resp = await axios.put(`/api/v1/plant-actions/${this.actionForm.editing.id}`, payload);
                } else {
                    resp = await axios.post('/api/v1/plant-actions', payload);
                }

                if (resp.status === 200 || resp.status === 201) {
                    this.closeActionForm();
                    // Reload actions
                    const actResp = await fetch(`/api/v1/plants/${this.currentPlant}/actions`);
                    if (actResp.ok) {
                        this.plantDetail.actions = await actResp.json();
                    }
                    // Refresh plant data to get updated counts
                    const plantResp = await fetch(`/api/v1/plants/${this.currentPlant}`);
                    if (plantResp.ok) {
                        this.plantDetail.plant = await plantResp.json();
                    }
                }
            } catch (err) {
                const msg = err.response?.data?.message || err.response?.data?.errors;
                this.actionForm.error = typeof msg === 'object' ? Object.values(msg).flat().join(', ') : (msg || 'Erreur lors de la sauvegarde');
            } finally {
                this.actionForm.loading = false;
            }
        },

        async deleteAction(actionId) {
            if (!confirm('Supprimer cette action ?')) return;
            try {
                await axios.delete(`/api/v1/plant-actions/${actionId}`);
                this.plantDetail.actions = this.plantDetail.actions.filter(a => a.id !== actionId);
                // Refresh plant counts
                const plantResp = await fetch(`/api/v1/plants/${this.currentPlant}`);
                if (plantResp.ok) {
                    this.plantDetail.plant = await plantResp.json();
                }
            } catch (err) {
                alert(err.response?.data?.message || 'Erreur lors de la suppression');
            }
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

        /**
         * Resize an image file client-side if it exceeds maxBytes.
         * Returns a Promise<File> — the original file if already small enough,
         * or a resized JPEG/WebP otherwise.
         * Uses a canvas to scale down progressively, keeping quality high.
         */
        _resizeImageIfNeeded(file, maxBytes = 10 * 1024 * 1024) {
            return new Promise((resolve, reject) => {
                if (file.size <= maxBytes) {
                    resolve(file);
                    return;
                }
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => {
                    URL.revokeObjectURL(url);
                    const canvas = document.createElement('canvas');
                    let { width, height } = img;
                    // Scale down progressively until the result fits.
                    // Start with quality 0.92, then reduce dimensions if still too big.
                    const outputType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                    let quality = 0.92;
                    let scale = 1.0;

                    const attempt = () => {
                        canvas.width = Math.round(width * scale);
                        canvas.height = Math.round(height * scale);
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        canvas.toBlob((blob) => {
                            if (!blob) { resolve(file); return; }
                            if (blob.size <= maxBytes || scale < 0.2) {
                                const resized = new File([blob], file.name, { type: outputType, lastModified: Date.now() });
                                console.log(`📷 Photo resized: ${(file.size/1024/1024).toFixed(1)}MB → ${(resized.size/1024/1024).toFixed(1)}MB (${canvas.width}×${canvas.height})`);
                                resolve(resized);
                            } else {
                                // Reduce scale by 15% each step
                                scale *= 0.85;
                                quality = Math.max(0.7, quality - 0.03);
                                attempt();
                            }
                        }, outputType, quality);
                    };
                    attempt();
                };
                img.onerror = () => {
                    URL.revokeObjectURL(url);
                    resolve(file); // fallback: send original
                };
                img.src = url;
            });
        },

        async handleNewPlantPhoto(event) {
            const raw = event.target.files[0] || null;
            if (raw) {
                const file = await this._resizeImageIfNeeded(raw);
                this.newPlant._photoFile = file;
                const reader = new FileReader();
                reader.onload = (e) => { this.newPlant._photoPreview = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.newPlant._photoFile = null;
                this.newPlant._photoPreview = null;
            }
        },

        clearNewPlantPhoto() {
            this.newPlant._photoFile = null;
            this.newPlant._photoPreview = null;
            const fileInput = document.getElementById('plantPhoto');
            if (fileInput) fileInput.value = '';
        },

        async addPlant() {
            if (this.submitting.plant) return;
            if (!this.user.isAuthenticated) {
                this.showAlert('Fonctionnalité de démonstration - Connectez-vous avec admin/admin123 pour enregistrer réellement', 'info');
                this.showAddPlantModal = false;
                this.resetNewPlantForm();
                return;
            }

            this.submitting.plant = true;
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

                const photoFile = this.newPlant._photoFile;

                // Remove non-API fields before sending
                delete plantData._photoFile;
                delete plantData._photoPreview;

                const response = await axios.post('/api/v1/plants', plantData);
                const createdPlant = response.data;

                // Upload photo if provided
                if (photoFile) {
                    try {
                        const formData = new FormData();
                        formData.append('plant_id', createdPlant.id);
                        formData.append('image', photoFile);
                        formData.append('photo_type', 'general');
                        formData.append('is_main_photo', '1');
                        await axios.post('/api/v1/plant-photos', formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                        });
                    } catch (photoErr) {
                        console.error('Photo upload failed:', photoErr);
                        this.showAlert('Plante créée mais erreur lors de l\'upload de la photo.', 'warning');
                    }
                }

                this.showAddPlantModal = false;
                this.resetNewPlantForm();
                this.showAlert('Plante ajoutée avec succès !', 'success');

                // Recharger les listes
                await this.loadPlants();

                // Naviguer vers la fiche de la plante créée
                await this.viewPlantDetail(createdPlant.id);
            } catch (error) {
                console.error('Error adding plant:', error);
                const status = error.response?.status || 'unknown';
                const msg = error.response?.data?.message || error.response?.data?.detail || JSON.stringify(error.response?.data?.errors || error.message);
                this.showAlert(`Erreur ${status}: ${msg}`, 'danger');
            } finally {
                this.submitting.plant = false;
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
                abundance: null,
                initial_abundance: null,
                health_status: 'good',
                clone_or_accession: '',
                cultivar: '',
                variety: '',
                is_private: false,
                notes: '',
                anecdotes: '',
                cultural_significance: '',
                ecological_notes: '',
                care_notes: '',
                // GPS fields
                latitude: null,
                longitude: null,
                gps_accuracy: null,
                // Photo optionnelle
                _photoFile: null,
                _photoPreview: null,
            };

            // Clear the file input if it exists
            const fileInput = document.getElementById('plantPhoto');
            if (fileInput) fileInput.value = '';

            // Reset GPS validation and preview
            this.gpsValidation.latitude = null;
            this.gpsValidation.longitude = null;
            this.showGpsPreview = false;

            // Reset selected taxon family
            this.selectedTaxonFamily = null;

            // Reset site autocomplete
            this.siteAutocomplete = { query: '', showDropdown: false, selectedSite: null };

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
        // ── Site Autocomplete ───────────────────────────────────────
        filteredSitesForAutocomplete() {
            const q = (this.siteAutocomplete.query || '').toLowerCase().trim();
            if (!q) return this.sites.slice(0, 20);
            return this.sites.filter(s =>
                (s.name && s.name.toLowerCase().includes(q)) ||
                (s.location && s.location.toLowerCase().includes(q))
            ).slice(0, 20);
        },
        selectSite(site) {
            this.newPlant.site = site.id;
            this.siteAutocomplete.selectedSite = site;
            this.siteAutocomplete.query = site.name;
            this.siteAutocomplete.showDropdown = false;
        },
        clearSiteSelection() {
            this.newPlant.site = null;
            this.siteAutocomplete.selectedSite = null;
            this.siteAutocomplete.query = '';
        },
        closeSiteDropdown() {
            setTimeout(() => { this.siteAutocomplete.showDropdown = false; }, 200);
        },

        // ── Cultivar Search (Wikidata) ────────────────────────────
        openCultivarSearch(target = 'newPlant') {
            this.cultivarSearch.target = target;
            this.cultivarSearch.query = '';
            this.cultivarSearch.results = [];
            this.cultivarSearch.loading = false;
            this.cultivarSearch.showModal = true;

            // Determine the species name for context
            let speciesName = null;
            if (target === 'newPlant' && this.taxonAutocomplete.selectedTaxon) {
                speciesName = this.taxonAutocomplete.selectedTaxon.binomial_name;
            } else if (target === 'editPlant') {
                if (this.taxonAutocompleteEdit?.selectedTaxon) {
                    speciesName = this.taxonAutocompleteEdit.selectedTaxon.binomial_name;
                } else if (this.plantDetail?.plant?.taxon) {
                    speciesName = this.plantDetail.plant.taxon.binomial_name;
                }
            }

            // Store species for the search
            this.cultivarSearch.species = speciesName;
        },
        async searchCultivars() {
            const q = this.cultivarSearch.query.trim();
            if (q.length < 2) return;
            this.cultivarSearch.loading = true;
            this.cultivarSearch.results = [];
            try {
                const params = { query: q };
                if (this.cultivarSearch.species) params.species = this.cultivarSearch.species;
                const { data } = await axios.get('/api/v1/plants/search-cultivars', { params });
                this.cultivarSearch.results = data.results || [];
            } catch (e) {
                console.error('Cultivar search error:', e);
                this.showAlert('Erreur lors de la recherche de cultivars', 'danger');
            }
            this.cultivarSearch.loading = false;
        },
        async selectCultivar(result) {
            const targetKey = this.cultivarSearch.target;
            const cultivarName = result.cultivar_name || result.name || result.cultivar || result.label || '';

            // For local DB cultivars, link via cultivar_id
            // For Wikidata, fetch details as before
            let cultivarInfo = null;
            let cultivarId = result.cultivar_id || null;

            if (result.source === 'wikidata' && result.wikidata_id) {
                try {
                    const { data } = await axios.get('/api/v1/plants/cultivar-details', { params: { wikidata_id: result.wikidata_id } });
                    cultivarInfo = data;
                } catch (e) {
                    console.error('Error fetching cultivar details:', e);
                }
            }

            if (targetKey === 'newPlant') {
                this.newPlant.cultivar = cultivarName;
                if (cultivarId) this.newPlant.cultivar_id = cultivarId;
                if (cultivarInfo) this.newPlant.cultivar_info = cultivarInfo;
                this.cultivarSearch.showModal = false;
                this.showAlert(`Cultivar "${cultivarName}" sélectionné`, 'success');
            } else if (targetKey === 'editPlant') {
                if (this.currentView === 'plant-detail' && this.plantDetail.plant) {
                    try {
                        const payload = { cultivar: cultivarName };
                        if (cultivarId) payload.cultivar_id = cultivarId;
                        if (cultivarInfo) payload.cultivar_info = cultivarInfo;
                        await axios.patch(`/api/v1/plants/${this.plantDetail.plant.id}`, payload);
                        await this.viewPlantDetail(this.plantDetail.plant.id);
                        this.cultivarSearch.showModal = false;
                        this.showAlert(`Cultivar "${cultivarName}" enregistré`, 'success');
                    } catch (e) {
                        this.showAlert('Erreur: ' + (e.response?.data?.message || e.message), 'danger');
                    }
                } else {
                    this.editPlantData.cultivar = cultivarName;
                    if (cultivarId) this.editPlantData.cultivar_id = cultivarId;
                    if (cultivarInfo) this.editPlantData.cultivar_info = cultivarInfo;
                    this.cultivarSearch.showModal = false;
                    this.showAlert(`Cultivar "${cultivarName}" sélectionné`, 'success');
                }
            }
        },

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

        async setIdentificationCertainty(value) {
            if (!this.user.isAuthenticated || !this.plantDetail.plant) return;
            try {
                const response = await axios.patch(`/api/v1/plants/${this.plantDetail.plant.id}`, {
                    identification_certainty: value
                });
                this.plantDetail.plant.identification_certainty = value;
            } catch (error) {
                console.error('Error updating certainty:', error);
                this.showAlert('Erreur lors de la mise à jour de la certitude', 'danger');
            }
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
        async handleNewObservationPhoto(event) {
            const raw = event.target.files[0] || null;
            if (raw) {
                const file = await this._resizeImageIfNeeded(raw);
                this.newObservation._photoFile = file;
                const reader = new FileReader();
                reader.onload = (e) => { this.newObservation._photoPreview = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.newObservation._photoFile = null;
                this.newObservation._photoPreview = null;
            }
        },

        clearNewObservationPhoto() {
            this.newObservation._photoFile = null;
            this.newObservation._photoPreview = null;
            const fileInput = document.getElementById('obsPhoto');
            if (fileInput) fileInput.value = '';
        },

        addObservation() {
            if (this.submitting.observation) return;
            if (!this.user.isAuthenticated) {
                this.showAlert('Fonctionnalité de démonstration - Connectez-vous avec admin/admin123 pour enregistrer réellement', 'info');
                this.showAddObservationModal = false;
                this.resetNewObservationForm();
                return;
            }

            this.submitting.observation = true;
            const photoFile = this.newObservation._photoFile;
            const payload = { ...this.newObservation };
            // Remove non-API fields
            delete payload._photoFile;
            delete payload._photoPreview;
            // Map frontend field names to Laravel _id fields
            if (payload.plant) { payload.plant_id = payload.plant; delete payload.plant; }
            if (payload.phenological_stage) { payload.phenological_stage_id = payload.phenological_stage; delete payload.phenological_stage; }
            if (payload.weather_conditions) { payload.weather_condition = payload.weather_conditions; delete payload.weather_conditions; }
            // Convert empty strings to null
            for (const [key, value] of Object.entries(payload)) {
                if (value === '') payload[key] = null;
            }

            axios.post('/api/v1/observations', payload)
                .then(async response => {
                    console.log('✅ Observation created:', response.data);

                    // Upload photo if provided
                    if (photoFile && response.data.id) {
                        try {
                            const formData = new FormData();
                            formData.append('observation_id', response.data.id);
                            formData.append('image', photoFile);
                            formData.append('photo_type', 'phenological_state');
                            await axios.post('/api/v1/observation-photos', formData, {
                                headers: { 'Content-Type': 'multipart/form-data' },
                            });
                        } catch (photoErr) {
                            console.error('Observation photo upload failed:', photoErr);
                            this.showAlert('Observation créée mais erreur lors de l\'upload de la photo.', 'warning');
                        }
                    }
                    const createdObservationId = response.data.id;

                    this.showAddObservationModal = false;
                    this.showAlert('Observation ajoutée avec succès !', 'success');
                    this.$nextTick(() => {
                        this.resetNewObservationForm();
                    });

                    // If coming from plant detail, stay on plant and reload its data
                    if (this.currentView === 'plant-detail' && this.plantDetail.plant) {
                        await this.viewPlantDetail(this.plantDetail.plant.id);
                    } else {
                        await this.viewObservationDetail(createdObservationId);
                    }
                })
                .catch(error => {
                    console.error('Error adding observation:', error);
                    console.error('Payload was:', payload);
                    console.error('Response data:', error.response?.data);
                    const errors = error.response?.data?.errors;
                    let msg = error.response ? `Erreur ${error.response.status}: ` : error.message;
                    if (errors) {
                        msg += Object.entries(errors).map(([f, msgs]) => `${f}: ${msgs.join(', ')}`).join(' | ');
                    } else {
                        msg += error.response?.data?.message || JSON.stringify(error.response?.data);
                    }
                    this.showAlert(msg, 'danger');
                })
                .finally(() => {
                    this.submitting.observation = false;
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
                is_public: true,
                _photoFile: null,
                _photoPreview: null,
            };
            const fileInput = document.getElementById('obsPhoto');
            if (fileInput) fileInput.value = '';
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
                    // Only update return view if navigating FROM another view (not refreshing current detail)
                    if (this.currentView !== 'observation-detail') {
                        this.observationReturnView = this.currentView;
                    }
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
            const returnTo = this.observationReturnView || 'observations';
            this.observationReturnView = null;
            this.currentObservation = null;
            this.telaComparison = null;

            if (returnTo === 'plant-detail' && this.plantDetail.plant) {
                window.location.hash = '#plant/' + this.plantDetail.plant.id;
                this.currentView = 'plant-detail';
            } else {
                window.location.hash = '#' + returnTo;
                this.currentView = returnTo;
            }
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
                const resizedPhoto = await this._resizeImageIfNeeded(this.photoFile);
                const formData = new FormData();
                formData.append('observation_id', this.newPhoto.observation);
                formData.append('image', resizedPhoto);
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

                    const observationId = this.newPhoto.observation;
                    this.closeUploadPhotoModal();

                    // Reload photos for this observation
                    await this.loadObservationPhotos(observationId);

                    // Refresh observation detail if viewing
                    if (this.currentView === 'observation-detail' &&
                        this.currentObservation?.id === observationId) {
                        await this.viewObservationDetail(observationId);
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
                const resizedPhoto = await this._resizeImageIfNeeded(fileInput.files[0]);
                const formData = new FormData();
                formData.append('image', resizedPhoto);
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

        openEditPhotoModal(photo, context = 'plant') {
            if (!this.user.isAuthenticated) {
                return;
            }
            this.editPhoto = {
                id: photo.id,
                title: photo.title || '',
                description: photo.description || '',
                photo_type: photo.photo_type,
                is_public: photo.is_public !== undefined ? photo.is_public : true,
                _context: context,
            };
            this.showEditPhotoModal = true;
        },

        async updatePhoto() {
            if (!this.user.isAuthenticated) {
                this.showEditPhotoModal = false;
                return;
            }

            this.photoOperationLoading = true;
            const isObservation = this.editPhoto._context === 'observation';
            const endpoint = isObservation
                ? `/api/v1/observation-photos/${this.editPhoto.id}`
                : `/api/v1/plant-photos/${this.editPhoto.id}`;

            try {
                await axios.patch(endpoint, {
                    title: this.editPhoto.title,
                    description: this.editPhoto.description,
                    photo_type: this.editPhoto.photo_type,
                    is_public: this.editPhoto.is_public
                });

                this.showEditPhotoModal = false;
                this.showAlert('Photo mise à jour avec succès !', 'success');

                if (isObservation && this.currentObservation) {
                    await this.loadObservationPhotos(this.currentObservation.id);
                } else if (this.currentView === 'plant-detail' && this.currentPlant) {
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
        
        // Export
        async launchExport() {
            this.exportState = { loading: true, success: false, error: '' };

            try {
                const params = new URLSearchParams();
                params.append('format', this.exportFilters.format);
                params.append('year', this.exportFilters.year || this.analysisYear);

                if (this.exportFilters.site_id) params.append('site_id', this.exportFilters.site_id);
                if (this.exportFilters.category) params.append('category', this.exportFilters.category);
                if (this.exportFilters.status) params.append('status', this.exportFilters.status);
                if (this.exportFilters.taxon) params.append('taxon', this.exportFilters.taxon);

                const response = await axios.get(`/api/v1/export?${params.toString()}`, {
                    responseType: 'blob',
                    timeout: 300000, // 5 minutes max
                });

                // Trigger download
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                const disposition = response.headers['content-disposition'];
                const filename = disposition
                    ? disposition.split('filename=')[1]?.replace(/"/g, '')
                    : `phenolab_export_${new Date().toISOString().slice(0,16).replace('T','_')}.zip`;
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);

                this.exportState.success = true;
                this.exportState.loading = false;
            } catch (error) {
                console.error('Export failed:', error);
                this.exportState.error = error.response?.status === 401
                    ? 'Vous devez etre connecte pour exporter.'
                    : 'Erreur lors de l\'export. Veuillez reessayer.';
                this.exportState.loading = false;
            }
        },

        async launchHugoExport() {
            this.hugoExportState = { loading: true, success: false, error: '' };

            try {
                const response = await axios.get('/api/v1/export/hugo', {
                    responseType: 'blob',
                    timeout: 600000, // 10 minutes max (full site generation)
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                const disposition = response.headers['content-disposition'];
                const filename = disposition
                    ? disposition.split('filename=')[1]?.replace(/"/g, '')
                    : `phenolab_hugo_${new Date().toISOString().slice(0,16).replace('T','_')}.zip`;
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);

                this.hugoExportState.success = true;
                this.hugoExportState.loading = false;
            } catch (error) {
                console.error('Hugo export failed:', error);
                this.hugoExportState.error = error.response?.status === 403
                    ? 'Acces reserve aux super-administrateurs.'
                    : error.response?.status === 401
                    ? 'Vous devez etre connecte.'
                    : 'Erreur lors de la generation du site. Veuillez reessayer.';
                this.hugoExportState.loading = false;
            }
        },

        // Help alert method (kept for backward compat)
        showHelpAlert() {
            this.currentView = 'help';
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
        
        // Get environment label in French (merged: 7 natural + 12 managed)
        getEnvironmentLabel(environment) {
            const labels = {
                // Environnements naturels
                'urban': 'Urbain',
                'suburban': 'Périurbain',
                'rural': 'Rural',
                'forest': 'Forêt',
                'garden': 'Jardin/Parc',
                'natural': 'Naturel',
                'agricultural': 'Agricole',
                // Types de site amenages
                'botanical_garden': 'Jardin botanique',
                'arboretum': 'Arboretum',
                'nursery': 'Pepiniere',
                'orchard': 'Verger',
                'vegetable_garden': 'Potager',
                'park': 'Parc public',
                'private_garden': 'Jardin prive',
                'school_garden': 'Jardin pedagogique',
                'community_garden': 'Jardin partage',
                'experimental': 'Parcelle experimentale',
                'natural_reserve': 'Reserve naturelle',
                'other': 'Autre'
            };
            return labels[environment] || environment;
        },

        // ===== CULTIVATION PROFILE HELPERS =====
        getExposureLabel(key) { return this.exposureOptions[key] || key; },
        getWateringLabel(key) { return this.wateringNeedsOptions[key] || key; },
        getDifficultyLabel(key) { return this.difficultyOptions[key] || key; },
        getSoilTypeLabel(key) { return this.soilTypeOptions[key] || key; },
        getSoilDrainageLabel(key) { return this.soilDrainageOptions[key] || key; },
        getSoilFertilityLabel(key) { return this.soilFertilityOptions[key] || key; },
        getUsageTypeLabel(key) { return this.usageTypeOptions[key] || key; },

        formatMonths(months) {
            if (!Array.isArray(months) || months.length === 0) return '';
            const sorted = months.slice().map(Number).sort((a, b) => a - b);
            return sorted.map(m => this.monthOptions.find(opt => opt.value === m)?.label || m).join(', ');
        },

        formatRange(min, max, unit) {
            const parts = [];
            if (min != null && min !== '') parts.push(min);
            if (max != null && max !== '' && max !== min) parts.push(max);
            if (parts.length === 0) return '';
            return parts.join('–') + (unit ? ' ' + unit : '');
        },

        parseTagList(value) {
            if (!value) return [];
            return value.split(',').map(s => s.trim()).filter(Boolean);
        },

        cultivationHasWhenData(p) {
            if (!p) return false;
            return ['planting_months', 'sowing_months', 'flowering_months', 'harvest_months']
                .some(k => Array.isArray(p[k]) && p[k].length > 0);
        },
        cultivationHasWhereData(p) {
            if (!p) return false;
            const keys = ['exposure', 'hardiness_min', 'usda_zone', 'soil_ph', 'soil_drainage',
                'soil_fertility', 'mature_height_min', 'mature_height_max',
                'mature_spread_min', 'mature_spread_max'];
            if (keys.some(k => p[k] != null && p[k] !== '')) return true;
            return ['suitable_environments', 'soil_types'].some(k => Array.isArray(p[k]) && p[k].length > 0);
        },
        cultivationHasCareData(p) {
            if (!p) return false;
            const keys = ['watering_needs', 'cultivation_difficulty', 'fertilizing_frequency',
                'pruning_period', 'mulching', 'winter_protection', 'propagation_methods',
                'watering_notes', 'fertilizing_notes', 'pruning_notes',
                'pest_susceptibility', 'disease_susceptibility'];
            if (keys.some(k => p[k] != null && p[k] !== '')) return true;
            if (['companion_plants', 'avoid_near', 'usage_types'].some(k => Array.isArray(p[k]) && p[k].length > 0)) return true;
            return p.is_edible || p.is_toxic;
        },

        openCultivationModal(plant) {
            if (!plant) return;
            const existing = plant.cultivation_profile || {};
            this.cultivationForm = {
                plantId: plant.id,
                plantName: plant.name || '',
                planting_months: Array.isArray(existing.planting_months) ? existing.planting_months.map(Number) : [],
                sowing_months: Array.isArray(existing.sowing_months) ? existing.sowing_months.map(Number) : [],
                harvest_months: Array.isArray(existing.harvest_months) ? existing.harvest_months.map(Number) : [],
                flowering_months: Array.isArray(existing.flowering_months) ? existing.flowering_months.map(Number) : [],
                exposure: existing.exposure || null,
                hardiness_min: existing.hardiness_min || '',
                usda_zone: existing.usda_zone || '',
                suitable_environments: Array.isArray(existing.suitable_environments) ? existing.suitable_environments : [],
                soil_types: Array.isArray(existing.soil_types) ? existing.soil_types : [],
                soil_ph: existing.soil_ph || '',
                soil_drainage: existing.soil_drainage || null,
                soil_fertility: existing.soil_fertility || null,
                mature_height_min: existing.mature_height_min ?? null,
                mature_height_max: existing.mature_height_max ?? null,
                mature_spread_min: existing.mature_spread_min ?? null,
                mature_spread_max: existing.mature_spread_max ?? null,
                watering_needs: existing.watering_needs || null,
                watering_notes: existing.watering_notes || '',
                fertilizing_frequency: existing.fertilizing_frequency || '',
                fertilizing_notes: existing.fertilizing_notes || '',
                pruning_period: existing.pruning_period || '',
                pruning_notes: existing.pruning_notes || '',
                mulching: existing.mulching || '',
                winter_protection: existing.winter_protection || '',
                pest_susceptibility: existing.pest_susceptibility || '',
                disease_susceptibility: existing.disease_susceptibility || '',
                companion_plants: Array.isArray(existing.companion_plants) ? existing.companion_plants.join(', ') : '',
                avoid_near: Array.isArray(existing.avoid_near) ? existing.avoid_near.join(', ') : '',
                propagation_methods: existing.propagation_methods || '',
                cultivation_difficulty: existing.cultivation_difficulty || null,
                usage_types: Array.isArray(existing.usage_types) ? existing.usage_types : [],
                is_edible: !!existing.is_edible,
                is_toxic: !!existing.is_toxic,
                notes: existing.notes || '',
                source: existing.source || '',
            };
            this.showCultivationModal = true;
        },

        async submitCultivationProfile() {
            if (!this.cultivationForm.plantId) return;
            this.cultivationFormSaving = true;
            try {
                const payload = { ...this.cultivationForm };
                delete payload.plantId;
                delete payload.plantName;
                // Convert comma-separated strings to arrays
                payload.companion_plants = this.parseTagList(payload.companion_plants);
                payload.avoid_near = this.parseTagList(payload.avoid_near);
                // strip empty strings to allow backend nullable validation
                Object.keys(payload).forEach(k => {
                    if (payload[k] === '') payload[k] = null;
                });
                if (payload.companion_plants && payload.companion_plants.length === 0) payload.companion_plants = null;
                if (payload.avoid_near && payload.avoid_near.length === 0) payload.avoid_near = null;
                const response = await axios.put(`/api/v1/plants/${this.cultivationForm.plantId}/cultivation-profile`, payload);
                if (this.plantDetail.plant && this.plantDetail.plant.id === this.cultivationForm.plantId) {
                    this.plantDetail.plant.cultivation_profile = response.data;
                }
                this.showAlert('Conditions de culture enregistrées', 'success');
                this.showCultivationModal = false;
            } catch (error) {
                const msg = error.response?.data?.message
                    || (error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join(', ') : null)
                    || 'Erreur lors de l\'enregistrement';
                this.showAlert(msg, 'danger');
            } finally {
                this.cultivationFormSaving = false;
            }
        },

        async deleteCultivationProfile(plant) {
            if (!plant?.id) return;
            if (!confirm('Supprimer les conditions de culture pour cette plante ?')) return;
            try {
                await axios.delete(`/api/v1/plants/${plant.id}/cultivation-profile`);
                if (this.plantDetail.plant && this.plantDetail.plant.id === plant.id) {
                    this.plantDetail.plant.cultivation_profile = null;
                }
                this.showAlert('Conditions de culture supprimées', 'success');
            } catch (error) {
                this.showAlert(error.response?.data?.message || 'Erreur lors de la suppression', 'danger');
            }
        },

        // Resolve a user-defined SiteCategory by id from the loaded list.
        getSiteCategoryById(id) {
            if (id == null) return null;
            const intId = typeof id === 'string' ? parseInt(id, 10) : id;
            return this.siteCategories.find(c => c.id === intId) || null;
        },

        // Display label for a site category id (uses breadcrumb if available).
        getSiteCategoryLabel(id) {
            const cat = this.getSiteCategoryById(id);
            return cat ? (cat.breadcrumb || cat.name) : '';
        },

        // Return [id, ...descendantIds] for hierarchical filtering.
        siteCategoryDescendantIds(rootId) {
            const ids = [rootId];
            const queue = [rootId];
            while (queue.length) {
                const current = queue.shift();
                this.siteCategories
                    .filter(c => c.parent_id === current)
                    .forEach(child => {
                        if (!ids.includes(child.id)) {
                            ids.push(child.id);
                            queue.push(child.id);
                        }
                    });
            }
            return ids;
        },

        // ── Site Categories: load + CRUD ────────────────────────────
        async loadSiteCategories() {
            this.siteCategoriesLoading = true;
            try {
                const response = await axios.get('/api/v1/site-categories', {
                    params: { ordering: 'sort_order' }
                });
                const data = response.data;
                this.siteCategories = Array.isArray(data) ? data : (data?.data || []);
            } catch (error) {
                console.error('loadSiteCategories failed', error);
                this.siteCategories = [];
            } finally {
                this.siteCategoriesLoading = false;
            }
        },

        resetAdminSiteCategoryForm() {
            this.adminSiteCategoryForm = {
                id: null, name: '', slug: '', description: '',
                icon: '', color: '', parent_id: null, sort_order: 0, is_active: true,
            };
        },

        editAdminSiteCategory(cat) {
            this.adminSiteCategoryForm = {
                id: cat.id,
                name: cat.name || '',
                slug: cat.slug || '',
                description: cat.description || '',
                icon: cat.icon || '',
                color: cat.color || '',
                parent_id: cat.parent_id || null,
                sort_order: cat.sort_order || 0,
                is_active: cat.is_active !== false,
            };
        },

        async submitAdminSiteCategory() {
            const payload = { ...this.adminSiteCategoryForm };
            const id = payload.id;
            delete payload.id;

            // Drop empty slug so the server auto-generates it.
            if (!payload.slug) delete payload.slug;

            try {
                if (id) {
                    await axios.put(`/api/v1/site-categories/${id}`, payload);
                    this.showAlert('Lieu mis a jour.', 'success');
                } else {
                    await axios.post('/api/v1/site-categories', payload);
                    this.showAlert('Lieu cree.', 'success');
                }
                this.resetAdminSiteCategoryForm();
                await this.loadSiteCategories();
            } catch (error) {
                console.error('submitAdminSiteCategory failed', error);
                if (error.response?.data?.errors) {
                    const msgs = Object.values(error.response.data.errors).flat().join(', ');
                    this.showAlert(msgs, 'danger');
                } else {
                    this.showAlert(error.response?.data?.message || 'Erreur lors de l\'enregistrement.', 'danger');
                }
            }
        },

        async deleteAdminSiteCategory(cat) {
            if (!confirm(`Supprimer le lieu "${cat.name}" ?`)) return;
            try {
                await axios.delete(`/api/v1/site-categories/${cat.id}`);
                this.showAlert('Lieu supprime.', 'success');
                await this.loadSiteCategories();
            } catch (error) {
                console.error('deleteAdminSiteCategory failed', error);
                this.showAlert(error.response?.data?.message || 'Suppression impossible.', 'danger');
            }
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
        getCategoryIcon(plant) {
            if (plant?.category?.icon) {
                return 'fas ' + plant.category.icon;
            }
            const fallback = {
                'trees': 'fas fa-tree',
                'shrubs': 'fas fa-leaf',
                'plants': 'fas fa-seedling',
                'animals': 'fas fa-paw',
                'insects': 'fas fa-bug',
            };
            return fallback[plant?.category?.category_type] || 'fas fa-seedling';
        },

        getCategoryColor(plant) {
            const colors = {
                'trees': 'text-success',
                'shrubs': 'text-info',
                'plants': 'text-primary',
                'animals': 'text-warning',
                'insects': 'text-danger',
            };
            return colors[plant?.category?.category_type] || 'text-success';
        },

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
                abundance: plant.abundance || null,
                initial_abundance: plant.initial_abundance || null,
                health_status: plant.health_status || 'good',
                identification_certainty: plant.identification_certainty || 'certain',
                clone_or_accession: plant.clone_or_accession || '',
                cultivar: plant.cultivar || '',
                variety: plant.variety || '',
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
                    abundance: this.editPlantData.abundance ? parseInt(this.editPlantData.abundance) : null,
                    initial_abundance: this.editPlantData.initial_abundance ? parseInt(this.editPlantData.initial_abundance) : null,
                    health_status: this.editPlantData.health_status,
                    identification_certainty: this.editPlantData.identification_certainty || 'certain',
                    clone_or_accession: this.editPlantData.clone_or_accession || null,
                    cultivar: this.editPlantData.cultivar || null,
                    variety: this.editPlantData.variety || null,
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
            if (!navigator.geolocation || !window.isSecureContext) {
                this.showAlert(!window.isSecureContext
                    ? 'Géolocalisation indisponible : HTTPS requis sur mobile.'
                    : 'Géolocalisation non supportée', 'warning');
                return;
            }
            if (navigator.permissions) {
                try {
                    const perm = await navigator.permissions.query({ name: 'geolocation' });
                    if (perm.state === 'denied') {
                        this.showAlert('Géolocalisation bloquée. Appuyez sur le cadenas 🔒 → Autorisations → Position → Autoriser, puis rechargez.', 'warning');
                        return;
                    }
                } catch (e) { /* proceed */ }
            }
            
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        resolve,
                        reject,
                        {
                            enableHighAccuracy: true,
                            timeout: 30000,
                            maximumAge: 60000
                        }
                    );
                });

                Object.assign(this.newPlant, {
                    latitude: position.coords.latitude.toFixed(6),
                    longitude: position.coords.longitude.toFixed(6),
                    gps_accuracy: position.coords.accuracy ? position.coords.accuracy.toFixed(1) : null,
                });

                this.validateGpsCoordinates();
                this.showAlert(
                    `Position obtenue (précision ~${position.coords.accuracy ? position.coords.accuracy.toFixed(0) : '?'} m)`,
                    'success'
                );

            } catch (error) {
                console.error('Erreur géolocalisation:', error);
                let msg;
                switch (error.code) {
                    case 1: msg = 'Géolocalisation refusée. Autorisez l\'accès dans les paramètres du navigateur.'; break;
                    case 2: msg = 'Position indisponible. Vérifiez que le GPS est activé.'; break;
                    case 3: msg = 'Délai GPS dépassé. Réessayez dehors ou dans un endroit dégagé.'; break;
                    default: msg = 'Impossible d\'obtenir la position.';
                }
                this.showAlert(msg, 'danger');
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

                // Ensure the DOM element is actually available (v-if timing)
                let mapEl = document.getElementById('generalMap');
                if (!mapEl) {
                    await new Promise(r => setTimeout(r, 100));
                    await this.$nextTick();
                    mapEl = document.getElementById('generalMap');
                }
                if (!mapEl) return;

                // Destroy stale map instance if its container was removed by v-if
                if (this.generalMap) {
                    const oldContainer = this.generalMap.getContainer();
                    if (!oldContainer || !document.body.contains(oldContainer)) {
                        this.generalMap.remove();
                        this.generalMap = null;
                        this.mapLayers = { sites: null, plants: null };
                    }
                }

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

                // Force Leaflet to recalculate container size after render
                this.generalMap.invalidateSize();
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
                    const lat = parseFloat(site.latitude), lng = parseFloat(site.longitude);
                    if (isFinite(lat) && isFinite(lng)) bounds.push([lat, lng]);
                });
            }

            if (this.mapViewMode === 'plants' || this.mapViewMode === 'both') {
                this.plants.forEach(plant => {
                    const lat = parseFloat(plant.latitude), lng = parseFloat(plant.longitude);
                    if (isFinite(lat) && isFinite(lng)) bounds.push([lat, lng]);
                });
            }
            
            if (bounds.length > 0) {
                this.generalMap.fitBounds(bounds, { padding: [20, 20] });
            } else {
                // Default to Nice, France if no data
                this.generalMap.setView([43.7102, 7.2620], 10);
            }
        },
        
        async refreshMapData() {
            this.selectedMapItem = null;
            try {
                await this.loadMapData();
                this.updateMapLayersWithClustering();
                this.centerMapOnData();
            } catch (error) {
                console.error('Error refreshing map data:', error);
                this.showAlert('Erreur lors du rafraîchissement de la carte', 'error');
            }
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
                    const lat = parseFloat(site.latitude), lng = parseFloat(site.longitude);
                    if (isFinite(lat) && isFinite(lng)) {
                        site.latitude = lat; site.longitude = lng;
                        const marker = L.marker([lat, lng], {
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
                const plantsWithGps = this.plants.filter(plant => {
                    const lat = parseFloat(plant.latitude);
                    const lng = parseFloat(plant.longitude);
                    return isFinite(lat) && isFinite(lng);
                }).map(p => ({ ...p, latitude: parseFloat(p.latitude), longitude: parseFloat(p.longitude) }));
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

        // ── Action Types Admin ──

        async loadActionTypes() {
            this.admin.loading = true;
            try {
                const { data } = await axios.get('/api/v1/plant-action-types/admin');
                this.admin.actionTypes = data;
            } catch (e) {
                this.setAdminMessage('Erreur chargement types d\'actions', 'danger');
            }
            this.admin.loading = false;
        },

        async seedActionTypes() {
            if (!confirm('Charger les 21 types d\'actions par défaut ?')) return;
            this.admin.loading = true;
            try {
                await this.ensureCsrf();
                const { data } = await axios.post('/api/v1/admin/seed-action-types');
                this.setAdminMessage(data.message || 'Types d\'actions chargés', 'success');
                this.loadActionTypes();
                this.loadAdminDashboard();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur', 'danger');
            }
            this.admin.loading = false;
        },

        resetActionTypeForm() {
            this.admin.newActionType = { name: '', slug: '', description: '', category: 'maintenance', icon: '', color: 'bg-secondary', applies_to: 'all', is_active: true, sort_order: 0 };
            this.admin.editingActionType = null;
        },

        editActionType(type) {
            this.admin.editingActionType = type.id;
            this.admin.newActionType = {
                name: type.name,
                slug: type.slug || '',
                description: type.description || '',
                category: type.category,
                icon: type.icon || '',
                color: type.color || 'bg-secondary',
                applies_to: type.applies_to || 'all',
                is_active: type.is_active !== false,
                sort_order: type.sort_order || 0,
            };
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async submitActionType() {
            const form = this.admin.newActionType;
            if (!form.name.trim()) {
                this.setAdminMessage('Le nom est obligatoire.', 'warning');
                return;
            }
            this.admin.loading = true;
            try {
                await this.ensureCsrf();
                if (this.admin.editingActionType) {
                    await axios.put('/api/v1/plant-action-types/' + this.admin.editingActionType, form);
                    this.setAdminMessage('Type d\'action mis à jour.', 'success');
                } else {
                    await axios.post('/api/v1/plant-action-types', form);
                    this.setAdminMessage('Type d\'action créé.', 'success');
                }
                this.resetActionTypeForm();
                this.loadActionTypes();
            } catch (e) {
                const msg = e.response?.data?.message || e.response?.data?.errors?.name?.[0] || 'Erreur';
                this.setAdminMessage(msg, 'danger');
            }
            this.admin.loading = false;
        },

        async deleteActionType(type) {
            if (!confirm('Supprimer le type "' + type.name + '" ?')) return;
            this.admin.loading = true;
            try {
                await this.ensureCsrf();
                await axios.delete('/api/v1/plant-action-types/' + type.id);
                this.setAdminMessage('Type supprimé.', 'success');
                this.loadActionTypes();
            } catch (e) {
                this.setAdminMessage(e.response?.data?.message || 'Erreur lors de la suppression', 'danger');
            }
            this.admin.loading = false;
        },

        // ── User Plant Tags ──

        async loadUserTags() {
            if (!this.user.isAuthenticated) return;
            try {
                const { data } = await axios.get('/api/v1/tags');
                this.userTags = data;
            } catch (e) {
                console.error('Error loading tags:', e);
            }
        },

        async loadPlantTags(plantId) {
            if (!this.user.isAuthenticated) { this.plantTags = []; return; }
            try {
                const { data } = await axios.get('/api/v1/tags/plant/' + plantId);
                this.plantTags = data;
            } catch (e) {
                this.plantTags = [];
            }
        },

        async createTag() {
            const form = this.newTagForm;
            if (!form.name.trim()) return;
            try {
                await this.ensureCsrf();
                const { data } = await axios.post('/api/v1/tags', {
                    name: form.name.trim(),
                    color: form.color || 'secondary',
                    group_id: form.group_id || null,
                });
                this.userTags.push(data);
                this.newTagForm = { name: '', color: 'secondary', group_id: null };
                this.showTagModal = false;
                this.showAlert('Tag "' + data.name + '" créé.', 'success');
            } catch (e) {
                this.showAlert(e.response?.data?.message || 'Erreur création tag', 'danger');
            }
        },

        async deleteTag(tag) {
            if (!confirm('Supprimer le tag "' + tag.name + '" et toutes ses affectations ?')) return;
            try {
                await this.ensureCsrf();
                await axios.delete('/api/v1/tags/' + tag.id);
                this.userTags = this.userTags.filter(t => t.id !== tag.id);
                this.plantTags = this.plantTags.filter(t => t.id !== tag.id);
                this.showAlert('Tag supprimé.', 'info');
            } catch (e) {
                this.showAlert(e.response?.data?.message || 'Erreur', 'danger');
            }
        },

        startEditTag(tag) {
            this.editingTag = { id: tag.id, name: tag.name, color: tag.color, group_id: tag.group_id };
        },

        cancelEditTag() {
            this.editingTag = null;
        },

        async updateTag() {
            if (!this.editingTag || !this.editingTag.name.trim()) return;
            try {
                await this.ensureCsrf();
                const { data } = await axios.put('/api/v1/tags/' + this.editingTag.id, {
                    name: this.editingTag.name.trim(),
                    color: this.editingTag.color,
                    group_id: this.editingTag.group_id || null,
                });
                const idx = this.userTags.findIndex(t => t.id === data.id);
                if (idx !== -1) this.userTags.splice(idx, 1, data);
                this.editingTag = null;
                this.showAlert('Tag mis à jour.', 'success');
            } catch (e) {
                this.showAlert(e.response?.data?.message || 'Erreur modification tag', 'danger');
            }
        },

        async assignTag(plantId, tagId) {
            try {
                await this.ensureCsrf();
                await axios.post('/api/v1/tags/assign', { plant_id: plantId, tag_id: tagId });
                await this.loadPlantTags(plantId);
                await this.loadUserTags();
            } catch (e) {
                this.showAlert(e.response?.data?.message || 'Erreur', 'danger');
            }
        },

        async unassignTag(plantId, tagId) {
            try {
                await this.ensureCsrf();
                await axios.post('/api/v1/tags/unassign', { plant_id: plantId, tag_id: tagId });
                this.plantTags = this.plantTags.filter(t => t.id !== tagId);
                await this.loadUserTags();
            } catch (e) {
                this.showAlert(e.response?.data?.message || 'Erreur', 'danger');
            }
        },

        availableTagsForPlant() {
            const assignedIds = this.plantTags.map(t => t.id);
            return this.userTags.filter(t => !assignedIds.includes(t.id));
        },

        // Plant picker: API-based search across ALL plants
        async searchPlantsForPicker() {
            this.plantPicker.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.plantPicker.query) params.append('search', this.plantPicker.query);
                if (this.plantPicker.siteFilter) params.append('site', this.plantPicker.siteFilter);
                params.append('per_page', '40');
                params.append('ordering', 'name');
                const response = await axios.get(`/api/v1/plants/?${params.toString()}`);
                this.plantPicker.results = this.extractCollection(response.data);
                this.plantPicker.totalCount = response.data.total ?? response.data.count ?? this.plantPicker.results.length;
            } catch (error) {
                console.error('Plant picker search error:', error);
                this.plantPicker.results = [];
                this.plantPicker.totalCount = 0;
            } finally {
                this.plantPicker.loading = false;
            }
        },

        // Debounced search triggered on input change
        onPlantPickerInput() {
            clearTimeout(this.plantPicker.debounceTimer);
            this.plantPicker.debounceTimer = setTimeout(() => {
                this.searchPlantsForPicker();
            }, 300);
        },

        // Triggered when site filter changes
        onPlantPickerSiteChange() {
            this.searchPlantsForPicker();
        },

        resetPlantPicker() {
            this.plantPicker.query = '';
            this.plantPicker.siteFilter = '';
            this.plantPicker.results = [];
            this.plantPicker.totalCount = 0;
            clearTimeout(this.plantPicker.debounceTimer);
        },

        selectPlantFromPicker(plant, target) {
            if (target === 'observation') {
                this.newObservation.plant = plant.id;
            } else if (target === 'photo') {
                this.newPhoto.plant = plant.id;
            }
            // Store selected plant in plants array so the display can find it
            if (!this.plants.find(p => p.id === plant.id)) {
                this.plants.push(plant);
            }
        },

        clearPlantSelection(target) {
            if (target === 'observation') {
                this.newObservation.plant = null;
            } else if (target === 'photo') {
                this.newPhoto.plant = null;
            }
            this.resetPlantPicker();
            this.searchPlantsForPicker();
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

            let url;
            if (this.admin.importType === 'cultivars') {
                url = '/api/v1/admin/import-cultivars';
                formData.append('file', this.admin.importFile);
                if (this.admin.importClear) formData.append('clear', '1');
            } else {
                url = this.admin.importType === 'ods' ? '/api/v1/admin/import-ods' : '/api/v1/admin/import-tela';
                formData.append('csv_file', this.admin.importFile);
                if (this.admin.importClear) formData.append('clear', '1');
            }
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
