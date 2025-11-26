<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Scalpels Webhook Routes
|--------------------------------------------------------------------------
|
| This file is where you register webhook routes for Scalpels tool modules.
| All routes should use the 'web' middleware and VerifyWebhookSignature
| middleware to ensure webhook requests are authentic.
|
| Example webhook route registration:
|
| Route::middleware(['web', \ArtisanBuild\Scalpels\Middleware\VerifyWebhookSignature::class])
|     ->prefix('scalpels/webhooks')
|     ->group(function () {
|         Route::post('/environmental/secrets-updated', function (Request $request) {
|             // Handle webhook payload
|             $payload = $request->json()->all();
|
|             // Process the event
|             event(new SecretsUpdated($payload));
|
|             return response()->json(['received' => true]);
|         });
|     });
|
*/

// Example webhook routes (commented out by default)
// Uncomment and customize these routes when implementing webhook handlers

/*
Route::middleware(['web', \ArtisanBuild\Scalpels\Middleware\VerifyWebhookSignature::class])
    ->prefix('scalpels/webhooks')
    ->name('scalpels.webhooks.')
    ->group(function () {
        // Environmental webhook example
        // Route::post('/environmental/secrets-updated', function (Request $request) {
        //     $payload = $request->json()->all();
        //     event(new SecretsUpdated($payload));
        //     return response()->json(['received' => true]);
        // })->name('environmental.secrets-updated');

        // ALF webhook example
        // Route::post('/alf/file-uploaded', function (Request $request) {
        //     $payload = $request->json()->all();
        //     event(new FileUploaded($payload));
        //     return response()->json(['received' => true]);
        // })->name('alf.file-uploaded');

        // Glimpse webhook example
        // Route::post('/glimpse/preview-ready', function (Request $request) {
        //     $payload = $request->json()->all();
        //     event(new PreviewReady($payload));
        //     return response()->json(['received' => true]);
        // })->name('glimpse.preview-ready');
    });
*/
