<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Request extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'problem_report';

    protected $guarded = ['id'];

    protected $fillable = ['user_id', 'date', 'total_cost', 'status_po', 'status_client', 'closed_by', 'closed_at', 'approved_by', 'approved_at', 'request_type_id', 'request_file', 'approved_file', 'new_product'];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function request_type()
    {
        return $this->belongsTo(RequestType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function closedby()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getApprovedByname()
    {
        $user_id = $this->approved_by;
        $username = User::where('id', $user_id)->get('fullname');

        $parsedData = json_decode($username, true);
        $fullname = $parsedData[0]["fullname"];

        return $fullname;
    }
}
