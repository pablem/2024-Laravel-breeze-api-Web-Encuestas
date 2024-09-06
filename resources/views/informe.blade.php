<!DOCTYPE html>
<html>

<head>
    <title>Informe</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }
    </style>
</head>

<body>
    <h1>{{ $informe['titulo_encuesta'] }}</h1>
    <p>Días restantes: {{ $informe['dias_restantes'] }}</p>
    <p>Número de Respuestas: {{ $informe['numero_respuestas'] }}</p>

    @foreach ($informe['preguntas'] as $pregunta)
    <h2>{{ $pregunta['titulo_pregunta'] }}</h2>
    <h3>Pregunta tipo "{{ $pregunta['tipo_pregunta'] }}"</h3>
    <table>
        <caption>Resumen de Respuestas</caption>
        <thead>
            <tr>
                <th>Opción</th>
                <th>Resultados</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pregunta['resultados'] as $resultado)
            <tr>
                <td>{{ $resultado['titulo_opcion'] }}</td>
                <td>{{ $resultado['resultado_opcion'] }}</td>
                <td>{{ $resultado['porcentaje'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3><strong>Estadísticas:</strong></h3>
    <ul>
        @foreach ($pregunta['estadisticas'] as $key => $value)
        @if (is_array($value))
        <!-- Para el caso de arrays anidados -->
        <li>{{ ucfirst(str_replace('_', ' ', $key)) }}:</li>
        <br>
        <table>
            <thead>
                <tr>
                    <th>Elemento</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($value as $subKey => $subValue)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $subKey)) }}</td>
                    <td>{{ $subValue }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <br>
        @else
        <li>
            <!-- Formato "Clave: Valor" para valores simples -->
            {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value ?? 'N/A' }}
        </li>
        @endif
        @endforeach
    </ul>
    @endforeach
</body>

</html>