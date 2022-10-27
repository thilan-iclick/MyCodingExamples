<?php

namespace App\Http\Controllers;

use App\Mail\SendPassword;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            ['AllowedUsersOnly'],
            [
                'except' => [
                    'me'
                ]
            ]
        );
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $user = Auth::guard('api')->user();

        return User::with(['type'])->where('id', '!=', $user->id)->where('user_status','!=',0)->get();
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $user->load('type.permission');
        return $user;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\User\Store  $request
     * @return \Illuminate\Http\Response
     */
    public function store(\App\Http\Requests\User\Store $request)
    {
        $data = $request->all();
        $randompw=Str::random(5);
        $data['password'] =  Hash::make($randompw);
        $user = User::create($data);
        Mail::to($user)->send(new SendPassword($user,$randompw));

        $user->rollCallAllowedCategories()->sync($request->roll_call_allowed_category_ids);

        $user = $user->fresh();
        $user->load('type');
        return $user;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user)
    {
        return $user;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\User\Store  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(\App\Http\Requests\User\Update $request, User $user)
    {
        
        $user->fill($request->all());
        $user->save();
        $user->rollCallAllowedCategories()->sync($request->roll_call_allowed_category_ids);
        $user->load('type');
        return $user;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->user_status = 0;
        $user->save();
        //$user->delete();
    }

    public function getUserTypes()
    {
        return UserType::all();
    }

    public function resetPassword(User $user)
    {
        $user->forceFill([
            'password' => Hash::make('password')
        ])->save();
    }
}
