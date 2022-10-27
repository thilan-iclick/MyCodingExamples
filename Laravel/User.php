<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\DB;
use App\Notifications\PasswordReset;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Find the user instance for the given username.
     *
     * @param  string  $username
     * @return \App\Models\User
     */
    public function findForPassport($username)
    {
        return $this->where('username', $username)->first();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'user_type_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'user_status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function type()
    {
        return $this->belongsTo(UserRole::class, 'user_type_id');
    }

    protected $appends = ['roll_call_allowed_category_ids'];

    public function getRollCallAllowedCategoryIdsAttribute()
    {
        return $this->rollCallAllowedCategories()->pluck('id');
    }

    public function rollCallAllowedCategories()
    {
        return $this->belongsToMany(MembershipCategory::class);
    }

    public static $rules =  [
        'name' => 'required',
        'email' => 'required|email',
        'username' => 'required|max:191|unique:users',
        'user_type_id' => 'required|exists:user_types,id',
        'roll_call_allowed_category_ids' => 'array|exists:membership_categories,id'
    ];

    public static $messages =  [
        'user_type_id.between' => 'Please select a valid user type',
    ];

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    public function isAllowed($urlName)
    {

        /*
         Make this value true only to train the system to record user type behaviour
        */
        $recordURI = false;

        if (is_null($urlName)) {
            $urlName = \Route::currentRouteName();
        }

        if ($recordURI) {

            $foundARecord = DB::table('allowed_uri_for_user_types')
                ->where('uri', $urlName)
                ->where('user_type_id', $this->user_type_id)
                ->count() > 0;

            if ($foundARecord  == false) {
                DB::table('allowed_uri_for_user_types')->insert(
                    [
                        'user_type_id' => $this->user_type_id,
                        'uri' => $urlName // $uri,
                    ]
                );
            }

            return true;
        } else {

            return DB::table('allowed_uri_for_user_types')
                ->where('uri', $urlName)
                ->where('user_type_id', $this->user_type_id)
                ->count() > 0;
        }
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordReset($token, $this->email));
    }
}
