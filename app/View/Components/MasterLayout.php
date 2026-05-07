<?php

namespace App\View\Components;

use Illuminate\View\Component;

class MasterLayout extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     //
    // }

public function __construct()
{
    // Default page title based on site_name from the database
    $generalSetting = \DB::table('settings')->where('key', 'general-setting')->value('value');
    $siteName = $generalSetting ? json_decode($generalSetting, true)['site_name'] ?? 'Laravel' : 'Laravel';

    // Default page title
    $pageTitle = trans('messages.handyman_services');  // Default to "Handyman Services"

    // Define route-title mappings in an associative array
    $routeTitles = [
        'category' => trans('messages.list_form_title', ['form' => trans('messages.category')]),
        'service' => __('messages.all_form_title', ['form' => __('messages.services')]),
        'subcategory' => trans('messages.list_form_title', ['form' => trans('messages.subcategory')]),
        'booking' => __('messages.bookings'),
        'servicepackage' => __('messages.packages'),
        'serviceaddon' => __('messages.addons'),
        'servicezone' => trans('messages.servicezone'),
        'bank' => trans('messages.list_form_title', ['form' => trans('messages.bank')]),
        'blog' => trans('messages.blogs'),
        'booking-rating' => __('messages.user_ratings'),
        'coupon' => trans('messages.list_form_title', ['form' => trans('messages.coupon')]),
        'user' => __('messages.customers'),
        'document' => trans('messages.list_form_title', ['form' => trans('messages.document')]),
        'earning' => __('messages.earnings'),
        'frontend-setting' => __('messages.frontend_setting'),
        'handyman' => trans('messages.list_form_title', ['form' => __('messages.handyman')]),
        'handyman-payout' => trans('messages.add_button_form', ['form' => trans('messages.handyman_payout')]),
        'handyman-rating' => __('messages.handyman_ratings'),
        'handymantype' => trans('messages.list_form_title', ['form' => trans('messages.handymantype')]),
        'helpdesk' => trans('messages.helpdesk'),
        'setting' => trans('messages.setting'),
        'push-notification' => trans('messages.list_form_title', ['form' => trans('messages.notification')]),
        'notification-templates' => __('messages.notification_templates'),
        'payment' => __('messages.payments'),
        'plans' => trans('messages.list_form_title', ['form' => trans('messages.plan')]),
        'post-job-request' => trans('messages.job_request_list'),
        'provider-address' => trans('messages.update_form_title', ['form' => trans('messages.provider_address')]),
        'provider' => __('messages.providers'),
        'providerdocument' => trans('messages.update_form_title', ['form' => trans('messages.providerdocument')]),
        'provider-payout' => trans('messages.add_button_form', ['form' => trans('messages.provider_payout')]),
        'providertype' => trans('messages.list_form_title', ['form' => trans('messages.providertype')]),
        'servicefaq' => trans('messages.list_form_title', ['form' => trans('messages.servicefaq')]),
        'settings' => __('messages.Settings'),
        'slider' => trans('messages.list_form_title', ['form' => trans('messages.slider')]),
        'provider-service-request' => __('messages.service_request', ['form' => __('messages.service')]),
        'user-service-list' => __('messages.list_form_title', ['form' => __('messages.service')]),
        'handyman/list/request' => __('messages.pending_list_form_title', ['form' => __('messages.handyman')]),
        'handyman/list/unassigned' => __('messages.unassigned_list_form_title', ['form' => __('messages.handyman')]),
        'provider/list/pending' => __('messages.pending_list_form_title', ['form' => __('messages.provider')]),
        'provider/list/subscribe' => __('messages.list_form_title', ['form' => __('messages.subscribe')]),
        'user/list/unverified' => __('messages.unverified'),
        'user/list/all' => __('messages.all_user'),
        'cash-payment-list' => __('messages.cash_payments'),
        'wallet' => __('messages.list_form_title', ['form' => __('messages.wallet')]),
        'withdrawal-request' => __('messages.list_form_title', ['form' => __('messages.provider_withdrawal_requests')]),
        'handyman-earning' => __('messages.handyman_earning'),
        'tax' => trans('messages.taxes'),
        'pages/term-condition' => __('messages.terms_condition'),
        'pages/privacy-policy' => __('messages.privacy_policy'),
        'pages/help-support' => __('messages.help_support'),
        'pages/refund-cancellation-policy' => __('messages.refund_cancellation_policy'),
        'pages/data-deletion-request' => __('messages.data_deletion_request'),
        'pages/about-us' => __('messages.about_us'),
        'referral-loyalty' => __('messages.referral_and_loyalty'),
        'loyalty-history-index' => __('messages.loyalty_point_history'),
        'loyalty-points' => __('messages.loyalty_points'),
    ];

    // Check if the current route exists in the mappings, then set the pageTitle
    foreach ($routeTitles as $route => $title) {
        if (request()->is($route)) {
            $pageTitle = $title;
            break;  // Exit the loop once a match is found
        }
    }

    // Share the page title globally
    view()->share('pageTitle', $pageTitle);
    view()->share('siteName', $siteName);  // Share the site name globally
}






    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.master-layout');
    }
}
