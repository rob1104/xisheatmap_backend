<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; color: #333333; line-height: 1.6; margin: 0; padding: 0; background-color: #f9fafb; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #111827 100%); padding: 30px 20px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; color: #f97316; } /* Naranja MESIL */
        .content { padding: 30px; }
        .details-box { background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f97316; }
        .footer { background: #f3f4f6; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>¡Gracias por tu apoyo!</h1>
        <p style="margin-top: 10px; opacity: 0.9;">Tu tarjeta de beneficios ha sido generada</p>
    </div>

    <div class="content">
        <p>Hola <strong>{{ $card->ineRecord->nombre }}</strong>,</p>

        <p>Como muestra de agradecimiento por sumarte a nuestra red, te hacemos entrega oficial de tu <strong>Tarjeta de Beneficios MESIL</strong>.</p>

        <div class="details-box">
            <p style="margin: 0 0 5px 0;"><strong>Folio de afiliado:</strong> {{ $card->folio_formateado }}</p>
            <p style="margin: 0;"><strong>Válida hasta:</strong> {{ $card->fecha_validez_fin->format('d / M / Y') }}</p>
        </div>

        <p><strong>¿Qué sigue?</strong><br>
            En este correo encontrarás una imagen adjunta. Descárgala y guárdala en tu celular. Podrás presentarla junto con tu identificación en los comercios afiliados para hacer válidos tus beneficios.</p>
    </div>

    <div class="footer">
        <p>Este correo se generó automáticamente. Si tienes dudas, comunícate con tu coordinador de brigada.</p>
    </div>
</div>
</body>
</html>
