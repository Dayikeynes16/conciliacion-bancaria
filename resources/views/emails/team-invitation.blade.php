<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }
        .wrapper {
            background-color: #f8fafc;
            padding: 40px 10px;
        }
        .content {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin: 0 auto;
            max-width: 500px;
            overflow: hidden;
        }
        .header {
            background-color: #0f172a;
            padding: 35px 20px;
            text-align: center;
        }
        .body {
            padding: 40px 35px;
            text-align: center;
        }
        h1 {
            color: #1e293b;
            font-size: 24px;
            font-weight: 800;
            margin-top: 0;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }
        p {
            color: #475569;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .button {
            background-color: #2563eb;
            border-radius: 8px;
            color: #ffffff !important;
            display: inline-block;
            font-size: 16px;
            font-weight: 700;
            line-height: 1;
            padding: 20px 35px;
            text-decoration: none;
            margin: 20px 0;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .link-text {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 15px;
            word-break: break-all;
        }
        .logo {
            max-height: 50px;
            width: auto;
        }
        .team-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 14px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="header">
                <img src="{{ asset('images/logo.png') }}" class="logo" alt="Logo">
            </div>
            <div class="body">
                <h1>¡Hola! 👋</h1>
                <p>
                    <strong>{{ $invitation->team->owner->name }}</strong> te ha invitado a unirte a su equipo de trabajo.
                </p>
                
                <div class="team-badge">
                    {{ $invitation->team->name }}
                </div>
                
                <p>Al aceptar, podrás colaborar en la conciliación bancaria, gestionar facturas y optimizar los procesos financieros juntos.</p>
                
                <a href="{{ $acceptUrl }}" class="button">ACEPTAR INVITACIÓN</a>
                
                <p style="font-size: 14px; color: #64748b; margin-top: 25px;">
                    Si no esperabas esta invitación, puedes ignorar este correo.
                </p>
            </div>
            <div class="footer">
                <p style="font-size: 13px; color: #64748b; font-weight: 600; margin: 0;">
                    © {{ date('Y') }} {{ config('app.name') }}
                </p>
                <div class="link-text">
                    Si tienes problemas con el botón, usa este enlace:<br>
                    <a href="{{ $acceptUrl }}" style="color: #2563eb; text-decoration: none;">{{ $acceptUrl }}</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
