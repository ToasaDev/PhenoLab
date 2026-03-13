/**
 * PhenoLab Error Handler
 *
 * Centralized error handling utility to replace duplicated catch blocks.
 * Provides consistent user feedback and logging for all API errors.
 *
 * @author PhenoLab Team
 * @version 1.0.0
 */

class ErrorHandler {
    /**
     * Handle API error with consistent user feedback
     *
     * @param {Object} error - Standardized error from ApiClient
     * @param {string} context - Operation context for logging (e.g., 'loadSites')
     * @param {Function} alertCallback - Function to show alerts to user
     * @returns {Object} Error details for caller to handle
     */
    static handle(error, context, alertCallback) {
        // Log error with context
        console.error(`[${context}]`, error);

        // Track error if analytics service available
        if (window.analytics) {
            window.analytics.track('error', {
                context,
                code: error.code,
                type: error.type,
                status: error.status
            });
        }

        // Handle authentication required
        if (error.authRequired) {
            alertCallback('Veuillez vous connecter pour continuer', 'warning');
            return {
                shouldRedirectToLogin: true,
                handled: true
            };
        }

        // Handle forbidden access
        if (error.forbidden) {
            alertCallback('Vous n\'avez pas la permission d\'effectuer cette action', 'warning');
            return {
                forbidden: true,
                handled: true
            };
        }

        // Handle network errors
        if (error.networkError) {
            alertCallback('Impossible de contacter le serveur. Vérifiez votre connexion internet.', 'danger');
            return {
                shouldRetry: true,
                networkError: true,
                handled: true
            };
        }

        // Handle validation errors
        if (error.validationErrors) {
            const messages = this._formatValidationErrors(error.validationErrors);
            alertCallback(`Erreurs de validation:\n${messages}`, 'warning');
            return {
                validationErrors: error.validationErrors,
                handled: true
            };
        }

        // Handle specific error codes
        switch (error.code) {
            case 'NOT_FOUND':
                alertCallback('La ressource demandée n\'a pas été trouvée', 'warning');
                return { notFound: true, handled: true };

            case 'SERVER_ERROR':
                alertCallback('Erreur serveur. Veuillez réessayer plus tard.', 'danger');
                return { serverError: true, handled: true };

            default:
                // Generic error message
                alertCallback(error.message || 'Une erreur est survenue', 'danger');
                return { genericError: true, handled: true };
        }
    }

    /**
     * Format validation errors for display
     * @param {Object} errors - Validation error object
     * @returns {string} Formatted error message
     * @private
     */
    static _formatValidationErrors(errors) {
        const messages = [];

        for (const [field, fieldErrors] of Object.entries(errors)) {
            const errorList = Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors];
            const fieldName = this._formatFieldName(field);
            messages.push(`• ${fieldName}: ${errorList.join(', ')}`);
        }

        return messages.join('\n');
    }

    /**
     * Format field name for display (camelCase to Human Readable)
     * @param {string} field - Field name
     * @returns {string} Formatted field name
     * @private
     */
    static _formatFieldName(field) {
        const translations = {
            'name': 'Nom',
            'description': 'Description',
            'latitude': 'Latitude',
            'longitude': 'Longitude',
            'altitude': 'Altitude',
            'environment': 'Environnement',
            'soil_type': 'Type de sol',
            'exposure': 'Exposition',
            'climate_zone': 'Zone climatique',
            'is_private': 'Privé',
            'location': 'Localisation',
            'plant': 'Plante',
            'phenological_stage': 'Stade phénologique',
            'observation_date': 'Date d\'observation',
            'intensity': 'Intensité',
            'notes': 'Notes',
            'weather_conditions': 'Conditions météo',
            'temperature': 'Température',
            'title': 'Titre',
            'photo_type': 'Type de photo',
            'image': 'Image'
        };

        return translations[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    /**
     * Handle error with automatic retry logic
     *
     * @param {Function} apiCall - Function to call
     * @param {number} maxRetries - Maximum retry attempts
     * @param {Function} alertCallback - Alert callback
     * @returns {Promise<any>}
     */
    static async withRetry(apiCall, maxRetries = 3, alertCallback) {
        let lastError;

        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                return await apiCall();
            } catch (error) {
                lastError = error;

                if (error.networkError && attempt < maxRetries) {
                    console.warn(`Retry attempt ${attempt}/${maxRetries}`);
                    await this._delay(1000 * attempt); // Exponential backoff
                    continue;
                }

                // Non-retryable error or max retries reached
                break;
            }
        }

        // Handle final error
        return this.handle(lastError, 'retryOperation', alertCallback);
    }

    /**
     * Delay utility for retry logic
     * @param {number} ms - Milliseconds to delay
     * @returns {Promise<void>}
     * @private
     */
    static _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Wrap async function with loading state and error handling
     *
     * @param {Object} context - Vue component context (this)
     * @param {string} loadingKey - Key in loading object (e.g., 'sites')
     * @param {Function} apiCall - Async function to execute
     * @param {Function} onSuccess - Success callback (optional)
     * @returns {Promise<any>}
     */
    static async withLoading(context, loadingKey, apiCall, onSuccess) {
        context.loading[loadingKey] = true;

        try {
            const result = await apiCall();

            if (onSuccess) {
                onSuccess(result);
            }

            return result;
        } catch (error) {
            const handled = this.handle(error, loadingKey, context.showAlert);

            if (handled.shouldRedirectToLogin) {
                context.showLoginModal = true;
            }

            throw error;
        } finally {
            context.loading[loadingKey] = false;
        }
    }

    /**
     * Create user-friendly error message from error object
     *
     * @param {Object} error - Error object
     * @returns {string} User-friendly message
     */
    static getUserMessage(error) {
        if (error.authRequired) {
            return 'Veuillez vous connecter';
        }

        if (error.forbidden) {
            return 'Permission refusée';
        }

        if (error.networkError) {
            return 'Problème de connexion';
        }

        if (error.validationErrors) {
            return 'Données invalides';
        }

        switch (error.code) {
            case 'NOT_FOUND': return 'Ressource introuvable';
            case 'SERVER_ERROR': return 'Erreur serveur';
            default: return error.message || 'Erreur inconnue';
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ErrorHandler };
}
