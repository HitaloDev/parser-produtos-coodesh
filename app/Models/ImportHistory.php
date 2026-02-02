<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    protected $fillable = [
        'filename',
        'status',
        'total_products',
        'imported_products',
        'failed_products',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'status' => ImportStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_products' => 'integer',
        'imported_products' => 'integer',
        'failed_products' => 'integer',
    ];
}
