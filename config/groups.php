<?php

return [
    /*
     * Groups Configuration
     * ------------------------------------------------
     * You may use this file to define system groups.
     * Defining these control auto groups like staffs and others
     */

    /*
     * Staff Group
     * - Auto Group (Adds all members of Departments to this Group)
     * - Can see all departments on groups index
     */
    'staff' => env('GROUP_STAFF_ID'),
    /**
     * Directors
     */
    'directors' => env('GROUP_DIRECTORS_ID'),
    /**
     * Attendees
     * TODO: - Adds all attendees that ever attended into this Group.
     */
    'attendees' => env('GROUP_ATTENDEES_ID'),
];
