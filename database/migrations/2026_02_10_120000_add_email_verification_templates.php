<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Constant;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Single migration for email_verification: Constant, NotificationTemplate,
     * NotificationTemplateContentMapping, MailTemplates, MailTemplateContentMapping.
     * Plus new_shop_created (New Shop Created) and (shop_activated Shop deactivated) templates.
     */
    public function up(): void
    {
        // 1. Constant for notification_type
        Constant::updateOrCreate(
            ['type' => 'notification_type', 'value' => 'email_verification'],
            ['name' => 'Email Verification']
        );

        // 2. NotificationTemplate
        $notificationTemplateExists = DB::table('notification_templates')
            ->where('name', 'email_verification')
            ->exists();

        if (!$notificationTemplateExists) {
            DB::table('notification_templates')->insert([
                'name'        => 'email_verification',
                'label'       => 'Email Verification',
                'description' => 'Email verification link send after registration',
                'type'        => 'email_verification',
                'to'          => json_encode(['user', 'provider', 'handyman']),
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

        $notificationTemplate = DB::table('notification_templates')
            ->where('name', 'email_verification')
            ->first();

        if ($notificationTemplate) {
            // 3. NotificationTemplateContentMapping (user, provider, handyman)
            $mailBody = '
                <p>Hello <strong>[[ user_name ]]</strong>,</p>

                <p>Welcome! We\'re glad to have you with us.</p>

                <p>To activate your account and complete your registration, please verify your email address by clicking the button below.</p>

                <div style="text-align:center; margin:30px 0;">
                    <a href="[[ verification_link ]]" style="background:#5f60b9; color:#ffffff; padding:12px 24px; text-decoration:none; border-radius:5px; font-size:14px; display:inline-block;">Verify Email</a>
                </div>

                <p>If you did not sign up for this account, you can safely ignore this email. No further action is required.</p>

                <p>Best regards,<br>The [[ company_name ]] Team</p>
            ';

            foreach (['user', 'provider', 'handyman'] as $userType) {
                $exists = DB::table('notification_template_content_mapping')
                    ->where('template_id', $notificationTemplate->id)
                    ->where('language', 'en')
                    ->where('user_type', $userType)
                    ->exists();

                if (!$exists) {
                    DB::table('notification_template_content_mapping')->insert([
                        'template_id'          => $notificationTemplate->id,
                        'language'             => 'en',
                        'user_type'            => $userType,
                        'subject'              => 'Email verification',
                        'template_detail'      => '<p>Please verify your email address. Check your inbox for the verification link.</p>',
                        'mail_subject'         => 'Email verification',
                        'mail_template_detail' => $mailBody,
                        'status'               => 1,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }           
        }

        // 4. MailTemplates + 5. MailTemplateContentMapping
        $mailTemplateExists = DB::table('mail_templates')->where('type', 'email_verification')->exists();

        if (!$mailTemplateExists) {
            $mailTemplateId = DB::table('mail_templates')->insertGetId([
                'type'      => 'email_verification',
                'name'      => 'email_verification',
                'label'     => 'Email Verification',
                'status'    => 1,
                'to'        => '["user","provider","handyman"]',
                'channels'  => json_encode(['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '0']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $detail = '
                <p>Hello <strong>[[ user_name ]]</strong>,</p>

                <p>Welcome! We\'re glad to have you with us.</p>

                <p>To activate your account and complete your registration, please verify your email address by clicking the button below.</p>

                <div style="text-align:center; margin:30px 0;">
                    <a href="[[ verification_link ]]" style="background:#5f60b9; color:#ffffff; padding:12px 24px; text-decoration:none; border-radius:5px; font-size:14px; display:inline-block;">Verify Email</a>
                </div>

                <p>If you did not sign up for this account, you can safely ignore this email. No further action is required.</p>

                <p>Best regards,<br>The [[ company_name ]] Team</p>
            ';
            foreach (['user', 'provider', 'handyman'] as $userType) {
                DB::table('mail_template_content_mappings')->insert([
                    'template_id'       => $mailTemplateId,
                    'language'          => 'en',
                    'notification_link' => '',
                    'notification_message' => '',
                    'user_type'         => $userType,
                    'status'            => 1,
                    'subject'           => 'Email verification',
                    'template_detail'   => $detail,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        }

        // --- Shop module notifications (do not modify email_verification above) ---

        foreach (
            [
                ['value' => 'new_shop_created', 'name' => 'New Shop Created'],
                ['value' => 'shop_activated', 'name' => 'Shop Activated'],
                ['value' => 'shop_deactivated', 'name' => 'Shop Deactivated'],

            ] as $constant
        ) {
            Constant::updateOrCreate(
                ['type' => 'notification_type', 'value' => $constant['value']],
                ['name' => $constant['name']]
            );
        }

        $shopTemplates = [
            'new_shop_created' => [
                'to' => json_encode(['admin', 'provider', 'user']),
                'label' => 'New Shop Created',
                'description' => 'Notification when a shop is created. Sent to related provider and users, or admin and users.',
            ],
            'shop_activated' => [
                'to' => json_encode(['admin', 'provider']),
                'label' => 'Shop activated',
                'description' => 'Notification when shop status is activated. Sent to related provider and admin.',
            ],

            'shop_deactivated' => [
                'to' => json_encode(['admin', 'provider']),
                'label' => 'Shop Deactivated',
                'description' => 'Notification when shop status is deactivated. Sent to related provider and admin.',
            ],
        ];

        foreach ($shopTemplates as $type => $config) {
            if (DB::table('notification_templates')->where('name', $type)->exists()) {
                continue;
            }
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

        $shopNotificationMappings = [
            'new_shop_created' => [
                ['user_type' => 'admin', 'subject' => 'New Shop Created', 'body' => '<p>Hello [[ admin_name ]],</p><p>A new shop "[[ shop_name ]]" has been created by provider [[ provider_name ]].</p>'],
                ['user_type' => 'provider', 'subject' => 'New Shop Created', 'body' => '<p>Hello [[ provider_name ]],</p><p>A new shop "[[ shop_name ]]" has been created for you.</p>'],
                ['user_type' => 'user', 'subject' => 'New Shop Created', 'body' => '<p>Hello [[ user_name ]],</p><p>A new shop "[[ shop_name ]]" is now available on the platform.</p>'],
            ],
            'shop_activated' => [
                ['user_type' => 'admin', 'subject' => 'Shop activated', 'body' => '<p>Hello [[ admin_name ]],</p><p>Shop "[[ shop_name ]]"  is currently available on the platform.</p>'],
                ['user_type' => 'provider', 'subject' => 'Your shop activated', 'body' => '<p>Hello [[ provider_name ]],</p><p>Your shop "[[ shop_name ]]"  is currently not available on the platform.</p>'],
            ],
             'shop_deactivated' => [
                ['user_type' => 'admin', 'subject' => 'Shop deactivated', 'body' => '<p>Hello [[ admin_name ]],</p><p>Shop "[[ shop_name ]]"  is currently not available on the platform.</p>'],
                ['user_type' => 'provider', 'subject' => 'Your shop deactivated', 'body' => '<p>Hello [[ provider_name ]],</p><p>Your shop "[[ shop_name ]]"  is currently not available on the platform.</p>'],
            ],
        ];

        foreach ($shopNotificationMappings as $type => $rows) {
            $template = DB::table('notification_templates')->where('name', $type)->first();
            if (!$template) {
                continue;
            }
            foreach ($rows as $row) {
                $exists = DB::table('notification_template_content_mapping')
                    ->where('template_id', $template->id)
                    ->where('language', 'en')
                    ->where('user_type', $row['user_type'])
                    ->exists();
                if (!$exists) {
                    $bestRegards = '<p>Best regards,<br>[[ company_name ]]</p>';
                    $bodyWithRegards = in_array($row['user_type'], ['user', 'provider'], true)
                        ? $row['body'] . $bestRegards
                        : $row['body'];
                    DB::table('notification_template_content_mapping')->insert([
                        'template_id'          => $template->id,
                        'language'             => 'en',
                        'user_type'            => $row['user_type'],
                        'subject'              => $row['subject'],
                        'template_detail'      => $row['body'],
                        'mail_subject'         => $row['subject'],
                        'mail_template_detail' => $bodyWithRegards ,
                        'status'               => 1,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }
        }

        foreach (['new_shop_created', 'shop_activated', 'shop_deactivated'] as $mailType) {
            if (DB::table('mail_templates')->where('type', $mailType)->exists()) {
                continue;
            }
            $mailId = DB::table('mail_templates')->insertGetId([
                'type'       => $mailType,
                'name'       => $mailType,
                'label'      => $shopTemplates[$mailType]['label'] ?? $mailType,
                'status'     => 1,
                'to'         => $shopTemplates[$mailType]['to'] ?? '[]',
                'channels'   => json_encode(['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '0']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $template = DB::table('notification_templates')->where('name', $mailType)->first();
            if (!$template) {
                continue;
            }
            $mappings = $shopNotificationMappings[$mailType] ?? [];
            $bestRegards = '<p>Best regards,<br>[[ company_name ]]</p>';
            foreach ($mappings as $row) {
                $bodyWithRegards = in_array($row['user_type'], ['user', 'provider'], true)
                    ? $row['body'] . $bestRegards
                    : $row['body'];
                DB::table('mail_template_content_mappings')->insert([
                    'template_id'             => $mailId,
                    'language'                => 'en',
                    'notification_link'      => '',
                    'notification_message'   => '',
                    'user_type'               => $row['user_type'],
                    'status'                  => 1,
                    'subject'                 => $row['subject'],
                    'template_detail'         => $bodyWithRegards,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);
            }
        }

        // Ensure existing shop template content: "Best regards" only for user and provider
        $bestRegardsLine = '<p>Best regards,<br>[[ company_name ]]</p>';
        foreach (['new_shop_created', 'shop_activated', 'shop_deactivated'] as $type) {
            $template = DB::table('notification_templates')->where('name', $type)->first();
            if (!$template) {
                continue;
            }
            // Append for user/provider if not present
            $rows = DB::table('notification_template_content_mapping')
                ->where('template_id', $template->id)
                ->whereIn('user_type', ['user', 'provider'])
                ->where('language', 'en')
                ->get();
            foreach ($rows as $r) {
                if (strpos($r->mail_template_detail ?? '', 'Best regards') === false) {
                    DB::table('notification_template_content_mapping')
                        ->where('id', $r->id)
                        ->update([
                            'mail_template_detail' => $r->mail_template_detail . $bestRegardsLine,
                            'template_detail'      => ($r->template_detail ?? '') . $bestRegardsLine,
                            'updated_at'           => now(),
                        ]);
                }
            }
            // Remove from admin if present
            $adminRows = DB::table('notification_template_content_mapping')
                ->where('template_id', $template->id)
                ->where('user_type', 'admin')
                ->where('language', 'en')
                ->get();
            foreach ($adminRows as $r) {
                $detail = $r->mail_template_detail ?? '';
                if (strpos($detail, 'Best regards') !== false) {
                    $clean = preg_replace('/<p>Best regards,<br>\[\[ company_name \]\]<\/p>\s*$/', '', $detail);
                    DB::table('notification_template_content_mapping')
                        ->where('id', $r->id)
                        ->update([
                            'mail_template_detail' => $clean,
                            'template_detail'      => preg_replace('/<p>Best regards,<br>\[\[ company_name \]\]<\/p>\s*$/', '', $r->template_detail ?? ''),
                            'updated_at'           => now(),
                        ]);
                }
            }
        }
        foreach (['new_shop_created', 'shop_activated','shop_deactivated'] as $mailType) {
            $mailTemplate = DB::table('mail_templates')->where('type', $mailType)->first();
            if (!$mailTemplate) {
                continue;
            }
            $mappings = DB::table('mail_template_content_mappings')
                ->where('template_id', $mailTemplate->id)
                ->where('language', 'en')
                ->get();
            foreach ($mappings as $m) {
                $hasRegards = strpos($m->template_detail ?? '', 'Best regards') !== false;
                if (in_array($m->user_type, ['user', 'provider'], true) && !$hasRegards) {
                    DB::table('mail_template_content_mappings')
                        ->where('id', $m->id)
                        ->update([
                            'template_detail' => ($m->template_detail ?? '') . $bestRegardsLine,
                            'updated_at'      => now(),
                        ]);
                } elseif ($m->user_type === 'admin' && $hasRegards) {
                    $clean = preg_replace('/<p>Best regards,<br>\[\[ company_name \]\]<\/p>\s*$/', '', $m->template_detail ?? '');
                    DB::table('mail_template_content_mappings')
                        ->where('id', $m->id)
                        ->update([
                            'template_detail' => $clean,
                            'updated_at'      => now(),
                        ]);
                }
            }
        }

        // --- Provider free plan assigned (separate from shop logic) ---
        Constant::updateOrCreate(
            ['type' => 'notification_type', 'value' => 'provider_free_plan_assigned'],
            ['name' => 'Provider Free Plan Assigned']
        );

        if (!DB::table('notification_templates')->where('name', 'provider_free_plan_assigned')->exists()) {
            DB::table('notification_templates')->insert([
                'name'        => 'provider_free_plan_assigned',
                'label'       => 'Provider Free Plan Assigned',
                'description' => 'Notification when a new provider is auto-assigned the free plan (earning_type=subscription, auto_assign_free_plan=1, record in provider_subscriptions, is_subscribed=1).',
                'type'        => 'provider_free_plan_assigned',
                'to'          => json_encode(['admin', 'provider']),
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
            \Log::info("magration file run ---------------");
        }

        $providerFreePlanTemplate = DB::table('notification_templates')->where('name', 'provider_free_plan_assigned')->first();
        if ($providerFreePlanTemplate) {
            $providerFreePlanMappings = [
                ['user_type' => 'admin', 'subject' => 'New provider assigned free trail', 'body' => '<p>Hello [[ admin_name ]],</p><p>A new provider <strong>[[ provider_name ]]</strong> has been automatically assigned the free plan <strong>[[ plan_title ]]</strong> upon registration.</p>'],
                ['user_type' => 'provider', 'subject' => 'Free trail assigned to you', 'body' => '<p>Hello [[ provider_name ]],</p><p>Welcome! You have been automatically assigned the free plan <strong>[[ plan_title ]]</strong>. You can now use the platform services.</p><p>Best regards,<br>[[ company_name ]]</p>'],
            ];
            foreach ($providerFreePlanMappings as $row) {
                $exists = DB::table('notification_template_content_mapping')
                    ->where('template_id', $providerFreePlanTemplate->id)
                    ->where('language', 'en')
                    ->where('user_type', $row['user_type'])
                    ->exists();
                if (!$exists) {
                    $bodyWithRegards = $row['user_type'] === 'provider' ? $row['body'] : $row['body'];
                    DB::table('notification_template_content_mapping')->insert([
                        'template_id'          => $providerFreePlanTemplate->id,
                        'language'             => 'en',
                        'user_type'            => $row['user_type'],
                        'subject'              => $row['subject'],
                        'template_detail'      => $row['body'],
                        'mail_subject'         => $row['subject'],
                        'mail_template_detail' => $bodyWithRegards,
                        'status'               => 1,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }
        }

        if (!DB::table('mail_templates')->where('type', 'provider_free_plan_assigned')->exists()) {
            $providerFreePlanMailId = DB::table('mail_templates')->insertGetId([
                'type'       => 'provider_free_plan_assigned',
                'name'       => 'provider_free_plan_assigned',
                'label'      => 'Provider Free Plan Assigned',
                'status'     => 1,
                'to'         => '["admin","provider"]',
                'channels'   => json_encode(['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '0']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $providerFreePlanTemplate = DB::table('notification_templates')->where('name', 'provider_free_plan_assigned')->first();
            if ($providerFreePlanTemplate) {
                $providerFreePlanMappings = [
                    ['user_type' => 'admin', 'subject' => 'New provider assigned free trail', 'body' => '<p>Hello [[ admin_name ]],</p><p>A new provider <strong>[[ provider_name ]]</strong> has been automatically assigned the free plan <strong>[[ plan_title ]]</strong> upon registration.</p>'],
                    ['user_type' => 'provider', 'subject' => 'Free trail assigned to you', 'body' => '<p>Hello [[ provider_name ]],</p><p>Welcome! You have been automatically assigned the free plan <strong>[[ plan_title ]]</strong>. You can now use the platform services.</p><p>Best regards,<br>[[ company_name ]]</p>'],
                ];
                foreach ($providerFreePlanMappings as $row) {
                    DB::table('mail_template_content_mappings')->insert([
                        'template_id'             => $providerFreePlanMailId,
                        'language'                => 'en',
                        'notification_link'      => '',
                        'notification_message'   => '',
                        'user_type'               => $row['user_type'],
                        'status'                  => 1,
                        'subject'                 => $row['subject'],
                        'template_detail'         => $row['body'],
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]);
                }
            }
        }

        // --- Free Plan Expiry Reminder (1 Day Before) ---
        Constant::updateOrCreate(
            ['type' => 'notification_type', 'value' => 'free_plan_expiry_reminder'],
            ['name' => 'Free Plan Expiry Reminder']
        );

        if (!DB::table('notification_templates')->where('name', 'free_plan_expiry_reminder')->exists()) {
            DB::table('notification_templates')->insert([
                'name'        => 'free_plan_expiry_reminder',
                'label'       => 'Free Plan Expiry Reminder',
                'description' => 'Notification sent 1 day before the free plan expires to remind the provider to upgrade.',
                'type'        => 'free_plan_expiry_reminder',
                'to'          => json_encode(['provider']),
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

        $freePlanExpiryTemplate = DB::table('notification_templates')->where('name', 'free_plan_expiry_reminder')->first();
        if ($freePlanExpiryTemplate) {
            $freePlanExpiryMappings = [
                [
                    'user_type' => 'provider', 
                    'subject' => 'Free Plan Expires Reminder', 
                    'body' => '<p>Hello <strong>[[ provider_name ]]</strong>,</p><p>This is a friendly reminder that your <strong>[[ plan_title ]]</strong> will expire on <strong>[[ expiry_date ]]</strong>.</p><p>To continue enjoying uninterrupted access to our platform services, please upgrade to a paid plan before your free plan expires.</p><p>If you have any questions or need assistance, feel free to contact our support team.</p><p>Best regards,<br>The [[ company_name ]] Team</p>'
                ],
            ];
            foreach ($freePlanExpiryMappings as $row) {
                $exists = DB::table('notification_template_content_mapping')
                    ->where('template_id', $freePlanExpiryTemplate->id)
                    ->where('language', 'en')
                    ->where('user_type', $row['user_type'])
                    ->exists();
                if (!$exists) {
                    DB::table('notification_template_content_mapping')->insert([
                        'template_id'          => $freePlanExpiryTemplate->id,
                        'language'             => 'en',
                        'user_type'            => $row['user_type'],
                        'subject'              => $row['subject'],
                        'template_detail'      => '<p>Your free plan expires tomorrow. Please upgrade to continue using our services.</p>',
                        'mail_subject'         => $row['subject'],
                        'mail_template_detail' => $row['body'],
                        'status'               => 1,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }
        }

        if (!DB::table('mail_templates')->where('type', 'free_plan_expiry_reminder')->exists()) {
            $freePlanExpiryMailId = DB::table('mail_templates')->insertGetId([
                'type'       => 'free_plan_expiry_reminder',
                'name'       => 'free_plan_expiry_reminder',
                'label'      => 'Free Plan Expiry Reminder',
                'status'     => 1,
                'to'         => '["provider"]',
                'channels'   => json_encode(['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '0']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $freePlanExpiryTemplate = DB::table('notification_templates')->where('name', 'free_plan_expiry_reminder')->first();
            if ($freePlanExpiryTemplate) {
                $freePlanExpiryMappings = [
                    [
                        'user_type' => 'provider', 
                        'subject' => 'Free Plan Expires Reminder', 
                        'body' => '<p>Hello <strong>[[ provider_name ]]</strong>,</p><p>This is a friendly reminder that your <strong>[[ plan_title ]]</strong> will expire on <strong>[[ expiry_date ]]</strong>.</p><p>To continue enjoying uninterrupted access to our platform services, please upgrade to a paid plan before your free plan expires.</p><p>If you have any questions or need assistance, feel free to contact our support team.</p><p>Best regards,<br>The [[ company_name ]] Team</p>'
                    ],
                ];
                foreach ($freePlanExpiryMappings as $row) {
                    DB::table('mail_template_content_mappings')->insert([
                        'template_id'             => $freePlanExpiryMailId,
                        'language'                => 'en',
                        'notification_link'      => '',
                        'notification_message'   => 'Your free plan expires tomorrow. Please upgrade to continue.',
                        'user_type'               => $row['user_type'],
                        'status'                  => 1,
                        'subject'                 => $row['subject'],
                        'template_detail'         => $row['body'],
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mail template + mappings (email_verification)
        $mailId = DB::table('mail_templates')->where('type', 'email_verification')->value('id');
        if ($mailId) {
            DB::table('mail_template_content_mappings')->where('template_id', $mailId)->delete();
            DB::table('mail_templates')->where('id', $mailId)->delete();
        }

        // Notification template content mappings (email_verification)
        $notificationTemplate = DB::table('notification_templates')->where('name', 'email_verification')->first();
        if ($notificationTemplate) {
            DB::table('notification_template_content_mapping')
                ->where('template_id', $notificationTemplate->id)
                ->delete();
        }

        // Notification template (email_verification)
        DB::table('notification_templates')->where('name', 'email_verification')->delete();

        // Constant (email_verification)
        Constant::where('type', 'notification_type')->where('value', 'email_verification')->delete();

        // Shop templates (rollback)
        foreach (['new_shop_created', 'shop_activated', 'shop_deactivated'] as $type) {
            $nt = DB::table('notification_templates')->where('name', $type)->first();
            if ($nt) {
                DB::table('notification_template_content_mapping')->where('template_id', $nt->id)->delete();
                DB::table('notification_templates')->where('id', $nt->id)->delete();
            }
            $mt = DB::table('mail_templates')->where('type', $type)->first();
            if ($mt) {
                DB::table('mail_template_content_mappings')->where('template_id', $mt->id)->delete();
                DB::table('mail_templates')->where('id', $mt->id)->delete();
            }
            Constant::where('type', 'notification_type')->where('value', $type)->delete();
        }

        // Provider free plan assigned
        $nt = DB::table('notification_templates')->where('name', 'provider_free_plan_assigned')->first();
        if ($nt) {
            DB::table('notification_template_content_mapping')->where('template_id', $nt->id)->delete();
            DB::table('notification_templates')->where('id', $nt->id)->delete();
        }
        $mt = DB::table('mail_templates')->where('type', 'provider_free_plan_assigned')->first();
        if ($mt) {
            DB::table('mail_template_content_mappings')->where('template_id', $mt->id)->delete();
            DB::table('mail_templates')->where('id', $mt->id)->delete();
        }
        Constant::where('type', 'notification_type')->where('value', 'provider_free_plan_assigned')->delete();

        // Free plan expiry reminder
        $nt = DB::table('notification_templates')->where('name', 'free_plan_expiry_reminder')->first();
        if ($nt) {
            DB::table('notification_template_content_mapping')->where('template_id', $nt->id)->delete();
            DB::table('notification_templates')->where('id', $nt->id)->delete();
        }
        $mt = DB::table('mail_templates')->where('type', 'free_plan_expiry_reminder')->first();
        if ($mt) {
            DB::table('mail_template_content_mappings')->where('template_id', $mt->id)->delete();
            DB::table('mail_templates')->where('id', $mt->id)->delete();
        }
        Constant::where('type', 'notification_type')->where('value', 'free_plan_expiry_reminder')->delete();
    }
};
