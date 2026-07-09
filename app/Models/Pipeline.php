<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'salesperson_id', 'department_id', 'stage',
        'project_name', 'chance_percent', 'chance_level', 'expected_start_date',
        'scope_of_service', 'customer_product', 'date_funnel',
        'value_per_annum', 'contract_period', 'number_of_months',
        'origin_country', 'port_of_loading', 'destination_country',
        'port_of_destination', 'operating_profit_margin', 'remarks_proposal',
        'date_secured', 'date_go_live', 'forecast_revenue',
        'remarks_contract', 'is_locked',
    ];

    protected $casts = [
        'expected_start_date' => 'date',
        'date_funnel' => 'datetime',
        'date_secured' => 'date',
        'date_go_live' => 'date',
        'is_locked' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function stageHistories()
    {
        return $this->hasMany(PipelineStageHistory::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}