<?php 
use App\Http\Controllers\Api\AuthenticationCenter\Officer\RankController;
/** ORGANIZATION SECTION */
Route::group([
  'prefix' => 'ranks' ,
  'namespace' => 'Api' ,
  'middleware' => 'auth:api'
  ], function() {
      Route::get('',[RankController::class,'index']);
      Route::get('structure',[RankController::class,'structure']);      
});
