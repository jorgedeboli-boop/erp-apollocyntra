<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador QR con Token</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        #qrcode {
            text-align: center;
            margin: 30px 0;
            min-height: 220px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        .info {
            background-color: #e3f2fd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid #2196F3;
        }
        .info p {
            margin: 5px 0;
            font-size: 14px;
        }
        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: monospace;
        }
        input:read-only {
            background-color: #f9f9f9;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Generador QR con Token</h1>
        
        <div class="info">
            <p><strong>ℹ️ Información:</strong></p>
            <p>Cada QR contiene un token único de un solo uso. Al hacer clic en "Generar Nuevo QR", se crea un token diferente.</p>
        </div>

        <label for="idInput">ID del Usuario/Producto:</label>
        <input type="text" id="idInput" value="12345" placeholder="Ingresa el ID">

        <label for="tokenDisplay">Token Actual:</label>
        <input type="hidden" id="tokenDisplay" readonly>

        <label for="urlDisplay">URL Generada:</label>
        <input type="text" id="urlDisplay" readonly>

        <div id="qrcode"></div>

        <button onclick="generarNuevoQR()">🔄 Generar Nuevo QR</button>
    </div>

    <script>
        // Función para generar un token único
        function generarToken() {
            // Genera un token aleatorio de 32 caracteres
            return 'tok_' + Math.random().toString(36).substr(2, 28) + Date.now().toString(36);
        }

        // Función para generar el QR
        function generarNuevoQR() {
            const id = document.getElementById('idInput').value || '12345';
            const token = generarToken();
            
            // Guardar el token en pantalla
            document.getElementById('tokenDisplay').value = token;
            
            // Construir URL con parámetros
            const url = `${window.location.origin}/get_photo/index.php?type=cliente&id=${encodeURIComponent(id)}&token=${token}`;
            document.getElementById('urlDisplay').value = url;
            
            // Limpiar QR anterior
            const qrcodeDiv = document.getElementById('qrcode');
            qrcodeDiv.innerHTML = '';
            
            // Generar nuevo QR
            new QRCode(qrcodeDiv, {
                text: url,
                width: 200,
                height: 200,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        // Generar QR al cargar la página
        window.addEventListener('load', function() {
            generarNuevoQR();
        });

        // Regenerar QR cuando cambies el ID
        document.getElementById('idInput').addEventListener('change', generarNuevoQR);
    </script>
</body>
</html>