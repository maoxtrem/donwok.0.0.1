# Seguimiento: Optimización Visual y Responsive (Premium Blue Design)

## 🎯 Objetivo General
Modernizar la interfaz de DonWok utilizando una paleta de colores coherente basada en azules profundos, mejorando el refinamiento visual (UX) y garantizando que el sistema sea 100% responsive sin alterar la lógica funcional.

---

## 🎨 Paleta de Colores Propuesta
- **Azul Base (Deep Dark):** `#0f172a` (Sidebar, Headers principales).
- **Azul Intermedio (Steel Blue):** `#1e293b` (Cards secundarias, Hover).
- **Azul de Acción (Electric Blue):** `#3b82f6` (Botones primarios, KPI destacados).
- **Fondo (Soft Slate):** `#f8fafc` (Contraste limpio para lectura).

---

## 🟩 Fase 1: Arquitectura Visual (Base)
- [ ] **1.1 Definición de Variables CSS:** Centralizar colores en `:root` dentro de `base.html.twig`.
- [ ] **1.2 Refactorización de Sidebar:** Hacerlo colapsable en móviles y con diseño más minimalista.
- [ ] **1.3 Estandarización de Cards:** Crear una clase `.dw-card` que sustituya/mejore el glass-morphism con bordes más finos y sombras suaves.

## 🟦 Fase 2: Componentes y Vistas
- [ ] **2.1 Botones y Estados:** Homogeneizar estilos de botones (bordes redondeados, transiciones suaves).
- [ ] **2.2 Optimización de Tablas:** Mejorar el espaciado y la legibilidad en pantallas pequeñas (scroll horizontal elegante).
- [ ] **2.3 Dashboard y Widgets:** Ajustar el diseño de los KPIs para que usen la escala de azules.

## 🟧 Fase 3: Responsive y Pulido
- [ ] **3.1 Media Queries:** Asegurar que el menú no estorbe en tablets y celulares.
- [ ] **3.2 Ajuste de Tipografía:** Refinar pesos de fuente para jerarquía visual.
- [ ] **3.3 Verificación de Interactividad:** Asegurar que los modales y eventos JS sigan funcionando al 100%.

---
*Nota: Este cambio es puramente estético y CSS-driven.*
