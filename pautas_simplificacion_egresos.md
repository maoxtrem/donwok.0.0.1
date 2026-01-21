# Seguimiento: Simplificación de Egresos (Salidas Inmediatas)

## 🎯 Objetivo
Simplificar el módulo de Egresos para que registre únicamente salidas de dinero inmediatas. Las deudas a largo/corto plazo se gestionarán exclusivamente desde el módulo de "Cuentas por Pagar".

---

## 🟩 Fase 1: Core (Backend)
- [x] **1.1 Refactorizar RegistrarEgresoHandler:** Eliminada la lógica de "A Crédito". Todos los registros generan una salida de dinero inmediata.

## 🟦 Fase 2: Frontend (Interfaz)
- [x] **2.1 Limpiar Formulario de Egresos:** Eliminado el switch "¿Es a crédito?" y cuenta de origen obligatoria siempre.

---
**¡Flujo Simplificado!** El módulo de Egresos ahora es exclusivo para salidas de efectivo inmediatas.

---
**Resultado esperado:** Un flujo más directo y menos confuso para el usuario.
