<?php 
use App\Http\Controllers\Api\AuthenticationCenter\Document\TransactionController;
/** Archeivement SECTION */
Route::group([
  'prefix' => 'documents' ,
  'namespace' => 'Api' ,
  'middleware' => 'api'
  ], function() {

    Route::group([
    'prefix' => 'transactions' ,
    'namespace' => 'Api' ,
    'middleware' => 'api'
    ], function() {
      Route::get('',[TransactionController::class,'index']);
      Route::get('{id}/read',[TransactionController::class,'read']);
      Route::get('{id}/accept',[TransactionController::class,'accepted']);
      Route::post('',[TransactionController::class,'store']);
      Route::post('',[TransactionController::class,'update']);

      // Route::delete('',[TransactionController::class,'destroy']);
      Route::post('upload/word',[TransactionController::class,'uploadWord']);
      Route::post('upload/pdf',[TransactionController::class,'uploadPdf']);
      Route::get('download/{document_id}/pdf',[TransactionController::class,'downloadPdf']);
      Route::get('download/{document_id}/word',[TransactionController::class,'downloadWord']);
      Route::get('preview/{document_id}/pdf',[TransactionController::class,'previewPdf']);
  });
    
});

