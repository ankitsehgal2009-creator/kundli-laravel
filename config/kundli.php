<?php

return [
    // The only admin account. Change it in .env with KUNDLI_ADMIN_EMAIL if needed.
    'admin_email' => strtolower(env('KUNDLI_ADMIN_EMAIL', 'ankitsehgal2009@gmail.com')),

    // Time zone used to show dates and times in Admin (report log). Change with KUNDLI_TIMEZONE.
    'timezone' => env('KUNDLI_TIMEZONE', 'Asia/Kolkata'),

    'questions' => [
        'In which city were you born?',
        "What is your mother's maiden name?",
        'What was the name of your first school?',
        'What was the name of your first pet?',
        'Who was your childhood best friend?',
        'What is your favourite food?',
        "What is your father's middle name?",
        'What was your first mobile phone brand?',
    ],
];
