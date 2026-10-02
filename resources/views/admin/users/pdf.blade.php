<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Cuentas</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1e1e2f;
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            color: #64748b;
            margin: 5px 0 0 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) {
            background-color: #fcfcfd;
        }
        .role-badge {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Catálogo de Cuentas</h1>
        <p>Reporte generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
        @if($filters['role'] || $filters['search'])
            <p style="margin-top: 5px;">
                Filtros aplicados: 
                @if($filters['search']) Búsqueda: "{{ $filters['search'] }}" @endif
                @if($filters['role']) | Rol: "{{ $filters['role'] }}" @endif
            </p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estructura</th>
                <th style="text-align: center;">Capturas</th>
                <th>Creación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>
                    <strong>{{ $user->name }}</strong><br>
                    <span style="color: #64748b; font-size: 9px;">{{ $user->email }}</span>
                </td>
                <td>
                    <span class="role-badge">{{ $user->role->value ?? $user->role }}</span>
                </td>
                <td>
                    @if($user->parent)
                        <span style="color: #64748b; font-size: 10px;">Jefe: {{ $user->parent->name }}</span>
                    @else
                        <span style="color: #94a3b8; font-size: 10px;">Sin jefe</span>
                    @endif
                    <br>
                    <span style="font-size: 9px;">Equipo: {{ $user->children_count }}</span>
                </td>
                <td style="text-align: center; font-weight: bold;">
                    {{ $user->ines_count }}
                </td>
                <td>
                    {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Este documento es confidencial y para uso exclusivo del sistema. Total de usuarios en este reporte: {{ count($users) }}.
    </div>

</body>
</html>
