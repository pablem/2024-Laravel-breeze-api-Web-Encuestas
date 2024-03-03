<?php

namespace App\Enums;

enum EstadoEncuesta: string {
	case Borrador = 'borrador';
    case Piloto = 'piloto';
    case Publicada = 'publicada';
}