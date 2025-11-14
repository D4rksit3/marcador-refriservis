<?php
// index.php - Página principal de marcación
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .btn-entrada {
            background: #10b981;
            color: white;
        }
        .btn-entrada:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        .btn-salida {
            background: #ef4444;
            color: white;
        }
        .btn-salida:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        .btn-refrigerio-out {
            background: #f59e0b;
            color: white;
        }
        .btn-refrigerio-out:hover {
            background: #d97706;
            transform: translateY(-2px);
        }
        .btn-refrigerio-in {
            background: #8b5cf6;
            color: white;
        }
        .btn-refrigerio-in:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        .btn-campo-in {
            background: #06b6d4;
            color: white;
        }
        .btn-campo-in:hover {
            background: #0891b2;
            transform: translateY(-2px);
        }
        .btn-campo-out {
            background: #ec4899;
            color: white;
        }
        .btn-campo-out:hover {
            background: #db2777;
            transform: translateY(-2px);
        }
        .admin-link {
            text-align: center;
            margin-top: 20px;
        }
        .admin-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .admin-link a:hover {
            text-decoration: underline;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .modal-icon {
            font-size: 60px;
            margin-bottom: 10px;
        }
        .modal-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        .modal-body {
            text-align: center;
            color: #666;
            line-height: 1.6;
        }
        .modal-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            text-align: left;
        }
        .modal-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .modal-info strong {
            color: #333;
        }
        .close-modal {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .close-modal:hover {
            background: #5568d3;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-size: 14px;
        }
        .success {
            background: #d1fae5;
            color: #059669;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #059669;
            font-size: 14px;
        }
        .loading {
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🕐 <?php echo SITE_NAME; ?></h1>
        <p class="subtitle">Registra tu asistencia de forma rápida y segura</p>
        
        <div id="message"></div>
        
        <form id="marcacionForm">
            <div class="form-group">
                <label for="dni">Ingresa tu DNI:</label>
                <input type="text" id="dni" name="dni" placeholder="Ej: 12345678" maxlength="20" required>
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn btn-entrada" onclick="marcar('entrada')">
                    🟢 Entrada
                </button>
                <button type="button" class="btn btn-salida" onclick="marcar('salida')">
                    🔴 Salida
                </button>
                <button type="button" class="btn btn-refrigerio-out" onclick="marcar('salida_refrigerio')">
                    ☕ Salida Refrigerio
                </button>
                <button type="button" class="btn btn-refrigerio-in" onclick="marcar('entrada_refrigerio')">
                    🍽️ Entrada Refrigerio
                </button>
                <button type="button" class="btn btn-campo-in" onclick="marcar('entrada_campo')">
                    🚗 Entrada Campo
                </button>
                <button type="button" class="btn btn-campo-out" onclick="marcar('salida_campo')">
                    🏢 Salida Campo
                </button>
            </div>
        </form>
        
        <div class="admin-link">
            <a href="admin/">🔐 Acceso Administrador</a>
        </div>
    </div>

    <!-- Modal -->
    <div id="marcacionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" id="modalIcon"></div>
                <h2 class="modal-title" id="modalTitle"></h2>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <button class="close-modal" onclick="cerrarModal()">Aceptar</button>
        </div>
    </div>

   <script>
    let currentPosition = null;

    // Opciones para máxima precisión
    const options = {
        enableHighAccuracy: true,   // Forzar GPS si está disponible
        timeout: 15000,             // Espera más tiempo
        maximumAge: 0               // Sin cache
    };

    // Obtener ubicación continuamente con watchPosition()
    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            (position) => {
                currentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };

                console.log("Lat:", currentPosition.lat);
                console.log("Lng:", currentPosition.lng);
                console.log("Precisión:", currentPosition.accuracy, "metros");
            },
            (error) => {
                console.error("Error GPS:", error);
            },
            options
        );
    }

    function marcar(tipo) {
        const dni = document.getElementById('dni').value.trim();

        if (!dni) {
            mostrarError('Por favor, ingresa tu DNI');
            return;
        }

        // Validar ubicación
        if (!currentPosition) {
            mostrarError("No se pudo obtener tu ubicación. Activa el GPS.");
            return;
        }

        // Validar precisión para evitar ubicación por IP
        if (currentPosition.accuracy > 50) {
            mostrarError(
                `La ubicación no es precisa (precisión: ${currentPosition.accuracy.toFixed(0)}m). ` +
                `Activa el GPS y espera unos segundos.`
            );
            return;
        }

        procesarMarcacion(dni, tipo);
    }

    function procesarMarcacion(dni, tipo) {
        mostrarLoading();

        const formData = new FormData();
        formData.append('dni', dni);
        formData.append('tipo', tipo);
        formData.append('lat', currentPosition.lat);
        formData.append('lng', currentPosition.lng);
        formData.append('accuracy', currentPosition.accuracy);

        fetch('procesar_marcacion.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarModalExito(data.data, tipo);
                document.getElementById('dni').value = '';
            } else {
                mostrarModalError(data.message);
            }
        })
        .catch(() => {
            mostrarModalError('Error de conexión. Intenta nuevamente.');
        });
    }

    function mostrarModalExito(data, tipo) {
        const iconos = {
            'entrada': '✅',
            'salida': '👋',
            'salida_refrigerio': '☕',
            'entrada_refrigerio': '🍽️',
            'entrada_campo': '🚗',
            'salida_campo': '🏢'
        };

        const titulos = {
            'entrada': 'Entrada Registrada',
            'salida': 'Salida Registrada',
            'salida_refrigerio': 'Salida a Refrigerio',
            'entrada_refrigerio': 'Regreso de Refrigerio',
            'entrada_campo': 'Salida a Campo',
            'salida_campo': 'Regreso de Campo'
        };

        document.getElementById('modalIcon').textContent = iconos[tipo];
        document.getElementById('modalTitle').textContent = titulos[tipo];

        document.getElementById('modalBody').innerHTML = `
            <div class="modal-info">
                <p><strong>Empleado:</strong> ${data.nombre}</p>
                <p><strong>DNI:</strong> ${data.dni}</p>
                <p><strong>Fecha:</strong> ${data.fecha}</p>
                <p><strong>Hora:</strong> ${data.hora}</p>
                <p><strong>Ubicación:</strong> ${data.direccion || 'Obteniendo dirección...'}</p>
                <p><strong>Coordenadas:</strong> ${data.latitud}, ${data.longitud}</p>
                <p><strong>Precisión GPS:</strong> ${currentPosition.accuracy.toFixed(0)} metros</p>
            </div>
            <p style="color: #059669; font-weight: 600;">¡Marcación exitosa!</p>
        `;

        document.getElementById('marcacionModal').style.display = 'block';
    }

    function mostrarModalError(mensaje) {
        document.getElementById('modalIcon').textContent = '❌';
        document.getElementById('modalTitle').textContent = 'Error';
        document.getElementById('modalBody').innerHTML = `<div class="error">${mensaje}</div>`;
        document.getElementById('marcacionModal').style.display = 'block';
    }

    function mostrarLoading() {
        document.getElementById('modalIcon').textContent = '';
        document.getElementById('modalTitle').textContent = 'Procesando...';
        document.getElementById('modalBody').innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p style="margin-top: 15px;">Registrando marcación...</p>
            </div>
        `;
        document.getElementById('marcacionModal').style.display = 'block';
    }

    function mostrarError(mensaje) {
        const messageDiv = document.getElementById('message');
        messageDiv.innerHTML = `<div class="error">${mensaje}</div>`;
        setTimeout(() => messageDiv.innerHTML = '', 3000);
    }

    function cerrarModal() {
        document.getElementById('marcacionModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('marcacionModal');
        if (event.target == modal) cerrarModal();
    }
</script>

</body>
</html>