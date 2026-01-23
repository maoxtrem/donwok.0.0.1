# Plan de Trabajo Actual

## 2026-01-23: Mejoras de UX en Punto de Venta (POS)

- **Meta:** Mantener el contenedor de la factura (`invoice-container`) visible durante el desplazamiento en la vista de Punto de Venta para mejorar la eficiencia operativa en escritorio.
- **Archivo Objetivo:** `frontend/templates/ventas/index.html.twig`.
- **Plan Técnico:**
    1.  Modificar los estilos de `.invoice-container` para usar `position: sticky`. (Completado)
    2.  Asegurar que el comportamiento sticky solo se aplique en pantallas grandes (desktop). (Completado)
    3.  Ajustar `top` y `max-height` para que el contenedor se adapte al viewport sin perderse. (Completado)
    4.  Verificar que el scroll interno de `invoice-body` siga funcionando correctamente. (Completado)
- **Estado:** Completado.
