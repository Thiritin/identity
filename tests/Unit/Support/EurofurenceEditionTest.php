<?php

use App\Support\EurofurenceEdition;

it('maps EF 1 to 1995', function () {
    expect(EurofurenceEdition::efToYear(1))->toBe(1995);
});

it('maps EF 25 to 2019 (pre-COVID)', function () {
    expect(EurofurenceEdition::efToYear(25))->toBe(2019);
});

it('maps EF 26 to 2022 (post-COVID gap)', function () {
    expect(EurofurenceEdition::efToYear(26))->toBe(2022);
});

it('maps EF 29 to 2025', function () {
    expect(EurofurenceEdition::efToYear(29))->toBe(2025);
});

it('maps EF 30 to 2026 (auto-increment)', function () {
    expect(EurofurenceEdition::efToYear(30))->toBe(2026);
});

it('returns null for EF 0', function () {
    expect(EurofurenceEdition::efToYear(0))->toBeNull();
});

it('converts year to EF number', function () {
    expect(EurofurenceEdition::yearToEf(1995))->toBe(1);
    expect(EurofurenceEdition::yearToEf(2022))->toBe(26);
    expect(EurofurenceEdition::yearToEf(2026))->toBe(30);
});

it('returns null for years with no event', function () {
    expect(EurofurenceEdition::yearToEf(2020))->toBeNull();
    expect(EurofurenceEdition::yearToEf(2021))->toBeNull();
});

it('returns all editions as array of number/year pairs', function () {
    $editions = EurofurenceEdition::allEditions();
    expect($editions)->toBeArray();
    expect($editions[0])->toBe(['number' => 1, 'year' => 1995]);
    expect(count($editions))->toBeGreaterThanOrEqual(29);
});

it('calculates current EF number', function () {
    $current = EurofurenceEdition::currentEf();
    expect($current)->toBeGreaterThanOrEqual(29);
});

it('returns valid years for staff dropdown', function () {
    $years = EurofurenceEdition::validYears();
    expect($years)->toContain(1995);
    expect($years)->toContain(2019);
    expect($years)->toContain(2022);
    expect($years)->not->toContain(2020);
    expect($years)->not->toContain(2021);
});
