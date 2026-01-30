# Configuración de Entornos - Mobile App

## Cambiar a Producción

### Paso 1: Actualizar URL de Producción

Edita el archivo `src/config/env.js`:

```javascript
export const ENV_CONFIG = {
    DEV: false, // ← Cambiar a false
    
    API_URL_DEV: 'http://192.168.1.128:8000/api',
    API_URL_PROD: 'https://tudominio.com/api', // ← Coloca tu URL real aquí
};
```

### Paso 2: Reconstruir la App

```bash
# Para Android
npx expo start --clear

# O si estás usando EAS Build
eas build --platform android --profile production
```

## Volver a Desarrollo

Simplemente cambia `DEV: true` en `src/config/env.js` y reinicia Metro bundler.

## Verificar URL Activa

Al iniciar la app, verás en la consola:
```
API URL: https://tudominio.com/api  (en producción)
API URL: http://192.168.1.128:8000/api  (en desarrollo)
```

## Archivos Modificados

- ✅ `src/config/env.js` - Configuración centralizada
- ✅ `src/services/api.js` - Importa URL desde config
