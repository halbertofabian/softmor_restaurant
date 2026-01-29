import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, SafeAreaView, FlatList, TouchableOpacity, Image, Dimensions, StatusBar, ActivityIndicator, Alert, ScrollView, Modal, TextInput } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS, SHADOWS } from '../constants/theme';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';
import { useFocusEffect } from '@react-navigation/native';

const OrderScreen = ({ route, navigation }) => {
    const { tableId, tableName } = route.params || { tableName: 'Mesa ?', tableId: null };
    const { selectedBranch } = useAuth();

    // State
    const [loading, setLoading] = useState(true);
    const [categories, setCategories] = useState([]);
    const [allProducts, setAllProducts] = useState([]);
    const [filteredProducts, setFilteredProducts] = useState([]);
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [currentOrder, setCurrentOrder] = useState(null);
    const [sendingOrder, setSendingOrder] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    // Modal State
    const [modalVisible, setModalVisible] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [quantity, setQuantity] = useState(1);
    const [notes, setNotes] = useState('');

    // Initial Load
    useEffect(() => {
        const init = async () => {
            await Promise.all([fetchProducts(), fetchOrCreateOrder()]);
            setLoading(false);
        };
        init();
    }, []);

    // Filter products when category changes
    // Filter products when category changes or search query changes
    useEffect(() => {
        let result = allProducts;

        if (searchQuery.trim().length > 0) {
            const query = searchQuery.toLowerCase();
            result = allProducts.filter(p =>
                p.name.toLowerCase().includes(query)
            );
        } else {
            if (selectedCategory !== 'all') {
                const category = categories.find(c => c.id === selectedCategory);
                result = category ? category.products : [];
            }
        }
        setFilteredProducts(result);
    }, [selectedCategory, allProducts, categories, searchQuery]);

    const fetchProducts = async () => {
        try {
            const response = await api.get('/products', { params: { branch_id: selectedBranch.id } });
            if (response.data.status === 'success') {
                const cats = response.data.data;
                setCategories(cats);
                // Flatten all products from all categories
                const all = cats.flatMap(c => c.products);
                setAllProducts(all);
            }
        } catch (error) {
            console.error("Error fetching products", error);
            Alert.alert('Error', 'No se pudieron cargar los productos');
        }
    };

    const fetchOrCreateOrder = async () => {
        try {
            const response = await api.post('/orders/get-or-create', {
                table_id: tableId,
                branch_id: selectedBranch.id
            });
            if (response.data.status === 'success') {
                setCurrentOrder(response.data.order);
            }
        } catch (error) {
            console.error("Error fetching order", error);
            Alert.alert('Error', 'No se pudo obtener la orden de la mesa');
            navigation.goBack();
        }
    };

    const openProductModal = (product) => {
        setSelectedProduct(product);
        setQuantity(1);
        setNotes('');
        setModalVisible(true);
    };

    const handleConfirmAdd = async () => {
        if (!selectedProduct || !currentOrder) return;

        try {
            const response = await api.post(`/orders/${currentOrder.id}/items`, {
                product_id: selectedProduct.id,
                quantity: quantity,
                notes: notes
            });

            if (response.data.status === 'success') {
                await refreshOrderDetails();
                setModalVisible(false);
            }
        } catch (error) {
            console.error(error);
            Alert.alert('Error', 'No se pudo agregar el producto');
        }
    };

    const refreshOrderDetails = async () => {
        if (!currentOrder) return;
        try {
            const response = await api.get(`/orders/${currentOrder.id}`);
            if (response.data.status === 'success') {
                setCurrentOrder(response.data.order);
            }
        } catch (error) {
            console.error(error);
        }
    };

    const handleSendToKitchen = async () => {
        if (!currentOrder) return;
        setSendingOrder(true);
        try {
            const response = await api.post(`/orders/${currentOrder.id}/send-kitchen`);
            if (response.data.status === 'success') {
                Alert.alert('Éxito', 'Orden enviada a cocina');
                await refreshOrderDetails();
            }
        } catch (error) {
            console.error(error);
            Alert.alert('Error', 'No se pudo enviar la orden');
        } finally {
            setSendingOrder(false);
        }
    };

    const renderCategory = ({ item }) => {
        const isSelected = selectedCategory === item.id;
        return (
            <TouchableOpacity
                style={[styles.categoryPill, isSelected && styles.activeCategoryPill]}
                onPress={() => setSelectedCategory(item.id)}
            >
                <Text style={[styles.categoryText, isSelected && styles.activeCategoryText]}>
                    {item.name}
                </Text>
            </TouchableOpacity>
        );
    };

    const renderProduct = ({ item }) => (
        <TouchableOpacity
            style={styles.productCard}
            onPress={() => openProductModal(item)}
            activeOpacity={0.7}
        >
            <View style={styles.productInfo}>
                <Text style={styles.productName}>{item.name}</Text>
                <Text style={styles.productPrice}>${parseFloat(item.price).toFixed(2)}</Text>
            </View>
            <View style={styles.addButton}>
                <Text style={styles.addIcon}>+</Text>
            </View>
        </TouchableOpacity>
    );

    const handleRemoveItem = async (item) => {
        if (!currentOrder) return;

        Alert.alert(
            'Eliminar Producto',
            `¿Estás seguro de eliminar ${item.product_name}?`,
            [
                { text: 'Cancelar', style: 'cancel' },
                {
                    text: 'Eliminar',
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            const response = await api.delete(`/orders/${currentOrder.id}/items/${item.id}`);
                            if (response.data.status === 'success') {
                                await refreshOrderDetails();
                            }
                        } catch (error) {
                            console.error(error);
                            Alert.alert('Error', 'No se pudo eliminar el producto');
                        }
                    }
                }
            ]
        );
    };

    const renderOrderItem = ({ item }) => (
        <View style={styles.cartItem}>
            <View style={{ marginRight: 10, alignItems: 'center' }}>
                <Text style={styles.cartQty}>{item.quantity}x</Text>
            </View>
            <View style={{ flex: 1 }}>
                <Text style={styles.cartName}>{item.product_name}</Text>
                {item.notes ? <Text style={styles.itemNotes}>{item.notes}</Text> : null}
                {item.status === 'sent' && <Text style={styles.sentTag}>Enviado</Text>}
            </View>
            <Text style={styles.cartPrice}>${(item.price * item.quantity).toFixed(2)}</Text>

            {item.status === 'pending' && (
                <TouchableOpacity
                    style={styles.deleteButton}
                    onPress={() => handleRemoveItem(item)}
                >
                    <Text style={styles.deleteIcon}>✕</Text>
                </TouchableOpacity>
            )}
        </View>
    );

    if (loading) {
        return (
            <View style={[styles.container, styles.center]}>
                <ActivityIndicator size="large" color={COLORS.primary} />
            </View>
        );
    }

    const pendingItems = currentOrder?.details?.filter(d => d.status === 'pending') || [];
    const sentItems = currentOrder?.details?.filter(d => d.status !== 'pending') || [];

    return (
        <SafeAreaView style={styles.container}>
            <StatusBar barStyle="light-content" backgroundColor={COLORS.background} />

            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
                    <Text style={styles.backIcon}>‹</Text>
                </TouchableOpacity>
                <Text style={styles.headerTitle}>Orden - {tableName}</Text>
                <View style={{ width: 40 }} />
            </View>

            {/* Content: Categories + Products */}
            <View style={styles.mainContent}>

                {/* Search Bar */}
                <View style={styles.searchContainer}>
                    <Ionicons name="search" size={20} color={COLORS.textLight} style={styles.searchIcon} />
                    <TextInput
                        style={styles.searchInput}
                        placeholder="Buscar producto..."
                        placeholderTextColor={COLORS.textLight}
                        value={searchQuery}
                        onChangeText={setSearchQuery}
                    />
                    {searchQuery.length > 0 && (
                        <TouchableOpacity onPress={() => setSearchQuery('')}>
                            <Ionicons name="close-circle" size={20} color={COLORS.textLight} />
                        </TouchableOpacity>
                    )}
                </View>

                {/* Categories */}
                <View style={styles.categoriesContainer}>
                    <FlatList
                        data={[{ id: 'all', name: 'Todas' }, ...categories]}
                        horizontal
                        showsHorizontalScrollIndicator={false}
                        renderItem={renderCategory}
                        keyExtractor={item => item.id.toString()}
                        contentContainerStyle={{ paddingHorizontal: 15 }}
                    />
                </View>

                {/* Products */}
                <FlatList
                    data={filteredProducts}
                    renderItem={renderProduct}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={{ padding: 15, paddingBottom: 250 }} // Padding para que no lo tape el carrito
                    numColumns={2}
                    columnWrapperStyle={{ justifyContent: 'space-between' }}
                    ListEmptyComponent={<Text style={styles.emptyText}>No hay productos en esta categoría</Text>}
                />
            </View>

            {/* Cart Panel (Fixed Bottom) */}
            <View style={styles.cartPanel}>
                <View style={styles.cartHeader}>
                    <Text style={styles.cartTitle}>Comanda</Text>
                    <Text style={styles.cartTotal}>Total: ${currentOrder?.total ? parseFloat(currentOrder.total).toFixed(2) : '0.00'}</Text>
                </View>

                <ScrollView style={styles.cartList}>
                    {pendingItems.length > 0 && (
                        <View style={styles.sectionContainer}>
                            <Text style={styles.sectionTitle}>Pendientes</Text>
                            {pendingItems.map(item => (
                                <React.Fragment key={item.id}>
                                    {renderOrderItem({ item })}
                                </React.Fragment>
                            ))}
                        </View>
                    )}

                    {sentItems.length > 0 && (
                        <View style={styles.sectionContainer}>
                            <Text style={[styles.sectionTitle, { color: COLORS.success }]}>Enviados</Text>
                            {sentItems.map(item => (
                                <React.Fragment key={item.id}>
                                    {renderOrderItem({ item })}
                                </React.Fragment>
                            ))}
                        </View>
                    )}

                    {pendingItems.length === 0 && sentItems.length === 0 && (
                        <Text style={styles.emptyCartText}>Agrega productos a la orden</Text>
                    )}
                </ScrollView>

                {pendingItems.length > 0 && (
                    <TouchableOpacity
                        style={styles.sendButton}
                        activeOpacity={0.8}
                        onPress={handleSendToKitchen}
                        disabled={sendingOrder}
                    >
                        {sendingOrder ? (
                            <ActivityIndicator color={COLORS.white} />
                        ) : (
                            <Text style={styles.sendButtonText}>Enviar a Cocina ({pendingItems.length})</Text>
                        )}
                    </TouchableOpacity>
                )}
            </View>

            {/* Modal */}
            <Modal
                animationType="slide"
                transparent={true}
                visible={modalVisible}
                onRequestClose={() => setModalVisible(false)}
            >
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>{selectedProduct?.name}</Text>
                            <TouchableOpacity onPress={() => setModalVisible(false)}>
                                <Text style={styles.closeIcon}>✕</Text>
                            </TouchableOpacity>
                        </View>

                        <Text style={styles.label}>Cantidad</Text>
                        <View style={styles.qtyContainer}>
                            <TouchableOpacity
                                style={[styles.qtyButton, quantity <= 1 && styles.qtyButtonDisabled]}
                                onPress={() => quantity > 1 && setQuantity(quantity - 1)}
                                disabled={quantity <= 1}
                            >
                                <Text style={styles.qtyButtonText}>-</Text>
                            </TouchableOpacity>
                            <Text style={styles.qtyValue}>{quantity}</Text>
                            <TouchableOpacity
                                style={styles.qtyButton}
                                onPress={() => setQuantity(quantity + 1)}
                            >
                                <Text style={styles.qtyButtonText}>+</Text>
                            </TouchableOpacity>
                        </View>

                        <Text style={styles.label}>Notas / Observaciones</Text>
                        <TextInput
                            style={styles.notesInput}
                            placeholder="Ej. Sin cebolla, Salsa aparte..."
                            placeholderTextColor={COLORS.textLight}
                            value={notes}
                            onChangeText={setNotes}
                            multiline
                        />

                        <TouchableOpacity
                            style={styles.confirmButton}
                            onPress={handleConfirmAdd}
                        >
                            <Text style={styles.confirmButtonText}>Agregar al Pedido</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
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
        paddingHorizontal: 15,
        paddingVertical: 15,
        backgroundColor: COLORS.secondary,
        ...SHADOWS.light,
    },
    backButton: {
        padding: 5,
    },
    backIcon: {
        fontSize: 32,
        color: COLORS.text,
        fontWeight: '300',
    },
    headerTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        color: COLORS.text,
    },
    mainContent: {
        flex: 1,
    },
    searchContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: COLORS.white,
        marginHorizontal: 15,
        marginTop: 15,
        paddingHorizontal: 15,
        borderRadius: 12,
        height: 50,
        ...SHADOWS.light,
    },
    searchIcon: {
        marginRight: 10,
    },
    searchInput: {
        flex: 1,
        color: COLORS.text,
        fontSize: 16,
        height: '100%',
    },
    categoriesContainer: {
        paddingVertical: 15,
        backgroundColor: COLORS.background,
    },
    categoryPill: {
        paddingHorizontal: 20,
        paddingVertical: 8,
        backgroundColor: COLORS.white, // Surface
        borderRadius: 20,
        marginRight: 10,
        borderWidth: 1,
        borderColor: 'transparent',
    },
    activeCategoryPill: {
        backgroundColor: COLORS.primary,
        borderColor: COLORS.primary,
    },
    categoryText: {
        color: COLORS.textLight,
        fontWeight: '600',
    },
    activeCategoryText: {
        color: '#FFFFFF', // Blanco sobre naranja siempre
    },
    productCard: {
        width: '48%',
        backgroundColor: COLORS.white,
        borderRadius: 12,
        padding: 15,
        marginBottom: 15,
        ...SHADOWS.light,
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    productInfo: {
        flex: 1,
    },
    productName: {
        fontSize: 14,
        fontWeight: '600',
        color: COLORS.text,
        marginBottom: 4,
    },
    productPrice: {
        fontSize: 12,
        color: COLORS.primary,
        fontWeight: 'bold',
    },
    addButton: {
        width: 30,
        height: 30,
        borderRadius: 15,
        backgroundColor: COLORS.inputBg,
        justifyContent: 'center',
        alignItems: 'center',
    },
    addIcon: {
        fontSize: 18,
        color: COLORS.text,
    },
    cartPanel: {
        backgroundColor: COLORS.secondary, // Panel oscuro diferente al fondo
        borderTopLeftRadius: 20,
        borderTopRightRadius: 20,
        padding: 20,
        paddingBottom: 30,
        ...SHADOWS.medium,
        position: 'absolute',
        bottom: 0,
        left: 0,
        right: 0,
        height: '45%', // Altura fija para el panel
    },
    cartHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: 15,
        borderBottomWidth: 1,
        borderBottomColor: COLORS.gray,
        paddingBottom: 10,
    },
    cartTitle: {
        fontSize: 16,
        fontWeight: 'bold',
        color: COLORS.text,
    },
    cartTotal: {
        fontSize: 18,
        fontWeight: 'bold',
        color: COLORS.success,
    },
    cartList: {
        flex: 1,
        marginBottom: 15,
    },
    sectionContainer: {
        marginBottom: 15,
    },
    sectionTitle: {
        fontSize: 12,
        textTransform: 'uppercase',
        color: COLORS.textLight,
        marginBottom: 5,
        fontWeight: 'bold',
    },
    cartItem: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 8,
        paddingVertical: 5,
    },
    deleteButton: {
        marginLeft: 10,
        padding: 5,
        backgroundColor: 'rgba(255, 82, 82, 0.1)',
        borderRadius: 8,
    },
    deleteIcon: {
        color: COLORS.danger,
        fontSize: 16,
        fontWeight: 'bold',
    },
    cartQty: {
        fontWeight: 'bold',
        color: COLORS.primary,
    },
    cartName: {
        color: COLORS.text,
    },
    itemNotes: {
        fontSize: 12,
        color: COLORS.textLight,
        fontStyle: 'italic',
        marginTop: 2,
    },
    sentTag: {
        fontSize: 10,
        color: COLORS.success,
        marginLeft: 5,
        fontStyle: 'italic'
    },
    cartPrice: {
        fontWeight: '600',
        color: COLORS.textLight,
    },
    emptyCartText: {
        textAlign: 'center',
        color: COLORS.textLight,
        marginTop: 20,
        fontStyle: 'italic',
    },
    sendButton: {
        backgroundColor: COLORS.primary,
        paddingVertical: 15,
        borderRadius: 12,
        alignItems: 'center',
    },
    sendButtonText: {
        color: '#FFFFFF',
        fontSize: 16,
        fontWeight: 'bold',
    },
    emptyText: {
        color: COLORS.textLight,
        textAlign: 'center',
        marginTop: 20,
        width: '100%'
    },
    // Modal Styles
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: COLORS.secondary,
        borderTopLeftRadius: 20,
        borderTopRightRadius: 20,
        padding: 20,
        minHeight: 300,
    },
    modalHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 20,
    },
    modalTitle: {
        fontSize: 20,
        fontWeight: 'bold',
        color: COLORS.text,
    },
    closeIcon: {
        fontSize: 24,
        color: COLORS.textLight,
    },
    label: {
        fontSize: 14,
        color: COLORS.textLight,
        marginBottom: 10,
    },
    qtyContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: 20,
    },
    qtyButton: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: COLORS.primary,
        justifyContent: 'center',
        alignItems: 'center',
    },
    qtyButtonDisabled: {
        backgroundColor: COLORS.gray,
    },
    qtyButtonText: {
        color: '#FFF',
        fontSize: 24,
        fontWeight: 'bold',
        lineHeight: 28,
    },
    qtyValue: {
        fontSize: 24,
        fontWeight: 'bold',
        color: COLORS.text,
        marginHorizontal: 30,
    },
    notesInput: {
        backgroundColor: COLORS.background,
        borderRadius: 10,
        padding: 15,
        color: COLORS.text,
        minHeight: 80,
        textAlignVertical: 'top',
        marginBottom: 20,
        borderWidth: 1,
        borderColor: COLORS.gray,
    },
    confirmButton: {
        backgroundColor: COLORS.primary,
        paddingVertical: 15,
        borderRadius: 12,
        alignItems: 'center',
    },
    confirmButtonText: {
        color: '#FFFFFF',
        fontWeight: 'bold',
        fontSize: 16,
    }
});

export default OrderScreen;
