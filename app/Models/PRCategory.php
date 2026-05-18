<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PRCategory extends Model
{
     use HasFactory, SoftDeletes;

    protected $table = 'problem_report_categories';

    protected $guarded = ['id'];

    protected $fillable = ['problem_report_category'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function problem_report()
    {
        return $this->hasMany(ProblemReport::class);
    }
}
