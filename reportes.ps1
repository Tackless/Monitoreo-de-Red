# Comprobación de privilegios elevados
if (-not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    $arguments = "& '" + $myInvocation.MyCommand.Definition + "'"
    Start-Process powershell -Verb runAs -ArgumentList $arguments
    exit
}

# Definir las direcciones IP o dominios a monitorear
$hosts = @("mail.falconmx.com", "facebook.com", "192.168.14.1", "falcon.intelisiscloud.com", "192.168.14.6")

# Obtener la fecha actual en formato deseado
$fechaActual = (Get-Date).ToString("yyyy-MM-dd")

# Definir la ruta del archivo de reporte con nombre dinámico
$reportFile = "C:\xampp\htdocs\MONITOR\Reporte de Red ($fechaActual).html"


# Crear el encabezado del archivo HTML
$header = @"
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Diario de Monitoreo de Red</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="my-4">Reporte Diario de Monitoreo de Red - $(Get-Date)</h1>
"@

# Crear el pie de página del archivo HTML
$footer = @"
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
"@

# Función para hacer ping y capturar resultados
function Ping-Hosts {
    param (
        [string[]]$hosts
    )
    $pingResults = "<h2>Resultados de Ping</h2><table class='table table-striped'><thead class='thead-light'><tr><th>Host</th><th>Disponible</th><th>Min (ms)</th><th>Max (ms)</th><th>Promedio (ms)</th><th>Paquetes Perdidos (%)</th><th>Calidad</th><th>Puerto</th></tr></thead><tbody>"
    foreach ($h in $hosts) {
        $ping = Test-Connection -ComputerName $h -Count 10 -ErrorAction SilentlyContinue

        if ($ping) {
            $responseTimes = $ping | Select-Object -ExpandProperty ResponseTime
            $minTime = $responseTimes | Measure-Object -Minimum | Select-Object -ExpandProperty Minimum
            $maxTime = $responseTimes | Measure-Object -Maximum | Select-Object -ExpandProperty Maximum
            $avgTime = $responseTimes | Measure-Object -Average | Select-Object -ExpandProperty Average

            $packetLoss = (($ping | Where-Object { $_.StatusCode -ne 0 }).Count / 10) * 100

            # Comprobar el puerto 8100 si es la dirección 192.168.14.6
            if ($h -eq "192.168.14.6") {
                $portTest = Test-NetConnection -ComputerName $h -Port 8100 -InformationLevel Detailed
                $portStatus = if ($portTest.TcpTestSucceeded) { "Abierto" } else { "Cerrado" }
            } elseif ($h -eq "falcon.intelisiscloud.com") {
                $portTest = Test-NetConnection -ComputerName $h -Port 1722 -InformationLevel Detailed
                $portStatus = if ($portTest.TcpTestSucceeded) { "Abierto" } else { "Cerrado" }
            }
             else {
                $portStatus = "N/A"
            }

            if ($packetLoss -eq 100) {
                $quality = "Desconectado"
            } elseif ($packetLoss -ge 50) {
                $quality = "Pérdida Significativa"
            } elseif ($avgTime -le 100) {
                $quality = "Buena"
            } elseif ($avgTime -le 200) {
                $quality = "Regular"
            } else {
                $quality = "Mala"
            }

            # $pingResults += "<tr><td>$h</td><td>Si</td><td>$minTime</td><td>$maxTime</td><td>$([math]::Round($avgTime, 2))</td><td>$([math]::Round($packetLoss, 2))</td><td>$quality</td></tr>"
            $pingResults += "<tr><td>$h</td><td>Si</td><td>$minTime</td><td>$maxTime</td><td>$([math]::Round($avgTime, 2))</td><td>$([math]::Round($packetLoss, 2))</td><td>$quality</td><td>$portStatus</td></tr>"
        } else {
            $pingResults += "<tr><td>$h</td><td>No</td><td>N/A</td><td>N/A></td><td>N/A</td><td>100</td><td>Desconectado</td></tr>"
        }
    }
    $pingResults += "</tbody></table>"
    return $pingResults
}

# Función para habilitar o deshabilitar una interfaz de red
function Enable-NetworkAdapter {
    param (
        [string]$adapterName
    )
    Write-Output "Habilitando adaptador: $adapterName"
    Enable-NetAdapter -Name $adapterName -Confirm:$false
}

function Disable-NetworkAdapter {
    param (
        [string]$adapterName
    )
    Write-Output "Deshabilitando adaptador: $adapterName"
    Disable-NetAdapter -Name $adapterName -Confirm:$false
}

# Función para obtener el estado de una interfaz de red
function Get-NetworkAdapterStatus {
    param (
        [string]$adapterName
    )
    return (Get-NetAdapter -Name $adapterName).Status
}

# Nombres de las interfaces de red (ajustar según sea necesario)
$wifiAdapter = "Wi-Fi"
$ethernetAdapter = "Ethernet"

# Deshabilitar WiFi al inicio
Disable-NetworkAdapter -adapterName $wifiAdapter

# Realizar escaneo con Ethernet
Enable-NetworkAdapter -adapterName $ethernetAdapter
Start-Sleep -Seconds 5

$ethernetStatus = Get-NetworkAdapterStatus -adapterName $ethernetAdapter
Write-Output "Estado de Ethernet: $ethernetStatus"
if ($ethernetStatus -eq "Up") {
    $pingReportEthernet = Ping-Hosts -hosts $hosts
} else {
    $pingReportEthernet = "<h2>Ethernet no disponible</h2>"
}

# Realizar escaneo con WiFi
Disable-NetworkAdapter -adapterName $ethernetAdapter
Enable-NetworkAdapter -adapterName $wifiAdapter
Start-Sleep -Seconds 10

$wifiStatus = Get-NetworkAdapterStatus -adapterName $wifiAdapter
Write-Output "Estado de WiFi: $wifiStatus"
if ($wifiStatus -eq "Up") {
    $pingReportWiFi = Ping-Hosts -hosts $hosts
} else {
    $pingReportWiFi = "<h2>WiFi no disponible</h2>"
}
Enable-NetAdapter -Name "Ethernet" -Confirm:$false

# Escribir el reporte en formato HTML
$header | Out-File -FilePath $reportFile -Encoding utf8
"<h2>Resultados con Ethernet</h2>" | Out-File -FilePath $reportFile -Append -Encoding utf8
$pingReportEthernet | Out-File -FilePath $reportFile -Append -Encoding utf8
"<h2>Resultados con WiFi</h2>" | Out-File -FilePath $reportFile -Append -Encoding utf8
$pingReportWiFi | Out-File -FilePath $reportFile -Append -Encoding utf8
$footer | Out-File -FilePath $reportFile -Append -Encoding utf8
