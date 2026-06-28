<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;
use Illuminate\Support\Str;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'welcome',
                'subject' => 'Welcome to YADA CRM',
                'body' => "Hi {{client_name}},\n\nWelcome to YADA CRM! We're excited to have you on board.\n\nYour account is now active and you can start using our platform.\n\nBest regards,\nThe YADA Team",
                'category' => 'onboarding',
                'variables' => ['client_name'],
            ],
            [
                'name' => 'renewal_reminder',
                'subject' => 'Subscription Renewal Reminder',
                'body' => "Hi {{client_name}},\n\nYour subscription is expiring in {{days_left}} days.\n\nPlease renew to continue using our services.\n\nBest regards,\nThe YADA Team",
                'category' => 'renewal',
                'variables' => ['client_name', 'days_left'],
            ],
            [
                'name' => 'demo_followup',
                'subject' => 'Thank you for the demo',
                'body' => "Hi {{client_name}},\n\nThank you for attending the demo. We hope you found it useful.\n\nIf you have any questions, please don't hesitate to reach out.\n\nBest regards,\nThe YADA Team",
                'category' => 'sales',
                'variables' => ['client_name'],
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::create([
                'id' => Str::uuid(),
                'name' => $template['name'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'category' => $template['category'],
                'variables' => $template['variables'],
                'active' => true,
                'created_by' => 3, // Admin user ID
            ]);
        }
    }
}