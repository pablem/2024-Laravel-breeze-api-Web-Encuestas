<!DOCTYPE html>
<html>
<head>
    <title>Encuesta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        p {
            color: #555;
        }
        .question {
            margin-bottom: 20px;
        }
        .option-list {
            list-style-type: none;
            padding: 0;
        }
        .option-list li {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .option-list input[type="checkbox"],
        .option-list input[type="radio"] {
            margin-right: 10px;
        }
        .text-area {
            width: 100%;
            height: 100px;
            resize: vertical;
        }
        .numeric-input,
        .rating-input {
            resize: vertical;
            width: 100px;
            height: 50px;
        }
    </style>
</head>
<body>
    <h1>{{ $encuesta->titulo_encuesta }}</h1>
    <p>{{ $encuesta->descripcion }}</p>

    <h2>Preguntas</h2>
    @foreach ($preguntas as $pregunta)
        <div class="question">
            <h3>{{ $pregunta->titulo_pregunta }}</h3>

            @if ($pregunta->tipo_pregunta->value === 'text')
                <textarea class="text-area" readonly></textarea>
            @elseif ($pregunta->tipo_pregunta->value === 'numeric')
                <input type="text" class="numeric-input" readonly>
            @elseif ($pregunta->tipo_pregunta->value === 'multiple choice')
                <ul class="option-list">
                    @foreach ($pregunta->seleccion as $opcion)
                        <li><input type="checkbox" readonly> {{ $opcion }}</li>
                    @endforeach
                </ul>
            @elseif ($pregunta->tipo_pregunta->value === 'list' || $pregunta->tipo_pregunta->value === 'unique choice')
                <ul class="option-list">
                    @foreach ($pregunta->seleccion as $opcion)
                        <li><input type="radio" readonly> {{ $opcion }}</li>
                    @endforeach
                </ul>
            @elseif ($pregunta->tipo_pregunta->value === 'rating')
                <p>Rango de puntuación: {{ $pregunta->rango_puntuacion[0] . ' - ' . $pregunta->rango_puntuacion[1] . ' (paso ' . $pregunta->rango_puntuacion[2] . ')'}}</p>
                <input type="text" class="rating-input" readonly>
            @endif
        </div>
    @endforeach
</body>
</html>
