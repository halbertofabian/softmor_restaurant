// Configuración de entornos
// Cambia DEV a false para producción

export const ENV_CONFIG = {
    DEV: false, // ← Configurado para PRODUCCIÓN
    // URLs
    API_URL_DEV: 'https://gestionalfood.com/api',
    API_URL_PROD: 'https://gestionalfood.com/api', // ← Coloca aquí tu URL de producción
};

// Exporta la URL activa según el entorno
export const API_URL = ENV_CONFIG.DEV ? ENV_CONFIG.API_URL_DEV : ENV_CONFIG.API_URL_PROD;
