# local_aitutor — POC de integración del Tutor IA en Moodle

Plugin `local` de Moodle que inyecta un widget de chat flotante ("burbuja")
en las páginas de Moodle. Es la POC de **integración** (cómo entra el widget
a Moodle, cómo se autentica, cómo sabe en qué curso está) — todavía no tiene
un tutor IA real detrás: el backend responde con un texto fijo.

## Qué resuelve esta POC

- Cómo inyectar UI propia (HTML/CSS/JS) en Moodle sin depender de bloques ni
  del theme, vía el callback `before_footer` (compatible con Moodle 4.1 y 4.5).
- Cómo autenticar las llamadas del widget contra Moodle sin inventar un login
  aparte: se reutiliza la sesión de Moodle (`sesskey`) en vez de manejar
  tokens en el navegador.
- Cómo obtener el contexto (usuario, curso) del lado del servidor, donde es
  confiable, en lugar de confiar en lo que mande el JS del navegador.
- Cómo restringir el widget a cursos puntuales (alcance por curso) en vez de
  mostrarlo/responder en toda la plataforma.

## Arquitectura

```
Navegador (bubble JS, antes_footer)
   │  fetch POST con sesskey + courseid (sesión de Moodle ya autenticada)
   ▼
local/aitutor/ajax.php (PHP, corre dentro de Moodle)
   │  require_login() + require_sesskey() + chequeo de curso habilitado
   │  (acá es donde, a futuro, se llama server-to-server al backend real
   │  del tutor con su propia API key -- hoy devuelve una respuesta fija)
   ▼
Respuesta JSON → se pinta en el panel del chat
```

La decisión clave: el navegador nunca le habla directo a un backend externo.
Todo pasa primero por Moodle (que ya sabe quién sos y en qué curso estás), y
sería Moodle quien reenvíe al backend real del tutor. Así no hay que resolver
CORS ni un segundo login en el navegador.

## Archivos

| Archivo | Qué hace |
|---|---|
| `version.php` | Metadata del plugin. `requires` está pineado al build de Moodle 4.1.0 para poder instalar en las dos LTS objetivo (4.1 y 4.5). |
| `lib.php` | Callback `local_aitutor_before_footer()` que arma e inyecta la burbuja + el chat (HTML/CSS/JS inline). También define `local_aitutor_is_course_enabled()`, el chequeo de alcance por curso. |
| `ajax.php` | Endpoint que recibe el mensaje del chat, valida sesión/sesskey/curso habilitado, y devuelve la respuesta (hoy hardcodeada). |
| `settings.php` | Página de configuración en el admin (lista de cursos habilitados). |
| `lang/es/`, `lang/en/` | Textos del plugin, en español (idioma del sitio) e inglés (fallback). |

## Alcance por curso (course scoping)

El widget **no aparece en ningún curso por defecto** — es opt-in explícito,
nunca opt-out. La razón: preferimos que un curso nuevo "no tenga tutor" por
omisión antes que exponerlo por accidente en un curso donde no corresponde.

El chequeo se hace en dos lugares, y el que realmente importa es el segundo:

1. **Frontend** (`lib.php`): si el curso actual no está habilitado, la
   burbuja directamente no se inyecta en el HTML. Es solo cosmético — evita
   mostrar algo que después no va a funcionar.
2. **Backend** (`ajax.php`): aunque alguien arme el POST a mano (DevTools,
   Postman) con un `courseid` de un curso no habilitado, el endpoint
   responde `403` sin ejecutar ninguna lógica del tutor. Esta es la
   protección real, porque no depende de que el navegador se comporte bien.

### Cómo configurarlo

1. Entrar como admin a **Administración del sitio → Plugins → Plugins
   locales → Tutor IA (POC)**.
2. En el campo **"Cursos habilitados"**, escribir los **shortnames** (nombre
   corto, no el nombre completo) de los cursos donde debe aparecer el
   widget, separados por coma. Ejemplo:
   ```
   comunidad29, curso-piloto-2
   ```
3. Guardar cambios. No hace falta purgar cachés ni reiniciar nada: el
   chequeo lee la configuración en cada request.
4. El shortname de un curso se ve en **Configuración del curso** (o en la
   URL al entrar a esa pantalla).

Vacío = deshabilitado en todos los cursos (incluida la portada del sitio,
que además está excluida siempre sin importar la configuración).

## Instalación / actualización

El plugin vive fuera de la imagen Docker, montado como bind mount (ver
`../docker/docker-compose.yml`) en `/var/www/html/local/aitutor` dentro de
ambos contenedores (`moodle-45` y `moodle-41`). Esto permite editar el
código en el host y verlo reflejado al instante en el contenedor, sin
rebuildear la imagen.

Después de cualquier cambio en `version.php`, `lib.php` (nuevas funciones de
instalación) o al agregar `settings.php` por primera vez, hay que avisarle a
Moodle que hay una actualización pendiente:

```bash
cd ../docker
docker compose exec moodle-45 php admin/cli/upgrade.php --non-interactive
docker compose exec moodle-41 php admin/cli/upgrade.php --non-interactive
```

Cambios que **no** requieren esto (por ejemplo, tocar el HTML/JS/CSS de la
burbuja en `lib.php`, o la lógica de `ajax.php`): alcanza con recargar la
página del navegador, ya que PHP los interpreta en cada request.

## Estado / próximos pasos

- [x] Widget flotante inyectado vía plugin `local`.
- [x] Autenticación reutilizando sesión de Moodle (sesskey), sin login aparte.
- [x] Contexto (usuario, curso) resuelto del lado del servidor.
- [x] Alcance por curso, opt-in, validado en frontend y backend.
- [ ] Conectar `ajax.php` a un backend real (hoy responde texto fijo).
- [ ] Definir cómo el tutor accede a contenido del curso (Web Services de
      Moodle: `core_course_get_contents`, foros, calificaciones, etc.).
- [ ] Pinear la versión exacta de Moodle 4.1 en `docker-compose.yml` (ver
      TODO en `../docker/versions.md`).
