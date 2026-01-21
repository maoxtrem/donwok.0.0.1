# Seguimiento de Objetivos: Refactorización de Deudas y Flujo de Caja

## 🎯 Objetivo General
Corregir el flujo de datos para distinguir entre **Movimientos de Efectivo** (lo que entra/sale de caja) y **Obligaciones** (Deudas/Créditos). El Cierre de Caja solo debe procesar dinero real movido, mientras que las deudas permanecen abiertas en el sistema hasta su liquidación total.

---

## 🟩 Fase 1: Core (Backend - Lógica Contable)
- [ ] **1.1 Refactorizar Entidad Gasto:** Añadir flag `esACredito`. Si es true, el gasto no debe aparecer en la Caja Diaria ni generar movimiento al cerrar (solo genera la deuda).
- [ ] **1.2 Refactorizar RegistrarEgresoHandler:** 
    - Si el egreso es "A Crédito" -> Crear la Deuda pero no marcar nada para el cierre de caja.
    - Si el egreso es "Contado" -> Marcar para cierre de caja.
- [ ] **1.3 Independizar Cierre de Caja de los Préstamos:** 
    - El Cierre de Caja ya no debe marcar el `Prestamo` como `isCerrado`.
    - Debe procesar únicamente los **Abonos** (`PagoPrestamo`) y, si hubo, el **Desembolso Inicial** (como un evento separado).
- [ ] **1.4 Eliminar `isCerrado` de la Entidad `Prestamo`:** La deuda se mantiene viva por su `saldoPendiente` y `estado`, no por el cierre de caja.

## 🟦 Fase 2: Frontend (Interfaz y UX)
- [ ] **2.1 Opción "A Crédito" en Egresos:** Añadir un switch al formulario para indicar si el gasto/inversión se paga hoy o se convierte en una deuda pendiente.
- [ ] **2.2 Vista de Caja Diaria:** Asegurar que solo muestre transacciones de dinero real (Ventas, Abonos, Gastos de contado).
- [ ] **2.3 Balance General:** Reflejar las deudas pendientes independientemente de cuántos cierres de caja hayan pasado.

## 🟧 Fase 3: Validación
- [ ] **3.1 Prueba de Deuda Proveedor:** Registrar gasto a crédito -> Verificar que NO sale dinero en el cierre de hoy -> Verificar que aparece en "Cuentas por Pagar".
- [ ] **3.2 Prueba de Abono:** Pagar cuota -> Verificar que SÍ aparece en el cierre de hoy.

---
*Nota: El dinero en caja debe cuadrar con la realidad física, las deudas son registros de compromiso futuro.*
