# Seguimiento de Objetivos: Visibilidad de Abonos en Caja Diaria

## 🎯 Objetivo General
Asegurar que todos los abonos (pagos de deudas y cobros de cartera) sean visibles en la vista de "Caja Diaria" antes del cierre, permitiendo un control total de los movimientos que se consolidarán.

---

## 🟩 Fase 1: Core (Backend)
- [ ] **1.1 Actualizar `egresosPendientes`:** Incluir en este listado los pagos realizados a deudas de la empresa (`PagoPrestamo` de préstamos `RECIBIDO`).
- [ ] **1.2 Actualizar `facturasEmitidas` (o crear `ingresosPendientes`):** Incluir los cobros de cartera (`PagoPrestamo` de préstamos `OTORGADO`) para que sumen al total de ingresos del día.

## 🟦 Fase 2: Frontend (Interfaz)
- [ ] **2.1 Ajustar Tabla de Ingresos:** Mostrar tanto facturas como abonos recibidos.
- [ ] **2.2 Ajustar Tabla de Egresos:** Mostrar tanto gastos/inversiones como pagos de deudas realizados.
- [ ] **2.3 Recálculo de Balance:** Asegurar que el saldo estimado tome en cuenta estos nuevos rubros.

---
*Nota: Un abono es un movimiento de dinero real y debe ser auditable antes del cierre.*
