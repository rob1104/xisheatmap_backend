<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tarjeta de Descuentos</title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap');
body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; -webkit-print-color-adjust: exact; }

        /* Dimensiones típicas de tarjeta CR80 (85.6mm x 54mm) convertidas a pixeles para 96dpi aprox */
.card-container {
    width: 324px;
            height: 204px;
            /* Fondo degradado oscuro parecido a tu template image_e00d94.png */
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        /* Marca de agua / Patrón de fondo sutil */
.card-pattern {
    position: absolute;
    inset: 0;
    opacity: 0.03;
    background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48ZyBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMSI+PHBhdGggZD0iTTAgMGg0MHY0MEgwVjB6bTIwIDIwaDIwdjIwSDIWMjB6TTAgMjBoMjB2MjBIMFYyMHoyMCAwaDIwdjIwSDIwVjB6Ii8+PC9nPjwvZz48L3N2Zz4=');
        }

        .header {
    padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-placeholder {
    font-weight: 800;
            font-size: 14px;
            color: #10B981; /* Verde esmeralda de tus pines */
        }

        .folio-box {
    text-align: right;
            font-size: 10px;
            opacity: 0.7;
        }

        .body-content {
    padding: 12px;
            position: relative;
            z-index: 2;
        }

        .title {
    text-transform: uppercase;
            font-weight: 800;
            font-size: 16px;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            background: linear-gradient(to right, #ffffff, #a7f3d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .voter-name {
    font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .voter-curp {
    font-size: 10px;
            opacity: 0.7;
            font-family: monospace;
            margin-bottom: 8px;
        }

        .qr-section {
    position: absolute;
    bottom: 12px;
            right: 12px;
            background: white;
            padding: 4px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-section img {
    width: 55px;
            height: 55px;
        }

        .validity-info {
    position: absolute;
    bottom: 12px;
            left: 12px;
            font-size: 9px;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="card-pattern"></div>

        <div class="header">
            <div class="logo-placeholder">RED DE APOYO</div>
            <div class="folio-box">
Folio No.<br>
                <span style="font-weight: 700; font-size: 12px; opacity: 1;">{{ $card->folio_formateado }}</span>
</div>
</div>

<div class="body-content">
    <div class="title">Tarjeta de Beneficios</div>

    <div style="font-size: 9px; text-transform: uppercase; opacity: 0.7; margin-top: 8px;">Simpatizante Registrado:</div>
    <div class="voter-name">{{ $card->ineRecord->nombre_completo }}</div>
    <div class="voter-curp">{{ $card->curp }}</div>
</div>

<div class="qr-section">
    {!! QrCode::size(100)->format('svg')->generate(json_encode($card->qr_data_json)) !!}
</div>

<div class="validity-info">
    Vigencia hasta:<br>
    <span style="font-weight: 700;">{{ $card->fecha_validez_fin->format('d / M / Y') }}</span>
</div>
</div>
</body>
</html>
