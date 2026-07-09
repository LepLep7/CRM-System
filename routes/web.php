<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\DropdownOptionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('pipelines', PipelineController::class);
    Route::patch('pipelines/{pipeline}/autosave', [PipelineController::class, 'autosaveField'])->name('pipelines.autosave');
    Route::post('pipelines/{pipeline}/upload-attachment', [PipelineController::class, 'uploadAttachment'])->name('pipelines.upload-attachment');
    Route::post('pipelines/{pipeline}/advance', [PipelineController::class, 'advanceStage'])->name('pipelines.advance');
    Route::get('dropdown-options', [DropdownOptionController::class, 'index'])->name('dropdown-options.index');
    Route::post('dropdown-options', [DropdownOptionController::class, 'store'])->name('dropdown-options.store');
    Route::put('dropdown-options/{dropdownOption}', [DropdownOptionController::class, 'update'])->name('dropdown-options.update');
    Route::delete('dropdown-options/{dropdownOption}', [DropdownOptionController::class, 'destroy'])->name('dropdown-options.destroy');
});

require __DIR__.'/auth.php';
