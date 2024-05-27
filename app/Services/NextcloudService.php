<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class NextcloudService
{
    public static function createGroup($groupId)
    {
        Http::nextcloud()->post("/ocs/v1.php/cloud/groups", [
            "groupid" => $groupId,
        ])->throw();
        return $groupId;
    }

    public static function setDisplayName($groupId, $displayName)
    {
        Http::nextcloud()->put("https://cloud.eurofurence.org/ocs/v2.php/cloud/groups/{$groupId}", [
            'key' => 'displayname',
            'value' => $displayName,
        ])->throw();
    }

    public static function checkUserExists($userId): bool
    {
        $res = Http::nextcloud()->get("ocs/v2.php/cloud/users/".$userId)->throwIfServerError();
        if ($res->notFound()) {
            return false;
        }
        if ($res->ok()) {
            return true;
        }
        $res->throw();
    }

    public static function addUserToGroup(Group $group, User $user)
    {
        // Check user
        if (!self::checkUserExists($user->hashid)) {
            self::createUser($user); // Create user also adds groups so we don't need to add them here
        } else {
            Http::nextcloud()->post("ocs/v2.php/cloud/users/{$user->hashid}/groups", [
                "groupid" => $group->hashid,
            ])->throw();
        }
    }

    public static function removeUserFromGroup(Group $group, User $user)
    {
        if (!self::checkUserExists($user->hashid)) {
            return;
        }
        Http::nextcloud()->delete("ocs/v2.php/cloud/users/{$user->hashid}/groups?groupid={$group->hashid}")->throw();
    }

    public static function setManageAcl(Group $group, User $user, bool $allow): void
    {
        Http::nextcloud()->post("apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL", [
            'mappingId' => $user->hashid,
            'mappingType' => 'user',
            'manageAcl' => $allow ? '1' : '0',
        ])->throwIfServerError();
    }

    public static function createUser(User $user)
    {
        Http::nextcloud()->post("ocs/v2.php/cloud/users", [
            'displayName' => $user->name,
            'email' => $user->email,
            'groups' => $user->groups()->whereNotNull('nextcloud_folder_name')->get()->pluck('hashid')->toArray(),
            'language' => 'en',
            'password' => '',
            'quota' => 'default',
            'subadmin' => [],
            'userid' => $user->hashid,
        ])->throw();
    }

    public static function createFolder(string $folderName, string $groupId): int
    {
        $response = Http::nextcloud()->post("apps/groupfolders/folders", [
            "mountpoint" => $folderName,
        ])->throw();
        $xml = simplexml_load_string($response->body());

        // enable acl for group (we have that enabled for all groups)
        Http::nextcloud()->post("apps/groupfolders/folders/{$xml->data->id}/acl", [
            "acl" => 1,
        ])->throw();
        // add group to folder apps/groupfolders/folders/$folderId/groups/$groupId
        Http::nextcloud()->post("apps/groupfolders/folders/{$xml->data->id}/groups", [
            "group" => $groupId,
        ])->throw();
        return (int) $xml->data->id;
    }

    public static function renameFolder(int $folderId, string $folderName): void
    {
        Http::nextcloud()->post("apps/groupfolders/folders/{$folderId}/mountpoint", [
            "mountpoint" => $folderName,
        ])->throw();
    }

}
