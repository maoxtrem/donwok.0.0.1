# Plan de Trabajo Actual

## 2026-01-23: Reorganización de Navegación (UX)

- **Meta:** Reestructurar el menú lateral (`sidebar`) para mejorar la coherencia y navegabilidad.
- **Archivo Objetivo:** `frontend/templates/base.html.twig` (donde reside el sidebar actual).
- **Plan Técnico:**
    1. Agrupar items por contexto funcional:
        - **Operación:** Dashboard, Punto de Venta, Gestión de Cola.
        - **Gestión:** Inventario, Informes y Analítica.
        - **Finanzas y Caja:** Arqueo, Balance, Movimientos, Egresos, Deudas, Préstamos.
        - **Herramientas:** Monitor Público.
    2. Aplicar cambios en el HTML del sidebar.
- **Estado:** Completado.
