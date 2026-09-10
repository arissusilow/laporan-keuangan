<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'is_super_admin', 'active', 'must_change_password'];

    protected $hidden = ['password', 'remember_token'];

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(ReportSession::class, 'report_session_members')
            ->withPivot(['role', 'can_add_in', 'can_add_out', 'can_edit_own', 'can_edit_all', 'can_cancel', 'can_export_pdf']);
    }

    public function reportMemberships(): HasMany
    {
        return $this->hasMany(ReportSessionMember::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }
}
