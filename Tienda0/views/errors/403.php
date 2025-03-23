<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acceso Denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            min-height: 100vh;
        }
        .error-container {
            max-width: 600px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: #343a40;
        }
        .btn-back {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container text-center error-container">
        <div class="mb-4">
            <div class="error-code">403</div>
            <h2 class="mb-3">Acceso Denegado</h2>
            <p class="lead">Lo sentimos, no tiene permisos para acceder a esta página.</p>
        </div>
        <div class="mb-4">
            <p>Si cree que esto es un error, por favor contacte al administrador.</p>
        </div>
        <a href="/" class="btn btn-primary btn-back">Volver al inicio</a>
    </div>
</body>
</html>