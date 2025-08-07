<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'device_token',
        'device_type',
        'device_name',
        'platform',
        'platform_version',
        'app_version',
        'is_active',
        'last_used_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Device types.
     */
    const TYPE_WEB = 'web';
    const TYPE_MOBILE = 'mobile';
    const TYPE_DESKTOP = 'desktop';

    /**
     * Platforms.
     */
    const PLATFORM_WEB = 'web';
    const PLATFORM_IOS = 'ios';
    const PLATFORM_ANDROID = 'android';
    const PLATFORM_WINDOWS = 'windows';
    const PLATFORM_MACOS = 'macos';
    const PLATFORM_LINUX = 'linux';

    /**
     * Get the user that owns the device.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active devices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for mobile devices.
     */
    public function scopeMobile($query)
    {
        return $query->where('device_type', self::TYPE_MOBILE);
    }

    /**
     * Scope for web devices.
     */
    public function scopeWeb($query)
    {
        return $query->where('device_type', self::TYPE_WEB);
    }

    /**
     * Update last used timestamp.
     */
    public function updateLastUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Deactivate the device.
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate the device.
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
    }
}
