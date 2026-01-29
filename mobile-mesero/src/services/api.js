import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Reemplaza con tu IP local para desarrollo
// Si usas emulador Android: 10.0.2.2
// Si usas dispositivo físico: Tu IP local (ej. 192.168.1.128)
// const API_URL = 'http://192.168.1.128:8000/api'; 
// Detectamos automáticamente si estamos en desarrollo
const API_URL = 'http://192.168.1.128:8000/api';

const api = axios.create({
    baseURL: API_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 10000,
});

// Interceptor para agregar el token a las peticiones
api.interceptors.request.use(
    async (config) => {
        const token = await AsyncStorage.getItem('userToken');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Interceptor para manejar errores (ej. token expirado)
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response && error.response.status === 401) {
            // Token expirado o inválido
            await AsyncStorage.removeItem('userToken');
            await AsyncStorage.removeItem('userInfo');
            // Aquí podríamos redirigir al login si tuviéramos acceso a la navegación
        }
        return Promise.reject(error);
    }
);

export default api;
