# Backend Proyecto Final - Grupo 2 "Web Encuestas" 

## Requisitos Previos

- PHP 8: [Instalación](https://www.php.net/manual/en/install.php) Es necesario tener PHP instalado para ejecutar el backend.
- Composer (v2.6.6): [Instalación](https://getcomposer.org/doc/00-intro.md) Utilizado para gestionar las dependencias de PHP.
- Laravel (v10.46.0): Este proyecto está construido con el framework Laravel.
- PostgreSQL (v14.8): [Instalación](https://www.postgresql.org/download/) Para gestionar la base de datos.


    ***Si php y Composer se instalan por primera vez, necesita modificar el archivo php.ini dentro de la carpeta de instalación de php y descomentar (quitar el ';') las líneas ';extension=fileinfo' y ';extension=pdo_pgsql' para poder usar composer y Postgres, respectivamente***
  
## Configuración Inicial

1. **Clona el Repositorio:**

   ```bash
   git clone https://github.com/pablem/2024-Laravel-breeze-api-Web-Encuestas.git
   ```
    **para actualizar el repositorio:**
   ```bash
   git pull origin main
   ```
   
3. **Instala Dependencias de PHP:**

    Ejecuta los siguientes comandos dentro de la carpeta raíz del proyecto:

    ```bash
    composer install
    ```

4. **Configura el Archivo de Entorno:**
    - Copia `.env.example` a `.env` y escribe tu contraseña de postgres
    ```bash
    cp .env.example .env
    ```
    - Configura las credenciales de PostgreSQL en el archivo `.env`:
    ```bash
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=nombre_de_tu_base_de_datos
    DB_USERNAME=tu_usuario
    DB_PASSWORD=tu_contraseña
    ```
    - Guía para configurar PostgreSQL con pgAdmin 4 (v7.1): pgAdmin es una herramienta gráfica que facilita la administración de bases de datos PostgreSQL. [Instalación](https://www.pgadmin.org/download/pgadmin-4-windows/)

5. **Genera la Clave de la Aplicación:**
    
    Ejecuta el siguiente comando para generar una clave única para la aplicación:

    ```bash
    php artisan key:generate
    ```

6. **Ejecuta las Migraciones y Seeders:**

    Este comando ejecutará las migraciones de base de datos y sembrará datos iniciales, incluyendo un usuario administrador (email: usuario@super.com, password: 123456):

    ```bash
    php artisan migrate:fresh --seed
    ```

## Ejecución del Proyecto

1. **Inicia servidor en el proyecto FRONTEND:**

    Asegúrate de estar en el directorio del proyecto frontend y ejecuta:

    ```bash
    npm run dev
    ```

2. **Inicia servidor backend (este repositorio):**

    Desde la carpeta raíz del proyecto backend, ejecuta:

    ```bash
    php artisan serve
    ```

## Mailtrap

Mailtrap es un servicio que simula el envío y recepción de correos electrónicos, ideal para entornos de desarrollo. En lugar de enviar correos reales, los emails son capturados y almacenados en un buzón virtual que puedes revisar desde el panel de Mailtrap.

### Configuración en Laravel:

1. Crea una cuenta en [Mailtrap](https://mailtrap.io/).
2. Copia las credenciales SMTP desde el panel de Mailtrap.
3. Configura el archivo .env de tu proyecto con las credenciales de Mailtrap:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_contraseña
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@tuapp.com"
MAIL_FROM_NAME="Tu App"
```
