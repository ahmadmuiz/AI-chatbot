<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Record an audit log entry.
     *
     * @param  string      $event       Short event identifier (e.g. 'chat.message', 'user.login')
     * @param  string      $description Human-readable description of what happened
     * @param  User|null   $subject     The user the action is about (null = current user)
     * @param  array       $metadata    Optional key-value data to store as JSON
     * @param  User|null   $actor       Who performed the action (null = current auth user)
     */
    public static function log(
        string $event,
        string $description,
        ?User  $subject  = null,
        array  $metadata = [],
        ?User  $actor    = null,
    ): void {
        try {
            $authUser = Auth::user();

            AuditLog::create([
                'user_id'     => $subject?->id ?? $authUser?->id,
                'actor_id'    => $actor?->id    ?? $authUser?->id,
                'event'       => $event,
                'description' => $description,
                'ip_address'  => Request::ip(),
                'user_agent'  => Request::userAgent(),
                'metadata'    => empty($metadata) ? null : $metadata,
            ]);
        } catch (\Throwable) {
            // Never let audit logging break the main flow
        }
    }
}
