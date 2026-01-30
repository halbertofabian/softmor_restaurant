import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_URL } from '../config/env';

// La URL se configura automáticamente según el entorno en src/config/env.js
console.log('API URL:', API_URL);

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
