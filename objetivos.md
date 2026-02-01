# Plan de Trabajo Actual

## 2026-01-23: Refinamiento de Botones de Efectivo (Suma Acumulativa)

- **Meta:** Cambiar el comportamiento de los botones de efectivo para que sumen al valor actual, mostrar denominaciones completas y añadir un botón de reset.
- **Archivo Objetivo:** `frontend/templates/pedidos/gestion.html.twig`.
- **Plan Técnico:**
    1.  Modificar los labels de los botones para mostrar los ceros completos con formato de miles. (Completado)
    2.  Actualizar la lógica de JS para realizar sumas acumulativas al presionar los botones. (Completado)
    3.  Añadir un botón de "X" (Reset) en el input-group del efectivo recibido. (Completado)
- **Estado:** Completado.

## 2026-01-28: Corrección de error de tipos en RegistrarEgresoHandler

- **Meta:** Corregir el error en la instanciación de la entidad `Prestamo` donde se omitía el argumento de categoría.
- **Plan Técnico:**
    1. Identificar la firma del constructor de `App\Domain\Entity\Prestamo`.
    2. Actualizar `RegistrarEgresoHandler::handle` para pasar el objeto `CategoriaFinanciera` como quinto argumento.
- **Estado:** Completado.
