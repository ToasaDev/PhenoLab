export class FormHelpers {
    static FORM_DEFAULTS = {
        site: {
            name: '', description: '', latitude: '', longitude: '', altitude: '',
            environment: '', soil_type: '', exposure: '', climate_zone: '', is_private: false,
        },
        plant: {
            name: '', description: '', taxon: '', category: '', site: '',
            planting_date: '', age_years: '', height_category: '', exact_height: '',
            health_status: 'good', clone_or_accession: '', is_private: false,
            notes: '', anecdotes: '', cultural_significance: '', ecological_notes: '',
            care_notes: '', latitude: '', longitude: '', gps_accuracy: '',
        },
        observation: {
            plant: '', phenological_stage: '', observation_date: new Date().toISOString().split('T')[0],
            intensity: '', notes: '', weather_condition: '', temperature: '', is_public: true,
        },
        photo: {
            plant: '', title: '', description: '', photo_type: 'general', is_public: true,
        },
        taxon: {
            taxon_id: '', genus: '', species: '', kingdom: 'Plantae', phylum: '',
            class_name: '', order: '', family: '', subspecies: '', variety: '',
            cultivar: '', common_name_fr: '', common_name_it: '', common_name_en: '',
            author: '', publication_year: '',
        },
    };

    static resetForm(context, formName, formType) {
        const defaults = this.FORM_DEFAULTS[formType];
        if (defaults && context[formName]) {
            context[formName] = JSON.parse(JSON.stringify(defaults));
        }
    }

    static validateRequired(formData, requiredFields) {
        const errors = [];
        for (const field of requiredFields) {
            const value = formData[field];
            if (value === null || value === undefined || value === '') {
                errors.push(`Le champ ${this._formatFieldName(field)} est requis.`);
            }
        }
        return errors;
    }

    static validateGPS(latitude, longitude) {
        const errors = [];
        if (latitude !== '' && latitude !== null) {
            const lat = parseFloat(latitude);
            if (isNaN(lat) || lat < -90 || lat > 90) {
                errors.push('La latitude doit être entre -90 et 90.');
            }
        }
        if (longitude !== '' && longitude !== null) {
            const lng = parseFloat(longitude);
            if (isNaN(lng) || lng < -180 || lng > 180) {
                errors.push('La longitude doit être entre -180 et 180.');
            }
        }
        return errors;
    }

    static formatSiteData(formData) {
        const data = { ...formData };
        if (data.latitude) data.latitude = parseFloat(data.latitude);
        if (data.longitude) data.longitude = parseFloat(data.longitude);
        if (data.altitude) data.altitude = parseFloat(data.altitude);
        return data;
    }

    static formatPlantData(formData) {
        const data = { ...formData };
        if (data.latitude) data.latitude = parseFloat(data.latitude);
        if (data.longitude) data.longitude = parseFloat(data.longitude);
        if (data.gps_accuracy) data.gps_accuracy = parseFloat(data.gps_accuracy);
        if (data.taxon) data.taxon_id = data.taxon;
        if (data.category) data.category_id = data.category;
        if (data.site) data.site_id = data.site;
        return data;
    }

    static getCurrentLocation(options = {}) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Géolocalisation non disponible'));
                return;
            }
            navigator.geolocation.getCurrentPosition(
                position => resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    altitude: position.coords.altitude,
                }),
                error => reject(error),
                { enableHighAccuracy: true, timeout: 10000, ...options }
            );
        });
    }

    static formatDate(date, locale = 'fr-FR') {
        if (!date) return '';
        return new Date(date).toLocaleDateString(locale);
    }

    static formatNumber(num, locale = 'fr-FR') {
        if (num === null || num === undefined) return '';
        return new Intl.NumberFormat(locale).format(num);
    }

    static debounce(func, wait = 300) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    static _formatFieldName(field) {
        const names = {
            name: 'Nom', description: 'Description', latitude: 'Latitude',
            longitude: 'Longitude', observation_date: "Date d'observation",
            plant: 'Plante', phenological_stage: 'Stade phénologique',
        };
        return names[field] || field.replace(/_/g, ' ');
    }
}

export default FormHelpers;
