# Seguimiento de Objetivos: Cierre de Caja

## 🎯 Objetivo General
Implementar un flujo donde las facturas emitidas no afecten el balance financiero hasta que se realice un cierre de caja manual, momento en el cual se consolidan como un movimiento de ingreso y las facturas quedan archivadas.

---

## 🟩 Fase 1: Core (Backend - DDD)
- [x] **1.1 Modificar Entidad Factura:** Añadir propiedad `estado` (ya existe) y constantes `STATUS_EMITIDA`, `STATUS_CERRADA`.
- [x] **1.2 Repositorio de Facturas:** Crear método para obtener facturas pendientes de cierre (`findByEstado(STATUS_EMITIDA)`).
- [x] **1.3 Handler de Cierre de Caja:** Crear `RealizarCierreCajaHandler` que:
    - Sume el total de facturas emitidas.
    - Cree un `MovimientoFinanciero` (Ingreso).
    - Marque las facturas como `CERRADA`.
- [x] **1.4 Endpoint API:** Exponer el comando de cierre de caja.

## 🟦 Fase 2: Frontend (Interfaz - React/Twig)
- [x] **2.1 Vista "Facturas del Día":** Tabla que liste facturas con estado `EMITIDA`.
- [x] **2.2 Acción "Cerrar Caja":** Implementar modal de confirmación y llamada al API del core.
- [x] **2.3 Vista "Movimientos":** Listado de movimientos financieros filtrables para ver los ingresos de cierres anteriores.

## 🟧 Fase 3: Validación
- [x] **3.1 Pruebas de Integración:** Verificar en `request.http` que el flujo completa el ciclo correctamente.
- [x] **3.2 UX/UI:** Asegurar que las facturas cerradas desaparezcan de la vista operativa.

---
**¡Implementación Exitosa!** Todos los puntos han sido cubiertos.
