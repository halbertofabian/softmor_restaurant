import 'react-native-gesture-handler';
import React, { useEffect } from 'react';
import { registerRootComponent } from 'expo';
import * as NavigationBar from 'expo-navigation-bar';
import { StatusBar, setStatusBarHidden } from 'expo-status-bar';
import AppNavigator from './src/navigation/AppNavigator';
import { AuthProvider } from './src/context/AuthContext';

function App() {
    useEffect(() => {
        async function enableImmersiveMode() {
            try {
                // Ocultar barra de navegación (botones de abajo)
                await NavigationBar.setVisibilityAsync('hidden');

                // Comportamiento inmersivo: deslizar para ver momentáneamente
                await NavigationBar.setBehaviorAsync('overlay-swipe');

                // Forzar ocultar barra de estado (arriba)
                setStatusBarHidden(true, 'none');
            } catch (error) {
                console.log("Error setting immersive mode:", error);
            }
        }
        enableImmersiveMode();
    }, []);

    return (
        <AuthProvider>
            <StatusBar hidden={true} style="light" />
            <AppNavigator />
        </AuthProvider>
    );
}

export default registerRootComponent(App);
