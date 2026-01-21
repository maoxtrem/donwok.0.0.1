# Seguimiento de Objetivos: Ajuste Meticuloso de Cuentas por Pagar

## 🎯 Objetivo General
Asegurar que el módulo de "Cuentas por Pagar" cargue correctamente las naturalezas de deuda (Inversión, Gasto Pendiente, Crédito) y maneje el flujo de efectivo inicial de forma inteligente (Solo 'Crédito' activa entrada de dinero hoy).

---

## 🟩 Fase 1: Backend (Consistencia de Datos)
- [ ] **1.1 Validación de Categorías:** Asegurar que los nombres en la BD sean exactamente: `Inversión`, `Gasto Pendiente`, `Crédito`.
- [ ] **1.2 Lógica de Desembolso:** 
    - `Inversión` o `Gasto Pendiente` -> Registra deuda, NO genera movimiento de caja hoy.
    - `Crédito` -> Registra deuda Y genera **INGRESO** en caja/banco hoy.

## 🟦 Fase 2: Frontend (UX y Carga de Datos)
- [ ] **2.1 Solucionar Carga de Select:** Verificar por qué no están cargando las opciones en `deudas.html.twig`.
- [ ] **2.2 Campo de Cuenta Condicional:** 
    - Si categoría == `Crédito` -> Habilitar campo cuenta (Obligatorio).
    - Si categoría == `Inversión` / `Gasto Pendiente` -> Deshabilitar campo cuenta (Informativo).
- [ ] **2.3 Listado de Obligaciones:** Mostrar claramente el tipo de deuda en la tabla.

## 🟧 Fase 3: Pruebas de Flujo
- [ ] **3.1 Prueba de Fuego (Crédito):** Registrar Crédito -> Verificar ingreso en Caja Diaria.
- [ ] **3.2 Prueba de Fuego (Inversión):** Registrar Inversión -> Verificar que Caja Diaria NO cambia.
- [ ] **3.3 Prueba de Fuego (Abono):** Pagar cuota -> Verificar salida de dinero (Egreso) en Caja Diaria.

---
*Nota: Se trabajará exclusivamente en la ruta http://localhost/caja/deudas hasta completar los puntos.*
