import React, { useState, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, Dimensions, StatusBar, ActivityIndicator, Alert } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { COLORS, SHADOWS } from '../constants/theme';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';

const { width } = Dimensions.get('window');
const SPACING = 15;
const ITEM_SIZE = (width - (SPACING * 3)) / 2;

const TablesScreen = ({ navigation }) => {
    const [tables, setTables] = useState([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const { selectedBranch, userInfo } = useAuth();

    // Get initials
    const getInitials = (name) => {
        if (!name) return 'U';
        return name.split(' ').map((n) => n[0]).join('').substring(0, 2).toUpperCase();
    };

    useFocusEffect(
        useCallback(() => {
            if (selectedBranch) {
                fetchTables();
            }
        }, [selectedBranch])
    );

    const fetchTables = async () => {
        try {
            const response = await api.get('/tables', {
                params: { branch_id: selectedBranch.id }
            });
            if (response.data.status === 'success') {
                setTables(response.data.data);
            }
        } catch (error) {
            console.error(error);
            Alert.alert('Error', 'No se pudieron cargar las mesas');
        } finally {
            setLoading(false);
        }
    };

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchTables();
        setRefreshing(false);
    };

    const handleTableAction = (table) => {
        navigation.navigate('Order', {
            tableId: table.id,
            tableName: table.name,
        });
    };

    const renderTableItem = ({ item }) => {
        const isFree = item.status === 'free';
        // Si está libre: Tarjeta normal (blanca/gris oscura en dark mode). Botón "Tomar Mesa"
        // Si está ocupada: Color distintivo? Usuario pidió "indícame su estado con colores".
        // Usaremos un borde o fondo sutil para ocupada, o el indicador de estado.
        // Requisito: "si esta libre, marcala con un boton que diga tomar mesa"
        // "si esta ocupada debe de ser un boton que diga ver comanda"

        return (
            <View
                style={[
                    styles.tableCard,
                    isFree ? styles.freeCardBorder : styles.occupiedCardBorder // Borde verde si libre, rojo si ocupada
                ]}
            >
                <View style={styles.tableHeader}>
                    <Text style={styles.tableName}>{item.name}</Text>
                    <View style={[styles.statusDot, { backgroundColor: isFree ? COLORS.success : COLORS.danger }]} />
                </View>

                <View style={styles.tableInfo}>
                    <Text style={styles.seatsText}>
                        👥 {item.seats} pers.
                    </Text>
                    <Text style={[styles.statusText, { color: isFree ? COLORS.success : COLORS.danger }]}>
                        {isFree ? 'LIBRE' : 'OCUPADA'}
                    </Text>
                </View>

                <TouchableOpacity
                    style={[
                        styles.actionButton,
                        !isFree ? styles.occupiedButton : styles.freeButton
                    ]}
                    activeOpacity={0.8}
                    onPress={() => handleTableAction(item)}
                >
                    <Text style={[
                        styles.actionText,
                        !isFree ? styles.occupiedButtonText : styles.freeButtonText
                    ]}>
                        {isFree ? 'Tomar Mesa' : 'Ver Comanda'}
                    </Text>
                </TouchableOpacity>
            </View>
        );
    };

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
                <TouchableOpacity style={styles.menuButton} onPress={() => navigation.openDrawer()}>
                    <Text style={styles.menuIcon}>☰</Text>
                </TouchableOpacity>
                <View>
                    <Text style={styles.headerTitle}>Mesas</Text>
                    <Text style={styles.headerSubtitle}>{selectedBranch?.name}</Text>
                </View>
                <View style={styles.userAvatar}>
                    <Text style={styles.userInitials}>{getInitials(userInfo?.name)}</Text>
                </View>
            </View>

            <FlatList
                data={tables}
                keyExtractor={item => item.id.toString()}
                renderItem={renderTableItem}
                numColumns={2}
                contentContainerStyle={styles.gridContainer}
                columnWrapperStyle={{ justifyContent: 'space-between' }}
                showsVerticalScrollIndicator={false}
                refreshing={refreshing}
                onRefresh={onRefresh}
                ListEmptyComponent={
                    <View style={styles.center}>
                        <Text style={{ color: COLORS.textLight, marginTop: 50 }}>No hay mesas registradas</Text>
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
    center: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center'
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 20,
        paddingTop: 15,
        paddingBottom: 20,
        backgroundColor: COLORS.secondary, // Header oscuro
        ...SHADOWS.light,
        zIndex: 10,
    },
    menuButton: {
        padding: 8,
    },
    menuIcon: {
        fontSize: 24,
        color: COLORS.text,
    },
    headerTitle: {
        fontSize: 20,
        fontWeight: 'bold',
        color: COLORS.text,
        textAlign: 'center',
    },
    headerSubtitle: {
        fontSize: 12,
        color: COLORS.textLight,
        textAlign: 'center',
    },
    userAvatar: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: COLORS.primary,
        justifyContent: 'center',
        alignItems: 'center',
    },
    userInitials: {
        color: COLORS.white,
        fontWeight: 'bold',
    },
    gridContainer: {
        padding: SPACING,
        paddingBottom: 100,
    },
    tableCard: {
        width: ITEM_SIZE,
        backgroundColor: COLORS.white, // Surface color (gris oscuro en dark mode)
        borderRadius: 16,
        padding: 15,
        marginBottom: SPACING,
        ...SHADOWS.light,
        justifyContent: 'space-between',
        minHeight: 140,
        borderWidth: 1,
        borderColor: 'transparent',
    },
    occupiedCardBorder: {
        borderColor: COLORS.danger, // Borde rojo sutil para ocupadas
    },
    freeCardBorder: {
        borderColor: COLORS.success, // Borde verde para libres
    },
    tableHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center', // Centrado verticalmente
        marginBottom: 10,
    },
    tableName: {
        fontSize: 16,
        fontWeight: 'bold',
        color: COLORS.text,
        flex: 1, // Para que el nombre ocupe espacio si es largo
    },
    statusDot: {
        width: 12,
        height: 12,
        borderRadius: 6,
    },
    tableInfo: {
        marginBottom: 15,
    },
    seatsText: {
        fontSize: 14,
        color: COLORS.textLight,
        marginBottom: 4,
    },
    statusText: {
        fontSize: 12,
        fontWeight: 'bold',
        // Color se define inline según estado
    },
    actionButton: {
        paddingVertical: 10,
        borderRadius: 8,
        alignItems: 'center',
        justifyContent: 'center',
    },
    freeButton: {
        backgroundColor: 'transparent',
        borderWidth: 1,
        borderColor: COLORS.success,
    },
    occupiedButton: {
        backgroundColor: 'transparent',
        borderWidth: 1,
        borderColor: COLORS.danger,
    },
    actionText: {
        fontSize: 14,
        fontWeight: 'bold',
    },
    freeButtonText: {
        color: COLORS.success,
    },
    occupiedButtonText: {
        color: COLORS.danger,
    }
});

export default TablesScreen;
