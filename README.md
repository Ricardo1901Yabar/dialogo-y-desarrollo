# Diálogo y Desarrollo — Revista Digital

URL de la pagina principal: http://midemo.42web.io/index.php?i=1
URL de la pagina del admin: http://midemo.42web.io/revista_admin/login.php

## 📝 Descripción

**Diálogo y Desarrollo** es una plataforma web tipo revista digital orientada a la difusión de contenidos informativos y académicos. Ofrece una interfaz pública interactiva para la lectura de reportajes, visualización de noticias, consulta y descarga de boletines informativos en PDF, reproducción de podcasts, alianzas institucionales y material audiovisual.

Cuenta con un módulo administrativo privado (`revista_admin/`) protegido mediante autenticación de usuarios y control de acceso por roles (Administrador, Editor y Redactor) que permite realizar operaciones CRUD (Crear, Leer, Actualizar y Eliminar) sobre todas las entidades del sistema.

---

## 🛠️ Tecnologías Utilizadas

- **Lenguaje Backend:** PHP (conexión PDO con consultas preparadas)
- **Base de Datos:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, FontAwesome, OwlCarousel, EasyResponsiveTabs
- **Servidor Local:** XAMPP (Apache + MySQL)
- **Control de Versiones:** Git & GitHub
- **Hosting en Producción:** InfinityFree (vPanel)

---

## 📂 Estructura del Proyecto

```text
dialogoydesarrollo/
│
├── index_files/                   # Recursos estáticos (estilos CSS, librerías JS, imágenes del sitio)
│   ├── bootstrap.min.js.descarga
│   ├── jquery-3.3.1.min.js.descarga
│   ├── owl.carousel.js.descarga
│   ├── style-starter.css
│   └── ...
│
├── revista_admin/                 # Panel de administración de la plataforma
│   ├── config/
│   │   └── conexion.php           # Conexión a la base de datos mediante PDO
│   ├── includes/                  # Componentes reutilizables del panel
│   │   ├── footer.php
│   │   └── header.php             # Barra de navegación lateral responsiva
│   ├── modulos/                   # Módulos CRUD de gestión
│   │   ├── autores.php            # Gestión de autores
│   │   ├── boletines.php          # Gestión de boletines
│   │   ├── noticias.php           # Publicación y edición de noticias
│   │   ├── podcasts.php           # Gestión de episodios y audios
│   │   ├── reportajes.php         # Gestión y publicación de reportajes
│   │   ├── usuarios.php           # Control de usuarios y roles
│   │   └── videos.php             # Gestión de enlaces y contenido multimedia
│   ├── uploads/                   # Archivos multimedia subidos dinámicamente
│   │   ├── audios/                # Archivos de audio (MP3)
│   │   ├── imagenes/              # Fotografías y portadas
│   │   └── pdfs/                  # Documentos y boletines adjuntos
│   ├── index.php                  # Dashboard / Inicio administrativo
│   ├── login.php                  # Formulario de autenticación
│   └── logout.php                 # Cierre y destrucción de sesión
│
├── alianzas.php                   # Sección de alianzas institucionales
├── boletines.php                  # Listado público y descarga de boletines
├── index.php                      # Portada principal del portal público
├── podcasts.php                   # Reproductor y catálogo de podcasts
├── reportaje-detalle.php          # Vista de lectura de reportajes completos
├── reportajes.php                 # Catálogo público de reportajes
├── sobre-dyd.php                  # Información institucional de la revista
└── README.md                      # Documentación técnica del proyecto

```

---

## 💻 Instalación Local (XAMPP)

1. **Clonar el repositorio:**
Ubícate en el directorio `htdocs` de XAMPP y ejecuta:
```bash
git clone [https://github.com/Ricardo1901Yabar/dialogo-y-desarrollo.git](https://github.com/Ricardo1901Yabar/dialogo-y-desarrollo.git)

```


2. **Iniciar servicios:**
Abre **XAMPP Control Panel** e inicia los módulos de **Apache** y **MySQL**.
3. **Importar la Base de Datos:**
* Ingresa a [http://localhost/phpmyadmin](http://localhost/phpmyadmin?utm_source=gemini).
* Crea una nueva base de datos llamada `revista_digital`.
* Selecciona la base de datos creada, entra en la pestaña **Importar** y carga el script SQL de la estructura.


4. **Acceder a la aplicación:**
* **Sitio Web Público:** `http://localhost/dialogoydesarrollo/index.php`
* **Panel Administrativo:** `http://localhost/dialogoydesarrollo/revista_admin/login.php`



---

## 🗄️ Migración de Base de Datos

La migración hacia el entorno de producción se gestiona de forma desacoplada al repositorio de código:

1. Exportación de las tablas y registros desde phpMyAdmin local.
2. Creación de la base de datos MySQL en el hosting mediante el panel de control (vPanel).
3. Importación del archivo `.sql` resultante a través del phpMyAdmin remoto provisto por el proveedor de hosting.

---

## 🚀 Despliegue en Producción (InfinityFree)

* **Configuración de Conexión:**
Los archivos de conexión definen las credenciales y el servidor remoto asignado (`sql103.infinityfree.com`) para interactuar con la base de datos en la nube.
* **Buenas Prácticas de Seguridad:**
Las claves sensibles de producción se mantienen aisladas y protegidas, realizándose la configuración definitiva en los archivos del servidor web.
* **Estructura en Producción:**
Todo el proyecto se encuentra desplegado y sirviendo contenido desde la carpeta raíz `htdocs/` del servidor remoto.

---

## 🔗 URL Pública del Proyecto

* 🚀 **Sitio Web en Vivo:** [http://midemo.42web.io/](http://midemo.42web.io/?utm_source=gemini)
* 🔐 **Panel Administrativo:** [http://midemo.42web.io/revista_admin/login.php](https://www.google.com/search?q=http://midemo.42web.io/revista_admin/login.php&utm_source=gemini)

---

## 📸 Evidencias del Despliegue

| N° | Evidencia Requerida | Estado |
| --- | --- | --- |
| 1 | Repositorio en GitHub | ✅ Completado |
| 2 | README.md Profesional | ✅ Completado |
| 3 | Panel de Hosting InfinityFree | ✅ Configurado |
| 4 | Base de Datos en phpMyAdmin Remoto | ✅ Migrado |
| 5 | Aplicación Web Funcionando en Vivo | ✅ Activo |
| 6 | Panel Administrativo (`revista_admin/`) en Vivo | ✅ Operativo |
| 7 | Funcionalidad Dinámica MySQL (CRUD) | ✅ Verificado |

---

*Práctica desarrollada para la asignatura Plataformas para el Desarrollo de Aplicaciones — Escuela Profesional de Ingeniería de Sistemas (Semestre 2026-II).*

```

```
