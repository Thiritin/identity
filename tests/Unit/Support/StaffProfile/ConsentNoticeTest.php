<?php

use App\Support\StaffProfile\ConsentNotice;

test('CURRENT_VERSION is a positive integer', function () {
    expect(ConsentNotice::CURRENT_VERSION)->toBeInt()->toBeGreaterThan(0);
});

test('GATED_USER_COLUMNS lists exactly the 15 expected column names in documented order', function () {
    expect(ConsentNotice::GATED_USER_COLUMNS)->toBe([
        'firstname',
        'lastname',
        'pronouns',
        'birthdate',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_telegram',
        'spoken_languages',
        'credit_as',
    ]);
});
