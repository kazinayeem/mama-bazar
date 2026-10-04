<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * TeamMember Model
 *
 * Represents leadership and company team members displayed on the public Team page,
 * website footer, and managed via the admin panel.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $position
 * @property string|null $image
 * @property string|null $bio
 * @property int $display_order
 * @property bool $is_active
 * @property bool $is_public
 * @property bool $show_in_footer
 * @property bool $show_email_publicly
 * @property array|null $social_links
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TeamMember extends Model
{
    use HasFactory;

    protected $table = 'team_members';

    protected $fillable = [
        'name',
        'email',
        'position',
        'image',
        'bio',
        'display_order',
        'is_active',
        'is_public',
        'show_in_footer',
        'show_email_publicly',
        'social_links',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'show_in_footer' => 'boolean',
            'show_email_publicly' => 'boolean',
            'social_links' => 'array',
        ];
    }

    /**
     * Scope a query to only include active members.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include publicly visible members.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include members designated for the footer.
     */
    public function scopeInFooter(Builder $query): Builder
    {
        return $query->where('show_in_footer', true);
    }

    /**
     * Scope a query to order members by display order then creation time.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Get resolved image or initials avatar fallback.
     */
    public function getAvatarUrlAttribute(): string
    {
        if (! empty($this->image)) {
            $img = trim($this->image);
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, '/')) {
                return $img;
            }

            return '/storage/'.ltrim($img, '/');
        }

        // SVG avatar with initials
        $initials = urlencode($this->initials);

        return "https://ui-avatars.com/api/?name={$initials}&color=0F4D2C&background=E8F5E9&bold=true&size=256";
    }

    /**
     * Get initials of team member.
     */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= mb_substr($w, 0, 1);
        }

        return strtoupper($initials) ?: 'TM';
    }

    /**
     * CamelCase accessors for API parity.
     */
    public function getDisplayOrderAttribute(): int
    {
        return (int) ($this->attributes['display_order'] ?? 0);
    }

    public function getIsActiveAttribute(): bool
    {
        return (bool) ($this->attributes['is_active'] ?? false);
    }

    public function getIsPublicAttribute(): bool
    {
        return (bool) ($this->attributes['is_public'] ?? false);
    }

    public function getShowInFooterAttribute(): bool
    {
        return (bool) ($this->attributes['show_in_footer'] ?? false);
    }

    public function getShowEmailPubliclyAttribute(): bool
    {
        return (bool) ($this->attributes['show_email_publicly'] ?? false);
    }

    public function getSocialLinksAttribute(): array
    {
        $links = $this->attributes['social_links'] ?? null;
        if (is_string($links)) {
            $decoded = json_decode($links, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($links) ? $links : [];
    }
}
