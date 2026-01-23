# Plan de Trabajo Actual

## 2026-01-23: Mejoras Visuales en Gráficos de Informes

- **Meta:** Mejorar la legibilidad de la gráfica lineal "Tendencia Diaria" agregando puntos visibles en los vértices de los datos.
- **Archivo Objetivo:** `frontend/templates/informes/index.html.twig`.
- **Plan Técnico:**
    1.  Modificar la configuración de `chartDiario` (Chart.js).
    2.  Cambiar `pointRadius: 0` a `pointRadius: 4` (o un valor visible) en los datasets.
    3.  Añadir `pointHoverRadius: 6` para mejorar la interacción.
- **Estado:** Completado.
