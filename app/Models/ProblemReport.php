<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProblemReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'problem_report';

    protected $guarded = ['id'];

    protected $fillable = ['problem_report_code', 'user_id', 'date', 'title', 'description', 'status', 'scheduled_at', 'status_client', 'closed_by', 'closed_at', 'pr_category_id', 'result_desc'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function prcategory()
    {
        return $this->belongsTo(PRCategory::class, 'pr_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function closedby()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

}
