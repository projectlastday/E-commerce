<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->get();

        $profile = $user->profile;

        return view('auth.onboarding', compact('categories', 'profile'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'preferred_categories' => 'required|array|min:1',
            'preferred_categories.*' => 'integer|exists:categories,id',
            'budget_tier' => 'required|string|in:BUDGET,MID,PREMIUM',
        ]);

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => $user->name,
                'phone_number' => $user->phone_number,
                'budget_tier' => $validated['budget_tier'],
                'preferred_category_ids' => array_map('intval', $validated['preferred_categories']),
                'onboarding_completed' => true,
            ]
        );

        return redirect()->route('home')->with('success', 'Preferensi rekomendasi Anda berhasil disimpan! Jelajahi produk pilihan khusus untuk Anda.');
    }
}
