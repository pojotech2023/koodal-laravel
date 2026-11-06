<?php

return [

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-south-1'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Razorpay
    |---------------------------------------------------------------------------
    | key_id is public and reaches the client. key_secret and webhook_secret
    | never leave the server — they are what makes a signature meaningful.
    */

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'currency' => env('RAZORPAY_CURRENCY', 'INR'),
        'company' => [
            'name' => env('COMPANY_NAME', 'Matrimony'),
            'gstin' => env('COMPANY_GSTIN'),
            'address' => env('COMPANY_ADDRESS'),
            'state_code' => env('COMPANY_STATE_CODE', '33'),
        ],
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'database_url' => env('FIREBASE_DATABASE_URL'),

        // 'log' writes the payload to the log instead of calling Google, so
        // local development needs no service account.
        'driver' => env('FCM_DRIVER', 'fcm'),
    ],

    /*
    |---------------------------------------------------------------------------
    | SMS
    |---------------------------------------------------------------------------
    | India requires DLT-registered templates; `template_id` is the registered
    | template, and the body must match it exactly or the message is dropped by
    | the operator without an error.
    */

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'endpoint' => env('SMS_ENDPOINT'),
        'api_key' => env('SMS_API_KEY'),
        'sender_id' => env('SMS_SENDER_ID', 'MATRMY'),
        'template_id' => env('SMS_TEMPLATE_ID'),
    ],

    'quickekyc' => [
        'key' => env('QUICKEKYC_API_KEY', '52edbbf2-a1b2-46aa-aae8-c05d9ba5eaab'),
        'generate_otp_url' => env('QUICKEKYC_GENERATE_OTP_URL', 'https://quickekyc.com/api/v1/aadhaar-v2/generate-otp'),
        'submit_otp_url' => env('QUICKEKYC_SUBMIT_OTP_URL', 'https://quickekyc.com/api/v1/aadhaar-v2/submit-otp'),
        // Accept the test Aadhaar numbers and OTPs (123456 / 603810), on the
        // website and in the API, when the provider refuses them. Anyone can
        // then verify any number, so only a test server may turn this on.
        'test_mode' => (bool) env('QUICKEKYC_TEST_MODE', false),
    ],

];

