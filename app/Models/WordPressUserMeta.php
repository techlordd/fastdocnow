<?php

namespace App\Models;

use Corcel\Model\Meta\UserMeta as CorcelUserMeta;

class WordPressUserMeta extends CorcelUserMeta
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'wordpress';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usermeta';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'meta_key',
        'meta_value'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'umeta_id';

    /**
     * Get the user that owns the meta.
     */
    public function user()
    {
        return $this->belongsTo(WordPressUser::class, 'user_id', 'ID');
    }

    /**
     * Scope a query to only include meta for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include meta with a specific key.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $key
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithKey($query, $key)
    {
        return $query->where('meta_key', $key);
    }

    /**
     * Get meta value with automatic unserialization for WordPress data.
     *
     * @param  string  $value
     * @return mixed
     */
    public function getMetaValueAttribute($value)
    {
        // Auto-unserialize WordPress serialized data
        if (is_string($value) && (strpos($value, 'a:') === 0 || strpos($value, 'O:') === 0)) {
            $unserialized = @unserialize($value);
            return $unserialized !== false ? $unserialized : $value;
        }

        return $value;
    }

    /**
     * Set meta value with automatic serialization for complex data.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setMetaValueAttribute($value)
    {
        // Auto-serialize arrays and objects for WordPress compatibility
        if (is_array($value) || is_object($value)) {
            $this->attributes['meta_value'] = serialize($value);
        } else {
            $this->attributes['meta_value'] = $value;
        }
    }
}
