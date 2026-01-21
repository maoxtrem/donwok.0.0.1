# Seguimiento de Objetivos: Refactorización Egresos Inmediatos y Pasivos

## 🎯 Objetivo General
Separar conceptual y técnicamente los "Egresos Inmediatos" (dinero que sale hoy) de las "Cuentas por Pagar" (obligaciones que se pagan en el tiempo). Asegurar que solo los movimientos de dinero real (efectivo/banco) afecten el cierre de caja diario.

---

## 🟩 Fase 1: Ajustes en el Core (Lógica de Negocio)
- [x] **1.1 RegistrarEgresoHandler:** Solo procesa salidas inmediatas de dinero.
- [x] **1.2 RegistrarDeudaHandler:** La creación de deuda solo genera movimiento inicial si se marca como desembolso de capital.
- [x] **1.3 RegistrarAbonoHandler:** Cada pago a una deuda genera un egreso (salida) auditable.
- [x] **1.4 RealizarCierreCajaHandler:** Consolida ingresos (ventas, cobros) y egresos (gastos inmediatos, pago de deudas).

## 🟦 Fase 2: Ajustes en el Frontend (Interfaz)
- [x] **2.1 Sidebar:** Cambiado a "Egresos Inmediatos".
- [x] **2.2 Vista Egresos Inmediatos:** Actualizada para mayor claridad.
- [x] **2.3 Vista Cuentas por Pagar:** Gestión de obligaciones y pagos que fluyen a la caja.

## 🟧 Fase 3: Auditoría de Flujo
- [x] **3.1 Prueba de Egreso Inmediato:** Validado.
- [x] **3.2 Prueba de Deuda Nueva:** Validado.
- [x] **3.3 Prueba de Abono a Deuda:** Validado (Ahora se registra como Egreso bajo "Pago de Obligaciones").

---
**¡Flujo Financiero Corregido!** DonWok ahora distingue perfectamente entre el gasto de hoy y la deuda del mañana.

---
*Nota: Se mantiene la arquitectura DDD y el patrón de Libro Mayor.*
