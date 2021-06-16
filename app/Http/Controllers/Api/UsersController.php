<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mureed;
use App\Models\Role;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{

    public function userList()
    {

    }

    //user register
    public function register(Request $request)
    {
        //set validation rules
        $rules = [
            'name' => 'required',
            'father_name' => 'required',
            'birthdate' => 'required',
            'gender' => 'required',
            'blood_group' => 'required',
            'nid' => 'required|unique:mureeds,nid',
            'nationality' => 'required',
            'mobile' => 'required',
            'home_address' => 'required',
            'division_id' => 'required',
            'district_id' => 'required',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ];

        //set validation custom message
        $messages = [
            'father_name.required' => 'Father/Husband name required',
            'division_id.required' => 'Division is required',
            'district_id.required' => 'District is required',
            'password_confirmation.required' => 'Confirm password required',
        ];

        //make validation
        $validation = Validator::make($request->all(), $rules, $messages);

        //check validation
        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validation->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            //register new user
            $user = new User();
            $user->name = $request->input('name');
            $user->username = User::userId();
            $user->password = app('hash')->make($request->input('password'));
            $user->otp = rand(111111, 999999);

            if ($user->save()) {

                //assign role
                $user->attachRole(Role::where('name', 'disciple')->first());
                //register as murids
                $murid = new Mureed();
                $murid->user_id = $user->id;
                $murid->division_id = $request->input('division_id');
                $murid->district_id = $request->input('district_id');
                $murid->upazila_id = $request->input('upazila_id');
                $murid->name = $request->input('name');
                $murid->father_name = $request->input('father_name');
                $murid->head_of_family = $request->input('head_of_family');
                $murid->birthdate = $request->input('birthdate');
                $murid->gender = $request->input('gender');
                $murid->blood_group = $request->input('blood_group');
                $murid->place = $request->input('place');
                $murid->nid = $request->input('nid');
                $murid->nationality = $request->input('nationality');
                $murid->profession = $request->input('prefession');
                $murid->home_address = $request->input('home_address');
                $murid->telephone_home = $request->input('telephone_home');
                $murid->mobile = $request->input('mobile');
                $murid->office_address = $request->input('office_address');
                $murid->telephone_office = $request->input('telephone_office');
                $murid->fax = $request->input('fax');
                $murid->emergency_contact = $request->input('emergency_contact');
                $murid->emergency_telephone = $request->input('emergency_telephone');
                $murid->disciple_of = $request->input('disciple_of');
                $murid->email = $request->input('email');
                $murid->website = $request->input('website');
                $murid->remarks = $request->input('remarks');

                //photo upload
                if ($request->hasFile('photo')) {
                    $photo_path = Utility::fileUpload($request, 'photo', 'mureeds');

                    $murid->photo = $photo_path;
                }
                //signature upload
                if ($request->hasFile('signature')) {
                    $sign_path = Utility::fileUpload($request, 'signature', 'mureeds');

                    $murid->signature = $sign_path;
                }

                $murid->created_by = $user->id;

                $murid->save();
                DB::commit();
                //create token
                $token = $user->createToken('ashrafia')->accessToken;

                $user->mureed;

                return response()->json([
                    'status' => true,
                    'message' => 'You have registered successfully',
                    'user' => $user,
                    'role' => $user->role()->name,
                    'token' => $token
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Failed to register',
            ]);

        }catch(\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'An error occured while register. '.$e->getMessage() . $e->getLine(),
            ]);
        }
    }

    // login
    public function login(Request $request)
    {
        //set validation rules
        $rules = [
            'username' => 'required',
            'password' => 'required'
        ];

        //set validation custom message
        $messages = [
            'username.required' => 'User ID is required'
        ];

        //make validation
        $validation = Validator::make($request->all(), $rules);

        //check validation
        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validation->errors()
            ], 422);
        }

        $credentials = $request->only('username', 'password');

        if ($authorized = Auth::guard()->attempt($credentials)) {
            $user = Auth::guard()->user();
            $token = $user->createToken($authorized)->accessToken;


            $user->mureed;

            return response()->json([
                'status' => true,
                'message' => 'Logged in successfully',
                'user' => $user,
                'role' => $user->role()->name,
                'token' => $token
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to login, Invalid credentials'
            ]);
        }
    }

    //user register
    public function update(Request $request)
    {
        if ($this->guard()->user()->id != $request->input('user_id')) {
            if (!$this->guard()->user()->hasRoles(['super_admin', 'admin'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied'
                ]);
            }
        }
        //set validation rules
        $rules = [
            'user_id' => 'required',
            'name' => 'required',
            'father_name' => 'required',
            'birthdate' => 'required',
            'gender' => 'required',
            'blood_group' => 'required',
            'nationality' => 'required',
            'mobile' => 'required',
            'home_address' => 'required',
            'division_id' => 'required',
            'district_id' => 'required',
        ];

        //set validation custom message
        $messages = [
            'father_name.required' => 'Father/Husband name required',
            'division_id.required' => 'Division is required',
            'district_id.required' => 'District is required',
            'password_confirmation.required' => 'Confirm password required',
        ];

        //make validation
        $validation = Validator::make($request->all(), $rules, $messages);

        //check validation
        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validation->errors()
            ], 422);
        }

        try {
                //udpate murids
                $murid = Mureed::where('user_id', $request->input('user_id'))->first();
                $murid->division_id = $request->input('division_id');
                $murid->district_id = $request->input('district_id');
                $murid->upazila_id = $request->input('upazila_id');
                $murid->name = $request->input('name');
                $murid->father_name = $request->input('father_name');
                $murid->head_of_family = $request->input('head_of_family');
                $murid->birthdate = $request->input('birthdate');
                $murid->gender = $request->input('gender');
                $murid->blood_group = $request->input('blood_group');
                $murid->place = $request->input('place');
                $murid->nid = $request->input('nid');
                $murid->nationality = $request->input('nationality');
                $murid->profession = $request->input('prefession');
                $murid->home_address = $request->input('home_address');
                $murid->telephone_home = $request->input('telephone_home');
                $murid->mobile = $request->input('mobile');
                $murid->office_address = $request->input('office_address');
                $murid->telephone_office = $request->input('telephone_office');
                $murid->fax = $request->input('fax');
                $murid->emergency_contact = $request->input('emergency_contact');
                $murid->emergency_telephone = $request->input('emergency_telephone');
                $murid->disciple_of = $request->input('disciple_of');
                $murid->email = $request->input('email');
                $murid->website = $request->input('website');
                $murid->remarks = $request->input('remarks');

                //photo upload
                if ($request->hasFile('photo')) {
                    //find old_photo
                    $old_photo = $murid->photo;
                    $photo_path = Utility::fileUpload($request, 'photo', 'mureeds');
                    $murid->photo = $photo_path;

                    if ($old_photo) {
                        unlink($old_photo);
                    }
                }
                //signature upload
                if ($request->hasFile('signature')) {
                    $old_sign = $murid->signature;
                    $sign_path = Utility::fileUpload($request, 'signature', 'mureeds');
                    $murid->signature = $sign_path;
                    if ($old_sign) {
                        unlink($old_sign);
                    }
                }

                $murid->updated_by = $this->guard()->user()->id;

                if ($murid->save()) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Updated successfully mureed information',
                        'murid' => $murid,
                    ]);
                }

            return response()->json([
                'status' => false,
                'message' => 'Failed to update',
            ]);

        }catch(\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occured while register. '.$e->getMessage() . $e->getLine(),
            ]);
        }
    }


    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        if ($this->guard()->user()) {
            return response()->json([
                'status' => true,
                'user' => $this->guard()->user(),
                'message' => 'Authorized'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

     /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $token = $this->guard()->user()->token();
        if ($token->revoke()) {
            return response()->json([
                'status' => true,
                'message' => 'You have been successfully logged out!'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to logout!'
        ]);

    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard('api');
    }
}
