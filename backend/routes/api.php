<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TypeProjectController;
use App\Http\Controllers\ProfileImgController;
use App\Http\Controllers\BackgroundImgController;
use App\Http\Controllers\MusicTrackController;
use App\Http\Controllers\TypeSkillController;
use App\Http\Controllers\SkillsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// lectura pública
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{id}', [ProjectController::class, 'show']);

Route::get('/typeprojects', [TypeProjectController::class, 'index']);
Route::get('/backgroundimgs', [BackgroundImgController::class, 'index']);
Route::get('/profileimgs', [ProfileImgController::class, 'index']);
Route::get('/musictrack', [MusicTrackController::class, 'index']);
Route::get('/typeskill', [TypeSkillController::class, 'index']);
Route::get('/skills', [SkillsController::class, 'index']);

// escritura: basic auth con las credenciales del .env
Route::middleware('admin')->group(function () {
    Route::get('/admin/check', fn () => response()->json(['message' => 'ok']));

    Route::post('/projects', [ProjectController::class, 'store']);
    Route::delete('/projects/{id}', [ProjectController::class, 'delete']);

    Route::post('/typeprojects', [TypeProjectController::class, 'store']);
    Route::delete('/typeprojects/{id}', [TypeProjectController::class, 'delete']);

    Route::post('/backgroundimgs', [BackgroundImgController::class, 'store']);
    Route::delete('/backgroundimgs/{id}', [BackgroundImgController::class, 'delete']);

    Route::post('/profileimgs', [ProfileImgController::class, 'store']);
    Route::delete('/profileimgs/{id}', [ProfileImgController::class, 'delete']);

    Route::post('/musictrack', [MusicTrackController::class, 'store']);
    Route::delete('/musictrack/{id}', [MusicTrackController::class, 'delete']);

    Route::post('/typeskill', [TypeSkillController::class, 'store']);
    Route::delete('/typeskill/{id}', [TypeSkillController::class, 'delete']);

    Route::post('/skills', [SkillsController::class, 'store']);
    Route::delete('/skills/{id}', [SkillsController::class, 'delete']);
});
