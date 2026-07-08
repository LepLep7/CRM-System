<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id', 'uploaded_by', 'category',
        'original_name', 'file_path', 'mime_type', 'size',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}