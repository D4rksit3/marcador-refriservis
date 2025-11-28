<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .main-card { background: rgba(255,255,255,0.95); border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .logo-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 20px 20px 0 0; }
        .btn-marcacion { border-radius: 15px; padding: 15px; font-weight: 600; transition: all 0.3s; border: none; color: white; }
        .btn-marcacion:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); color: white; }
        .btn-entrada { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .btn-salida { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); }
        .btn-refrigerio { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
        .btn-campo { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); }
        .hora-display { font-size: 3.5rem; font-weight: 700; color: #333; }
        #video { width: 100%; max-width: 350px; border-radius: 15px; border: 4px solid #667eea; }
        .photo-preview { max-width: 150px; border-radius: 10px; border: 3px solid #28a745; }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 9999; }
        .location-info { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="bg-white p-4 rounded text-center">
            <div class="spinner-border text-primary mb-3"></div>
            <h5>Procesando...</h5>
        </div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="main-card">
                    <div class="logo-section text-center">
                        <h2><i class="fas fa-clock me-2"></i><?= SITE_NAME ?></h2>
                        <p class="mb-0 opacity-75">Control de Asistencia</p>
                    </div>
                    
                    <div class="p-4">
                        <div class="text-center mb-4">
                            <div class="hora-display" id="horaActual">--:--:--</div>
                            <div class="text-muted" id="fechaActual">--</div>
                        </div>
                        
                        <form id="formMarcacion">
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-id-card me-2"></i>DNI</label>
                                <input type="text" class="form-control form-control-lg text-center" id="dni" maxlength="8" pattern="[0-9]{8}" placeholder="12345678" required>
                            </div>
                            
                            <div id="infoEmpleado" class="alert alert-info" style="display:none;">
                                <strong><i class="fas fa-user me-2"></i><span id="nombreEmpleado"></span></strong>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-hand-pointer me-2"></i>Tipo de Marcación</label>
                                <div class="row g-2">
                                    <div class="col-6"><button type="button" class="btn btn-marcacion btn-entrada w-100" data-tipo="entrada"><i class="fas fa-sign-in-alt d-block mb-1"></i>Entrada</button></div>
                                    <div class="col-6"><button type="button" class="btn btn-marcacion btn-salida w-100" data-tipo="salida"><i class="fas fa-sign-out-alt d-block mb-1"></i>Salida</button></div>
                                    <div class="col-6"><button type="button" class="btn btn-marcacion btn-refrigerio w-100" data-tipo="salida_refrigerio"><i class="fas fa-coffee d-block mb-1"></i>Sal. Refrigerio</button></div>
                                    <div class="col-6"><button type="button" class="btn btn-marcacion btn-refrigerio w-100" data-tipo="entrada_refrigerio"><i class="fas fa-utensils d-block mb-1"></i>Ent. Refrigerio</button></div>
                                    <div class="col-6"><button type="button" class="btn btn-marcacion btn-campo w-100" data-tipo="salida_campo" data-foto="true"><i class="fas fa-car d-block mb-1"></i>Sal. Campo 📷</button></div>
                                    <div class="col-6"><button type="button" class="btn btn-marcacion btn-campo w-100" data-tipo="entrada_campo" data-foto="true"><i class="fas fa-building d-block mb-1"></i>Ent. Campo 📷</button></div>
                                </div>
                            </div>
                            
                            <div id="seccionFoto" style="display:none;">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-camera me-2"></i>Foto de Validación Requerida</h6>
                                    <p class="mb-0 small">Tome una foto para evidenciar su ubicación.</p>
                                </div>
                                <div id="videoContainer" class="text-center">
                                    <video id="video" autoplay playsinline></video>
                                    <canvas id="canvas" style="display:none;"></canvas>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-lg btn-primary" id="btnCapturar"><i class="fas fa-camera me-2"></i>Tomar Foto</button>
                                    </div>
                                </div>
                                <div id="fotoCapturada" class="text-center" style="display:none;">
                                    <p class="text-success"><i class="fas fa-check-circle me-2"></i>Foto capturada</p>
                                    <img id="photoPreview" class="photo-preview">
                                </div>
                            </div>
                            
                            <div class="location-info">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" id="locationSpinner"></div>
                                    <span id="locationText">Obteniendo ubicación...</span>
                                </div>
                                <input type="hidden" id="latitud"><input type="hidden" id="longitud"><input type="hidden" id="direccion">
                                <input type="hidden" id="fotoBase64"><input type="hidden" id="tipoMarcacion">
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-4"><a href="admin/login.php" class="text-white"><i class="fas fa-lock me-1"></i>Administración</a></div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modalResultado" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content"><div class="modal-body text-center p-5" id="modalBody"></div></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let ubicacionOk = false, tipoSeleccionado = null, requiereFoto = false, fotoOk = false, stream = null;
        
        setInterval(() => {
            const now = new Date();
            document.getElementById('horaActual').textContent = now.toLocaleTimeString('es-PE');
            document.getElementById('fechaActual').textContent = now.toLocaleDateString('es-PE', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
        }, 1000);
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                document.getElementById('latitud').value = pos.coords.latitude;
                document.getElementById('longitud').value = pos.coords.longitude;
                document.getElementById('locationSpinner').style.display = 'none';
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`)
                    .then(r => r.json()).then(data => {
                        document.getElementById('direccion').value = data.display_name || '';
                        document.getElementById('locationText').innerHTML = '<i class="fas fa-map-marker-alt text-success me-2"></i>' + (data.display_name || 'Ubicación obtenida');
                        ubicacionOk = true;
                    });
            }, () => {
                document.getElementById('locationSpinner').style.display = 'none';
                document.getElementById('locationText').innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-2"></i>Active el GPS';
            }, {enableHighAccuracy: true});
        }
        
        document.getElementById('dni').addEventListener('input', function() {
            if (this.value.length === 8) {
                fetch('ajax/validar_dni.php?dni=' + this.value).then(r => r.json()).then(data => {
                    if (data.success) {
                        document.getElementById('nombreEmpleado').textContent = data.nombre;
                        document.getElementById('infoEmpleado').style.display = 'block';
                    } else { document.getElementById('infoEmpleado').style.display = 'none'; }
                });
            }
        });
        
        document.querySelectorAll('.btn-marcacion').forEach(btn => {
            btn.addEventListener('click', function() {
                tipoSeleccionado = this.dataset.tipo;
                requiereFoto = this.dataset.foto === 'true';
                document.getElementById('tipoMarcacion').value = tipoSeleccionado;
                document.querySelectorAll('.btn-marcacion').forEach(b => b.classList.remove('border', 'border-3', 'border-dark'));
                this.classList.add('border', 'border-3', 'border-dark');
                if (requiereFoto) { document.getElementById('seccionFoto').style.display = 'block'; iniciarCamara(); }
                else { document.getElementById('seccionFoto').style.display = 'none'; if (stream) stream.getTracks().forEach(t => t.stop()); procesarMarcacion(); }
            });
        });
        
        async function iniciarCamara() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}});
                document.getElementById('video').srcObject = stream;
                document.getElementById('videoContainer').style.display = 'block';
                document.getElementById('fotoCapturada').style.display = 'none';
                fotoOk = false;
            } catch (e) { alert('No se pudo acceder a la cámara'); }
        }
        
        document.getElementById('btnCapturar').addEventListener('click', function() {
            const video = document.getElementById('video'), canvas = document.getElementById('canvas'), ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth; canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            ctx.fillStyle = 'rgba(0,0,0,0.5)'; ctx.fillRect(0, canvas.height - 50, canvas.width, 50);
            ctx.fillStyle = '#fff'; ctx.font = 'bold 14px Arial';
            ctx.fillText(new Date().toLocaleString('es-PE'), 10, canvas.height - 30);
            ctx.font = '12px Arial'; ctx.fillText((document.getElementById('direccion').value || '').substring(0, 50), 10, canvas.height - 10);
            const foto = canvas.toDataURL('image/jpeg', 0.8);
            document.getElementById('fotoBase64').value = foto;
            document.getElementById('photoPreview').src = foto;
            document.getElementById('videoContainer').style.display = 'none';
            document.getElementById('fotoCapturada').style.display = 'block';
            if (stream) stream.getTracks().forEach(t => t.stop());
            fotoOk = true; setTimeout(procesarMarcacion, 500);
        });
        
        function procesarMarcacion() {
            const dni = document.getElementById('dni').value;
            if (!dni || dni.length !== 8) return mostrarResultado('error', 'Ingrese un DNI válido');
            if (!tipoSeleccionado) return mostrarResultado('error', 'Seleccione tipo de marcación');
            if (!ubicacionOk) return mostrarResultado('error', 'Esperando ubicación GPS');
            if (requiereFoto && !fotoOk) return mostrarResultado('error', 'Debe tomar la foto');
            document.getElementById('loadingOverlay').style.display = 'flex';
            const formData = new FormData();
            formData.append('dni', dni); formData.append('tipo', tipoSeleccionado);
            formData.append('latitud', document.getElementById('latitud').value);
            formData.append('longitud', document.getElementById('longitud').value);
            formData.append('direccion', document.getElementById('direccion').value);
            formData.append('foto_validacion', document.getElementById('fotoBase64').value);
            fetch('procesar_marcacion.php', {method: 'POST', body: formData}).then(r => r.json()).then(data => {
                document.getElementById('loadingOverlay').style.display = 'none';
                mostrarResultado(data.success ? 'success' : 'error', data.message, data.empleado);
                if (data.success) setTimeout(() => location.reload(), 3000);
            }).catch(() => { document.getElementById('loadingOverlay').style.display = 'none'; mostrarResultado('error', 'Error de conexión'); });
        }
        
        function mostrarResultado(tipo, msg, empleado = '') {
            document.getElementById('modalBody').innerHTML = tipo === 'success' 
                ? `<div class="text-success"><i class="fas fa-check-circle" style="font-size:5rem"></i></div><h3>¡Marcación Exitosa!</h3>${empleado ? '<p>'+empleado+'</p>' : ''}<p class="text-muted">${msg}</p>`
                : `<div class="text-danger"><i class="fas fa-times-circle" style="font-size:5rem"></i></div><h3>Error</h3><p class="text-muted">${msg}</p><button class="btn btn-danger mt-3" data-bs-dismiss="modal">Reintentar</button>`;
            new bootstrap.Modal(document.getElementById('modalResultado')).show();
        }
    </script>
</body>
</html>
