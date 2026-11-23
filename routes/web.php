<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\TelemetryController;
use Illuminate\Support\Facades\Route;

// 1. Storefront Routes
Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products/{slug}', [StorefrontController::class, 'show'])->name('products.show');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('category.show');

// 2. Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 3. Cold-Start Onboarding Wizard
Route::middleware('auth')->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// 4. Shopping Cart Routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update/{cartKey}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{cartKey}', [CartController::class, 'remove'])->name('cart.remove');

// 5. Checkout & Order Lifecycle
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');

// 6. Telemetry Ingestion Endpoint
Route::post('/api/events/interaction', [TelemetryController::class, 'track'])->name('events.track');
