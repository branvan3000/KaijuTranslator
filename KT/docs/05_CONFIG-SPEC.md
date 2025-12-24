# 05 — Especificación de configuración

## 1. Configuración mínima (obligatoria)

- `base_lang`: idioma base (ej.: `es`)
- `languages`: idiomas activos (ej.: `["es","en","fr"]`)
- `base_url`: (Opcional) La URL pública del sitio. Requerido para sitemaps.
- `translation_provider`: `openai`, `deepseek` o `gemini`.
- `api_key`: clave API (preferible en variables de entorno)

## 2. Descubrimiento y Escaneo

- `allowed_paths`: lista blanca de directorios escaneables (raíz del proyecto por defecto).
- `excluded_paths`: patrones de exclusión (ej. `vendor`, `.git`, `KT`).

## 3. SEO (toggles)

- `seo.hreflang_enabled` (default: true): Activa la inyección de etiquetas `hreflang`.
- `seo.canonical_strategy`: (default: `self`):
  - `self`: apunta a la URL traducida actual.
  - `none`: desactiva la inyección automática de canonical.

## 4. Rutas y Almacenamiento

- `cache_path`: directorio para el cache de archivos HTML.
- `sitemaps_path`: directorio donde se generarán los sitemaps XML.

---

> [!NOTE]
> Los campos `mode`, `discovery_mode`, `qa` y `state_path` han sido eliminados del motor para simplificar la arquitectura y asegurar un mantenimiento más robusto.
