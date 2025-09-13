# 📁 Estructura CSS Modular - DragonTech

La estructura de CSS ha sido reorganizada para mejor mantenimiento y escalabilidad.

## 🗂️ Estructura de Archivos

```
styles/
├── base.css                    # Variables, reset, utilidades y estilos base
├── main.css                    # Archivo principal que importa todos los módulos
├── components/
│   └── navigation.css          # Navegación, header, footer y menús
└── pages/
    ├── home.css               # Página principal, hero section y grid de laptops
    ├── cart.css               # Carrito de compras y controles
    ├── checkout.css           # Proceso de checkout y confirmación de pedidos
    └── profile.css            # Perfil de usuario e historial de pedidos
```

## 📋 Descripción de Archivos

### 🎨 `base.css`
- **Variables CSS** (colores, transiciones, sombras, etc.)
- **Reset CSS** y normalización
- **Utilidades** (containers, text-center, hidden, etc.)
- **Botones base** (btn-primary, btn-secondary, btn-outline)
- **Formularios base** (form-group, inputs, textareas)
- **Mensajes** (success, error, warning, info)
- **Cards** (card, card-header, card-title)
- **Responsive base**

### 🧭 `components/navigation.css`
- **Header principal** (.header)
- **Logo y dragon-logo** (.logo, .dragon-logo)
- **Navegación principal** (.nav, .nav a)
- **Navbar alternativo** (.navbar, .nav-container)
- **Footer** (.footer, .footer-content)
- **Responsive navigation**

### 🏠 `pages/home.css`
- **Hero section** (.hero, .hero-content, .hero-buttons)
- **Section titles** (.section-title)
- **Grid de laptops** (.laptops-grid, .laptop-card)
- **Laptop cards** (.laptop-image, .laptop-info, .laptop-actions)
- **Estados vacíos** (.empty-state)
- **Responsive home**

### 🛒 `pages/cart.css`
- **Contenedor del carrito** (.cart-container, .cart-items)
- **Items del carrito** (.cart-item, .item-details)
- **Controles de cantidad** (.quantity-controls, .quantity-btn)
- **Resumen del carrito** (.cart-summary, .summary-line)
- **Botón de checkout** (.checkout-btn)
- **Badges de seguridad** (.security-badges, .badge)
- **Responsive cart**

### 💳 `pages/checkout.css`
- **Contenedor checkout** (.checkout-container, .checkout-form)
- **Formularios** (.form-section, .form-row)
- **Métodos de pago** (.payment-methods, .payment-option)
- **Resumen del pedido** (.order-summary, .order-totals)
- **Confirmación** (.confirmation-container, .confirmation-header)
- **Detalles del pedido** (.order-details, .order-items)
- **Timeline de pasos** (.steps-timeline, .step)
- **Responsive checkout**

### 👤 `pages/profile.css`
- **Contenedor del perfil** (.profile-container, .profile-header)
- **Avatar y información** (.profile-avatar, .profile-info)
- **Secciones del perfil** (.profile-section, .profile-form)
- **Historial de pedidos** (.orders-list, .order-card)
- **Estados de pedidos** (.status-pendiente, .status-entregado, etc.)
- **Responsive profile**

### 🎯 `main.css`
- **Importa todos los módulos** usando @import
- **Estilos adicionales** (login, formularios de registro)
- **Animaciones globales** (fadeIn, slideIn, scaleIn)
- **Estados de carga** (.loading, .spinner)
- **Print styles** para impresión
- **Accesibilidad** (focus-visible, prefers-reduced-motion)
- **Scrollbar personalizada**

## 🎯 Beneficios de la Nueva Estructura

### ✅ **Mantenibilidad**
- Cada archivo tiene una responsabilidad específica
- Es fácil encontrar y modificar estilos relacionados
- Menos conflictos al trabajar en equipo

### ✅ **Escalabilidad**
- Fácil agregar nuevas páginas sin afectar existentes
- Componentes reutilizables bien organizados
- Variables centralizadas para cambios globales

### ✅ **Performance**
- El navegador puede cachear archivos individuales
- Posibilidad de cargar solo los estilos necesarios
- Mejor compresión y optimización

### ✅ **Organización**
- Estructura lógica y predecible
- Separación clara de concerns
- Documentación implícita por estructura

## 📝 Convenciones de Nomenclatura

### 🏷️ **Clases de Página**
```css
.home-* { }          /* Específico de la página home */
.cart-* { }          /* Específico del carrito */
.checkout-* { }      /* Específico del checkout */
.profile-* { }       /* Específico del perfil */
```

### 🏷️ **Clases de Componente**
```css
.btn-* { }           /* Botones */
.card-* { }          /* Cards */
.form-* { }          /* Formularios */
.nav-* { }           /* Navegación */
```

### 🏷️ **Clases de Estado**
```css
.active { }          /* Estado activo */
.disabled { }        /* Estado deshabilitado */
.loading { }         /* Estado de carga */
.error { }           /* Estado de error */
```

## 🔧 Cómo Agregar Nuevos Estilos

### ➕ **Para una nueva página:**
1. Crear archivo en `styles/pages/nueva-pagina.css`
2. Agregar import en `styles/main.css`
3. Usar variables de `base.css`

### ➕ **Para un nuevo componente:**
1. Crear archivo en `styles/components/nuevo-componente.css`
2. Agregar import en `styles/main.css`
3. Seguir convenciones de nomenclatura

### ➕ **Para nuevas variables:**
1. Agregar en la sección `:root` de `base.css`
2. Usar nomenclatura consistente (--primary, --text-secondary, etc.)
3. Documentar el propósito de la variable

## 🎨 Variables CSS Disponibles

```css
/* Colores */
--primary: #ff3864;
--secondary: #00d9ff;
--accent: #7e42ff;
--success: #00ff9d;
--error: #ff3864;

/* Fondos */
--bg-primary: #0a0a12;
--bg-secondary: #12121f;
--bg-tertiary: #1a1a2e;

/* Textos */
--text-primary: #ffffff;
--text-secondary: #b4b4cc;
--text-muted: #6b6b8a;

/* Transiciones */
--transition-fast: 0.2s ease;
--transition-normal: 0.3s ease;
--transition-slow: 0.5s ease;

/* Bordes y sombras */
--border-radius: 8px;
--border-radius-large: 15px;
--shadow-small: 0 2px 8px rgba(0, 0, 0, 0.2);
--shadow-medium: 0 5px 15px rgba(0, 0, 0, 0.3);
--shadow-large: 0 10px 30px rgba(0, 0, 0, 0.3);
```

## 📱 Responsive Design

Todos los archivos incluyen media queries para:
- **768px** - Tablets y pantallas medianas
- **480px** - Móviles pequeños

## 🚀 Migración Completada

✅ Todas las páginas PHP han sido actualizadas para usar `styles/main.css`
✅ El archivo original `style.css` puede mantenerse como respaldo
✅ La funcionalidad existente se mantiene intacta
✅ Mejoras en organización y mantenibilidad

---

**🎉 ¡La estructura CSS modular está lista para usar!**