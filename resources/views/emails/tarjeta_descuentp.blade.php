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
        .cta-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 22px 20px; margin: 25px 0 10px 0; text-align: center; }
        .cta-btn { background: #ea580c; background-image: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #ffffff !important; text-decoration: none; padding: 13px 26px; border-radius: 6px; font-weight: bold; font-size: 15px; display: inline-block; box-shadow: 0 3px 6px rgba(234, 88, 12, 0.25); }
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

        <!-- Sección de Comercios Participantes -->
        <div class="cta-box" style="background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 10px; padding: 22px 20px; margin: 25px 0 10px 0; text-align: center;">
            <div style="font-size: 24px; margin-bottom: 6px;">🏬</div>
            <h2 style="margin: 0 0 8px 0; color: #9a3412; font-size: 18px; font-weight: 700;">¡Conoce los Comercios Participantes!</h2>
            <p style="margin: 0 0 18px 0; color: #4b5563; font-size: 14px; line-height: 1.5;">
                Descubre todos los establecimientos afiliados y las promociones exclusivas que puedes aprovechar con tu tarjeta.
            </p>
            <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                    <td align="center" style="border-radius: 6px; background-color: #ea580c;">
                        <a href="https://casasmesil.com.mx/comercios-participantes/" target="_blank" class="cta-btn" style="background: #ea580c; background-image: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: #ffffff !important; text-decoration: none; padding: 13px 26px; border-radius: 6px; font-weight: bold; font-size: 15px; display: inline-block;">
                            Ver Comercios Participantes &rarr;
                        </a>
                    </td>
                </tr>
            </table>
            <p style="margin: 14px 0 0 0; font-size: 12px; color: #6b7280;">
                O visita: <a href="https://casasmesil.com.mx/comercios-participantes/" target="_blank" style="color: #ea580c; text-decoration: underline; word-break: break-all;">https://casasmesil.com.mx/comercios-participantes/</a>
            </p>
        </div>
    </div>

    <div class="footer">
        <p>Este correo se generó automáticamente. Si tienes dudas, comunícate con tu coordinador de brigada.</p>
    </div>
</div>
</body>
</html>
