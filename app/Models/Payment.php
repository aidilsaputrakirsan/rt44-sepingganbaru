<?php

namespace App\Models;

use App\Models\Due;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'due_id',
        'recorded_by',
        'payer_id',
        'amount_paid',
        'method',
        'status',
        'proof_path',
        'verified_at',
    ];

    public function due()
    {
        return $this->belongsTo(Due::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }
}
