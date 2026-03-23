# Guía para desarrolladores — Alquipress Core

## Estructura del proyecto

```
alquipress-core/          ← Raíz = el plugin de WordPress
├── alquipress-core.php   ← Archivo principal del plugin
├── includes/             ← Clases y módulos PHP
│   ├── modules/          ← Módulos funcionales del CRM
│   ├── admin/            ← Vistas y assets del panel de administración
│   └── integrations/     ← Integraciones con WooCommerce, etc.
├── app/                  ← Entorno WordPress local (desarrollo)
│   └── public/           ← Instalación WordPress local
└── .cpanel.yml           ← Despliegue automático a producción/pre
```

> La raíz del repositorio **es el plugin**. La carpeta `app/` es solo el entorno
> local de desarrollo y **no se despliega**.

---

## Ramas

| Rama    | Propósito |
|---------|-----------|
| `main`  | Rama principal. Se despliega automáticamente a `pre.alquipress.com` |
| `jaime` | Desarrollo de Jaime |
| `jimmy` | Desarrollo de Jimmy |
| `yago`  | Desarrollo de Yago |

---

## Flujo de trabajo

### 1. Antes de empezar a trabajar

Actualiza siempre tu rama con los últimos cambios de `main`:

```bash
git checkout jaime          # cambia a tu rama (jaime / jimmy / yago)
git fetch origin
git merge origin/main       # trae los cambios de main a tu rama
```

Resuelve cualquier conflicto antes de ponerte a desarrollar.

### 2. Durante el desarrollo

Haz commits frecuentes y descriptivos:

```bash
git add .
git commit -m "feat: descripción corta de lo que hiciste"
git push origin jaime       # sube tu rama al remoto
```

#### Formato de commits recomendado

```
feat: nueva funcionalidad
fix: corrección de bug
refactor: reorganización de código sin cambiar funcionalidad
style: cambios de estilos CSS/JS
docs: cambios en documentación
chore: tareas de mantenimiento (dependencias, config, etc.)
```

### 3. Cuando termines una tarea

Avisa al responsable del proyecto para revisar e integrar tu rama en `main`.
No hagas merge directo a `main` tú mismo.

---

## Configuración del entorno local

### Requisitos

- [Local by Flywheel](https://localwp.com/) o similar para correr WordPress
- PHP 8.0+
- WordPress 6.0+
- Plugins requeridos (instalados aparte, no están en el repo):
  - WooCommerce
  - WooCommerce Bookings
  - WooCommerce Deposits
  - Advanced Custom Fields PRO
  - MailPoet

### Pasos para configurar

1. **Clona el repositorio**
   ```bash
   git clone <url-del-repo>
   cd alquipress-core
   git checkout jaime   # o tu rama
   ```

2. **Copia el wp-config de ejemplo**
   ```bash
   cp app/public/wp-config-sample.php app/public/wp-config.php
   ```
   Edita `wp-config.php` con los datos de tu base de datos local.
   **Nunca subas este archivo al repositorio.**

3. **Enlaza el plugin**
   El submodulo en `app/public/wp-content/plugins/alquipress-core` apunta
   a la raíz del repo. Si usas Local by Flywheel, puedes crear un symlink:
   ```bash
   # Desde la raíz del repo
   ln -s $(pwd) app/public/wp-content/plugins/alquipress-core
   ```

4. **Activa el plugin** en el panel de WordPress → Plugins.

---

## Despliegue

El despliegue es automático via cPanel:

- Cualquier push a `main` → despliega en `pre.alquipress.com`

El archivo `.cpanel.yml` en la raíz gestiona esto automáticamente.
No es necesario hacer nada manualmente.

---

## Lo que NO debe subirse al repositorio

El `.gitignore` ya está configurado para excluir:

- `app/public/wp-config.php` — credenciales de BD locales
- `app/public/wp-content/plugins/` — plugins de terceros
- `app/public/wp-content/uploads/` — archivos subidos
- `logs/` y `*.log` — logs locales
- `app/sql/*.sql` — volcados de base de datos
- `.DS_Store` — archivos de macOS
- Scripts PHP de debug temporales (`check-*.php`, `debug-*.php`, etc.)

Si en algún momento ves que git quiere subir alguno de estos archivos,
revisa que tu `.gitignore` esté correcto y ejecuta:

```bash
git rm --cached <archivo>
```
