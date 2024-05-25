# Backend Proyecto Final - Grupo 2 "Web Encuestas" 

## Requisitos Previos

- PHP
- Composer
- Base de datos: PostgreSQL

## Configuración Inicial

1. **Clona el Repositorio:**

2. **Instala Dependencias de PHP:**
    ```bash
    composer install
    ```

3. **Configura el Archivo de Entorno:**
    - Copia `.env.example` a `.env` y escribe tu contraseña de postgres 

4. **Genera la Clave de la Aplicación:**
    ```bash
    php artisan key:generate
    ```

5. **Ejecuta las Migraciones + usuario (mail: usuario@ejemplo.com pass:123456)**
    ```bash
    php artisan migrate:fresh --seed
    ```

## Ejecución del Proyecto

1. **Inicia servidor en el proyecto FRONTEND:**
    ```bash
    npm run dev
    ```

2. **Inicia servidor backend (este repositorio):**
    ```bash
    php artisan serve
    ```