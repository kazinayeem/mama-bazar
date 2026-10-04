<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = [
            'Founder & CEO',
            'Co-Founder & CTO',
            'Chief Operating Officer (COO)',
            'Managing Director',
            'Project Manager',
            'Lead Software Engineer',
            'UI/UX Designer',
            'Marketing Director',
            'Head of Supply Chain',
            'Customer Relations Manager',
        ];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'position' => fake()->randomElement($positions),
            'image' => null,
            'bio' => fake()->sentence(12),
            'display_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
            'is_public' => true,
            'show_in_footer' => fake()->boolean(40),
            'show_email_publicly' => fake()->boolean(60),
            'social_links' => [
                'linkedin' => 'https://linkedin.com/in/'.fake()->userName(),
                'twitter' => 'https://twitter.com/'.fake()->userName(),
                'github' => 'https://github.com/'.fake()->userName(),
            ],
        ];
    }

    /**
     * Indicate that the team member is in the footer.
     */
    public function inFooter(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_in_footer' => true,
            'is_active' => true,
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the team member is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the team member is private/not public.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
