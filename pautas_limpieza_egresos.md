# Seguimiento de Objetivos: Simplificación y Limpieza de Egresos Inmediatos

## 🎯 Objetivo General
Limpiar el módulo de "Egresos Inmediatos" para que solo maneje tres categorías específicas y asegurar que los registros desaparezcan de esta vista tras el cierre de caja, delegando el historial al Libro Mayor (Movimientos).

---

## 🟩 Fase 1: Core (Backend)
- [x] **1.1 Ajustar Categorías:** Asegurado que existan "Inversión", "Gasto" y "Préstamo Otorgado".
- [x] **1.2 Filtrado de Egresos:** El listado ahora solo muestra pendientes de cierre.
- [x] **1.3 Endpoint de Categorías Filtradas:** Implementado filtro en el controlador.

## 🟦 Fase 2: Frontend (Interfaz)
- [x] **2.1 Formulario de Egresos:** Opciones limitadas a las 3 requeridas.
- [x] **2.2 Limpieza de Historial Local:** La tabla se vacía automáticamente tras el cierre masivo.

---
**¡Módulo Simplificado!** Los egresos inmediatos ahora son transitorios y el historial se centraliza en Movimientos.

## 🟧 Fase 3: Validación
- [ ] **3.1 Prueba de Flujo Completo:** Registrar un gasto -> Ver en cola -> Cerrar caja -> Verificar que ya no aparece en "Egresos Inmediatos" pero SÍ en "Movimientos".

---
*Nota: La fuente de verdad histórica es la tabla `movimientos_financieros`.*
