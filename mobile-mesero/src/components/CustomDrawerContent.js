import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Image } from 'react-native';
import { DrawerContentScrollView, DrawerItem } from '@react-navigation/drawer';
import { COLORS } from '../constants/theme';

import { useAuth } from '../context/AuthContext';

const CustomDrawerContent = (props) => {
    const { userInfo, selectedBranch, logout } = useAuth();

    // Get initials
    const getInitials = (name) => {
        if (!name) return 'U';
        return name.split(' ').map((n) => n[0]).join('').substring(0, 2).toUpperCase();
    };

    return (
        <View style={{ flex: 1, backgroundColor: COLORS.background }}>
            <DrawerContentScrollView {...props} contentContainerStyle={{ backgroundColor: COLORS.secondary }}>
                <View style={styles.userInfoSection}>
                    <View style={styles.avatar}>
                        <Text style={styles.avatarText}>{getInitials(userInfo?.name)}</Text>
                    </View>
                    <Text style={styles.userName}>{userInfo?.name || 'Usuario'}</Text>
                    <Text style={styles.userRole}>{userInfo?.role || 'Rol'}</Text>

                    <View style={styles.branchBadge}>
                        <Text style={styles.branchText}>📍 {selectedBranch?.name || 'Sin Sucursal'}</Text>
                    </View>
                </View>

                <View style={styles.drawerSection}>
                    <TouchableOpacity
                        style={styles.drawerItem}
                        onPress={() => props.navigation.navigate('TablesStack')}
                    >
                        <Text style={styles.itemIcon}>🍽️</Text>
                        <Text style={styles.itemText}>Mesas</Text>
                    </TouchableOpacity>

                    {/* Add more items here like "Historial", "Configuración" */}
                </View>
            </DrawerContentScrollView>

            <View style={styles.bottomDrawerSection}>
                <TouchableOpacity
                    style={styles.signOutButton}
                    onPress={logout}
                >
                    <Text style={styles.signOutText}>Cerrar Sesión</Text>
                </TouchableOpacity>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    userInfoSection: {
        padding: 20,
        backgroundColor: COLORS.secondary, // Header oscuro pero distinto al fondo
        marginBottom: 10,
        alignItems: 'center',
        paddingTop: 50, // Más espacio arriba
    },
    avatar: {
        height: 70,
        width: 70,
        borderRadius: 35,
        backgroundColor: COLORS.primary, // Naranja
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 10,
    },
    avatarText: {
        fontSize: 24,
        fontWeight: 'bold',
        color: COLORS.text, // Blanco
    },
    userName: {
        fontSize: 18,
        fontWeight: 'bold',
        color: COLORS.text, // Blanco
        marginTop: 5,
    },
    userRole: {
        fontSize: 14,
        color: COLORS.textLight,
        marginBottom: 10,
    },
    branchBadge: {
        backgroundColor: 'rgba(255, 171, 29, 0.2)', // Naranja transparente
        paddingHorizontal: 12,
        paddingVertical: 5,
        borderRadius: 10,
        borderWidth: 1,
        borderColor: COLORS.primary,
    },
    branchText: {
        color: COLORS.primary,
        fontWeight: '600',
        fontSize: 12,
    },
    drawerSection: {
        flex: 1,
        backgroundColor: COLORS.background,
        paddingTop: 10,
    },
    drawerItem: { // Usado para items custom si los hay fuera del DrawerItemList
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: 15,
        paddingHorizontal: 20,
    },
    itemIcon: {
        fontSize: 22,
        marginRight: 15,
        color: COLORS.textLight,
    },
    itemText: {
        fontSize: 16,
        color: COLORS.text,
        fontWeight: '500',
    },
    bottomDrawerSection: {
        padding: 20,
        borderTopColor: COLORS.gray,
        borderTopWidth: 1,
        backgroundColor: COLORS.secondary,
    },
    signOutButton: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        paddingVertical: 10,
        backgroundColor: 'rgba(255, 82, 82, 0.1)',
        borderRadius: 10,
    },
    signOutText: {
        color: COLORS.danger,
        fontWeight: 'bold',
        fontSize: 16,
    },
});

export default CustomDrawerContent;
