import React, { createContext, useState, useEffect, useContext } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../services/api';

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
    const [isLoading, setIsLoading] = useState(true);
    const [userToken, setUserToken] = useState(null);
    const [userInfo, setUserInfo] = useState(null);
    const [selectedBranch, setSelectedBranch] = useState(null);

    // Verificar si ya hay sesión al iniciar la app
    const isLoggedIn = async () => {
        try {
            setIsLoading(true);
            let userToken = await AsyncStorage.getItem('userToken');
            let userInfo = await AsyncStorage.getItem('userInfo');
            let branch = await AsyncStorage.getItem('selectedBranch');

            if (userToken) {
                setUserToken(userToken);
                setUserInfo(JSON.parse(userInfo));
                if (branch) setSelectedBranch(JSON.parse(branch));
            }
            setIsLoading(false);
        } catch (e) {
            console.log(`isLoggedIn error: ${e}`);
            setIsLoading(false);
        }
    };

    useEffect(() => {
        isLoggedIn();
    }, []);

    const login = async (email, password) => {
        setIsLoading(true);
        try {
            const response = await api.post('/login', { email, password });

            if (response.data.status === 'success') {
                const token = response.data.token;
                const user = response.data.user;

                setUserToken(token);
                setUserInfo(user);

                await AsyncStorage.setItem('userToken', token);
                await AsyncStorage.setItem('userInfo', JSON.stringify(user));

                setIsLoading(false);
                return { success: true };
            } else {
                setIsLoading(false);
                return { success: false, message: response.data.message || 'Error al iniciar sesión' };
            }
        } catch (error) {
            console.error(error);
            setIsLoading(false);
            return {
                success: false,
                message: error.response?.data?.message || 'Error de conexión. Verifica tu internet o IP.'
            };
        }
    };

    const logout = async () => {
        setIsLoading(true);
        try {
            // Intentar logout en servidor (opcional, si falla igual cerramos local)
            await api.post('/logout');
        } catch (e) {
            console.log("Logout server error:", e);
        } finally {
            setUserToken(null);
            setUserInfo(null);
            setSelectedBranch(null);
            await AsyncStorage.removeItem('userToken');
            await AsyncStorage.removeItem('userInfo');
            await AsyncStorage.removeItem('selectedBranch');
            setIsLoading(false);
        }
    };

    const selectBranch = async (branch) => {
        setSelectedBranch(branch);
        await AsyncStorage.setItem('selectedBranch', JSON.stringify(branch));
    };

    return (
        <AuthContext.Provider value={{
            isLoading,
            userToken,
            userInfo,
            selectedBranch,
            login,
            logout,
            selectBranch
        }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => useContext(AuthContext);
