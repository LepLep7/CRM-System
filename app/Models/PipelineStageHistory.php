<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineStageHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id', 'changed_by', 'from_stage', 'to_stage',
        'is_rollback', 'approved_by',
    ];

    protected $casts = [
        'is_rollback' => 'boolean',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}