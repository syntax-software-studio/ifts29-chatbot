# Versiones pineadas para la POC

Si cambia algo acá, se refleja en `docker-compose.yml` y se commitea junto. No se usa  `latest`.

| Instancia  | Moodle ref              | PHP        | DB               | Estado                     |
|------------|--------------------------|------------|------------------|----------------------------|
| moodle-45  | `v4.5.13` (tag, 2026-08-08) | 8.3-apache | postgres:15.8    | ✅ pineado                 |
| moodle-41  | `MOODLE_401_STABLE` (rama, **sin pinear a commit**) | 8.1-apache | postgres:14.13   | ⚠️ pendiente, ver abajo    |

## Por qué falta pinear 4.1

`v4.5.13` revisado contra los tags del repo oficial de Moodle. Para la rama 4.1 falta confirmación de cuál fue el último tag/commit antes de que terminara el soporte de seguridad (8 dic 2025), así que dejé apuntando a la rama estable `MOODLE_401_STABLE` como placeholder — **eso NO es reproducible** (la rama puede recibir commits) y hay que resolverlo a un commit SHA fijo antes de considerar esto una POC seria.

### Cómo resolverlo (correr una sola vez y pegar el resultado acá)

```bash
git ls-remote https://github.com/moodle/moodle.git 'refs/tags/v4.1.*'
# Tomar el tag más alto (el último release/security patch de la 4.1.x)
```

Una vez identificado, reemplazar en `docker-compose.yml`:

```yaml
MOODLE_GIT_REF: "v4.1.XX"   # commit/tag exacto, no la rama
```

y actualizar esta tabla con el tag exacto + su commit SHA (`git rev-parse v4.1.XX`)
para que quede doblemente trazable (tag Y sha, por si el tag se llegara a mover).

## Cómo auditar qué corrió efectivamente

Cada imagen guarda en build-time el ref y la fecha de build en
`/var/www/html/.build-version`. Para verificar qué quedó levantado:

```bash
docker compose exec moodle-45 cat /var/www/html/.build-version
docker compose exec moodle-41 cat /var/www/html/.build-version
```
