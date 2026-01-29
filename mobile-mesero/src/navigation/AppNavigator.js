import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { createDrawerNavigator } from '@react-navigation/drawer';
import { NavigationContainer } from '@react-navigation/native';

import LoginScreen from '../screens/LoginScreen';
import BranchSelectionScreen from '../screens/BranchSelectionScreen';
import TablesScreen from '../screens/TablesScreen';
import OrderScreen from '../screens/OrderScreen';
import CustomDrawerContent from '../components/CustomDrawerContent';
import { COLORS } from '../constants/theme';

const Stack = createStackNavigator();
const Drawer = createDrawerNavigator();

const DrawerNavigator = () => {
    return (
        <Drawer.Navigator
            initialRouteName="TablesStack"
            drawerContent={props => <CustomDrawerContent {...props} />}
            screenOptions={{
                headerShown: false,
                drawerStyle: {
                    backgroundColor: COLORS.background, // Fondo oscuro para todo el drawer
                    width: 280,
                },
                drawerActiveBackgroundColor: COLORS.primary, // Fondo naranja para ítem seleccionado
                drawerActiveTintColor: COLORS.white,       // Texto blanco para seleccionado
                drawerInactiveTintColor: COLORS.textLight, // Texto gris para no seleccionados
                drawerLabelStyle: { marginLeft: -20, fontWeight: 'bold' },
            }}
        >
            <Drawer.Screen name="TablesStack" component={TablesStack} options={{ title: 'Mesas' }} />
        </Drawer.Navigator>
    );
};

const TablesStack = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }}>
            <Stack.Screen name="Tables" component={TablesScreen} />
            <Stack.Screen name="Order" component={OrderScreen} />
        </Stack.Navigator>
    );
}

import { ActivityIndicator, View } from 'react-native';
import { useAuth } from '../context/AuthContext';

const AppNavigator = () => {
    const { userToken, isLoading, selectedBranch } = useAuth();

    if (isLoading) {
        return (
            <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
                <ActivityIndicator size="large" color={COLORS.primary} />
            </View>
        );
    }

    return (
        <NavigationContainer>
            <Stack.Navigator screenOptions={{ headerShown: false }}>
                {userToken ? (
                    // Usuario autenticado
                    <>
                        {!selectedBranch ? (
                            <Stack.Screen name="BranchSelection" component={BranchSelectionScreen} />
                        ) : null}
                        <Stack.Screen name="DrawerNavigator" component={DrawerNavigator} />
                    </>
                ) : (
                    // Usuario NO autenticado
                    <Stack.Screen name="Login" component={LoginScreen} />
                )}
            </Stack.Navigator>
        </NavigationContainer>
    );
};

export default AppNavigator;
