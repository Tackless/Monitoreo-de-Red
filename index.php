<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ejecutar el script PowerShell reportes.ps1
    $psScriptPath = "C:\\xampp\\htdocs\\MONITOR\\reportes.ps1"; // Cambia esta ruta a la ubicación real de tu script PowerShell
    $output = shell_exec("powershell -ExecutionPolicy Bypass -File \"$psScriptPath\"");
    
    // Una vez que el script ha terminado, redirigir a procesar_reporte.php
    header("Location: procesar_reporte.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Diario de Monitoreo de Red</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-image: url('principal.jpg'); /* Cambia esta ruta a tu imagen de fondo */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            text-align: center;
            color: white;
        }

        h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7); /* Sombra para mejor legibilidad */
        }

        .btn {
            padding: 15px 30px;
            font-size: 1.5em;
            color: white;
            background-color: #007BFF;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background-color: #0056b3;
        }

        form {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Reporte Diario de Monitoreo de Red</h1>
    <form method="post">
        <button type="submit" class="btn">Generar Reporte</button>
    </form>
</body>
</html>


