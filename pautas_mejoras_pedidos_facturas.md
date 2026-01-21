# Seguimiento de Objetivos: Mejoras Pedidos y Facturas

## 🎯 Objetivo General
Mejorar la visibilidad de los pedidos en cola, permitir la visualización de detalles de factura, implementar la anulación de facturas (auditables) y la eliminación de pedidos pendientes.

---

## 🟩 Fase 1: Core (Backend - DDD)
- [x] **1.1 Entidad Factura:** Añadir constante `ESTADO_ANULADA`.
- [x] **1.2 Lógica de Cierre:** Asegurar que `RealizarCierreCajaHandler` ignore facturas con estado `ANULADA`.
- [x] **1.3 Handler de Anulación:** Crear `AnularFacturaHandler` (Cambio de estado).
- [x] **1.4 Handler de Eliminación:** Crear `EliminarPedidoHandler` (Eliminación física de la BD).
- [x] **1.5 Endpoint de Estadísticas:** Crear endpoint en `PedidoController` que devuelva conteos de pedidos (Pendientes vs Terminados).

## 🟦 Fase 2: Frontend (Interfaz - Twig/JS)
- [x] **2.1 Badges de Notificación (Ventas):** Insertar contadores en tiempo real en la vista de Ventas (POS).
- [x] **2.2 Detalle de Factura (Caja):** Implementar modal para ver los productos/ítems de cada factura.
- [x] **2.3 Acción de Anular:** Añadir botón de anulación en la lista de caja con confirmación.
- [x] **2.4 Acción de Eliminar Pedido:** Añadir botón de eliminar en la "Gestión de Cola" para pedidos no facturados.

## 🟧 Fase 3: Validación
- [ ] **3.1 Pruebas de Integración:** Verificar que una factura anulada no sume al cierre de caja.
- [ ] **3.2 Sincronización SSE:** Asegurar que los badges se actualicen al recibir eventos de Mercure.

---
*Nota: Se mantiene el estándar de Bootstrap 5 y la separación de lógica.*
