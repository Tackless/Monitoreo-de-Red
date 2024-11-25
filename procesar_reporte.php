<?php
set_time_limit(300);

// Establecer la hora local
date_default_timezone_set('America/Mexico_City');

//Se crea la ruta base como variable para modificar a futuro
$rutaBase = "C:/xampp/htdocs/MONITOR";

// Obtener la fecha actual en el mismo formato que se usa en PowerShell
$fechaActual = date('Y-m-d');
$fechaHoraActual = date('Y-m-d H-i-s');
$mesActual = date('M-Y');

//Crea ruta de las carpetas para archivar
$rutaCarpetaMes = $rutaBase . "/$mesActual";

//Verifica que exista la carpeta del mes si no la crea
if (!file_exists($rutaCarpetaMes)) {
    mkdir($rutaCarpetaMes, 0777, true);
}

// Definir la ruta del archivo de reporte con el nombre dinámico
$file_path = $rutaBase . "/Reporte de Red ($fechaActual).html";

//Define la ruta de donde se van a archivar los reportes generados
$archivoArchivado = $rutaCarpetaMes . "/Reporte de Red ($fechaHoraActual).html";

$max_wait_time = 360;
$wait_interval = 10;

$start_time = time();

while (!file_exists($file_path)) {
    $time_elapsed = time() - $start_time;
    
    if ($time_elapsed >= $max_wait_time) {
        echo "Error: El archivo de reporte no fue generado dentro del tiempo esperado.";
        break;
    }
    
    sleep($wait_interval);
}

if (file_exists($file_path)) {
    $html = file_get_contents($file_path);

    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $tables = $dom->getElementsByTagName('table');
    $hostsData = [];

    // Mapa de nombres amigables
    $hostNames = [
        'mail.falconmx.com' => 'Correo Electrónico',
        'facebook.com' => 'Navegación Web',
        '192.168.14.1' => 'Equipo de Seguridad',
        'falcon.intelisiscloud.com' => 'Intelisis',
        '192.168.14.6' => 'Genera'
    ];

    foreach ($tables as $table) {
        $rows = $table->getElementsByTagName('tr');
        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length > 0) {
                $host = $cols->item(0) ? $cols->item(0)->textContent : 'N/A';
                $avgTime = $cols->item(4) ? floatval($cols->item(4)->textContent) : 0;
                $packetLoss = $cols->item(5) ? floatval($cols->item(5)->textContent) : 0;

                // Determinar el ícono basado en los criterios de calidad y pérdida de paquetes
                if ($packetLoss == 100) {
                    $statusIcon = 'icon-bad';
                    $icon = '✗';
                    $statusMessage = 'Servicio no disponible';
                } elseif ($host == 'mail.falconmx.com') {
                    if ($avgTime < 200) {
                        $statusIcon = 'icon-good';
                        $icon = '✔️';
                        $statusMessage = 'Servicio activo';
                    } elseif ($avgTime >= 200 && $avgTime <= 250) {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    } else {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    }
                } elseif ($host == 'falcon.intelisiscloud.com') {
                    if ($avgTime < 200) {
                        $statusIcon = 'icon-good';
                        $icon = '✔️';
                        $statusMessage = 'Servicio activo';
                    } elseif ($avgTime >= 200 && $avgTime <= 250) {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    } else {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    }
                } 
                elseif ($host == '192.168.14.6') {
                    if ($avgTime < 200) {
                        $statusIcon = 'icon-good';
                        $icon = '✔️';
                        $statusMessage = 'Servicio activo';
                    } elseif ($avgTime >= 200 && $avgTime <= 250) {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    } else {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    }
                }elseif ($host == 'facebook.com') {
                    if ($avgTime < 200) {
                        $statusIcon = 'icon-good';
                        $icon = '✔️';
                        $statusMessage = 'Servicio activo';
                    } elseif ($avgTime >= 200 && $avgTime <= 250) {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    } else {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    }
                } else {
                    if ($avgTime <= 30 && $packetLoss <= 0) {
                        $statusIcon = 'icon-good';
                        $icon = '✔️';
                        $statusMessage = 'Servicio activo';
                    } elseif (($avgTime > 30 && $avgTime < 50) || ($packetLoss > 20 && $packetLoss <= 50)) {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    } else {
                        $statusIcon = 'icon-regular';
                        $icon = '⚠️';
                        $statusMessage = 'Servicio activo';
                    }
                }

                // Aplicar el nombre amigable si existe
                $displayName = isset($hostNames[$host]) ? $hostNames[$host] : $host;

                $hostsData[] = [
                    'host' => $displayName,
                    'avgTime' => $avgTime,
                    'statusIcon' => $statusIcon,
                    'icon' => $icon,
                    'statusMessage' => $statusMessage
                ];
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Procesar Reporte</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            body {
                display: flex;
                flex-direction: column;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                font-family: Arial, sans-serif;
                background-image: url('principal.jpg'); /* Ruta a tu imagen */
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-attachment: fixed;
            }

            #report-container {
                text-align: center;
                background-color: rgba(255, 255, 255, 0.8); /* Fondo semitransparente para legibilidad */
                padding: 20px;
                border-radius: 10px;
                width: 90%;
                max-width: 1000px;
            }

            .spinner {
                border: 4px solid rgba(0, 0, 0, 0.1);
                width: 36px;
                height: 36px;
                border-radius: 50%;
                border-left-color: #09f;
                animation: spin 1s ease infinite;
                display: inline-block;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .icon-good { color: green; }
            .icon-regular { color: orange; }
            .icon-bad { color: red; }

            .host-result {
                display: none;
            }

            .report-data {
                display: none;
            }

            .divider {
                border-top: 2px solid #ccc;
                margin: 20px 0;
            }

            h2 {
                margin: 20px 0;
            }

            h3 {
                margin: 10px 0;
            }
        </style>
    </head>
    <body>    
        <div id="report-container">
            <h2>ETHERNET</h2>
            <?php
            $showWiFiTitle = false;
            foreach ($hostsData as $index => $hostData):
                ?>
                <div class="host-result" id="host-<?php echo $index; ?>">
                    <h3><?php echo htmlspecialchars($hostData['host']); ?></h3>
                    <div class="spinner"></div>
                    <div class="report-data">
                        <span class="<?php echo $hostData['statusIcon']; ?>"><?php echo $hostData['icon']; ?></span>
                        <p>Promedio de tiempo de respuesta: <?php echo htmlspecialchars($hostData['avgTime']); ?> ms</p>
                        <p><?php echo htmlspecialchars($hostData['statusMessage']); ?></p>
                    </div>
                </div>
                <?php 
                
                // Mostrar el título "WiFi" solo una vez, después del último host de "ETHERNET"
                if ($hostData['host'] == 'Genera' && !$showWiFiTitle):
                    ?>
                    <div class="divider"></div>
                    <h2>WiFi</h2>
                    <?php $showWiFiTitle = true; // Asegura que el título "WiFi" solo aparezca una vez ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const hostResults = document.querySelectorAll('.host-result');
                let delay = 0;

                hostResults.forEach((hostResult, index) => {
                    setTimeout(() => {
                        hostResult.style.display = 'block';
                        const spinner = hostResult.querySelector('.spinner');
                        const reportData = hostResult.querySelector('.report-data');

                        setTimeout(() => {
                            spinner.style.display = 'none';
                            reportData.style.display = 'block';
                        }, 3000); // Espera 3 segundos para mostrar el ícono y datos
                    }, delay);

                    delay += 3000; // Incrementa el retraso para cada host
                });
            });
        </script>
    </body>
    </html>

    <?php

    if (file_exists($file_path)) {
        rename($file_path, $archivoArchivado);
    }

} else {
    echo "No se pudo encontrar el archivo de reporte. El script PHP no fue modificado.";
}
?>
