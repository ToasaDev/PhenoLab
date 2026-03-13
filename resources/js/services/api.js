import axios from 'axios';

const apiClient = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

// CSRF token interceptor
apiClient.interceptors.request.use(config => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

// Error interceptor
apiClient.interceptors.response.use(
    response => response,
    error => {
        const transformed = {
            status: error.response?.status || 0,
            data: error.response?.data || {},
            message: error.response?.data?.message || error.message || 'Erreur réseau',
            category: error.response ? (error.response.status >= 500 ? 'server' : 'client') : 'network',
        };
        return Promise.reject(transformed);
    }
);

export const api = {
    // Auth
    auth: {
        csrfToken: () => apiClient.get('/auth/csrf-token'),
        login: (credentials) => apiClient.post('/auth/login', credentials),
        logout: () => apiClient.post('/auth/logout'),
        status: () => apiClient.get('/auth/status'),
    },

    // Categories
    categories: {
        list: (params) => apiClient.get('/categories', { params }),
        get: (id) => apiClient.get(`/categories/${id}`),
        create: (data) => apiClient.post('/categories', data),
        update: (id, data) => apiClient.put(`/categories/${id}`, data),
        delete: (id) => apiClient.delete(`/categories/${id}`),
        byType: () => apiClient.get('/categories/by-type'),
    },

    // Phenological Stages
    stages: {
        list: (params) => apiClient.get('/phenological-stages', { params }),
        get: (id) => apiClient.get(`/phenological-stages/${id}`),
        byEvent: () => apiClient.get('/phenological-stages/by-event'),
    },

    // Sites
    sites: {
        list: (params) => apiClient.get('/sites', { params }),
        get: (id) => apiClient.get(`/sites/${id}`),
        create: (data) => apiClient.post('/sites', data),
        update: (id, data) => apiClient.put(`/sites/${id}`, data),
        delete: (id) => apiClient.delete(`/sites/${id}`),
        geojson: () => apiClient.get('/sites/geojson'),
        nearby: (params) => apiClient.get('/sites/nearby', { params }),
        mySites: () => apiClient.get('/sites/my-sites'),
        plants: (id, params) => apiClient.get(`/sites/${id}/plants`, { params }),
        statistics: (id) => apiClient.get(`/sites/${id}/statistics`),
        updateDrawingOverlay: (id, data) => apiClient.patch(`/sites/${id}/drawing-overlay`, data),
        listLayers: (id) => apiClient.get(`/sites/${id}/layers`),
        createLayer: (id, data) => apiClient.post(`/sites/${id}/layers`, data),
        updateLayer: (id, layerId, data) => apiClient.patch(`/sites/${id}/layers/${layerId}`, data),
        deleteLayer: (id, layerId) => apiClient.delete(`/sites/${id}/layers/${layerId}`),
    },

    // Taxons
    taxons: {
        list: (params) => apiClient.get('/taxons', { params }),
        get: (id) => apiClient.get(`/taxons/${id}`),
        create: (data) => apiClient.post('/taxons', data),
        update: (id, data) => apiClient.put(`/taxons/${id}`, data),
        delete: (id) => apiClient.delete(`/taxons/${id}`),
        search: (query) => apiClient.get('/taxons', { params: { search: query } }),
    },

    // Plant Positions
    positions: {
        list: (params) => apiClient.get('/plant-positions', { params }),
        get: (id) => apiClient.get(`/plant-positions/${id}`),
        create: (data) => apiClient.post('/plant-positions', data),
        update: (id, data) => apiClient.put(`/plant-positions/${id}`, data),
        delete: (id) => apiClient.delete(`/plant-positions/${id}`),
        succession: (id) => apiClient.get(`/plant-positions/${id}/succession`),
    },

    // Plants
    plants: {
        list: (params) => apiClient.get('/plants', { params }),
        get: (id) => apiClient.get(`/plants/${id}`),
        create: (data) => apiClient.post('/plants', data),
        update: (id, data) => apiClient.put(`/plants/${id}`, data),
        delete: (id) => apiClient.delete(`/plants/${id}`),
        myPlants: () => apiClient.get('/plants/my-plants'),
        byCategory: () => apiClient.get('/plants/by-category'),
        bySite: () => apiClient.get('/plants/by-site'),
        observations: (id, params) => apiClient.get(`/plants/${id}/observations`, { params }),
        photos: (id) => apiClient.get(`/plants/${id}/photos`),
        statistics: (id) => apiClient.get(`/plants/${id}/statistics`),
        siteMap: (params) => apiClient.get('/plants/site-map', { params }),
        nearby: (params) => apiClient.get('/plants/nearby', { params }),
        updateGps: (id, data) => apiClient.post(`/plants/${id}/update-gps`, data),
        export: (params) => apiClient.get('/plants/export', { params }),
        markDead: (id, data) => apiClient.post(`/plants/${id}/mark-dead`, data),
        replace: (id, data) => apiClient.post(`/plants/${id}/replace`, data),
        bulkUpdateMapPositions: (data) => apiClient.post('/plants/bulk-update-map-positions', data),
        search: (query) => apiClient.get('/plants', { params: { search: query } }),
    },

    // Observations
    observations: {
        list: (params) => apiClient.get('/observations', { params }),
        get: (id) => apiClient.get(`/observations/${id}`),
        create: (data) => apiClient.post('/observations', data),
        update: (id, data) => apiClient.put(`/observations/${id}`, data),
        delete: (id) => apiClient.delete(`/observations/${id}`),
        myObservations: () => apiClient.get('/observations/my-observations'),
        byPlant: (params) => apiClient.get('/observations/by-plant', { params }),
        byStage: () => apiClient.get('/observations/by-stage'),
        yearsAvailable: () => apiClient.get('/observations/years-available'),
        monthlyCounts: (params) => apiClient.get('/observations/monthly-counts', { params }),
        validate: (id) => apiClient.post(`/observations/${id}/validate`),
    },

    // Tela Observations
    tela: {
        list: (params) => apiClient.get('/tela-observations', { params }),
        get: (id) => apiClient.get(`/tela-observations/${id}`),
        byTaxon: (params) => apiClient.get('/tela-observations/by-taxon', { params }),
        statistics: () => apiClient.get('/tela-observations/statistics'),
    },

    // Comparison & Statistics
    comparison: {
        compare: (params) => apiClient.get('/comparison', { params }),
    },
    statistics: {
        index: () => apiClient.get('/statistics'),
    },

    // ODS
    ods: {
        search: (params) => apiClient.get('/ods-search', { params }),
        stats: () => apiClient.get('/ods-stats'),
        evolution: () => apiClient.get('/ods-evolution'),
    },

    // Global Search
    search: {
        global: (params) => apiClient.get('/search', { params }),
    },

    // Plant Photos
    plantPhotos: {
        list: (params) => apiClient.get('/plant-photos', { params }),
        get: (id) => apiClient.get(`/plant-photos/${id}`),
        upload: (formData) => apiClient.post('/plant-photos', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }),
        update: (id, data) => apiClient.put(`/plant-photos/${id}`, data),
        delete: (id) => apiClient.delete(`/plant-photos/${id}`),
        myPhotos: () => apiClient.get('/plant-photos/my-photos'),
        byPlant: (params) => apiClient.get('/plant-photos/by-plant', { params }),
        mainPhotos: () => apiClient.get('/plant-photos/main-photos'),
        setAsMain: (id) => apiClient.post(`/plant-photos/${id}/set-as-main`),
    },

    // Observation Photos
    observationPhotos: {
        list: (params) => apiClient.get('/observation-photos', { params }),
        get: (id) => apiClient.get(`/observation-photos/${id}`),
        upload: (formData) => apiClient.post('/observation-photos', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }),
        update: (id, data) => apiClient.put(`/observation-photos/${id}`, data),
        delete: (id) => apiClient.delete(`/observation-photos/${id}`),
        myPhotos: () => apiClient.get('/observation-photos/my-photos'),
        byObservation: (params) => apiClient.get('/observation-photos/by-observation', { params }),
    },

    // Activity
    activity: {
        list: (params) => apiClient.get('/activity', { params }),
    },
};

export default api;
