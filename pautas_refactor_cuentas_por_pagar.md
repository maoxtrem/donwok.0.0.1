# Seguimiento de Objetivos: Refactorización de Cuentas por Pagar (Pasivos)

## 🎯 Objetivo General
Rediseñar el módulo de "Cuentas por Pagar" para que permita clasificar las deudas de la empresa en tres tipos (Inversión, Gasto, Préstamo de Capital) y asegurar que el flujo de efectivo sea preciso: entrada de dinero inicial solo para préstamos, y salida de dinero para todos los abonos.

---

## 🟩 Fase 1: Core (Backend - Lógica de Pasivos)
- [ ] **1.1 Modificar Entidad Préstamo:** Añadir relación con `CategoriaFinanciera` para clasificar la naturaleza de la deuda.
- [ ] **1.2 Refactorizar RegistrarDeudaHandler:** 
    - **Inversión / Gasto:** Registra la obligación sin mover efectivo hoy (Cierre de Caja limpio).
    - **Préstamo (Efectivo):** Registra la obligación y genera un `PagoPrestamo` (Desembolso) como **INGRESO** en la caja de hoy.
- [ ] **1.3 Refactorizar RegistrarAbonoHandler:** Asegurar que CUALQUIER abono a estas deudas genere un **EGRESO** en la caja del día del pago.

## 🟦 Fase 2: Frontend (Interfaz Premium)
- [ ] **2.1 Rediseño de Vista Deudas:** Aplicar el diseño de dos columnas (Formulario Izquierda | Listado Derecha) de Egresos.
- [ ] **2.2 Formulario Inteligente:** 
    - Selector de Categoría (Inversión, Gasto, Préstamo).
    - Lógica para indicar si entra dinero hoy (automático por categoría).
- [ ] **2.3 Tabla de Obligaciones:** Mostrar el tipo de deuda y el saldo pendiente de forma clara.

## 🟧 Fase 3: Integración y Cierre
- [ ] **3.1 Actualización de Esquema:** `schema:update --force`.
- [ ] **3.2 Prueba de Fuego:** 
    - Deuda por Inversión -> No afecta caja hoy.
    - Préstamo de Capital -> Sube el efectivo hoy.
    - Abono a cualquiera -> Baja el efectivo hoy.

---
*Nota: La contabilidad de DonWok debe ser exacta entre lo que se debe y lo que se tiene.*
