<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #1f2937; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; font-size: 14px; }
        .footer { margin-top: 20px; font-size: 0.8em; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Has sido invitado a unirte a un equipo</h2>
        
        <p>Hola,</p>
        
        <p>{{ $invitation->team->owner->name }} te invita a unirte a su equipo <strong>{{ $invitation->team->name }}</strong> y colaborar.</p>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $acceptUrl }}" class="button">Aceptar Invitación</a>
        </p>
        
        <p>Si no esperabas esta invitación, puedes ignorar este correo.</p>
        
        <div class="footer">
            <p>Si tienes problemas con el botón, copia y pega el siguiente enlace en tu navegador:</p>
            <p>{{ $acceptUrl }}</p>
        </div>
    </div>
</body>
</html>
