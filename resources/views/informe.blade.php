<!DOCTYPE html>
<html>
<head>
    <title>Informe</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
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

    @foreach ($informe['preguntas'] as $pregunta)
        <h2>{{ $pregunta['titulo_pregunta'] }}</h2>
        <table>
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
    @endforeach
</body>
</html>
