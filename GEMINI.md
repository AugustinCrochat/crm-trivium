# CRM Trivium - Project Documentation

## Project Overview
**Trivium CRM** is a custom-built management system for Trivium, a logistics and import company. It is designed to bridge the gap between commercial operations (quotes, sales) and logistics (shipments, trips) while integrating with external ERP systems like **Tango Tiendas** and **Tango Gestión**.

### Main Modules
- **Comercial**: Management of *Clientes* (Leads/Active), *Presupuestos* (Quotes), and *Ventas* (Sales).
- **Catálogo**: Product management synchronized with Tango.
- **Logística**: Planning of *Viajes* (Trips), tracking of *Envíos* (Shipments), and *Transportes*.
- **Importaciones**: Tracking of international shipments (*Importaciones*) and *Forwarders*.
- **Integración**: Dedicated logic for Tango API and Webhooks.

## Architecture & Technology Stack
- **Language**: PHP 8+ (Vanilla).
- **Database**: MySQL 8.0 (PDO for connection).
- **Frontend**: Tailwind CSS (CDN-based) with a responsive sidebar layout.
- **Icons**: Inline SVGs (Heroicons).
- **Patterns**: Traditional Multi-Page Application (MPA) with directory-based routing (e.g., `/clientes/index.php`).
- **AJAX**: Custom `fetch`-based live search on main index pages.

## Key Files & Directories
- `config/db.php`: Database connection, session management, and global constants.
- `includes/functions.php`: Essential utility functions (`esc`, `money`, `badge`, `csrf`, `flash`).
- `includes/header.php` & `includes/footer.php`: Global layout, navigation sidebar, and shared JS logic.
- `tango/`: Integration layer for Tango Tiendas API.
- `api/sync.php`: Backend endpoint for Tango Gestión synchronization.
- `assets/css/app.css`: Custom CSS overrides for Tailwind.

## Building and Running
1. **Requirements**: PHP 8.1+, MySQL 8.0.
2. **Installation**:
   - Clone the repository to your web server (Apache/Nginx).
   - Import the database schema (if provided, otherwise infer from models).
   - Configure `config/db.php` with correct credentials.
3. **Environment**: Currently uses hardcoded credentials in `config/db.php`.
   - *TODO: Implement .env support for easier environment management.*

## Development Conventions
- **Language**: Mixed. Business logic (folders, variables) is in **Spanish**, while technical infrastructure is often in **English**.
- **Security**:
  - Always use `esc($value)` for escaping HTML output.
  - Use `csrf_field()` and `verify_csrf()` for all state-changing POST requests.
- **Live Search**: Index pages should use a form with `method="GET"` and an `input[type="search"]`. The results should be wrapped in a container with `id="search-results"` to support the automatic AJAX debounce.
- **Formatting**:
  - Dates: `d/m/Y` (using `fecha()` helper).
  - Currency: `$ 1.234,56` (using `money()` helper).
- **Icons**: Prefer using inline SVGs from Heroicons for consistency.
