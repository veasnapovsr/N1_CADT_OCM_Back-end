<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesAvatarUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use App\Models\User;

class ProfileController extends Controller
{
    use HandlesAvatarUploads;

    public function getAuthUser (Request $request)
    {
        $user = Auth::user();
        \App\Models\Log\Log::access([
            'system' => 'client' ,
            'user_id' => $user->id ,
            'class' => self::class ,
            'func' => __FUNCTION__ ,
            'desp' => 'read profile information'
        ]); 
        // if( Storage::disk('public')->exists( $user->avatar_url ) )  $user->avatar_url = Storage::url( $user->avatar_url  );
        if( Storage::disk('public')->exists( $user->avatar_url ) )  $user->avatar_url = Storage::disk("public")->url( $user->avatar_url  );
        return response( [
            'user' => $user != null ? $user : null ,
            'message' => 'អានព័ត៌មានអ្នកប្រើប្រាស់ បានជោគជ័យ !'
        ],200 );
    }

    public function updateAuthUser (Request $request)
    {
        $user = User::find(Auth::id());
        $user->update([
            'firstname' => $request->firstname ,
            'lastname' => $request->lastname ,
            'role' => $request->role ? 1 : 0 ,
            'username' => $request->username ,
            'updated_at' => $request->updated_at ,
        ]);
        $user->save();
        \App\Models\Log\Log::access([
            'system' => 'client' ,
            'user_id' => $user->id ,
            'class' => self::class ,
            'func' => __FUNCTION__ ,
            'desp' => 'update profile information'
        ]); 
        return response([
            'user' => $user ,
            'message' => 'រក្សាទុកព័ត៌មានបានជោគជ័យ !'
        ],200);
    }

    public function updateAuthUserPassword(Request $request)
    {
        // $this->validate($request, [
        //     'current' => 'required',
        //     'password' => 'required|confirmed',
        //     'password_confirmation' => 'required'
        // ]);

        $user = User::find(Auth::id());

        if (!Hash::check($request->current, $user->password)) {
            \App\Models\Log\Log::access([
                'system' => 'client' ,
                'user_id' => $user->id ,
                'class' => self::class ,
                'func' => __FUNCTION__ ,
                'desp' => 'update password - current password and new password does not match'
            ]); 
            return response([
                'message' => 'ពាក្យសម្ងាត់បច្ចុប្បន្ន មិនត្រឹមត្រូវឡើយ !'
            ],201);
        }

        $user->password = Hash::make($request->password);
        $user->save();
        \App\Models\Log\Log::access([
            'system' => 'client' ,
            'user_id' => $user->id ,
            'class' => self::class ,
            'func' => __FUNCTION__ ,
            'desp' => 'update password successfully'
        ]); 
        return response([
            'user' => $user ,
            'message' => 'ផ្លាស់ប្ដូរពាក្យសម្ងាត់ថ្មីបានជោគជ័យ !'
        ],200);
    }
    public function upload(Request $request){
        $user = Auth::user();
        if( $user ){

            $uniqeName = $this->storeAvatarUpload($request, $user->id);
            if( $uniqeName == null ){
                return response([
                    'result' => $request->allFiles() ,
                    'message' => 'មានបញ្ហាជាមួយរូបភាពដែលអ្នកបញ្ជូនមក។'
                ],403);
            }
            $user->avatar_url = $uniqeName ;
            $user->save();

            \App\Models\Log\Log::access([
                'system' => 'client' ,
                'user_id' => $user->id ,
                'class' => self::class ,
                'func' => __FUNCTION__ ,
                'desp' => 'update profile picture successfully'
            ]); 
            if( Storage::disk('public')->exists( $user->avatar_url ) ){
                // $user->avatar_url = Storage::url( $user->avatar_url  );
                $user->avatar_url = Storage::disk("public")->url( $user->avatar_url  );
                return response([
                    'user' => $user ,
                    'message' => 'ជោគជ័យក្នុងការដាក់រូបភាពរបស់អ្នកប្រើប្រាស !'
                ],200);
            }
        }else{
            \App\Models\Log\Log::access([
                'system' => 'client' ,
                'user_id' => $user->id ,
                'class' => self::class ,
                'func' => __FUNCTION__ ,
                'desp' => 'failed to update profile picture'
            ]); 
            return response([
                'message' => 'បរាជ័យក្នុងការដាក់រូបភាពរបស់អ្នកប្រើប្រាស !'
            ],201);
        }
    }
}
