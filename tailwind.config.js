/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,vue,ts}',
    ],
    theme: {
        extend: {
            colors: {
                // Dominio Acuícola - Tonos Agua / Cuenca Hidrográfica
                aquatic: {
                    50: '#f0f9fa',
                    100: '#d7f0f3',
                    200: '#b1e1e8',
                    300: '#7cc9d6',
                    400: '#3fa8bd',
                    500: '#238ba1',
                    600: '#1b7084',
                    700: '#1a5b6d',
                    800: '#1a4b59', // Azul estanque profundo
                    900: '#103742', // Agua abisal / institucional
                    950: '#08232b',
                },
                // Dominio Acuícola - Tonos Tierra / Talud de Estanque / Margen Arcillosa
                earth: {
                    50: '#f9f8f6',
                    100: '#f1ede6',
                    200: '#e3dbce',
                    300: '#cfc1af',
                    400: '#b7a28c',
                    500: '#a38972',
                    600: '#8e725d',
                    700: '#735b4a',
                    800: '#5e4b3e',
                    900: '#4e3f35',
                    950: '#2a211b',
                },
                // Estados Biológicos y Operativos (Reservados estrictamente para alertas reales)
                bio: {
                    optimal: '#15803d',     // Verde zootécnico óptimo
                    'optimal-bg': '#f0fdf4',
                    'optimal-border': '#bbf7d0',
                    
                    warning: '#b45309',     // Ámbar preventivo (carencia ICA próxima, stock < 5 días)
                    'warning-bg': '#fffbeb',
                    'warning-border': '#fde68a',
                    
                    critical: '#b91c1c',    // Rojo crítico (asfixia, O2 < 3.0 mg/L, cuarentena)
                    'critical-bg': '#fef2f2',
                    'critical-border': '#fecaca',
                },
                slate: {
                    850: '#172033',
                    950: '#0b1120',
                }
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
                mono: ['JetBrains Mono', 'Fira Code', 'SFMono-Regular', 'Menlo', 'monospace'],
            },
            boxShadow: {
                'card-operational': '0 1px 3px 0 rgba(16, 55, 66, 0.05), 0 1px 2px -1px rgba(16, 55, 66, 0.05)',
                'level1-kpi': '0 4px 6px -1px rgba(16, 55, 66, 0.08), 0 2px 4px -2px rgba(16, 55, 66, 0.06)',
            }
        },
    },
    plugins: [],
};
