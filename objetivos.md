# Plan de Trabajo Actual

## 2026-01-23: Visualización de Total de Deudas

- **Meta:** Agregar un resumen visual que muestre la suma total de todas las deudas pendientes en la vista de "Cuentas por Pagar".
- **Archivo Objetivo:** `frontend/templates/caja/deudas.html.twig`.
- **Plan Técnico:**
    1.  Insertar un componente visual (KPI card) sobre el listado de deudas para mostrar el total acumulado. (Completado)
    2.  Modificar la función `renderDeudas` para calcular la suma de `saldoPendiente`. (Completado)
    3.  Aplicar formato de moneda profesional (COP) a toda la vista. (Completado)
- **Estado:** Completado.
