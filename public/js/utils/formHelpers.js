/**
 * PhenoLab Form Helpers
 *
 * Reusable utilities for form handling, modal management, and data validation.
 * Eliminates duplicated form reset and modal logic.
 *
 * @author PhenoLab Team
 * @version 1.0.0
 */

class FormHelpers {
    /**
     * Default form templates
     */
    static FORM_DEFAULTS = {
        site: {
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

        plant: {
            name: '',
            description: '',
            taxon: null,
            category: null,
            site: null,
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
            latitude: null,
            longitude: null,
            gps_accuracy: null
        },

        observation: {
            plant: null,
            phenological_stage: null,
            observation_date: new Date().toISOString().split('T')[0],
            intensity: 1,
            notes: '',
            weather_conditions: '',
            temperature: null,
            is_public: true
        },

        photo: {
            plant: null,
            title: '',
            description: '',
            photo_type: 'general',
            is_public: true
        },

        taxon: {
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
        }
    };

    /**
     * Reset form to default values
     *
     * @param {Object} context - Vue component context (this)
     * @param {string} formName - Form name (e.g., 'newSite', 'editSite')
     * @param {string} formType - Form type from FORM_DEFAULTS (e.g., 'site', 'plant')
     * @returns {Object} Reset form data
     */
    static resetForm(context, formName, formType) {
        const defaults = this.FORM_DEFAULTS[formType];

        if (!defaults) {
            console.error(`Unknown form type: ${formType}`);
            return {};
        }

        // Deep clone defaults to avoid reference issues
        const resetData = JSON.parse(JSON.stringify(defaults));

        // Update observation date to today if it's an observation form
        if (formType === 'observation') {
            resetData.observation_date = new Date().toISOString().split('T')[0];
        }

        // Update context
        context[formName] = resetData;

        // Reset associated state if exists
        if (formType === 'plant') {
            if (context.gpsValidation) {
                context.gpsValidation.latitude = null;
                context.gpsValidation.longitude = null;
            }
            if (context.showGpsPreview) {
                context.showGpsPreview = false;
            }
            if (context.gpsMap) {
                context.gpsMap.remove();
                context.gpsMap = null;
            }
        }

        // Reset file inputs if any
        if (formType === 'photo') {
            const fileInput = document.getElementById('photo-file');
            if (fileInput) {
                fileInput.value = '';
            }
        }

        return resetData;
    }

    /**
     * Open modal with proper Bootstrap handling
     *
     * @param {string} modalId - Modal DOM element ID
     * @param {Object} context - Vue component context (optional)
     */
    static openModal(modalId, context) {
        // Close all modals first
        this.closeAllModals();

        // Wait for Vue to update DOM
        const openModalInstance = () => {
            const modalElement = document.getElementById(modalId);

            if (!modalElement) {
                console.error(`Modal not found: ${modalId}`);
                return;
            }

            // Add body class for modal state
            document.body.classList.add('modal-open');

            // Get or create Bootstrap modal instance
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();
        };

        if (context && context.$nextTick) {
            context.$nextTick(openModalInstance);
        } else {
            setTimeout(openModalInstance, 10);
        }
    }

    /**
     * Close all modals and clean up
     */
    static closeAllModals() {
        // Remove any lingering modal backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());

        // Reset body styling
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        // Hide all modals
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });

        // Force cleanup after a short delay
        setTimeout(() => {
            document.body.classList.remove('modal-open');
            const remainingBackdrops = document.querySelectorAll('.modal-backdrop');
            remainingBackdrops.forEach(backdrop => backdrop.remove());
        }, 100);
    }

    /**
     * Validate required fields in form
     *
     * @param {Object} formData - Form data object
     * @param {Array<string>} requiredFields - Array of required field names
     * @returns {Object} Validation result { valid: boolean, missing: Array<string> }
     */
    static validateRequired(formData, requiredFields) {
        const missing = [];

        for (const field of requiredFields) {
            const value = formData[field];

            if (value === null || value === undefined || value === '') {
                missing.push(field);
            }
        }

        return {
            valid: missing.length === 0,
            missing
        };
    }

    /**
     * Validate GPS coordinates
     *
     * @param {number} latitude - Latitude value
     * @param {number} longitude - Longitude value
     * @returns {Object} Validation result { valid: boolean, errors: Object }
     */
    static validateGPS(latitude, longitude) {
        const errors = {};
        let valid = true;

        // Validate latitude
        const lat = parseFloat(latitude);
        if (isNaN(lat)) {
            errors.latitude = 'Latitude doit être un nombre';
            valid = false;
        } else if (lat < -90 || lat > 90) {
            errors.latitude = 'Latitude doit être entre -90 et 90';
            valid = false;
        }

        // Validate longitude
        const lng = parseFloat(longitude);
        if (isNaN(lng)) {
            errors.longitude = 'Longitude doit être un nombre';
            valid = false;
        } else if (lng < -180 || lng > 180) {
            errors.longitude = 'Longitude doit être entre -180 et 180';
            valid = false;
        }

        return { valid, errors };
    }

    /**
     * Format form data for API submission (Site)
     *
     * @param {Object} formData - Form data with latitude/longitude
     * @returns {Object} Formatted data with GeoJSON location
     */
    static formatSiteData(formData) {
        const apiData = { ...formData };

        // Convert lat/lng to GeoJSON Point
        if (apiData.latitude && apiData.longitude) {
            apiData.location = {
                type: 'Point',
                coordinates: [apiData.longitude, apiData.latitude]
            };

            // Remove individual lat/lng (handled by location field)
            delete apiData.latitude;
            delete apiData.longitude;
        }

        return apiData;
    }

    /**
     * Format form data for API submission (Plant)
     *
     * @param {Object} formData - Form data with GPS coordinates
     * @returns {Object} Formatted data with GeoJSON location
     */
    static formatPlantData(formData) {
        const apiData = { ...formData };

        // Convert lat/lng to GeoJSON Point
        if (apiData.latitude && apiData.longitude) {
            apiData.location = {
                type: 'Point',
                coordinates: [apiData.longitude, apiData.latitude]
            };

            // Remove individual lat/lng
            delete apiData.latitude;
            delete apiData.longitude;
        }

        return apiData;
    }

    /**
     * Create watcher for modal visibility
     *
     * @param {string} modalId - Modal DOM element ID
     * @returns {Function} Watcher function for Vue
     */
    static createModalWatcher(modalId) {
        return function(show) {
            if (show) {
                FormHelpers.openModal(modalId, this);
            }
        };
    }

    /**
     * Get current location using browser geolocation API
     *
     * @param {Object} options - Geolocation options
     * @returns {Promise<Object>} Position { latitude, longitude, accuracy }
     */
    static getCurrentLocation(options = {}) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Géolocalisation non supportée'));
                return;
            }

            const defaultOptions = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000,
                ...options
            };

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude.toFixed(6),
                        longitude: position.coords.longitude.toFixed(6),
                        accuracy: position.coords.accuracy ? position.coords.accuracy.toFixed(1) : null
                    });
                },
                (error) => {
                    reject(error);
                },
                defaultOptions
            );
        });
    }

    /**
     * Format date for display
     *
     * @param {string|Date} date - Date to format
     * @param {string} locale - Locale (default: 'fr-FR')
     * @returns {string} Formatted date
     */
    static formatDate(date, locale = 'fr-FR') {
        if (!date) return 'Non définie';

        const dateObj = typeof date === 'string' ? new Date(date) : date;

        return dateObj.toLocaleDateString(locale, {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    /**
     * Format number with locale-specific formatting
     *
     * @param {number} num - Number to format
     * @param {string} locale - Locale (default: 'fr-FR')
     * @returns {string} Formatted number
     */
    static formatNumber(num, locale = 'fr-FR') {
        return new Intl.NumberFormat(locale).format(num);
    }

    /**
     * Debounce function calls
     *
     * @param {Function} func - Function to debounce
     * @param {number} wait - Milliseconds to wait
     * @returns {Function} Debounced function
     */
    static debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FormHelpers };
}
