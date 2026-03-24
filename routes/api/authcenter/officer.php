<?php 
use App\Http\Controllers\Api\AuthenticationCenter\OfficerController;
use App\Http\Controllers\Api\AuthenticationCenter\Officer\OfficerJobController;

/** Officer SECTION */
Route::group([
  'prefix' => 'officers' ,
  'middleware' => 'auth:api'
  ], function() {
    /**
     * Methods to apply for each of the CRUD operations
     * Create => POST
     * Read => GET
     * Update => PUT
     * Delete => DELETE
     */

    /**
     * Get all records
     */
    Route::get('',[OfficerController::class,'index']);
    // Route::get('{id}/read',[OfficerController::class,'read']);
    Route::get('{id}/mybackground',[OfficerController::class,'mybackground']);
    /**
     * Create a record
     */
    Route::post('upload',[OfficerController::class,'upload']);
    Route::post('create',[OfficerController::class,'storeOfficer']);
    Route::post('createnonofficer',[OfficerController::class,'storeNonOfficer']);
    /**
     * Update a reccord with id
     */
    Route::put('update',[OfficerController::class,'update']);
    /**
     * Delete a record
     */
    Route::delete('{id}/delete',[OfficerController::class,'destroy']);

    /**
     * Activate, Deactivate account
     */
    Route::put('activate',[OfficerController::class,'activate']);
    Route::put('deactivate',[OfficerController::class,'deactivate']);

    /**
     * Officer Job 
     */
    Route::get('job', [OfficerJobController::class, 'index']);
    Route::post('job/add', [OfficerJobController::class, 'addOfficeJob']);
    Route::get('job/{id}/read', [OfficerJobController::class, 'read']);
    Route::put('job/update', [OfficerJobController::class, 'updateOfficerJob']);
    Route::delete('job/{id}/destroy', [OfficerJobController::class, 'destroyOfficerJob']);
    // Route::get('/users/{id}', [OfficerJobController::class, 'show']);

});
Route::group([
  'prefix' => 'officers' ,
  'middleware' => 'api'
  ], function(){
  /**
   * Get a record with public_key
   */
  Route::get('signatures',[OfficerController::class,'officersSignatures']);
  Route::get('{key}/read',[OfficerController::class,'readPublic']);
  Route::get('{key}/publicphoto',[OfficerController::class,'publicPhoto']);
});