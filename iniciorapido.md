🚀 Inicio Rápido - Sistema de Marcaciones
⚡ Puesta en marcha en 5 minutos
1️⃣ Preparar la Base de Datos (2 min)
sql
-- Opción A: Desde phpMyAdmin
1. Abre phpMyAdmin
2. Crea una base de datos llamada: sistema_marcaciones
3. Selecciona la base de datos
4. Ve a "Importar"
5. Selecciona el archivo: database.sql
6. Haz clic en "Continuar"

-- Opción B: Desde línea de comandos
mysql -u root -p
CREATE DATABASE sistema_marcaciones CHARACTER SET utf8mb4;
USE sistema_marcaciones;
SOURCE /ruta/a/database.sql;
EXIT;
2️⃣ Configurar Conexión (1 min)
Edita config.php líneas 5-8:

php
define('DB_HOST', 'localhost');        // Tu host MySQL
define('DB_NAME', 'sistema_marcaciones'); // Nombre de BD
define('DB_USER', 'root');              // Tu usuario MySQL
define('DB_PASS', '');                  // Tu contraseña MySQL
3️⃣ Subir Archivos al Servidor (1 min)
📁 Copiar todos los archivos a:
   /var/www/html/sistema_marcaciones/     (Linux)
   C:\xampp\htdocs\sistema_marcaciones\   (Windows XAMPP)
   C:\wamp64\www\sistema_marcaciones\     (Windows WAMP)
4️⃣ Probar el Sistema (1 min)
Página Pública:

http://localhost/sistema_marcaciones/
Prueba con DNI: 12345678
Permite geolocalización cuando lo solicite
Panel Admin:

http://localhost/sistema_marcaciones/admin/
Usuario: admin
Contraseña: admin123
✅ ¡Listo! Sistema funcionando
🎯 Primeros Pasos Recomendados
1. Cambiar Contraseña Admin
php
// Ejecuta este código PHP para generar nueva contraseña:
<?php
echo password_hash('tu_nueva_contraseña', PASSWORD_DEFAULT);
?>

// Copia el resultado y actualiza en la base de datos:
UPDATE administradores SET password = 'HASH_GENERADO' WHERE usuario = 'admin';
2. Agregar Usuarios Reales
Ve a: Admin → Usuarios → Agregar Usuario
Completa el formulario con datos reales
El DNI será usado para marcar asistencia
3. Probar Marcación
Abre la página pública
Ingresa el DNI de un usuario registrado
Haz clic en "Entrada"
Verifica en Admin → Dashboard
4. Explorar Reportes
Ve a: Admin → Reportes
Ajusta fechas
Prueba exportar a Excel
5. Ver Mapa
Ve a: Admin → Mapa
Selecciona fecha de hoy
Observa las ubicaciones de las marcaciones
🔧 Solución de Problemas Rápida
❌ "Error de conexión a la base de datos"
✅ Verifica que MySQL esté corriendo
✅ Revisa credenciales en config.php
✅ Confirma que la base de datos existe
❌ "DNI no válido"
✅ El usuario debe existir en la tabla usuarios
✅ Verifica que el estado sea 'activo'
✅ Prueba con DNI: 12345678 (usuario de ejemplo)
❌ "No se pudo obtener la ubicación"
✅ Permite geolocalización en el navegador
✅ En Chrome: click en 🔒 junto a URL → Permisos → Ubicación → Permitir
✅ Para producción necesitas HTTPS
❌ Página en blanco / Error 500
✅ Verifica permisos de archivos: chmod 755 -R
✅ Revisa logs de PHP: /var/log/apache2/error.log
✅ Activa errores temporalmente en config.php: ini_set('display_errors', 1);
📊 Datos de Prueba
Si instalaste datos_prueba.sql, tienes estos usuarios disponibles:

DNI	Nombre	Cargo
12345678	Juan Carlos Pérez López	Desarrollador
87654321	María Elena García Rodríguez	Analista
11223344	Pedro Luis Martínez Silva	Supervisor
🎓 Siguientes Pasos
Personalizar: Cambia nombre del sistema en config.php
Usuarios Reales: Elimina usuarios de prueba, agrega reales
Seguridad: Cambia contraseña admin, configura HTTPS
Backup: Configura respaldo automático de la base de datos
Producción: Lee README.md completo para deployment
📞 ¿Necesitas Ayuda?
📖 Documentación completa: README.md
📁 Estructura del proyecto: ESTRUCTURA_PROYECTO.txt
🗃️ SQL de estructura: database.sql
🧪 SQL de prueba: datos_prueba.sql
⚙️ Ajustes Rápidos Comunes
Cambiar Zona Horaria
php
// En config.php línea 13:
define('TIMEZONE', 'America/Lima'); // Cambia según tu país
Zonas comunes:

🇵🇪 Perú: America/Lima
🇲🇽 México: America/Mexico_City
🇨🇴 Colombia: America/Bogota
🇦🇷 Argentina: America/Buenos_Aires
🇨🇱 Chile: America/Santiago
Cambiar Nombre del Sistema
php
// En config.php línea 11:
define('SITE_NAME', 'Mi Empresa - Control de Asistencia');
Ajustar Ubicación por Defecto del Mapa
javascript
// En admin/mapa.php línea 189:
const defaultCenter = [-12.0464, -77.0428]; // [latitud, longitud]
🎉 ¡Sistema Listo para Usar!
Tu sistema de marcaciones está completamente funcional. Comienza agregando usuarios y realizando marcaciones.

Tiempo total de instalación: ~5 minutos ⚡

Para funcionalidades avanzadas y personalización, consulta la documentación completa en README.md

