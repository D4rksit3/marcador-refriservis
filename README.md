🕐 Sistema de Marcaciones - Manual de Instalación
Sistema completo de control de asistencia con geolocalización en tiempo real, desarrollado en PHP nativo sin dependencias externas.

📋 Características Principales
✅ Página de Marcación Pública: 6 tipos de marcación (Entrada, Salida, Refrigerio, Campo)
📍 Geolocalización en Tiempo Real: Captura automática de ubicación con dirección
👥 Gestión de Usuarios: CRUD completo con datos personales
📊 Reportes Detallados: Todas las marcaciones en una fila por día
📈 Gráficas Estadísticas: Visualización de datos con Chart.js
🗺️ Mapa Interactivo: Visualización de ubicaciones con Leaflet/OpenStreetMap
📥 Exportación: Descarga de reportes en Excel y PDF
🔍 Filtros Avanzados: Por DNI, nombre, fechas y más
🔐 Panel de Administración: Sistema completo de gestión
🛠️ Requisitos del Sistema
PHP 7.4 o superior
MySQL 5.7 o superior / MariaDB 10.2 o superior
Servidor Web (Apache, Nginx, etc.)
Extensiones PHP requeridas:
PDO
pdo_mysql
curl (para geolocalización)
📦 Instalación
Paso 1: Descargar los archivos
Crea la siguiente estructura de carpetas:

sistema_marcaciones/
├── config.php
├── index.php
├── procesar_marcacion.php
├── admin/
│   ├── includes/
│   │   ├── header.php
│   │   └── footer.php
│   ├── login.php
│   ├── index.php
│   ├── usuarios.php
│   ├── reportes.php
│   ├── graficas.php
│   ├── mapa.php
│   └── logout.php
└── database.sql
Paso 2: Configurar la Base de Datos
Abre phpMyAdmin o tu gestor de MySQL
Crea una nueva base de datos llamada sistema_marcaciones
Importa el archivo database.sql que contiene toda la estructura
Paso 3: Configurar la Conexión
Edita el archivo config.php y actualiza las credenciales de tu base de datos:

php
define('DB_HOST', 'localhost');      // Host de la base de datos
define('DB_NAME', 'sistema_marcaciones'); // Nombre de la base de datos
define('DB_USER', 'root');           // Usuario de MySQL
define('DB_PASS', '');               // Contraseña de MySQL
Paso 4: Configurar Zona Horaria
En config.php, ajusta la zona horaria según tu ubicación:

php
define('TIMEZONE', 'America/Lima'); // Cambia según tu zona horaria
Zonas horarias comunes:

América/Lima (Perú)
América/Mexico_City (México)
América/Bogota (Colombia)
América/Buenos_Aires (Argentina)
América/Santiago (Chile)
Paso 5: Configurar Permisos
Asegúrate de que los archivos tengan los permisos correctos:

bash
chmod 755 -R sistema_marcaciones/
Paso 6: Probar la Instalación
Abre tu navegador y ve a: http://tu-servidor/sistema_marcaciones/
Deberías ver la página de marcaciones
🔑 Acceso al Panel de Administración
URL: http://tu-servidor/sistema_marcaciones/admin/

Credenciales por defecto:

Usuario: admin
Contraseña: admin123
⚠️ IMPORTANTE: Cambia estas credenciales después de la primera instalación.

Cambiar Contraseña del Admin
Para cambiar la contraseña, ejecuta este código en PHP:

php
<?php
$nueva_password = password_hash('tu_nueva_contraseña', PASSWORD_DEFAULT);
echo $nueva_password;
?>
Luego actualiza la tabla administradores con el nuevo hash.

📱 Uso del Sistema
Para Empleados (Página Pública)
Ingresar DNI en el campo
Seleccionar el tipo de marcación:
🟢 Entrada: Al llegar a la oficina
🔴 Salida: Al salir de la oficina
☕ Salida Refrigerio: Al salir a almorzar/refrigerio
🍽️ Entrada Refrigerio: Al regresar del refrigerio
🚗 Entrada Campo: Al salir a realizar trabajo de campo
🏢 Salida Campo: Al regresar del trabajo de campo
El sistema capturará automáticamente:
Fecha y hora exacta
Ubicación GPS (latitud y longitud)
Dirección completa del lugar
Para Administradores
📊 Dashboard
Vista general de estadísticas
Marcaciones del día
Usuarios más activos
Gráficos de resumen
👥 Gestión de Usuarios
Agregar nuevos empleados
Editar información personal
Activar/Desactivar usuarios
Eliminar usuarios (también elimina sus marcaciones)
Campos requeridos:

DNI (único)
Nombres
Apellidos
Correo electrónico (único)
Teléfono (opcional)
Cargo (opcional)
Departamento (opcional)
Fecha de ingreso (opcional)
📋 Reportes
Ver todas las marcaciones consolidadas por día
Filtrar por:
DNI
Nombre
Rango de fechas
Exportar a:
Excel: Incluye todas las columnas con formato
PDF: Versión simplificada para impresión
Ver detalles completos de cada marcación con ubicación
📈 Gráficas
Visualiza estadísticas con gráficos interactivos:

Marcaciones por día (últimos 7 días)
Marcaciones por tipo (último mes)
Usuarios más activos (Top 10)
Marcaciones por día de la semana
Estadísticas por departamento
🗺️ Mapa
Visualiza todas las marcaciones en un mapa interactivo
Filtrar por fecha y tipo de marcación
Ver ubicación exacta de cada marcación
Información detallada en popups
Integración con Google Maps
🔧 Configuraciones Avanzadas
Personalizar el Nombre del Sistema
En config.php:

php
define('SITE_NAME', 'Sistema de Marcaciones'); // Cambia el nombre aquí
Habilitar HTTPS
Si tu servidor tiene SSL, modifica la configuración de cookies en config.php:

php
ini_set('session.cookie_secure', 1); // Agregar esta línea
Ajustar Límite de Geolocalización
El sistema usa Nominatim (OpenStreetMap) para obtener direcciones. Si necesitas cambiar el proveedor, modifica la función getAddressFromCoordinates() en config.php.

📊 Estructura de la Base de Datos
Tabla: usuarios
Almacena información de los empleados que pueden marcar asistencia.

Tabla: administradores
Usuarios con acceso al panel de administración.

Tabla: marcaciones
Registra todas las marcaciones con ubicación y detalles.

Vista: v_marcaciones_diarias
Vista consolidada que agrupa todas las marcaciones de un día en una sola fila para reportes.

🐛 Solución de Problemas
Error: "No se puede conectar a la base de datos"
Verifica las credenciales en config.php
Asegúrate de que MySQL esté corriendo
Verifica que el usuario tenga permisos
Error: "No se pudo obtener la ubicación"
El usuario debe permitir la geolocalización en el navegador
HTTPS es requerido en navegadores modernos para geolocalización
Verifica que el navegador soporte geolocalización
Las direcciones no se muestran
Verifica la conexión a internet
El sistema usa Nominatim (OpenStreetMap) que puede tener límites de uso
Si es necesario, implementa tu propia API de geocodificación
Los reportes no se exportan
Verifica que PHP tenga permisos de escritura
Asegúrate de que no haya errores en la consulta SQL
Revisa los logs de error de PHP
🔒 Seguridad
Las contraseñas se almacenan con hash bcrypt
Protección contra SQL Injection usando PDO con prepared statements
Sanitización de todos los inputs del usuario
Validación de sesiones en todas las páginas admin
Tokens CSRF recomendados para producción
🚀 Mejoras Sugeridas para Producción
Implementar tokens CSRF en formularios
Agregar rate limiting para prevenir abusos
Implementar logs de auditoría para todas las acciones
Agregar autenticación de dos factores (2FA)
Implementar backup automático de la base de datos
Usar TCPDF o DomPDF para PDFs más profesionales
Agregar notificaciones por correo para marcaciones
Implementar API REST para aplicaciones móviles
Agregar caché para mejorar rendimiento
Implementar sistema de roles (supervisor, gerente, etc.)
📞 Soporte
Para reportar bugs o solicitar nuevas características, por favor documenta:

Versión de PHP
Versión de MySQL
Navegador utilizado
Pasos para reproducir el error
Mensaje de error completo
📄 Licencia
Este sistema fue desarrollado como solución personalizada. Puedes modificarlo según tus necesidades.

Desarrollado con ❤️ usando PHP nativo - Sin dependencias externas

