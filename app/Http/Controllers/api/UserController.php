<?php

namespace App\Http\Controllers\api;

use DB;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($flag)
    {
        $query=User::select('name','email','status');
        //Flag-0 All user are required to display
        //Flag-1 only the user whose status is 1
        if($flag==1){
            $query->where('status',1);
        }else if(flag!=0){
            return response()->json([
                'message' => 'Invalid parameter.Status is either 0 or 1'
            ],400);
        }
        $user=$query->get();
        // p($user);
        if(count($user)>0){
            $response= [
                'message' => count($user).' user found',
                'status' => 1,
                'data' => $user,
            ];
        }else{
            $response= [
                'message' => count($user).' user found',
                'status' => 0,
            ];
        }
        return response()->json($response,200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       $validated=Validator::make($request->all(),[
            'name' => ['required'],
            'email' => ['required','email','unique:users,email'],
            'password' => ['required','min:8','confirmed'],
            'password_confirmation' => ['required'],  
        ]);
        if($validated->fails()){
            return response()->json($validated->messages(),400);
        }
        else{
            $data=[
                'name'=> $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ];
            
            DB::beginTransaction();
            try{
                $user=User::create($data);
                DB::commit();
            }catch(\Exception $e)
            {
                DB::rollBack();
                p($e->getMessage());
                $user= null;
            }
            if($user != null){
                return response()->json([
                    'message' => 'user has been created successfully!'
                ],200);
            }else{
                return response()->json(
                ['message' => 'Internal serve error'],500
                );
            }
        }
        // p($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user=User::find($id);
        // dd($user);
        if(is_null($user)){
            $response= [
                'message' => 'User has not been found',
                'status' => 0,
            ];
        }
        else{
            $response= [
                'message' => ' User has been  found',
                'status' => 1,
                'data' => $user,
            ];
        }
        return response()->json($response,200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }
    public function changePassword(Request $request,$id)
    {
        $user=User::find($id);
        if(is_null($user)){
            return response()->json([
                'message' => 'user does not exists',
                'status' => '0'
            ],404);
        }else{

            if($user->password == $request->password){
                if($request->newpassword == $request->confirm_password){
                    DB::beginTransaction();
                    try{
                        $validated=Validator::make($request->all(),[
                            'newpassword' => ['required','min:8'],
                        ]);
                        if($validated->fails()){
                            return response()->json($validated->messages(),400);
                        }
                        else{
                            $user->password=$request->newpassword;
                            $user->save();
                            DB::commit();
                        }
                       
                    }catch(\Exception $err){
                        $user=null;
                        DB::rollBack();
                    }
                    if(is_null($user)){
                        return response()->json([
                            'message' => 'Internal serve error',
                            'status' => '0',
                            'error_msg' => $err->getMessage()
                            
                        ],500);
                    }
                    else{
                        return response()->json([
                            'message' => 'Password has been update successfully',
                            'status' => '1'
                        ],200);
                    }
                }
                else{
                    return response()->json([
                        'message' => 'New Password and confirm password does not match',
                        'status' => '0'
                    ],400);
                }
            }
            else{
                return response()->json([
                    'message' => 'Password does not match',
                    'status' => '0'
                ],400);
            }
            return response()->json([
                'message' => 'user has been update successfully',
                'status' => '1'
            ],200);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user=User::find($id);
        if(is_null($user)){
            return response()->json([
                'message' => 'user does not exists',
                'status' => '0'
            ],404);
        }else{
            DB::beginTransaction();
            try{
                $user->name=$request->name;
                $user->email=$request->email;
                $user->pincode=$request->pincode;
                $user->address=$request->address;
                $user->contact=$request->contact;
                $user->save();
                DB::commit();
            }catch(\Exception $err){
                DB::rollBack();
                $user=null;
            }
            if(is_null($user)){
                return response()->json([
                    'message' => 'Internal serve error',
                    'status' => '0',
                    'error_msg' => $err->getMessage()
                    
                ],500);
            }
            else{
                return response()->json([
                    'message' => 'user has been update successfully',
                    'status' => '1'
                ],200);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user=User::find($id);
        if(is_null($user)){
            $response= [
                'message' => 'User does not exists',
                'status' => 0,
            ];
            $respCode=404;
        }
        else{
            DB::beginTransaction();
            
            try{
                $user->delete();
                DB::commit();
                $response=[
                    'message' =>'User deleted successfully',
                    'status' => 1
                 ];
                 $respCode=200;
            }catch(\Exception $err){
                DB::rollBack();
                $response=[
                    'message' =>'Internal serve error',
                    'status' => 0
                 ];
                 $respCode=500;
            }
        }
        return response()->json($response,$respCode);
    }
}
