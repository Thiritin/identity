<?php

use App\Enums\StaffProfileVisibility;

it('has the correct cases', function () {
    expect(StaffProfileVisibility::cases())->toHaveCount(4);
    expect(StaffProfileVisibility::AllStaff->value)->toBe('all_staff');
    expect(StaffProfileVisibility::MyDepartments->value)->toBe('my_departments');
    expect(StaffProfileVisibility::LeadsAndDirectors->value)->toBe('leads_and_directors');
    expect(StaffProfileVisibility::DirectorsOnly->value)->toBe('directors_only');
});

it('can be created from string value', function () {
    expect(StaffProfileVisibility::from('all_staff'))->toBe(StaffProfileVisibility::AllStaff);
    expect(StaffProfileVisibility::from('directors_only'))->toBe(StaffProfileVisibility::DirectorsOnly);
});

it('returns null for invalid value via tryFrom', function () {
    expect(StaffProfileVisibility::tryFrom('invalid'))->toBeNull();
});
