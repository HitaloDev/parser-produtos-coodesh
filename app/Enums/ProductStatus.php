<?php

namespace App\Enums;

enum ProductStatus: string
{
    case DRAFT = 'draft';
    case TRASH = 'trash';
    case PUBLISHED = 'published';
}
