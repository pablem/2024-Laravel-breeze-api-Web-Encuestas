<?php

namespace App\Enums;

enum TipoPregunta: string {
	case Text = 'text';
    case Multiple = 'multiple choice';
    case Unique = 'unique choice';
    case List = 'list';
    case Rating = 'rating';
    case Numeric = 'numeric';
}