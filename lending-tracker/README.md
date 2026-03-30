# Lending Tracker (PHP y MySQL)

Sistema simple para administrar préstamos/inversiones en hosting compartido (Hostinger friendly) con UI *mobile-first*.

## Funcionalidades

- Login para admin y para prestamistas
- Panel admin con:
  - Total prestado
  - Cantidad de préstamos activos
  - Configuración de la tasa anual global de retorno
- El admin puede crear cuentas de prestamistas
- El admin puede crear préstamos para cada prestamista
- El admin puede registrar depósitos y fechas de pago
- Panel del prestamista con:
  - Total prestado
  - Interés total proyectado
  - Valor de ganancia diaria
  - Simulador de préstamos usando la tasa fija del admin
  - Historial de depósitos y pagos

## Fórmula de ganancia diaria

En el panel del prestamista se usa la fórmula pedida:

- `Valor diario = (Capital + Interés) / Días inmovilizado`

Donde:

- `Interés = Capital * (TasaAnual / 100) * (Días / 365)`

## Setup

1. Crear base de datos y tablas:
   - Importá `sql/schema.sql` en phpMyAdmin.
2. Configurá las credenciales en `config/database.php`:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
3. Subí la carpeta `lending-tracker` a tu Hostinger (dentro de `public_html`).
4. Entrá a:
   - `/lending-tracker/login.php`

## Login admin por defecto

- Email: `admin@local.test`
- Contraseña: `admin123`

Cambiá esa contraseña apenas entres por primera vez (después podemos agregar una pantalla de cambio de contraseña).

