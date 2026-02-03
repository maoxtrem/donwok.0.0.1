# Plan de Trabajo Actual

## 2026-01-23: Refinamiento de Botones de Efectivo (Suma Acumulativa)

- **Meta:** Cambiar el comportamiento de los botones de efectivo para que sumen al valor actual, mostrar denominaciones completas y añadir un botón de reset.
- **Archivo Objetivo:** `frontend/templates/pedidos/gestion.html.twig`.
- **Plan Técnico:**
    1.  Modificar los labels de los botones para mostrar los ceros completos con formato de miles. (Completado)
    2.  Actualizar la lógica de JS para realizar sumas acumulativas al presionar los botones. (Completado)
    3.  Añadir un botón de "X" (Reset) en el input-group del efectivo recibido. (Completado)
- **Estado:** Completado.

## 2026-02-02: Gestión de Pago y Tipo de Pedido

- **Meta:** Añadir control de pago previo y tipo de pedido (Mesa, Llevar, Domicilio, WhatsApp) sin afectar el flujo de facturación final.
- **Plan Técnico:**
    1.  Modificar la entidad `Factura` en el núcleo (`core`) para incluir los campos `esPago` (bool) y `tipo` (string).
    2.  Actualizar `PedidoRequestDTO` y `CreatePedidoHandler` para soportar estos nuevos campos en la creación.
    3.  Añadir un endpoint o actualizar el existente para permitir cambiar el estado de pago y el tipo de un pedido existente.
    4.  Actualizar la interfaz del frontend (`pedidos/gestion.html.twig`) para mostrar y permitir editar estos campos.
- **Estado:** En progreso.
