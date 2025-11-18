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
        /* --- Reset y base --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f9; /* Fondo gris claro suave */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* --- Contenedor Principal --- */
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 450px;
            width: 100%;
            transition: all 0.3s ease;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 26px;
        }
        .subtitle {
            text-align: center;
            color: #6c757d; /* Gris oscuro para subtítulos */
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        /* --- Formulario y DNI --- */
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
            font-size: 14px;
        }
        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #007bff; /* Azul clásico */
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* --- Botón DNI (Principal) --- */
        .btn-dni {
            background: #007bff; /* Azul primario */
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
            width: 100%;
        }
        .btn-dni:hover {
            background: #0056b3; /* Azul más oscuro al pasar el ratón */
            transform: none; /* Quitamos el efecto 3D para un look más plano */
        }

        /* --- Enlace Admin --- */
        .admin-link {
            text-align: center;
            margin-top: 20px;
        }
        .admin-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .admin-link a:hover {
            text-decoration: underline;
        }

        /* --- Botones de Opciones (Modal) --- */
        .btn-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Colores de Marcación Suavizados */
        .btn-entrada {
            background: #28a745; /* Verde Bootstrap (éxito) */
            color: white;
        }
        .btn-salida {
            background: #dc3545; /* Rojo Bootstrap (peligro) */
            color: white;
        }
        .btn-refrigerio-out {
            background: #ffc107; /* Amarillo oscuro (advertencia) */
            color: #333;
        }
        .btn-refrigerio-in {
            background: #6f42c1; /* Púrpura */
            color: white;
        }
        .btn-campo-out {
            background: #17a2b8; /* Cyan (info) */
            color: white;
        }
        .btn-campo-in {
            background: #fd7e14; /* Naranja */
            color: white;
        }
        
        /* --- Modales Comunes (Loading/Resultado) --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideIn 0.3s;
        }
        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .modal-icon {
            font-size: 50px;
            margin-bottom: 10px;
        }
        .modal-title {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
        }
        .modal-body {
            text-align: center;
            color: #666;
            line-height: 1.5;
        }
        .modal-info {
            background: #e9ecef; /* Gris muy claro para info box */
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: left;
            border-left: 4px solid #007bff; /* Borde azul de acento */
        }
        .modal-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .modal-info strong {
            color: #333;
        }
        .close-modal {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.2s;
        }
        .close-modal:hover {
            background: #0056b3;
        }
        
        /* Mensajes de Estado */
        .error {
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            color: #155724; 
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            font-size: 14px;
        }
        .loading {
            text-align: center;
            padding: 15px;
        }
        .spinner {
            border: 4px solid #e9ecef;
            border-top: 4px solid #007bff; 
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        /* Animaciones */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Estilos para el modal de opciones de marcación */
        .modal-marcacion-opciones .modal-content {
            background-color: #fff;
            padding: 30px;
            max-width: 450px;
        }
        .modal-marcacion-opciones .modal-title {
            font-size: 20px;
        }
        
        /* Media Query para móvil (Mejora la visualización en pantallas muy pequeñas) */
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            .btn-group {
                grid-template-columns: 1fr; /* Una columna en móvil */
            }
            .modal-content {
                margin: 5% auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🕐 <?php echo SITE_NAME; ?></h1>
        <p class="subtitle">Ingresa tu DNI y presiona "Marcar"</p>
        
        <div id="message"></div>
        
        <form id="dniForm">
            <div class="form-group">
                <label for="dni">Ingresa tu DNI:</label>
                <input type="text" id="dni" name="dni" placeholder="Ej: 12345678" maxlength="20" required autofocus>
            </div>
            
            <button type="submit" class="btn-dni">
                Marcar
            </button>
        </form>
        
        <div class="admin-link">
            <a href="admin/">🔐 Acceso Administrador</a>
        </div>
    </div>

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

    <div id="opcionesModal" class="modal modal-marcacion-opciones">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">👋</div>
                <h2 class="modal-title" id="opcionesModalTitle"></h2>
                <p class="subtitle" id="opcionesModalSubtitle">Selecciona el tipo de marcación:</p>
            </div>
            <div class="modal-body">
                <div class="btn-group">
                    <button type="button" class="btn btn-entrada" data-tipo="entrada">
                        🟢 Entrada
                    </button>
                    <button type="button" class="btn btn-refrigerio-in" data-tipo="entrada_refrigerio">
                        🍽️ Entrada Refrigerio
                    </button>
                    <button type="button" class="btn btn-refrigerio-out" data-tipo="salida_refrigerio">
                        ☕ Salida Refrigerio
                    </button>
                    <button type="button" class="btn btn-campo-in" data-tipo="entrada_campo">
                        🏢 Entrada Campo
                    </button>
                    <button type="button" class="btn btn-campo-out" data-tipo="salida_campo">
                        🚗 Salida Campo
                    </button>
                    <button type="button" class="btn btn-salida" data-tipo="salida">
                        🔴 Salida
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentPosition = null;
    let currentDNI = null;
    let dniInput = document.getElementById('dni');

    // Opciones para máxima precisión
    const options = {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
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

    // --- DISPARADOR: SUBMIT (al presionar Enter o el botón "Marcar") ---
    document.getElementById('dniForm').addEventListener('submit', function(e) {
        e.preventDefault();
        validarDniYMostrarOpciones();
    });

    // Delegar eventos de clic para los botones de marcación dentro del modal
    document.getElementById('opcionesModal').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn') && e.target.closest('.btn-group')) {
            const tipo = e.target.getAttribute('data-tipo');
            if (tipo) {
                cerrarOpcionesModal();
                marcar(tipo);
            }
        }
    });

    function validarDniYMostrarOpciones() {
        const dni = dniInput.value.trim();
        
        // 1. Validaciones básicas
        if (!dni || dni.length < 5) { 
            mostrarError('DNI incompleto o vacío.');
            return;
        }

        if (!currentPosition) {
            mostrarError("No se pudo obtener tu ubicación. Activa el GPS y espera unos segundos.");
            return;
        }

        if (currentPosition.accuracy > 50) {
            mostrarError(
                `La ubicación no es precisa (precisión: ${currentPosition.accuracy.toFixed(0)}m). ` +
                `Activa el GPS y espera unos segundos.`
            );
            return;
        }


        // 2. Llamada al servidor para validar DNI
        mostrarLoading();

        const formData = new FormData();
        formData.append('dni', dni);
        formData.append('validar', 'true'); // Indicamos al PHP que solo debe validar

        fetch('procesar_marcacion.php', { 
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            cerrarModal(); // Ocultar el loading

            if (data.success) {
                currentDNI = dni; // Guardar el DNI válido
                mostrarOpcionesModal(data.data.nombre); // data.data.nombre viene del servidor
            } else {
                mostrarModalError(data.message || 'Error de validación de DNI.');
                dniInput.value = ''; // Limpiar campo si falla
            }
        })
        .catch(() => {
            cerrarModal();
            mostrarModalError('Error de conexión con el servidor (procesar_marcacion.php).');
            dniInput.value = '';
        });
    }

    function marcar(tipo) {
        if (!currentDNI) {
            mostrarError('El DNI no ha sido validado. Intenta de nuevo.');
            return;
        }
        
        // Re-validación de ubicación antes de marcar
        if (!currentPosition || currentPosition.accuracy > 50) {
            mostrarModalError("La ubicación se perdió o no es precisa. Vuelve a ingresar tu DNI.");
            currentDNI = null;
            dniInput.value = '';
            return;
        }

        procesarMarcacion(currentDNI, tipo);
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
                dniInput.value = '';
                currentDNI = null; // Limpiar DNI
            } else {
                mostrarModalError(data.message);
            }
        })
        .catch(() => {
            mostrarModalError('Error de conexión al registrar la marcación. Intenta nuevamente.');
        });
    }

    // --- Funciones de Modal ---

    function mostrarOpcionesModal(nombre) {
        document.getElementById('opcionesModalTitle').textContent = `Hola, ${nombre}`;
        document.getElementById('opcionesModal').style.display = 'block';
    }

    function cerrarOpcionesModal() {
        document.getElementById('opcionesModal').style.display = 'none';
    }

    function mostrarModalExito(data, tipo) {
        const iconos = {
            'entrada': '✅',
            'salida': '👋',
            'salida_refrigerio': '☕',
            'entrada_refrigerio': '🍽️',
            'salida_campo': '🚗',
            'entrada_campo': '🏢'
        };

        const titulos = {
            'entrada': 'Entrada Registrada',
            'salida': 'Salida Registrada',
            'salida_refrigerio': 'Salida a Refrigerio',
            'entrada_refrigerio': 'Regreso de Refrigerio',
            'salida_campo': 'Salida a Campo',
            'entrada_campo': 'Regreso de Campo'
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
                <p><strong>Precisión GPS:</strong> ${currentPosition ? currentPosition.accuracy.toFixed(0) : 'N/A'} metros</p>
            </div>
            <p style="color: #28a745; font-weight: 600;">¡Marcación exitosa!</p>
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
                <p style="margin-top: 15px;">Validando DNI y ubicación...</p>
            </div>
        `;
        document.getElementById('marcacionModal').style.display = 'block';
    }

    function mostrarError(mensaje) {
        const messageDiv = document.getElementById('message');
        messageDiv.innerHTML = `<div class="error">${mensaje}</div>`;
        setTimeout(() => messageDiv.innerHTML = '', 4000);
    }

    function cerrarModal() {
        document.getElementById('marcacionModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const marcacionModal = document.getElementById('marcacionModal');
        const opcionesModal = document.getElementById('opcionesModal');
        if (event.target == marcacionModal) cerrarModal();
        if (event.target == opcionesModal) cerrarOpcionesModal(); 
    }
</script>

</body>
</html>