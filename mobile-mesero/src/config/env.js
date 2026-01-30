// Configuración de entornos
// Cambia DEV a false para producción

export const ENV_CONFIG = {
    DEV: false, // ← Configurado para PRODUCCIÓN
    
    // URLs
    API_URL_DEV: 'http://192.168.1.128:8000/api',
    API_URL_PROD: 'https://restaurant.softmor.cloud/api', // ← Coloca aquí tu URL de producción
};

// Exporta la URL activa según el entorno
export const API_URL = ENV_CONFIG.DEV ? ENV_CONFIG.API_URL_DEV : ENV_CONFIG.API_URL_PROD;
