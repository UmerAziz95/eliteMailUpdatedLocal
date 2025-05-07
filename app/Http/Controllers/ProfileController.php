<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Auth;

class ProfileController extends Controller
{
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $user = Auth::user();

        // Delete old image if exists
        if ($user->profile_image) {
            Storage::disk('public')->delete('profile_images/' . $user->profile_image);
        }

        // Store new image
        $imageName = time() . '.' . $request->profile_image->extension();
        $request->profile_image->storeAs('public/profile_images', $imageName);

        // Update user profile image
        $user->profile_image = $imageName;
        $user->save();

        return response()->json([
            'success' => true,
            'image_url' => asset('storage/profile_images/' . $imageName)
        ]);
    }
}