<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'amount',
        'payment_method',
        'vendor',
        'member_id',
        'member_name',
        'expense_date',
        'reference_number',
        'attachment_url',
        'notes',
        'status',
        'created_by_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'expense_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
