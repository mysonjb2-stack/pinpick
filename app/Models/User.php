<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'profile_image', 'provider', 'provider_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? str_replace('http://', 'https://', $value) : null,
            set: fn ($value) => $value ? str_replace('http://', 'https://', $value) : null,
        );
    }

    public function places()
    {
        return $this->hasMany(Place::class);
    }
}
