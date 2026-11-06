<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatQuickRepliesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $all = config('matrimony.chat.quick_replies', [
            'male' => [
                'Hi! I liked your profile and would like to connect.',
                'Hello! Hope you are having a wonderful day.',
                'Hi, could we connect with each other\'s families?',
                'Hello, are you open to having a conversation?',
                'Hi! Please feel free to view my full profile.',
                'Namaste! Interested in taking this forward.',
            ],
            'female' => [
                'Hi! Thank you for showing interest in my profile.',
                'Hello! I saw your profile and would love to connect.',
                'Hi! Are your parents also managing your profile?',
                'Hello! Hope you are doing well today.',
                'Hi! Feel free to connect with my family as well.',
                'Namaste! Would love to know more about you.',
            ],
        ]);

        $gender = strtolower(trim((string) $request->query('gender', '')));

        if ($gender === 'male' || $gender === 'female') {
            return ApiResponse::success([
                'gender' => $gender,
                'templates' => $all[$gender] ?? [],
                'all' => $all,
            ], 'Quick reply templates retrieved successfully');
        }

        // If user is authenticated and has a gender, optionally hint their relevant templates
        $user = $request->user();
        $userGender = $user?->gender;

        return ApiResponse::success([
            'templates' => $all,
            'user_gender' => $userGender,
            'recommended' => $userGender && isset($all[$userGender]) ? $all[$userGender] : null,
        ], 'Quick reply templates retrieved successfully');
    }
}

