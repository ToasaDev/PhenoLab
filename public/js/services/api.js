/**
 * PhenoLab API Client
 *
 * Centralized API service to replace scattered axios calls.
 * Provides type-safe methods, consistent error handling, and request/response interceptors.
 *
 * @author PhenoLab Team
 * @version 1.0.0
 */

class ApiClient {
    /**
     * Initialize API client with base configuration
     * @param {string} baseURL - Base API URL (default: '/api/v1')
     */
    constructor(baseURL = '/api/v1') {
        this.baseURL = baseURL;

        // Create axios instance with default config
        this.client = axios.create({
            baseURL: baseURL,
            timeout: 15000,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        // Add response interceptor for consistent error handling
        this.client.interceptors.response.use(
            response => response,
            error => {
                // Transform axios error to standardized format
                return Promise.reject(this._transformError(error));
            }
        );

        // Let Sanctum / Axios use the XSRF-TOKEN cookie instead of a stale meta token.
        this.client.interceptors.request.use(config => {
            config.headers['X-Requested-With'] = 'XMLHttpRequest';
            config.withCredentials = true;
            return config;
        });
    }

    /**
     * Transform axios error to standardized error object
     * @param {Error} error - Axios error object
     * @returns {Object} Standardized error
     * @private
     */
    _transformError(error) {
        const standardError = {
            originalError: error,
            timestamp: new Date().toISOString()
        };

        if (error.response) {
            // Server responded with error status
            standardError.type = 'server';
            standardError.status = error.response.status;
            standardError.data = error.response.data;

            switch (error.response.status) {
                case 400:
                    standardError.code = 'VALIDATION_ERROR';
                    standardError.message = 'Validation échouée';
                    standardError.validationErrors = error.response.data;
                    break;
                case 401:
                    standardError.code = 'UNAUTHORIZED';
                    standardError.message = 'Non authentifié';
                    standardError.authRequired = true;
                    break;
                case 403:
                    standardError.code = 'FORBIDDEN';
                    standardError.message = 'Permission refusée';
                    standardError.forbidden = true;
                    break;
                case 404:
                    standardError.code = 'NOT_FOUND';
                    standardError.message = 'Ressource non trouvée';
                    break;
                case 500:
                    standardError.code = 'SERVER_ERROR';
                    standardError.message = 'Erreur serveur interne';
                    break;
                default:
                    standardError.code = 'UNKNOWN_SERVER_ERROR';
                    standardError.message = `Erreur serveur (${error.response.status})`;
            }
        } else if (error.request) {
            // Request made but no response received
            standardError.type = 'network';
            standardError.code = 'NETWORK_ERROR';
            standardError.message = 'Impossible de contacter le serveur';
            standardError.networkError = true;
        } else {
            // Error in request configuration
            standardError.type = 'client';
            standardError.code = 'CLIENT_ERROR';
            standardError.message = 'Erreur de configuration de la requête';
        }

        return standardError;
    }

    /**
     * Sites API endpoints
     */
    sites = {
        /**
         * List all sites
         * @returns {Promise<Array>}
         */
        list: () => this.client.get('/sites'),

        /**
         * Get site by ID
         * @param {number} id - Site ID
         * @returns {Promise<Object>}
         */
        get: (id) => this.client.get(`/sites/${id}`),

        /**
         * Create new site
         * @param {Object} data - Site data
         * @returns {Promise<Object>}
         */
        create: (data) => this.client.post('/sites', data),

        /**
         * Update existing site
         * @param {number} id - Site ID
         * @param {Object} data - Updated site data
         * @returns {Promise<Object>}
         */
        update: (id, data) => this.client.put(`/sites/${id}`, data),

        /**
         * Delete site
         * @param {number} id - Site ID
         * @returns {Promise<void>}
         */
        delete: (id) => this.client.delete(`/sites/${id}`),

        /**
         * Get site statistics
         * @param {number} id - Site ID
         * @returns {Promise<Object>}
         */
        statistics: (id) => this.client.get(`/sites/${id}/statistics`)
    };

    /**
     * Plants API endpoints
     */
    plants = {
        /**
         * List plants with optional filters
         * @param {Object} filters - Query parameters
         * @returns {Promise<Array>}
         */
        list: (filters = {}) => this.client.get('/plants', { params: filters }),

        /**
         * Get plant by ID
         * @param {number} id - Plant ID
         * @returns {Promise<Object>}
         */
        get: (id) => this.client.get(`/plants/${id}`),

        /**
         * Create new plant
         * @param {Object} data - Plant data
         * @returns {Promise<Object>}
         */
        create: (data) => this.client.post('/plants', data),

        /**
         * Update existing plant
         * @param {number} id - Plant ID
         * @param {Object} data - Updated plant data
         * @returns {Promise<Object>}
         */
        update: (id, data) => this.client.put(`/plants/${id}`, data),

        /**
         * Delete plant
         * @param {number} id - Plant ID
         * @returns {Promise<void>}
         */
        delete: (id) => this.client.delete(`/plants/${id}`),

        /**
         * Get plant observations
         * @param {number} id - Plant ID
         * @returns {Promise<Array>}
         */
        observations: (id) => this.client.get(`/plants/${id}/observations`),

        /**
         * Get plant photos
         * @param {number} id - Plant ID
         * @returns {Promise<Array>}
         */
        photos: (id) => this.client.get(`/plants/${id}/photos`),

        /**
         * Get plant statistics
         * @param {number} id - Plant ID
         * @returns {Promise<Object>}
         */
        statistics: (id) => this.client.get(`/plants/${id}/statistics`),

        /**
         * Get site map with plants
         * @param {number} siteId - Site ID
         * @returns {Promise<Object>}
         */
        siteMap: (siteId) => this.client.get('/plants/site-map', { params: { site_id: siteId } }),

        /**
         * Search plants by query
         * @param {string} query - Search query
         * @returns {Promise<Array>}
         */
        search: (query) => this.client.get('/plants', { params: { search: query } })
    };

    /**
     * Observations API endpoints
     */
    observations = {
        /**
         * List observations
         * @returns {Promise<Array>}
         */
        list: () => this.client.get('/observations'),

        /**
         * Get observation by ID
         * @param {number} id - Observation ID
         * @returns {Promise<Object>}
         */
        get: (id) => this.client.get(`/observations/${id}`),

        /**
         * Create new observation
         * @param {Object} data - Observation data
         * @returns {Promise<Object>}
         */
        create: (data) => this.client.post('/observations', data),

        /**
         * Update existing observation
         * @param {number} id - Observation ID
         * @param {Object} data - Updated observation data
         * @returns {Promise<Object>}
         */
        update: (id, data) => this.client.put(`/observations/${id}`, data),

        /**
         * Delete observation
         * @param {number} id - Observation ID
         * @returns {Promise<void>}
         */
        delete: (id) => this.client.delete(`/observations/${id}`)
    };

    /**
     * Photos API endpoints
     */
    photos = {
        /**
         * Upload plant photo
         * @param {FormData} formData - Form data with image file
         * @returns {Promise<Object>}
         */
        uploadPlantPhoto: (formData) => this.client.post('/plant-photos', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        }),

        /**
         * Upload observation photo
         * @param {FormData} formData - Form data with image file
         * @returns {Promise<Object>}
         */
        uploadObservationPhoto: (formData) => this.client.post('/observation-photos', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        }),

        /**
         * Delete plant photo
         * @param {number} id - Photo ID
         * @returns {Promise<void>}
         */
        deletePlantPhoto: (id) => this.client.delete(`/plant-photos/${id}`),

        /**
         * Delete observation photo
         * @param {number} id - Photo ID
         * @returns {Promise<void>}
         */
        deleteObservationPhoto: (id) => this.client.delete(`/observation-photos/${id}`)
    };

    /**
     * Authentication API endpoints
     */
    auth = {
        /**
         * Login user
         * @param {Object} credentials - Username and password
         * @returns {Promise<Object>}
         */
        login: (credentials) => this.client.post('/auth/login', credentials),

        /**
         * Logout user
         * @returns {Promise<void>}
         */
        logout: () => this.client.post('/auth/logout'),

        /**
         * Get current user auth status
         * @returns {Promise<Object>}
         */
        status: () => this.client.get('/auth/status')
    };

    /**
     * Core data API endpoints (categories, phenological stages)
     */
    core = {
        /**
         * List categories
         * @returns {Promise<Array>}
         */
        categories: () => this.client.get('/categories'),

        /**
         * List phenological stages
         * @returns {Promise<Array>}
         */
        phenologicalStages: () => this.client.get('/phenological-stages')
    };

    /**
     * Taxons API endpoints
     */
    taxons = {
        /**
         * List taxons
         * @returns {Promise<Array>}
         */
        list: () => this.client.get('/taxons'),

        /**
         * Get taxon by ID
         * @param {number} id - Taxon ID
         * @returns {Promise<Object>}
         */
        get: (id) => this.client.get(`/taxons/${id}`)
    };
}

// Export singleton instance
const api = new ApiClient();

// Export class for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ApiClient, api };
}
