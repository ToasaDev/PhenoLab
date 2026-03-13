export class ErrorHandler {
    static handle(error, context = '', alertCallback = null) {
        const message = this.getUserMessage(error);
        console.error(`[${context}]`, error);

        if (alertCallback) {
            alertCallback(message, 'danger');
        }

        if (error.status === 401) {
            window.dispatchEvent(new CustomEvent('auth:required'));
        }

        return message;
    }

    static async withRetry(apiCall, maxRetries = 3, alertCallback = null) {
        let lastError;
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                return await apiCall();
            } catch (error) {
                lastError = error;
                if (error.category !== 'network' || attempt === maxRetries) break;
                await this._delay(Math.pow(2, attempt) * 500);
            }
        }
        throw lastError;
    }

    static async withLoading(context, loadingKey, apiCall, onSuccess) {
        if (context.loading) context.loading[loadingKey] = true;
        try {
            const result = await apiCall();
            if (onSuccess) onSuccess(result);
            return result;
        } catch (error) {
            this.handle(error, loadingKey);
            throw error;
        } finally {
            if (context.loading) context.loading[loadingKey] = false;
        }
    }

    static getUserMessage(error) {
        if (!error) return 'Une erreur inconnue est survenue.';

        if (error.category === 'network') {
            return 'Erreur réseau. Vérifiez votre connexion.';
        }

        if (error.data?.errors) {
            return this._formatValidationErrors(error.data.errors);
        }

        if (error.data?.message) return error.data.message;
        if (error.message) return error.message;

        const statusMessages = {
            400: 'Données invalides.',
            401: 'Authentification requise.',
            403: 'Accès non autorisé.',
            404: 'Ressource non trouvée.',
            422: 'Erreur de validation.',
            500: 'Erreur serveur.',
        };

        return statusMessages[error.status] || 'Une erreur est survenue.';
    }

    static _formatValidationErrors(errors) {
        const messages = [];
        for (const [field, fieldErrors] of Object.entries(errors)) {
            const fieldName = this._formatFieldName(field);
            const errorList = Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors];
            messages.push(`${fieldName} : ${errorList.join(', ')}`);
        }
        return messages.join('\n');
    }

    static _formatFieldName(field) {
        const names = {
            name: 'Nom', description: 'Description', latitude: 'Latitude',
            longitude: 'Longitude', observation_date: 'Date d\'observation',
            plant: 'Plante', phenological_stage: 'Stade phénologique',
            image: 'Image', email: 'Email', password: 'Mot de passe',
        };
        return names[field] || field.replace(/_/g, ' ');
    }

    static _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

export default ErrorHandler;
