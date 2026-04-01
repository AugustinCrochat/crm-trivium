# Deploy - Sistema de Préstamos

## 1. Supabase Setup

1. Ve a [supabase.com](https://supabase.com) y crea un nuevo proyecto
2. En el SQL Editor de Supabase, ejecuta el contenido de `supabase/schema.sql`
3. En Settings > API, copia:
   - `Project URL` → será tu `DATABASE_URL` host
   - `anon public` key → la necesitarás después
4. En Settings > Database, copia el `password` del usuario `postgres`

## 2. Server API (Vercel)

1. Ve a [vercel.com](https://vercel.com) e importa el repositorio
2. Selecciona la carpeta `server/` como root
3. En Environment Variables, añade:
   - `DATABASE_URL`: `postgresql://postgres:[PASSWORD]@db.[PROJECT-REF].supabase.co:5432/postgres`
4. Deploy
5. Copia la URL del deployment (ej: `https://server-xxx.vercel.app`)

## 3. Client (Vercel)

1. En Vercel, importa el repositorio nuevamente
2. Selecciona la carpeta `client/` como root
3. En Environment Variables, añade:
   - `VITE_API_URL`: URL del server (ej: `https://server-xxx.vercel.app/api`)
4. Deploy

## 4. Actualizar Client con la URL del API

Si el client no puede alcanzar el API, verifica que `VITE_API_URL` apunte correctamente al server de Vercel.

## Migrar datos locales (opcional)

Si tienes datos en `server/database.sqlite` y quieres migrarlos:

1. Exporta los datos del SQLite
2. Insértalos en Supabase usando el SQL Editor o la API de Supabase

## Estructura de URLs

- Frontend: `https://tu-proyecto.vercel.app`
- API: `https://tu-server.vercel.app/api`
