<?php

namespace App\Http\Controllers;

use App\Models\TypePermission;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['AllowedUsersOnly']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return UserRole::with('permission')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validData = $request->validate([
            'title' => "required|unique:" . UserRole::getTableName() . ",title",
            "permission" => 'required|array|min:1',
            "permission.*.uri" => 'required'
        ], [], [
            "permission.0.uri" => "URI"
        ]);

        $type = UserRole::create($request->only(['title']));

        $permissionList = [];
        foreach ($request->permission as $per) {
            $permissionList[] = new TypePermission(['uri' => $per['uri']]);
        }

        $type->permission()->saveMany($permissionList);

        return  $type;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserRole  $userRole
     * @return \Illuminate\Http\Response
     */
    public function show(UserRole $userRole)
    {
        return $userRole->load('permission');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserRole  $userRole
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserRole $userRole)
    {
        if ($userRole->id == 1) {
            //abort(403, 'You cannot edit this user type');
        }

        $request->validate([
            'title' => ["required", Rule::unique(UserRole::getTableName())->ignore($userRole->id)],
            "permission" => 'required|array|min:1',
            "permission.*.uri" => 'required'
        ], [], [
            "permission.0.uri" => "URI"
        ]);

        $userRole->fill($request->only(['title']));
        $userRole->save();

        $userRole->permission()->delete();

        $permissionList = [];
        foreach ($request->permission as $per) {
            $permissionList[] = new TypePermission(['uri' => $per['uri']]);
        }

        $userRole->permission()->saveMany($permissionList);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserRole  $userRole
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserRole $userRole)
    {
        if ($userRole->id == 1) {
            abort(403, 'You cannot delete this user type');
        }

        if ($userRole->users()->count() == 0) {
            $userRole->permission()->delete();
            $userRole->delete();
        } else {
            abort(403, 'This type is assigned to users');
        }
    }
}
