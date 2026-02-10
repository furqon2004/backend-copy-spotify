<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    public function show()
    {
        return response()->json(auth()->user());
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->only(['full_name', 'date_of_birth', 'gender']);

        // Handle profile image upload via Cloudinary
        if ($request->hasFile('profile_image')) {
            $request->validate([
                'profile_image' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            // Delete old image if exists
            if ($user->profile_image_url) {
                $publicId = $this->cloudinary->getPublicIdFromUrl($user->profile_image_url);
                if ($publicId) {
                    $this->cloudinary->delete($publicId);
                }
            }

            $data['profile_image_url'] = $this->cloudinary->uploadImage(
                $request->file('profile_image'),
                'avatars/users'
            );
        }

        $user->update($data);

        return response()->json($user);
    }
}
