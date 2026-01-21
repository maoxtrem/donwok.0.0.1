# Seguimiento de Objetivos: Sistema de Informes y Analítica Avanzada

## 🎯 Objetivo General
Implementar un centro de analítica con Chart.js que permita visualizar la salud financiera de la empresa, comparando ingresos vs. egresos, rentabilidad de productos y tendencias de crecimiento mediante gráficas interactivas y filtros de fecha.

---

## 🟩 Fase 1: Core (Backend - Analítica)
- [ ] **1.1 Métodos de Agregación en Repositorio:**
    - `MovimientoFinancieroRepository`: Método para obtener totales agrupados por día y tipo (Ingreso/Egreso) en un rango de fechas.
    - `FacturaDetalleRepository`: Método para calcular la utilidad bruta (Ventas - Costos) agrupada por producto o por día.
- [ ] **1.2 Handler de Estadísticas (KPIs):** Crear `ObtenerResumenInformeHandler` para calcular:
    - Total Ingresos, Total Gastos, Total Inversiones.
    - Margen de Ganancia Neto.
    - Porcentaje de Crecimiento (comparado con el periodo anterior).
- [ ] **1.3 Endpoints de API:**
    - `GET /informes/movimientos-diarios?desde=...&hasta=...`
    - `GET /informes/rentabilidad-productos`
    - `GET /informes/resumen-general`

## 🟦 Fase 2: Frontend (Interfaz y Gráficas)
- [ ] **2.1 Vista de Informes:** Crear `templates/informes/index.html.twig`.
- [ ] **2.2 Integración de Chart.js:** Configurar la librería y crear wrappers para los gráficos.
- [ ] **2.3 Gráfica Lineal (Tendencia):** 
    - Eje X: Días.
    - Eje Y: Montos.
    - Líneas: Ingresos, Gastos, Inversiones.
- [ ] **2.4 Gráfica de Barras (Rentabilidad):** Comparativa de Ventas vs. Costos por categoría o producto.
- [ ] **2.5 Tarjetas de Indicadores:** 
    - Ganancia Total.
    - Promedio de Venta Diaria.
    - Indicador de Crecimiento (Flecha arriba/abajo).

## 🟧 Fase 3: Refinamiento y Auditoría
- [ ] **3.1 Validación de Datos:** Asegurar que los cálculos coincidan con el libro mayor (`MovimientoFinanciero`).
- [ ] **3.2 Optimización de Consultas:** Verificar que los reportes sean rápidos incluso con miles de registros.
- [ ] **3.3 UI/UX:** Aplicar Bootstrap 5 para que el dashboard sea "premium" y responsivo.

---
*Nota: Se utilizarán exclusivamente las interfaces de repositorio definidas en las fases anteriores.*
