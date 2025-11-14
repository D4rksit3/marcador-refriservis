<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>

<div class="container">

    <h1>🕐 <?php echo SITE_NAME; ?></h1>
    <p class="subtitle">Registra tu asistencia de forma rápida y segura</p>

    <div id="message"></div>

    <form id="dniForm">
        <div class="form-group">
            <label for="dni">Ingresa tu DNI:</label>
            <input type="text" id="dni" name="dni" placeholder="Ej: 12345678" maxlength="20" required>
        </div>

        <button type="button" class="btn btn-entrada" onclick="validarDNI()">
            Continuar
        </button>
    </form>

    <div class="admin-link">
        <a href="admin/">🔐 Acceso Administrador</a>
    </div>

</div>

<!-- Modal de carga -->
<div id="loadingModal" class="modal">
    <div class="modal-content">
        <div class="loader"></div>
        <h2 class="modal-title">Procesando...</h2>
    </div>
</div>

<!-- Modal error -->
<div id="errorModal" class="modal">
    <div class="modal-content">
        <h2 class="modal-title" id="errorTitle">Error</h2>
        <p id="errorMessage"></p>
        <button class="close-modal" onclick="cerrarModal()">Cerrar</button>
    </div>
</div>

<!-- Modal selección de tipo de marcación -->
<div id="tipoModal" class="modal">
    <div class="modal-content">

        <h2 class="modal-title" style="text-align:center;">Selecciona el tipo de marcación</h2>

        <div class="btn-group" style="margin-top:20px;">
            <button class="btn btn-entrada" onclick="marcar('entrada')">🟢 Entrada</button>
            <button class="btn btn-salida" onclick="marcar('salida')">🔴 Salida</button>
            <button class="btn btn-refrigerio-out" onclick="marcar('salida_refrigerio')">☕ Salida Refrigerio</button>
            <button class="btn btn-refrigerio-in" onclick="marcar('entrada_refrigerio')">🍽️ Entrada Refrigerio</button>
            <button class="btn btn-campo-in" onclick="marcar('entrada_campo')">🚗 Entrada Campo</button>
            <button class="btn btn-campo-out" onclick="marcar('salida_campo')">🏢 Salida Campo</button>
        </div>

        <button class="close-modal" onclick="cerrarTipoModal()">Cancelar</button>

    </div>
</div>

<script>
// -----------------------
// Validar DNI antes de mostrar tipos
// -----------------------
function validarDNI() {
    const dni = document.getElementById('dni').value.trim();

    if (!dni) {
        mostrarError('Por favor, ingresa tu DNI');
        return;
    }

    mostrarLoading();

    const formData = new FormData();
    formData.append('dni', dni);
    formData.append('validar', '1');

    fetch('procesar_marcacion.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        cerrarModal();

        if (data.success) {
            document.getElementById('tipoModal').style.display = 'block';
        } else {
            mostrarError("El DNI no existe en el sistema");
        }
    })
    .catch(() => {
        mostrarError("Error de conexión");
    });
}

// -----------------------
// Enviar marcación
// -----------------------
function marcar(tipo) {
    const dni = document.getElementById('dni').value.trim();

    if (!dni) {
        mostrarError("Ingresa tu DNI primero");
        return;
    }

    mostrarLoading();

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            const formData = new FormData();
            formData.append('dni', dni);
            formData.append('tipo', tipo);
            formData.append('lat', lat);
            formData.append('lng', lng);

            fetch('procesar_marcacion.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                cerrarModal();
                if (data.success) {
                    alert("Marcación registrada: " + tipo.toUpperCase());
                    location.reload();
                } else {
                    mostrarError(data.message || "Error al registrar marcación");
                }
            })
            .catch(() => {
                mostrarError("Error de conexión");
            });

        },
        (err) => {
            cerrarModal();
            mostrarError("Activa el GPS para registrar la marcación");
        }
    );
}

function cerrarTipoModal() {
    document.getElementById('tipoModal').style.display = 'none';
}

// -----------------------
// Modales reutilizables
// -----------------------
function mostrarLoading() {
    document.getElementById('loadingModal').style.display = 'block';
}

function cerrarModal() {
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
}

function mostrarError(msg) {
    document.getElementById('errorMessage').innerText = msg;
    document.getElementById('errorModal').style.display = 'block';
}
</script>

</body>
</html>
