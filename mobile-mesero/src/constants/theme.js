export const COLORS = {
    primary: '#FFAB1D', // Naranja original (Mantiene identidad)
    secondary: '#1E1E1E', // Gris oscuro neutro (antes #2A2D3E se veía morado)
    background: '#121212', // Fondo general oscuro
    white: '#1E1E1E',     // Superficies/Tarjetas (Antes blanco, ahora gris oscuro)
    black: '#FFFFFF',     // Usado para texto sobre fondos "blanco" (ahora #1E1E1E)? No, mejor usar 'text'
    text: '#FFFFFF',      // Texto principal (Blanco)
    textLight: '#AAAAAA', // Texto secundario (Gris claro)
    success: '#4CAF50',
    danger: '#FF5252',
    warning: '#FFC107',
    gray: '#333333',      // Bordes/Separadores oscuros
    inputBg: '#2A2A2A',   // Fondo de inputs
};

export const SHADOWS = {
    light: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 3,
        elevation: 3,
    },
    medium: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.15,
        shadowRadius: 6,
        elevation: 5,
    },
};
