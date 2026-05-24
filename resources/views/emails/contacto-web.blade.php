<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo mensaje de contacto</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111827;">
    <h1 style="font-size: 20px; margin-bottom: 12px;">Nuevo mensaje desde SmartSuper</h1>

    <p><strong>Nombre:</strong> {{ $datos['nombre'] }}</p>
    <p><strong>Email:</strong> {{ $datos['email'] }}</p>
    <p><strong>Asunto:</strong> {{ $datos['asunto'] ?: 'Sin asunto' }}</p>

    <h2 style="font-size: 17px; margin-top: 24px;">Mensaje</h2>
    <p style="white-space: pre-line;">{{ $datos['mensaje'] }}</p>

    <hr style="margin: 24px 0; border: 0; border-top: 1px solid #e5e7eb;">

    <p style="font-size: 12px; color: #6b7280; margin: 0;">IP origen: {{ $ip ?? 'No disponible' }}</p>
    <p style="font-size: 12px; color: #6b7280; margin: 0;">User-Agent: {{ $userAgent ?? 'No disponible' }}</p>
</body>
</html>
