<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;

test('division allows only DivisionDirector', function () {
    expect(GroupTypeEnum::Division->allowedLevels())
        ->toBe([GroupUserLevel::DivisionDirector]);
});

test('department allows Director and Member', function () {
    expect(GroupTypeEnum::Department->allowedLevels())
        ->toBe([GroupUserLevel::Director, GroupUserLevel::Member]);
});

test('team allows TeamLead and Member', function () {
    expect(GroupTypeEnum::Team->allowedLevels())
        ->toBe([GroupUserLevel::TeamLead, GroupUserLevel::Member]);
});

test('default types return empty allowedLevels (unrestricted)', function () {
    expect(GroupTypeEnum::Default->allowedLevels())->toBe([]);
    expect(GroupTypeEnum::Automated->allowedLevels())->toBe([]);
    expect(GroupTypeEnum::Root->allowedLevels())->toBe([]);
});

test('division child type is department', function () {
    expect(GroupTypeEnum::Division->childGroupType())->toBe(GroupTypeEnum::Department);
});

test('department child type is team', function () {
    expect(GroupTypeEnum::Department->childGroupType())->toBe(GroupTypeEnum::Team);
});

test('team has no child type', function () {
    expect(GroupTypeEnum::Team->childGroupType())->toBeNull();
});

test('default types have no child type', function () {
    expect(GroupTypeEnum::Default->childGroupType())->toBeNull();
    expect(GroupTypeEnum::Automated->childGroupType())->toBeNull();
    expect(GroupTypeEnum::Root->childGroupType())->toBeNull();
});

test('topLeadLevel returns correct level per type', function () {
    expect(GroupTypeEnum::Division->topLeadLevel())->toBe(GroupUserLevel::DivisionDirector);
    expect(GroupTypeEnum::Department->topLeadLevel())->toBe(GroupUserLevel::Director);
    expect(GroupTypeEnum::Team->topLeadLevel())->toBe(GroupUserLevel::TeamLead);
});

test('default types have no topLeadLevel', function () {
    expect(GroupTypeEnum::Default->topLeadLevel())->toBeNull();
    expect(GroupTypeEnum::Automated->topLeadLevel())->toBeNull();
    expect(GroupTypeEnum::Root->topLeadLevel())->toBeNull();
});
