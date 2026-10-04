<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure default site setting for footer team
        SiteSetting::updateOrCreate(
            ['key' => 'footer_team_enabled'],
            ['value' => '1']
        );
        SiteSetting::updateOrCreate(
            ['key' => 'footer_team_title'],
            ['value' => 'Leadership & Core Team']
        );

        if (TeamMember::count() > 0) {
            return;
        }

        $members = [
            [
                'name' => 'Mohammad Ali Nayeem',
                'email' => 'nayeem@mama-bazar.com',
                'position' => 'Founder & CEO',
                'image' => null,
                'bio' => 'Visionary founder dedicated to revolutionizing neighborhood grocery and household retail with fast technology and local sourcing across Bangladesh.',
                'display_order' => 1,
                'is_active' => true,
                'is_public' => true,
                'show_in_footer' => true,
                'show_email_publicly' => true,
                'social_links' => [
                    'linkedin' => 'https://linkedin.com',
                    'twitter' => 'https://twitter.com',
                ],
            ],
            [
                'name' => 'Tanvir Ahmed',
                'email' => 'tanvir.cto@mama-bazar.com',
                'position' => 'Co-Founder & CTO',
                'image' => null,
                'bio' => 'Leads platform engineering, system reliability, scalable e-commerce infrastructure, and technology innovations.',
                'display_order' => 2,
                'is_active' => true,
                'is_public' => true,
                'show_in_footer' => true,
                'show_email_publicly' => true,
                'social_links' => [
                    'linkedin' => 'https://linkedin.com',
                    'github' => 'https://github.com',
                ],
            ],
            [
                'name' => 'Farhana Rahman',
                'email' => 'farhana.coo@mama-bazar.com',
                'position' => 'Chief Operating Officer (COO)',
                'image' => null,
                'bio' => 'Oversees daily dispatch logistics, vendor supply partnerships, customer fulfillment operations, and quality inspection workflows.',
                'display_order' => 3,
                'is_active' => true,
                'is_public' => true,
                'show_in_footer' => true,
                'show_email_publicly' => false,
                'social_links' => [
                    'linkedin' => 'https://linkedin.com',
                ],
            ],
            [
                'name' => 'Zubair Hossain',
                'email' => 'zubair.md@mama-bazar.com',
                'position' => 'Managing Director',
                'image' => null,
                'bio' => 'Drives business growth, corporate strategic alliances, and expansion across metropolitan and regional retail centers.',
                'display_order' => 4,
                'is_active' => true,
                'is_public' => true,
                'show_in_footer' => true,
                'show_email_publicly' => false,
                'social_links' => [
                    'linkedin' => 'https://linkedin.com',
                ],
            ],
            [
                'name' => 'Nusrat Jahan',
                'email' => 'nusrat.pm@mama-bazar.com',
                'position' => 'Project Manager',
                'image' => null,
                'bio' => 'Coordinates cross-functional product roadmaps, agile team sprints, delivery milestones, and operational excellence.',
                'display_order' => 5,
                'is_active' => true,
                'is_public' => true,
                'show_in_footer' => false,
                'show_email_publicly' => true,
                'social_links' => [
                    'linkedin' => 'https://linkedin.com',
                ],
            ],
            [
                'name' => 'Arifur Rahman',
                'email' => 'arif.eng@mama-bazar.com',
                'position' => 'Lead Software Engineer',
                'image' => null,
                'bio' => 'Full-stack software architect specializing in Laravel, high-throughput database design, and real-time inventory systems.',
                'display_order' => 6,
                'is_active' => true,
                'is_public' => true,
                'show_in_footer' => false,
                'show_email_publicly' => true,
                'social_links' => [
                    'github' => 'https://github.com',
                    'linkedin' => 'https://linkedin.com',
                ],
            ],
            [
                'name' => 'Sabrina Akter',
                'email' => 'sabrina.ux@mama-bazar.com',
                'position' => 'UI/UX Designer',
                'image' => null,
                'bio' => 'Designs intuitive, delight-driven shopping interfaces, user journeys, design systems, and mobile-friendly shopping experiences.',
                'display_order' => 7,
                'is_active' => true,
                'is_public' => true,
                'show_in_footer' => false,
                'show_email_publicly' => false,
                'social_links' => [
                    'linkedin' => 'https://linkedin.com',
                ],
            ],
            [
                'name' => 'Imran Mahmud',
                'email' => 'imran.mkt@mama-bazar.com',
                'position' => 'Marketing Manager',
                'image' => null,
                'bio' => 'Champions brand storytelling, customer acquisition campaigns, digital growth strategies, and social media engagement.',
                'display_order' => 8,
                'is_active' => true,
                'is_public' => true,
                'show_in_footer' => false,
                'show_email_publicly' => true,
                'social_links' => [
                    'linkedin' => 'https://linkedin.com',
                    'twitter' => 'https://twitter.com',
                ],
            ],
        ];

        foreach ($members as $data) {
            TeamMember::create($data);
        }
    }
}
