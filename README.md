# XisHeatMap - Backend & Dashboard Administrativo

Este es el repositorio del backend (API) y el panel de administración web (Dashboard) para la plataforma **XisHeatMap**. El sistema está diseñado para gestionar y supervisar operaciones de campo, administrar el padrón/lista nominal, coordinar brigadistas y generar tarjetas de descuento con códigos QR.

## 🚀 Tecnologías Principales

*   **Framework Backend:** Laravel 11
*   **Frontend (Panel Admin):** Vue.js 3 con Inertia.js
*   **Estilos:** Tailwind CSS
*   **Generación de PDFs:** DOMPDF (`barryvdh/laravel-dompdf`)
*   **Exportación de Excel:** Laravel Excel (`maatwebsite/excel`)
*   **Roles y Permisos:** Spatie Permission
*   **Generación de QR y Correo:** `simplesoftwareio/simple-qrcode`, Laravel Mail.

## ✨ Características y Módulos

1.  **Gestión de Red y Usuarios (Estructura Jerárquica)**
    *   Administración de roles escalonados: Administrador, Coordinador de sector, Gestor seccional, Presidente de comité, Integrante de comité.
    *   Organigrama en árbol para rastrear a quién reporta cada brigadista.
    *   Exportación de directorios a PDF y Excel.
2.  **Módulo de Capturas (INEs)**
    *   Recepción de datos desde la App Móvil (fotos de INE frontal/reverso, nombre, CURP, teléfono, correo).
    *   Validación y auditoría de capturas realizadas en campo.
3.  **Generador de Tarjetas de Descuento**
    *   Generación automática de tarjetas en formato imagen/PDF con diseño personalizado y Código QR.
    *   Envío de tarjetas automatizado vía correo electrónico.
    *   Historial de envíos y botón de reenvío.
4.  **Lista Nominal y Secciones Electorales**
    *   Carga y procesamiento de cortes de lista nominal.
    *   Cruce de información de capturas vs padrón.
5.  **Mapa de Calor y Tracking Espacial (HeatMap)**
    *   Visualización geográfica (GeoJSON) de las capturas.
    *   Rastreo de la ubicación de los brigadistas activos en tiempo real o histórico.

## ⚙️ Requisitos Previos

*   PHP >= 8.3
*   Composer
*   Node.js (>= 18) y NPM
*   Base de datos compatible (MySQL / PostgreSQL)
*   Servidor web (Nginx / Apache) o usar `artisan serve` para desarrollo local.

## 🛠️ Instalación y Configuración

1.  **Clonar el repositorio y entrar a la carpeta**
    ```bash
    cd xisheatmap_backend
    ```

2.  **Instalar dependencias de PHP y Node**
    ```bash
    composer install
    npm install
    ```

3.  **Configurar Variables de Entorno**
    *   Copia el archivo de ejemplo: `cp .env.example .env`
    *   Genera la llave de la aplicación: `php artisan key:generate`
    *   Configura las variables de base de datos (`DB_*`) y el servidor de correos (`MAIL_*`).

4.  **Base de Datos y Almacenamiento**
    *   Ejecuta las migraciones (y seeders si existen):
        ```bash
        php artisan migrate --seed
        ```
    *   Enlaza la carpeta de almacenamiento público (necesario para ver fotos de INEs y PDFs):
        ```bash
        php artisan storage:link
        ```

5.  **Compilar los Assets del Frontend**
    ```bash
    npm run build
    # O para desarrollo: npm run dev
    ```

6.  **Colas de Trabajo (Importante para correos)**
    El envío de correos y la generación de imágenes QR es pesado, por lo que se recomienda usar *Jobs*. Asegúrate de correr los workers:
    ```bash
    php artisan queue:work
    ```

## 📂 Estructura Relevante del Proyecto

*   `app/Http/Controllers/Admin`: Controladores para el panel administrativo (Usuarios, Tarjetas, HeatMap, etc.).
*   `app/Http/Controllers/Api`: Endpoints consumidos por la aplicación móvil Quasar.
*   `resources/js/Pages`: Vistas en Vue.js + Inertia para el dashboard.
*   `routes/web.php` y `routes/api.php`: Definición de las rutas del sistema.
*   `public/templates`: Imágenes base usadas para generar las tarjetas de descuento.

## 🧑‍💻 Comandos Útiles

*   **Limpiar cachés:** `php artisan optimize:clear`
*   **Probar envío de correos (Tarjeta):** Visitar temporalmente `/test-correo` (si la ruta está habilitada en web.php) para validar diseño y credenciales SMTP.
