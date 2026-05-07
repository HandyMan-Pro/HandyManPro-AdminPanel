<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Constant;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add templates for subscription cancellation, expiration, forgot password, and password reset success.
     */
    public function up(): void
    {
        // 1. Constants for notification types
        Constant::updateOrCreate(
            ['type' => 'notification_type', 'value' => 'subscription_cancelled'],
            ['name' => 'Subscription Cancelled']
        );

        Constant::updateOrCreate(
            ['type' => 'notification_type', 'value' => 'subscription_expired'],
            ['name' => 'Subscription Expired']
        );

        Constant::updateOrCreate(
            ['type' => 'notification_type', 'value' => 'forgot_password'],
            ['name' => 'Forgot Password']
        );

        Constant::updateOrCreate(
            ['type' => 'notification_type', 'value' => 'password_reset'],
            ['name' => 'Password Reset Success']
        );

        // Notification param buttons (copied "as-is" from NotificationTemplateSeeder.php)
        Constant::updateOrCreate(
            ['type' => 'notification_param_button', 'value' => 'shop_name'],
            ['name' => 'Shop Name']
        );
        Constant::updateOrCreate(
            ['type' => 'notification_param_button', 'value' => 'shop_address'],
            ['name' => 'Shop Address']
        );
        Constant::updateOrCreate(
            ['type' => 'notification_param_button', 'value' => 'shop_city'],
            ['name' => 'Shop City']
        );
        Constant::updateOrCreate(
            ['type' => 'notification_param_button', 'value' => 'shop_registration'],
            ['name' => 'Shop Registration']
        );
        // Duplicate entry is intentional (kept as-is from seeder selection)
        Constant::updateOrCreate(
            ['type' => 'notification_param_button', 'value' => 'shop_name'],
            ['name' => 'Shop Name']
        );
        // Keep trailing space in value ("plan_duration ")
        Constant::updateOrCreate(
            ['type' => 'notification_param_button', 'value' => 'plan_duration '],
            ['name' => 'Plan Duration']
        );
        Constant::updateOrCreate(
            ['type' => 'notification_param_button', 'value' => 'plan_type'],
            ['name' => 'Plan Type']
        );
        Constant::updateOrCreate(
            ['type' => 'notification_param_button', 'value' => 'plan_description'],
            ['name' => 'Plan Description']
        );
       

        // 2. Notification Templates
        $templates = [
            'subscription_cancelled' => [
                'label' => 'Subscription Cancelled',
                'description' => 'Notification sent when a provider cancels their subscription plan',
                'to' => json_encode(['admin', 'provider']),
            ],
            'subscription_expired' => [
                'label' => 'Subscription Expired',
                'description' => 'Notification sent when a subscription plan expires',
                'to' => json_encode(['admin', 'provider']),
            ],
            'forgot_password' => [
                'label' => 'Forgot Password',
                'description' => 'Email sent when a user requests a password reset link',
                'to' => json_encode(['admin', 'provider', 'handyman', 'user']),
            ],
            'password_reset' => [
                'label' => 'Password Reset Successful',
                'description' => 'Email sent after the user successfully resets their password',
                'to' => json_encode(['admin', 'provider', 'handyman', 'user']),
            ],
        ];

        foreach ($templates as $type => $config) {
            if (!DB::table('notification_templates')->where('name', $type)->exists()) {
                DB::table('notification_templates')->insert([
                    'name'        => $type,
                    'label'       => $config['label'],
                    'description' => $config['description'],
                    'type'        => $type,
                    'to'          => $config['to'],
                    'bcc'         => null,
                    'cc'          => null,
                    'status'      => 1,
                    'channels'    => json_encode(['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '0']),
                    'created_by'  => null,
                    'updated_by'  => null,
                    'deleted_by'  => null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        $userTypes = ['admin', 'provider', 'handyman', 'user'];
        $forgotMappings = [];
        foreach ($userTypes as $ut) {
            $forgotMappings[] = [
                'user_type' => $ut,
                'subject' => 'Forgot Password',
                'template_detail' => '<p>Password reset requested for [[ user_email ]].</p>',
                'mail_subject' => 'Forgot Password',
                'mail_template_detail' => '<p>Hello [[ user_name ]],</p>'
                    . '<p>You are receiving this email because we received a password reset request for your account.</p>'
                    . '<p><a href="[[ reset_link ]]" style="display:inline-block;padding:12px 24px;background:#5F60B9;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Reset Password</a></p>'
                    . '<p>This password reset link will expire in 60 minutes.</p>'
                    . '<p>If you did not request a password reset, no further action is required.</p>'
                    . '<p>If you’re having trouble clicking the button, copy and paste the URL below into your web browser:<br><a href="[[ reset_link ]]">[[ reset_link ]]</a></p>'
                    . '<p>Regards,<br>[[ company_name ]]</p>',
            ];
        }

        $successMappings = [];
        foreach ($userTypes as $ut) {
            $successMappings[] = [
                'user_type' => $ut,
                'subject' => 'Password Reset Successful',
                'template_detail' => '<p>Your password was updated for [[ user_email ]].</p>',
                'mail_subject' => 'Password Reset Successful',
                'mail_template_detail' => '<p>Hello [[ user_name ]],</p>'
                    . '<p>Your password has been reset successfully.</p>'
                    . '<p>If you did not perform this action, please contact our support team immediately.</p>'
                    . '<p>Regards,<br>[[ company_name ]]</p>',
            ];
        }

        // 3. Notification Template Content Mappings
        $notificationMappings = [
            'subscription_cancelled' => [
                [
                    'user_type' => 'admin',
                    'subject' => 'Subscription Cancelled',
                    'template_detail' => '<p>[[ provider_name ]] has cancelled their subscription plan [[ plan_name ]].</p>',
                    'mail_subject' => 'Subscription Cancelled',
                    'mail_template_detail' => '<p>Hello [[ admin_name ]],</p><p>Provider <strong>[[ provider_name ]]</strong> has cancelled their subscription plan <strong>[[ plan_name ]]</strong>.</p><p>Cancellation Date: <strong>[[ cancellation_date ]]</strong></p>',
                ],
                [
                    'user_type' => 'provider',
                    'subject' => 'Subscription Cancelled Successfully',
                    'template_detail' => '<p>Your subscription plan [[ plan_name ]] has been cancelled successfully.</p>',
                    'mail_subject' => 'Subscription Cancelled Successfully',
                    'mail_template_detail' => '<p>Hello <strong>[[ provider_name ]]</strong>,</p><p>Your subscription plan <strong>[[ plan_name ]]</strong> has been cancelled successfully.</p><p>Cancellation Date: <strong>[[ cancellation_date ]]</strong></p><p>You can reactivate your subscription anytime by purchasing a new plan.</p><p>If you have any questions, feel free to contact our support team.</p><p>Best regards,<br>The [[ company_name ]] Team</p>',
                ],
            ],
            'subscription_expired' => [
                [
                    'user_type' => 'admin',
                    'subject' => 'Subscription Expired',
                    'template_detail' => '<p>[[ provider_name ]]\'s subscription plan [[ plan_name ]] has expired.</p>',
                    'mail_subject' => 'Subscription Expired',
                    'mail_template_detail' => '<p>Hello [[ admin_name ]],</p><p>Provider <strong>[[ provider_name ]]</strong>\'s subscription plan <strong>[[ plan_name ]]</strong> has expired.</p><p>Expiry Date: <strong>[[ expiry_date ]]</strong></p>',
                ],
                [
                    'user_type' => 'provider',
                    'subject' => 'Your Subscription Has Expired',
                    'template_detail' => '<p>Your subscription plan [[ plan_name ]] has expired.</p>',
                    'mail_subject' => 'Your Subscription Has Expired',
                    'mail_template_detail' => '<p>Hello <strong>[[ provider_name ]]</strong>,</p><p>Your subscription plan <strong>[[ plan_name ]]</strong> has expired on <strong>[[ expiry_date ]]</strong>.</p><p>To continue enjoying our platform services, please renew your subscription or purchase a new plan.</p><p>If you have any questions or need assistance, feel free to contact our support team.</p><p>Best regards,<br>The [[ company_name ]] Team</p>',
                ],
            ],
            'forgot_password' => $forgotMappings,
            'password_reset' => $successMappings,
        ];

        foreach ($notificationMappings as $type => $mappings) {
            $template = DB::table('notification_templates')->where('name', $type)->first();
            if (!$template) {
                continue;
            }

            foreach ($mappings as $mapping) {
                $exists = DB::table('notification_template_content_mapping')
                    ->where('template_id', $template->id)
                    ->where('language', 'en')
                    ->where('user_type', $mapping['user_type'])
                    ->exists();

                if (!$exists) {
                    DB::table('notification_template_content_mapping')->insert([
                        'template_id'          => $template->id,
                        'language'             => 'en',
                        'user_type'            => $mapping['user_type'],
                        'subject'              => $mapping['subject'],
                        'template_detail'      => $mapping['template_detail'],
                        'mail_subject'         => $mapping['mail_subject'],
                        'mail_template_detail' => $mapping['mail_template_detail'],
                        'status'               => 1,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }
        }

        // 4. Mail Templates
        foreach ($templates as $type => $config) {
            if (!DB::table('mail_templates')->where('type', $type)->exists()) {
                DB::table('mail_templates')->insert([
                    'type'       => $type,
                    'name'       => $type,
                    'label'      => $config['label'],
                    'status'     => 1,
                    'to'         => $config['to'],
                    'channels'   => json_encode(['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '0']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 5. Mail Template Content Mappings
        foreach ($notificationMappings as $type => $mappings) {
            $mailTemplate = DB::table('mail_templates')->where('type', $type)->first();
            if (!$mailTemplate) {
                continue;
            }

            foreach ($mappings as $mapping) {
                $exists = DB::table('mail_template_content_mappings')
                    ->where('template_id', $mailTemplate->id)
                    ->where('language', 'en')
                    ->where('user_type', $mapping['user_type'])
                    ->exists();

                if (!$exists) {
                    DB::table('mail_template_content_mappings')->insert([
                        'template_id'          => $mailTemplate->id,
                        'language'             => 'en',
                        'notification_link'    => '',
                        'notification_message' => '',
                        'user_type'            => $mapping['user_type'],
                        'status'               => 1,
                        'subject'              => $mapping['mail_subject'],
                        'template_detail'      => $mapping['mail_template_detail'],
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }
        }

        // Align forgot-password CTA label for rows inserted before this text was updated
        $forgotMailIds = DB::table('mail_templates')->where('type', 'forgot_password')->pluck('id');
        foreach ($forgotMailIds as $mailTid) {
            DB::table('mail_template_content_mappings')
                ->where('template_id', $mailTid)
                ->update([
                    'template_detail' => DB::raw("REPLACE(template_detail, 'Forgot Password</a>', 'Reset Password</a>')"),
                    'updated_at' => now(),
                ]);
        }

        $forgotNotifIds = DB::table('notification_templates')->where('name', 'forgot_password')->pluck('id');
        foreach ($forgotNotifIds as $notifTid) {
            DB::table('notification_template_content_mapping')
                ->where('template_id', $notifTid)
                ->update([
                    'mail_template_detail' => DB::raw("REPLACE(mail_template_detail, 'Forgot Password</a>', 'Reset Password</a>')"),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert provider_subscriptions foreign key to cascading behavior.
        Schema::table('provider_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });

        // Remove mail template content mappings
        foreach (['subscription_cancelled', 'subscription_expired', 'forgot_password', 'password_reset'] as $type) {
            $mailTemplate = DB::table('mail_templates')->where('type', $type)->first();
            if ($mailTemplate) {
                DB::table('mail_template_content_mappings')->where('template_id', $mailTemplate->id)->delete();
                DB::table('mail_templates')->where('id', $mailTemplate->id)->delete();
            }
        }

        // Remove notification template content mappings
        foreach (['subscription_cancelled', 'subscription_expired', 'forgot_password', 'password_reset'] as $type) {
            $notificationTemplate = DB::table('notification_templates')->where('name', $type)->first();
            if ($notificationTemplate) {
                DB::table('notification_template_content_mapping')->where('template_id', $notificationTemplate->id)->delete();
                DB::table('notification_templates')->where('id', $notificationTemplate->id)->delete();
            }
        }

        // Remove constants
        Constant::where('type', 'notification_type')->whereIn('value', [
            'subscription_cancelled',
            'subscription_expired',
            'forgot_password',
            'password_reset',
        ])->delete();

        Constant::where('type', 'notification_param_button')->whereIn('value', [
            'shop_name',
            'shop_address',
            'shop_city',
            'shop_registration',
            'plan_duration ',
            'plan_type',
            'plan_description',
        ])->delete();
    }
};

