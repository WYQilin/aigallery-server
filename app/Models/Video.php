<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    protected $appends = ['url'];
    protected $hidden = ['file_path'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 视频链接
     * @return string
     */
    public function getUrlAttribute() {
        return Storage::url($this->attributes['file_name']);
    }
}
