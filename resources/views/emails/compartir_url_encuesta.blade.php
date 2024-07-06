
@component('mail::message')

### *¡Buenas! La isguiente encuesta se encuesntra disponible al público, para que cualquiera la pueda responder:*

# <center>{{ $titulo_encuesta }}</center>

> {{ $descripcion }}

@component('mail::button', ['url' => $url])
Responder Encuesta
@endcomponent

También puedes seguir el siguiente enlace para responder: {{ $url }} 

**{{ $mensaje_finalizacion }}**

*Gracias por su participación.*
*Saludos cordiales.*
*El equipo de Web Encuestas.*
@endcomponent
