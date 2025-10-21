<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGroupAccess;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAllUsers()
    {
        return User::with(['groups', 'projects'])->get();
    }

    public function getUserById($userId)
    {
        return User::with(['groups', 'projects'])->findOrFail($userId);
    }

    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function updateUser($userId, array $data)
    {
        $user = User::findOrFail($userId);
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        $user->update($data);
        return $user;
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        return $user->delete();
    }

    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['active' => !$user->active]);
        return $user;
    }

    public function getUserProjects($userId)
    {
        return User::findOrFail($userId)->projects;
    }

    public function getUserGroups($userId, $projectId = null)
    {
        $user = User::findOrFail($userId);
        
        if ($projectId) {
            return $user->getGroupsForProject($projectId);
        }
        
        return $user->groups;
    }
}