<?php

namespace App\Support\StaffProfile;

final class ConsentNotice
{
    public const CURRENT_VERSION = 1;

    /**
     * Columns on `users` gated by staff profile consent.
     * Order reflects display order on the profile page.
     *
     * Pivot-table data (group_user.credit_as, convention_attendee rows) is
     * gated too, but is cleared directly by the withdrawal controller — it
     * is not a column list and so is not represented here.
     *
     * If a future migration adds or drops a staff-profile column on users,
     * this list must be updated in the same commit, along with the
     * ShowProfileController read path, the notice categories, and the unit
     * test that pins the list.
     */
    public const GATED_USER_COLUMNS = [
        // identity & contact
        'firstname',
        'lastname',
        'pronouns',
        'birthdate',
        'phone',
        // postal address
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'country',
        // emergency contact
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_telegram',
        // skills
        'spoken_languages',
        // credits
        'credit_as',
    ];
}
