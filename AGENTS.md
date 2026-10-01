# Reglas y Directivas del Proyecto El SAS Piscícola

- **Proyecto:** El SAS Piscícola, SaaS multi-tenant para fincas piscícolas. Stack: Laravel + PostgreSQL + API RESTful. Zona horaria America/Bogota.
- **Multi-Tenancy:** Toda tabla de negocio lleva tenant_id y todo se filtra por tenant (global scope).
- **Roles:** propietario, tecnico_acuicola, operario_alimentador, celador.
- **Alcance Estricto:** Haz SOLO lo que pida el prompt actual. No agregues módulos, páginas, paquetes ni datos de ejemplo que no se pidan.
- **Aprobación Previa:** Antes de crear o modificar archivos, lista cuáles vas a tocar y espera mi aprobación.
- **Integridad del Stack:** No cambies el stack ni reescribas archivos existentes sin que se pida. Si algo choca, pregunta antes.
- **Transaccionalidad y Auditoría:** Todo movimiento de stock, peces o biomasa va en transacción y queda como registro histórico, nunca solo sobrescribir saldos.
- **Pruebas Automatizadas:** Escribe pruebas para cada regla de negocio y córrelas antes de terminar.
- **Cierre:** Al terminar, resume en máximo 10 líneas qué hiciste y qué falta.
