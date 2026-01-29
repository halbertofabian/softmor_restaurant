import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, StatusBar, ActivityIndicator, Alert } from 'react-native';
import { COLORS, SHADOWS } from '../constants/theme';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';

const BranchSelectionScreen = ({ navigation }) => {
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(true);
    const { selectBranch, logout } = useAuth();

    useEffect(() => {
        loadBranches();
    }, []);

    const loadBranches = async () => {
        try {
            const response = await api.get('/branches');
            // Verifica la estructura de tu respuesta API. 
            // Según ApiBranchController, devuelve { status: 'success', data: [...] }
            if (response.data.status === 'success') {
                setBranches(response.data.data);
            }
        } catch (error) {
            console.error(error);
            Alert.alert('Error', 'No se pudieron cargar las sucursales');
        } finally {
            setLoading(false);
        }
    };

    const handleSelectBranch = async (branch) => {
        setLoading(true);
        try {
            const response = await api.get(`/branches/${branch.id}/verify`);
            if (response.data.status === 'success') {
                await selectBranch(branch);
                // navigation.replace('DrawerNavigator'); // YA NO ES NECESARIO, AppNavigator lo maneja por estado
            } else {
                Alert.alert('Acceso Denegado', 'No tienes permiso para acceder a esta sucursal.');
            }
        } catch (error) {
            console.error(error);
            const message = error.response?.data?.message || 'Error al verificar acceso';
            Alert.alert('Acceso Denegado', message);
        } finally {
            setLoading(false);
        }
    };

    const renderBranchItem = ({ item }) => (
        <TouchableOpacity
            style={styles.card}
            activeOpacity={0.7}
            onPress={() => handleSelectBranch(item)}
        >
            <View style={styles.iconContainer}>
                <Text style={styles.iconText}>🏢</Text>
            </View>
            <View style={styles.cardContent}>
                <Text style={styles.branchName}>{item.name}</Text>
                <Text style={styles.branchAddress}>{item.address || 'Sin dirección'}</Text>
            </View>
            <View style={styles.arrowContainer}>
                <Text style={styles.arrow}>›</Text>
            </View>
        </TouchableOpacity>
    );

    if (loading) {
        return (
            <View style={[styles.container, styles.center]}>
                <ActivityIndicator size="large" color={COLORS.primary} />
            </View>
        );
    }

    return (
        <SafeAreaView style={styles.container}>
            <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />
            <View style={styles.header}>
                <Text style={styles.headerTitle}>Seleccionar Sucursal</Text>
                <Text style={styles.headerSubtitle}>Elige donde trabajarás hoy</Text>
            </View>

            <FlatList
                data={branches}
                keyExtractor={item => item.id.toString()}
                renderItem={renderBranchItem}
                contentContainerStyle={styles.listContainer}
                showsVerticalScrollIndicator={false}
                ListEmptyComponent={
                    <View style={styles.emptyContainer}>
                        <Text style={styles.emptyText}>No tienes sucursales asignadas</Text>
                        <TouchableOpacity onPress={logout} style={styles.logoutButton}>
                            <Text style={styles.logoutText}>Cerrar Sesión</Text>
                        </TouchableOpacity>
                    </View>
                }
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: COLORS.background,
    },
    header: {
        paddingHorizontal: 20,
        paddingTop: 40,
        paddingBottom: 20,
    },
    headerTitle: {
        fontSize: 24,
        fontWeight: 'bold',
        color: COLORS.text,
    },
    headerSubtitle: {
        fontSize: 14,
        color: COLORS.textLight,
        marginTop: 5,
    },
    listContainer: {
        padding: 20,
    },
    card: {
        backgroundColor: COLORS.white,
        borderRadius: 16,
        padding: 16,
        marginBottom: 16,
        flexDirection: 'row',
        alignItems: 'center',
        ...SHADOWS.light,
    },
    iconContainer: {
        width: 48,
        height: 48,
        borderRadius: 12,
        backgroundColor: 'rgba(255, 171, 29, 0.1)', // Primary with opacity
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 16,
    },
    iconText: {
        fontSize: 24,
    },
    cardContent: {
        flex: 1,
    },
    branchName: {
        fontSize: 16,
        fontWeight: 'bold',
        color: COLORS.text,
        marginBottom: 4,
    },
    branchAddress: {
        fontSize: 12,
        color: COLORS.textLight,
    },
    arrowContainer: {
        width: 30,
        alignItems: 'center',
    },
    arrow: {
        fontSize: 24,
        color: COLORS.gray,
        fontWeight: '300',
    },
    center: {
        justifyContent: 'center',
        alignItems: 'center',
    },
    emptyContainer: {
        alignItems: 'center',
        paddingTop: 50,
    },
    emptyText: {
        color: COLORS.textLight,
        fontSize: 16,
        marginBottom: 20,
    },
    logoutButton: {
        padding: 10,
    },
    logoutText: {
        color: COLORS.danger,
        fontWeight: 'bold',
    },
});

export default BranchSelectionScreen;
