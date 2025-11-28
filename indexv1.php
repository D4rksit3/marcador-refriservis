<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Marcación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .header {
            background: #1a1a1a;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .header p {
            font-size: 12px;
            color: #999;
            font-weight: 400;
        }
        
        .clock-section {
            background: #f9f9f9;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .clock-display {
            text-align: center;
        }
        
        .clock-time {
            font-size: 42px;
            font-weight: 700;
            color: #1a1a1a;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        
        .clock-date {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .location-display {
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e5e5;
            text-align: center;
        }
        
        .location-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }
        
        .location-text {
            font-size: 13px;
            color: #333;
            font-weight: 600;
            word-break: break-word;
            min-height: 18px;
        }
        
        .location-coords {
            font-size: 10px;
            color: #999;
            margin-top: 5px;
            font-family: 'Courier New', monospace;
        }
        
        .content {
            padding: 20px;
        }
        
        .gps-warning {
            background: #fff3cd;
            color: #856404;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
            font-size: 13px;
            text-align: center;
        }
        
        .gps-warning strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            color: #1a1a1a;
            transition: all 0.3s;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #333;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.05);
            background: #fafafa;
        }
        
        input[type="text"]:disabled {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
        }
        
        .btn-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .btn {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            background: white;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn:hover:not(:disabled) {
            background: #1a1a1a;
            color: white;
            border-color: #1a1a1a;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .btn:disabled {
            background: #e5e5e5;
            color: #999;
            border-color: #ddd;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn-refresh {
            background: #1a1a1a;
            color: white;
            border-color: #1a1a1a;
            grid-column: span 2;
        }
        
        .btn-refresh:hover {
            opacity: 0.9;
            background: #1a1a1a;
            color: white;
        }
        
        .admin-link {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #e5e5e5;
        }
        
        .admin-link a {
            font-size: 11px;
            color: #999;
            text-decoration: none;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .admin-link a:hover {
            color: #1a1a1a;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background: white;
            margin: auto;
            transform: translateY(-50%);
            padding: 30px 20px;
            border-radius: 12px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: fixed;
            top: 50%;
            left: 50%;
            margin-left: -45%;
        }
        
        .modal-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 20px;
            color: #1a1a1a;
            margin-bottom: 12px;
            font-weight: 700;
        }
        
        .modal-body {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .modal-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: left;
            border-left: 3px solid #1a1a1a;
        }
        
        .modal-info p {
            margin: 8px 0;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .modal-info strong {
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .close-modal {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
        }
        
        .close-modal:hover {
            opacity: 0.9;
        }
        
        .spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 2px solid #e5e5e5;
            border-top-color: #1a1a1a;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .gps-status {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .gps-active {
            background: #4caf50;
        }
        
        .gps-inactive {
            background: #dc2626;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .header h1 {
                font-size: 18px;
            }
            
            .clock-time {
                font-size: 38px;
            }
            
            .location-text {
                font-size: 12px;
            }
            
            .btn {
                padding: 11px;
                font-size: 11px;
            }
            
            .modal-content {
                padding: 25px 15px;
            }
            
            .modal-title {
                font-size: 18px;
            }
            
            .modal-body {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MARCACIÓN DE ASISTENCIA</h1>
            <p>Sistema de Control de Refriservis</p>
        </div>
        
        <div class="clock-section">
            <div class="clock-display">
                <div class="clock-time" id="clockTime">00:00:00</div>
                <div class="clock-date" id="clockDate">--</div>
            </div>
            <div class="location-display">
                <span class="location-label">
                    <span class="gps-status gps-inactive" id="gpsStatus"></span>UBICACIÓN GPS
                </span>
                <div class="location-text" id="locationText">Detectando...</div>
                <div class="location-coords" id="locationCoords">-</div>
            </div>
        </div>
        
        <div class="content">
            <div id="gpsWarning" class="gps-warning" style="display: none;">
                <strong>⚠️ GPS NO DETECTADO</strong>
                Debes activar tu GPS para poder realizar la marcación. Por favor:
                <br><br>
                1. Activa el GPS en tu dispositivo<br>
                2. Permite acceso a ubicación en tu navegador<br>
                3. Haz clic en "Verificar GPS"
            </div>
            
            <form id="marcacionForm">
                <div class="form-group">
                    <label for="dni">DNI</label>
                    <input type="text" id="dni" name="dni" placeholder="Ej: 12345678" maxlength="20" required disabled>
                </div>
                
                <div class="btn-grid">
                    <button type="button" class="btn" onclick="marcar('entrada')" disabled>🟢 Entrada</button>
                    <button type="button" class="btn" onclick="marcar('salida')" disabled>🔴 Salida</button>
                    <button type="button" class="btn" onclick="marcar('salida_refrigerio')" disabled>☕ Ingreso Refrigerio</button>
                    <button type="button" class="btn" onclick="marcar('entrada_refrigerio')" disabled>🍽️ Retorno Refrigerio</button>
                    <button type="button" class="btn" onclick="marcar('entrada_campo')" disabled>🚗 Ingreso Campo</button>
                    <button type="button" class="btn" onclick="marcar('salida_campo')" disabled>🏢 Salida Campo</button>
                    <button type="button" class="btn btn-refresh" onclick="verificarGPS()" id="btnRefresh">🔄 VERIFICAR GPS</button>
                </div>
            </form>
            
            <div class="admin-link">
                <a href="web.seguricloud.com">Desarrollado por SeguriCloud</a>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="marcacionModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon" id="modalIcon"></div>
            <h2 class="modal-title" id="modalTitle"></h2>
            <div class="modal-body" id="modalBody"></div>
            <button class="close-modal" onclick="cerrarModal()">Aceptar</button>
        </div>
    </div>

    <script>
        let currentPosition = null;
        let currentLocationName = 'Detectando...';
        let deviceId = null;
        let gpsActivo = false;

        function getDeviceId() {
            let storedDeviceId = localStorage.getItem('device_marcacion_id');
            if (!storedDeviceId) {
                storedDeviceId = 'DEV_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('device_marcacion_id', storedDeviceId);
            }
            return storedDeviceId;
        }

        deviceId = getDeviceId();

        function actualizarReloj() {
            const ahora = new Date();
            const horas = String(ahora.getHours()).padStart(2, '0');
            const minutos = String(ahora.getMinutes()).padStart(2, '0');
            const segundos = String(ahora.getSeconds()).padStart(2, '0');
            
            document.getElementById('clockTime').textContent = `${horas}:${minutos}:${segundos}`;
            
            const opciones = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            };
            const fecha = ahora.toLocaleDateString('es-PE', opciones);
            document.getElementById('clockDate').textContent = fecha;
        }

        async function obtenerNombreUbicacion(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                const address = data.address || {};
                const ciudad = address.city || address.town || address.village || 'Ubicación desconocida';
                const distrito = address.suburb || address.neighbourhood || '';
                return distrito ? `${distrito}, ${ciudad}` : ciudad;
            } catch (error) {
                console.log('No se pudo obtener nombre de ubicación');
                return 'Ubicación GPS';
            }
        }

        function actualizarEstadoGPS(activo) {
            gpsActivo = activo;
            const dni = document.getElementById('dni');
            const btnMarcacion = document.querySelectorAll('.btn-grid .btn:not(.btn-refresh)');
            const warning = document.getElementById('gpsWarning');
            
            if (activo) {
                document.getElementById('gpsStatus').className = 'gps-status gps-active';
                warning.style.display = 'none';
                dni.disabled = false;
                btnMarcacion.forEach(btn => btn.disabled = false);
            } else {
                document.getElementById('gpsStatus').className = 'gps-status gps-inactive';
                warning.style.display = 'block';
                dni.disabled = true;
                btnMarcacion.forEach(btn => btn.disabled = true);
                document.getElementById('locationText').textContent = 'GPS Desactivado';
                document.getElementById('locationCoords').textContent = '-';
            }
        }

        function verificarGPS() {
            if (!navigator.geolocation) {
                actualizarEstadoGPS(false);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    currentPosition = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    actualizarEstadoGPS(true);
                    
                    const lat = currentPosition.lat.toFixed(4);
                    const lng = currentPosition.lng.toFixed(4);
                    document.getElementById('locationCoords').textContent = `${lat}, ${lng}`;
                    
                    currentLocationName = await obtenerNombreUbicacion(currentPosition.lat, currentPosition.lng);
                    document.getElementById('locationText').textContent = currentLocationName;
                },
                (error) => {
                    actualizarEstadoGPS(false);
                    currentPosition = null;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        actualizarReloj();
        setInterval(actualizarReloj, 1000);
        verificarGPS();
        setInterval(verificarGPS, 30000);

        function marcar(tipo) {
            const dni = document.getElementById('dni').value.trim();
            
            if (!dni) {
                mostrarModalError('Ingresa tu DNI');
                return;
            }

            if (!currentPosition) {
                mostrarModalError('GPS Requerido\n\nActiva tu GPS e intenta nuevamente');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    currentPosition = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    procesarMarcacion(dni, tipo);
                },
                () => {
                    mostrarModalError('Error de GPS\n\nNo se pudo obtener tu ubicación');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        function procesarMarcacion(dni, tipo) {
            mostrarLoading();

            const formData = new FormData();
            formData.append('dni', dni);
            formData.append('tipo_marcacion', tipo);
            formData.append('lat', currentPosition.lat);
            formData.append('lng', currentPosition.lng);
            formData.append('device_id', deviceId);

            fetch('procesar_marcacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarModalExito(data.data, tipo);
                    document.getElementById('dni').value = '';
                } else {
                    mostrarModalError(data.message);
                }
            })
            .catch(error => {
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
                'entrada_refrigerio': 'Retorno de Refrigerio',
                'entrada_campo': 'Salida a Campo',
                'salida_campo': 'Regreso de Campo'
            };

            document.getElementById('modalIcon').textContent = iconos[tipo];
            document.getElementById('modalTitle').textContent = titulos[tipo];
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-info">
                    <p><span>Empleado:</span> <strong>${data.nombre}</strong></p>
                    <p><span>DNI:</span> <strong>${data.dni}</strong></p>
                    <p><span>Fecha:</span> <strong>${data.fecha}</strong></p>
                    <p><span>Hora:</span> <strong>${data.hora}</strong></p>
                    ${data.estado_horario ? `<p><span>Estado:</span> <strong style="color: ${data.color_estado}">${data.estado_horario}</strong></p>` : ''}
                    <p><span>Ubicación:</span> <strong>${data.direccion || currentLocationName}</strong></p>
                </div>
            `;

            document.getElementById('marcacionModal').style.display = 'block';
        }

        function mostrarModalError(mensaje) {
            document.getElementById('modalIcon').textContent = '⚠️';
            document.getElementById('modalTitle').textContent = 'Error';
            document.getElementById('modalBody').innerHTML = `<p>${mensaje}</p>`;
            document.getElementById('marcacionModal').style.display = 'block';
        }

        function mostrarLoading() {
            document.getElementById('modalIcon').textContent = '';
            document.getElementById('modalTitle').textContent = 'Procesando';
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center;"><div class="spinner" style="margin: 20px auto;"></div><p>Registrando marcación...</p></div>';
            document.getElementById('marcacionModal').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('marcacionModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('marcacionModal');
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>
