<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudioHistory extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'judul',
        'filter',
        'file',
        'original_name',
    ];

    /**
     * Get the formatted creation date.
     */
    public function getWaktuAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y, H:i') : '';
    }
}
