<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'fullname',
        'password',
        'badan_usaha_id',
        'division_id',
        'role_id',
        'profile_picture',
        'approval_id',
        'notif_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function division()
    {
        return $this->belongsTo(Divisi::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function canAccessInsuranceMenu()
    {
        return $this->canManageInsurance() || $this->id == 75;
    }

    public function canManageInsurance()
    {
        return $this->role_id == 1 || ($this->role_id == 3 && $this->division_id == 6);
    }

    public function badan_usaha()
    {
        return $this->belongsTo(BadanUsaha::class);
    }

    public function request_type()
    {
        return $this->belongsTo(RequestType::class);
    }

    public function approval()
    {
        return $this->belongsTo(User::class, 'approval_id');
    }

    public function problem_report()
    {
        return $this->hasMany(ProblemReport::class);
    }

    public function getProfilePic()
    {
        if(!$this->profile_picture){
            return asset('images/default.png');
        }

        return asset('storage/profile/'.$this->profile_picture);
    }
}
