<?php

namespace App\Enums;

enum StaffProfileVisibility: string
{
    case AllStaff = 'all_staff';
    case MyDepartments = 'my_departments';
    case LeadsAndDirectors = 'leads_and_directors';
    case DirectorsOnly = 'directors_only';
}
