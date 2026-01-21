# Seguimiento de Objetivos: Cierre de Caja Integral (4 Pilares)

## 🎯 Objetivo General
Refactorizar el sistema de caja para que las Facturas, Gastos, Inversiones y Préstamos queden en estado "Pendiente de Cierre" y no afecten los movimientos financieros hasta que se realice el cierre manual de caja.

---

## 🟩 Fase 1: Core (Backend - Lógica de Negocio)
- [ ] **1.1 Estados en Entidades:** Añadir campo `isCerrado` (boolean) o `estado` a las entidades `Gasto` y `Prestamo`.
- [ ] **1.2 Refactorizar RegistrarEgresoHandler:** Eliminar la creación automática de `MovimientoFinanciero`. El registro ahora solo guarda la entidad (Gasto/Prestamo) en estado pendiente.
- [ ] **1.3 Refactorizar RealizarCierreCajaHandler:** 
    - Recolectar: Facturas (`FACTURADO`), Gastos, Inversiones y Préstamos (`Pendientes`).
    - Consolidar movimientos financieros por cada tipo y cuenta.
    - Marcar TODO como cerrado.
- [ ] **1.4 Nuevos Endpoints de Consulta:**
    - Listado de "Egresos en Cola" (Gastos, Inversiones, Préstamos).
    - Endpoint para eliminación física de egresos en cola.

## 🟦 Fase 2: Frontend (Interfaz de Caja Integral)
- [ ] **2.1 Rediseño de "Caja Diaria":** 
    - Sección de Ingresos (Facturas).
    - Sección de Egresos (Gastos, Inversiones, Préstamos).
- [ ] **2.2 Tablero de Resumen:** Mostrar en tiempo real: `Total Ingresos - Total Egresos = Saldo en Caja`.
- [ ] **2.3 Acciones de Gestión:**
    - Botón "Anular" para facturas.
    - Botón "Eliminar" para gastos, inversiones y préstamos.
- [ ] **2.4 Confirmación de Cierre:** Mostrar el resumen final antes de procesar el cierre masivo.

## 🟧 Fase 3: Base de Datos y Validación
- [ ] **3.1 Actualización de Esquema:** Ejecutar `schema:update --force`.
- [ ] **3.2 Pruebas de Integridad:** Verificar que tras el cierre, los egresos ya no permiten eliminación.

---
*Nota: Se centraliza la verdad financiera en el acto del Cierre de Caja.*
