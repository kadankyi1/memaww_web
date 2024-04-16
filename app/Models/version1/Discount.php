<?php

namespace App\Models\version1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;
    
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'discount_id';

    protected $fillable = [
        'discount_id',
        'discount_code',
        'discount_percentage',
        'discount_restricted_to_user_id',
        'discount_admin_name',
        'discount_reusable',
        'discount_can_be_used',
        'created_at',
        'updated_at',
    ];
}
