<?php

namespace App\Enums;

enum UserRole: string {
    case Super = 'Super';
	case Administrador = 'Administrador';
    case Editor = 'Editor';
    case Publicador = 'Publicador';
}