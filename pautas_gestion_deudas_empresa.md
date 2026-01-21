# Seguimiento de Objetivos: Gestión de Deudas de la Empresa (Pasivos)

## 🎯 Objetivo General
Implementar un módulo de "Cuentas por Pagar" que permita registrar obligaciones financieras adquiridas por la empresa (Créditos recibidos, Compras a crédito, Inversiones pendientes de pago) y gestionar su amortización mediante abonos que impacten el Libro Mayor como Egresos.

---

## 🟩 Fase 1: Core (Backend - Pasivos)
- [x] **1.1 Adaptar Entidad Prestamo:** Soporta `RECIBIDO` y `OTORGADO`.
- [x] **1.2 Handler para Registro de Obligaciones:** Implementado `RegistrarDeudaHandler`.
- [x] **1.3 Refactorizar RegistrarAbonoHandler:** Soporta abonos de cartera (Ingreso) y pagos de deuda (Egreso).
- [x] **1.4 Endpoints de Deudas:** `GET /caja/deudas` y `POST /caja/deudas/registrar`.

## 🟦 Fase 2: Frontend (Gestión de Pasivos)
- [x] **2.1 Vista "Cuentas por Pagar":** Implementada con listado y gestión.
- [x] **2.2 Formulario de Nueva Obligación:** Soporta créditos y deudas a proveedores.
- [x] **2.3 Lógica de Pago de Cuotas:** Integrada con el Libro Mayor.

## 🟧 Fase 3: Integración en Balance
- [x] **3.1 Actualizar Balance General:** Añadida sección de Pasivos y Patrimonio Neto.
- [x] **3.2 Reporte de Amortización:** Visible en el historial de movimientos.

---
**¡Sistema de Pasivos Integrado!** DonWok ahora tiene control total de sus obligaciones.

---
*Nota: Una deuda bien gestionada es la clave para el crecimiento de DonWok.*
