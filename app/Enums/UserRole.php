<?php

namespace App\Enums;

enum UserRole: string {
	case Admin = 'Administrador';
    case Editor = 'Editor';
    case Publicador = 'Publicador';
}