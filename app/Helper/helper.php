<?php

use \Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Google\Client as Google_Client;

function authSession($force = false)
{
    $session = new \App\Models\User;
    if ($force) {
        $user = \Auth::user()->getRoleNames();
        \Session::put('auth_user', $user);
        $session = \Session::get('auth_user');
        return $session;
    }
    if (\Session::has('auth_user')) {
        $session = \Session::get('auth_user');
    } else {
        $user = \Auth::user();
        \Session::put('auth_user', $user);
        $session = \Session::get('auth_user');
    }
    return $session;
}

function comman_message_response($message, $status_code = 200)
{
    return response()->json(['message' => $message], $status_code);
}

function comman_custom_response($response, $status_code = 200)
{
    return response()->json($response, $status_code);
}

function checkMenuRoleAndPermission($menu)
{
    if (\Auth::check()) {
        if ($menu->data('role') == null && auth()->user()->hasRole('admin')) {
            return true;
        }

        if ($menu->data('permission') == null && $menu->data('role') == null) {
            return true;
        }

        if ($menu->data('role') != null) {
            if (is_array($menu->data('role'))) {
                if (auth()->user()->hasAnyRole($menu->data('role'))) {
                    return true;
                }
            }
            if (auth()->user()->hasAnyRole($menu->data('role'))) {
                return true;
            }
        }

        if ($menu->data('permission') != null) {
            if (is_array($menu->data('permission'))) {
                if (auth()->user()->hasAnyPermission($menu->data('permission'))) {
                    return true;
                }
            }
            if (auth()->user()->can($menu->data('permission'))) {
                return true;
            }
        }
    }

    return false;
}

function checkRolePermission($role, $permission)
{
    try {
        if ($role->hasPermissionTo($permission)) {
            return true;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function demoUserPermission()
{
    if (\Auth::user()->hasAnyRole(['demo_admin'])) {
        return false; // Changed from true to false to allow demo admin to perform actions
    } else {
        return false;
    }
}

function getSingleMedia($model, $collection = 'profile_image', $skip = true)
{
    if (!\Auth::check() && $skip) {
        return asset('images/user/user.png');
    }
    $media = null;
    if ($model !== null) {
        $media = $model->getFirstMedia($collection);
    }

    if (getFileExistsCheck($media)) {
        $url = $media->getFullUrl();
        
        // Add cache buster for theme assets (logo, favicon, etc.)
        if (in_array($collection, ['logo', 'favicon', 'footer_logo', 'loader'])) {
            $themeSetup = \App\Models\Setting::where('type', 'theme-setup')->where('key', 'theme-setup')->first();
            if ($themeSetup) {
                $values = json_decode($themeSetup->value, true);
                $cacheBuster = $values['cache_buster'] ?? time();
                $url .= '?v=' . $cacheBuster;
            }
        }
        
        return $url;
    } else {

        switch ($collection) {
            case 'image_icon':
                $media = asset('images/user/user.png');
                break;
            case 'profile_image':
                $media = asset('images/user/user.png');
                break;
            case 'service_attachment':
                $media = asset('images/default.png');
                break;
            case 'banner_attachment':
                $media = asset('images/default.png');
                break;
            case 'site_logo':
                $media = asset('images/logo.png');
                break;
            case 'site_favicon':
                $media = asset('images/favicon.png');
                break;
            case 'app_image':
                $media = asset('images/frontend/mb-serv-1.png');
                break;
            case 'app_image_full':
                $media = asset('images/frontend/mb-serv-full.png');
                break;
            case 'footer_logo':
                $media = asset('landing-images/logo/logo.png');
                break;
            case 'logo':
                $media = asset('images/logo.png');
                break;
            case 'favicon':
                $media = asset('images/favicon.png');
                break;
            case 'loader':
                $media = asset('images/loader.gif');
                break;
            case 'helpdesk_attachment':
                $media = asset('images/default.png');
                break;
            case 'helpdesk_activity_attachment':
                $media = asset('images/default.png');
                break;
            default:
                $media = asset('images/default.png');
                break;
        }
        return $media;
    }
}

function getFileExistsCheck($media)
{
    $mediaCondition = false;

    if ($media) {
        if ($media->disk == 'public') {
            $mediaCondition = file_exists($media->getPath());
        } else {
            $mediaCondition = \Storage::disk($media->disk)->exists($media->getPath());
        }
    }

    return $mediaCondition;
}

function storeMediaFile($model, $file, $name)
{
    if ($file) {
        if (!in_array($name, ['service_attachment', 'package_attachment', 'blog_attachment', 'section5_attachment', 'helpdesk_attachment', 'helpdesk_activity_attachment', 'banner_attachment'])) {
            $model->clearMediaCollection($name);
        }
        if (is_array($file)) {
            foreach ($file as $key => $value) {
                $model->addMedia($value)->toMediaCollection($name);
            }
        } else {
            $model->addMedia($file)->toMediaCollection($name);
        }
    }

    return true;
}

function storeAttachments($request, $attachmentPrefix, $data)
{

    $file = [];

    if ($request->is('api/*')) {
        if ($request->has('attachment_count')) {
            for ($i = 0; $i < $request->attachment_count; $i++) {
                $attachment = "{$attachmentPrefix}_{$i}";
                if ($request->$attachment != null) {
                    $file[] = $request->$attachment;
                }
            }
            storeMediaFile($data, $file, $attachmentPrefix);
        }
    } else {

        if ($request->hasFile($attachmentPrefix)) {

            storeMediaFile($data, $request->file($attachmentPrefix), $attachmentPrefix);
        }
    }
}

function getAttachments($attchments)
{
    $files = [];
    if (count($attchments) > 0) {
        foreach ($attchments as $attchment) {
            if (getFileExistsCheck($attchment)) {
                array_push($files, $attchment->getFullUrl());
            }
        }
    }

    return $files;
}

function getAttachmentArray($attchments)
{
    $files = [];
    if (count($attchments) > 0) {
        foreach ($attchments as $attchment) {
            if (getFileExistsCheck($attchment)) {
                $file = [
                    'id' => $attchment->id,
                    'url' => $attchment->getFullUrl()
                ];
                array_push($files, $file);
            }
        }
    }

    return $files;
}

function getMediaFileExit($model, $collection = 'profile_image')
{
    if ($model == null) {
        return asset('images/user/user.png');;
    }

    $media = $model->getFirstMedia($collection);

    return getFileExistsCheck($media);
}

function saveBookingActivity($data)
{
    $admin = \App\Models\AppSetting::first();
    date_default_timezone_set($admin->time_zone ?? 'UTC');
    $data['datetime'] = date('Y-m-d H:i:s');
    $role = auth()->user()->user_type;
    switch ($data['activity_type']) {
        case "add_booking":

            $customer_name = $data['booking']->customer->display_name;

            $data['activity_message'] = __('messages.booking_added', ['name' => $customer_name]);
            $data['activity_type'] = __('messages.add_booking');
            $activity_data = [
                'service_id' => $data['booking']->service_id,
                'service_name' => isset($data['booking']->service) ? $data['booking']->service->name : '',
                'customer_id' => $data['booking']->customer_id,
                'customer_name' => isset($data['booking']->customer) ? $data['booking']->customer->display_name : '',
                'provider_id' => $data['booking']->provider_id,
                'provider_name' => isset($data['booking']->provider) ? $data['booking']->provider->display_name : '',
            ];
            $sendTo = ['admin', 'provider', 'demo_admin'];
            break;
        case "assigned_booking":
            $assigned_handyman = handymanNames($data['booking']->handymanAdded);
            $data['activity_message'] = __('messages.booking_assigned', ['name' => $assigned_handyman]);
            $data['activity_type'] = __('messages.assigned_booking');

            $activity_data = [
                'handyman_id' => $data['booking']->handymanAdded->pluck('handyman_id'),
                'handyman_name' => $data['booking']->handymanAdded,
            ];
            $sendTo = ['handyman', 'user', 'admin', 'demo_admin'];
            break;

        case "transfer_booking":
            $assigned_handyman = handymanNames($data['booking']->handymanAdded);

            $data['activity_type'] = __('messages.transfer_booking');
            $data['activity_message'] = __('messages.booking_transfer', ['name' => $assigned_handyman]);
            $activity_data = [
                'handyman_id' => $data['booking']->handymanAdded->pluck('handyman_id'),
                'handyman_name' => $data['booking']->handymanAdded,
            ];
            $sendTo = ['handyman'];
            break;

        case "update_booking_status":

            $status = \App\Models\BookingStatus::bookingStatus($data['booking']->status);
            $old_status = \App\Models\BookingStatus::bookingStatus($data['booking']->old_status);
            $data['activity_type'] = __('messages.update_booking_status');
            $data['activity_message'] = __('messages.booking_status_update', ['from' => $old_status, 'to' => $status]);
            $activity_data = [
                'reason' => $data['booking']->reason,
                'status' => $data['booking']->status,
                'status_label' => $status,
                'old_status' => $data['booking']->old_status,
                'old_status_label' => $old_status,
            ];

            $sendTo = removeArrayValue(['admin', 'provider', 'handyman', 'user', 'demo_admin'], $role);
            break;
        case "cancel_booking":
            $status = \App\Models\BookingStatus::bookingStatus($data['booking']->status);
            $old_status = \App\Models\BookingStatus::bookingStatus($data['booking']->old_status);
            $data['activity_type'] = __('messages.cancel_booking');

            $data['activity_message'] = __('messages.cancel_booking_message', ['name' => $role]);


            $activity_data = [
                'reason' => $data['booking']->reason,
                'status' => $data['booking']->status,
                'status_label' => \App\Models\BookingStatus::bookingStatus($data['booking']->status),
            ];
            $sendTo = removeArrayValue(['admin', 'provider', 'handyman', 'user', 'demo_admin'], $role);
            break;
        case "payment_message_status":
            $data['activity_type'] = __('messages.payment_message_status');

            $data['activity_message'] = __('messages.payment_message', ['status' => $data['payment_status']]);

            $activity_data = [
                'activity_type' => $data['activity_type'],
                'payment_status' => $data['payment_status'],
                'booking_id' => $data['booking_id'],
            ];
            $sendTo = ['user'];
            break;

        default:
            $activity_data = [];
            break;
    }
    $data['activity_data'] = json_encode($activity_data);
    \App\Models\BookingActivity::create($data);
    $notification_data = [
        'id'   => $data['booking']->id,
        'type' => $data['activity_type'],
        'subject' => $data['activity_type'],
        'message' => $data['activity_message'],
        "ios_badgeType" => "Increase",
        "ios_badgeCount" => 1,
        "notification-type" => 'booking'
    ];
    foreach ($sendTo as $to) {
        switch ($to) {
            case 'admin':
                $user = \App\Models\User::getUserByKeyValue('user_type', 'admin');
                break;

            case 'demo_admin':
                $user = \App\Models\User::getUserByKeyValue('user_type', 'demo_admin');
                break;

            case 'provider':
                $user = \App\Models\User::getUserByKeyValue('id', $data['booking']->provider_id);
                break;

            case 'handyman':
                $handymans = $data['booking']->handymanAdded->pluck('handyman_id');
                foreach ($handymans as $id) {
                    $user = \App\Models\User::getUserByKeyValue('id', $id);
                    if ($user->user_type != 'provider') {
                        sendNotification('provider', $user, $notification_data);
                    }
                }
                break;

            case 'user':
                $user = \App\Models\User::getUserByKeyValue('id', $data['booking']->customer_id);
                break;
        }
        if ($to != 'handyman') {
            sendNotification($to, $user, $notification_data);
        }
    }
}

function formatOffset($offset)
{
    $hours = $offset / 3600;
    $remainder = $offset % 3600;
    $sign = $hours > 0 ? '+' : '-';
    $hour = (int) abs($hours);
    $minutes = (int) abs($remainder / 60);

    if ($hour == 0 and $minutes == 0) {
        $sign = ' ';
    }
    return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT)
        . ':' . str_pad($minutes, 2, '0');
}

function settingSession($type = 'get')
{
    if (\Session::get('setting_data') == '') {
        $type = 'set';
    }
    switch ($type) {
        case "set":
            $settings = \App\Models\AppSetting::first();
            \Session::put('setting_data', $settings);
            break;
        default:
            break;
    }
    return \Session::get('setting_data');
}

function imageSession($type = 'get')
{
    if (\Session::get('images_data') == '') {
        $type = 'set';
    }
    switch ($type) {
        case "set":
            $settings = \App\Models\Setting::where('type', 'theme-setup')->where('key', 'theme-setup')->first();
            \Session::put('images_data', $settings);
            break;
        default:
            break;
    }
    return \Session::get('images_data');
}

function sitesetupSession($type = 'get')
{
    if (\Session::get('setup_data') == '') {
        $type = 'set';
    }
    switch ($type) {
        case "set":
            $sitesetup = App\Models\Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
            $settings = $sitesetup ? json_decode($sitesetup->value) : null;
            if (!empty($settings)) {
                \Session::put('setup_data', $settings);
            }

            break;
        default:
            break;
    }
    return \Session::get('setup_data');
}

/**
 * Get the default/primary language from settings
 * Falls back to config if not set in database
 */
function getDefaultLanguage()
{
    $siteSetup = sitesetupSession('get');
    
    // Check if default_language is set in site setup
    if ($siteSetup && isset($siteSetup->default_language) && !empty($siteSetup->default_language)) {
        return $siteSetup->default_language;
    }
    
    // Fall back to config
    return config('app.locale', 'en');
}

function envChanges($type, $value)
{
    $path = base_path('.env');

    $checkType = $type . '="';
    if (strpos($value, ' ') || strpos(file_get_contents($path), $checkType) || preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $value)) {
        $value = '"' . $value . '"';
    }

    $value = str_replace('\\', '\\\\', $value);

    if (file_exists($path)) {
        $typeValue = env($type);

        if (strpos(env($type), ' ') || strpos(file_get_contents($path), $checkType)) {
            $typeValue = '"' . env($type) . '"';
        }

        file_put_contents($path, str_replace(
            $type . '=' . $typeValue,
            $type . '=' . $value,
            file_get_contents($path)
        ));

        $onesignal = collect(config('constant.ONESIGNAL'))->keys();

        $checkArray = \Arr::collapse([$onesignal, ['DEFAULT_LANGUAGE']]);


        if (in_array($type, $checkArray)) {
            if (env($type) === null) {
                file_put_contents($path, "\n" . $type . '=' . $value, FILE_APPEND);
            }
        }
    }
}

function getPriceFormat($price)
{
    $price = (float)$price;

    $setting = App\Models\Setting::getValueByKey('site-setup', 'site-setup');
    // $sitesetup = App\Models\Setting::where('type','site-setup')->where('key', 'site-setup')->first();
    // $sitesetupdata = $sitesetup ? json_decode($sitesetup->value) : null;
    $currencyId = $setting ? $setting->default_currency : "231";
    $currency_position = $setting ? $setting->currency_position : "left";
    $afterdecimalpoint = $setting ? $setting->digitafter_decimal_point : "2";
    $country = App\Models\Country::find($currencyId);

    $symbol = '$';
    if (!empty($country)) {
        $symbol = $country->symbol;
    }

    $position = 'left';
    if (!empty($currency_position)) {
        $position = $currency_position;
    }

    if ($position == 'left') {
        $price = $symbol . "" . number_format((float)$price, $afterdecimalpoint, '.', '');
    } else {
        $price = number_format((float)$price, $afterdecimalpoint, '.', '') . "" . $symbol;
    }

    return $price;
}

function currency_data()
{

    $setting = App\Models\Setting::getValueByKey('site-setup', 'site-setup');
    // $sitesetup = App\Models\Setting::where('type','site-setup')->where('key', 'site-setup')->first();
    // $sitesetupdata = $sitesetup ? json_decode($sitesetup->value) : null;
    $currencyId = $setting ? $setting->default_currency : "231";
    $currency_position = $setting ? $setting->currency_position : "left";
    $country = App\Models\Country::find($currencyId);

    $symbol = '$';
    if (!empty($country)) {
        $symbol = $country->symbol;
    }
    $position = 'left';
    if (!empty($currency_position)) {
        $position = $currency_position;
    }

    $data = [
        'currency_symbol' => $symbol,
        'currency_position' => $position,
    ];

    return  $data;
}

function payment_status()
{

    return [
        'pending' => __('messages.pending'),
        'paid' => __('messages.paid'),
        'failed' => __('messages.failed'),
        'refunded' => __('messages.refunded')
    ];
}

function timeZoneList()
{
    $list = \DateTimeZone::listAbbreviations();
    $idents = \DateTimeZone::listIdentifiers();

    $data = $offset = $added = array();
    foreach ($list as $abbr => $info) {
        foreach ($info as $zone) {
            if (!empty($zone['timezone_id']) and !in_array($zone['timezone_id'], $added) and in_array($zone['timezone_id'], $idents)) {

                $z = new \DateTimeZone($zone['timezone_id']);
                $c = new \DateTime(null, $z);
                $zone['time'] = $c->format('H:i a');
                $offset[] = $zone['offset'] = $z->getOffset($c);
                $data[] = $zone;
                $added[] = $zone['timezone_id'];
            }
        }
    }

    array_multisort($offset, SORT_ASC, $data);
    $options = array();
    foreach ($data as $key => $row) {

        $options[$row['timezone_id']] = $row['time'] . ' - ' . formatOffset($row['offset'])  . ' ' . $row['timezone_id'];
    }
    $options['America/Sao_Paulo'] = '3:00 pm -  GMT-03:00 America/Sao_Paulo';
    return $options;
}

function dateFormatList()
{
    return [
        'Y-m-d' => date('Y-m-d'),
        'm-d-Y' => date('m-d-Y'),
        'd-m-Y' => date('d-m-Y'),
        'd/m/Y' => date('d/m/Y'),
        'm/d/Y' => date('m/d/Y'),
        'Y/m/d' => date('Y/m/d'),
        'Y.m.d' => date('Y.m.d'),
        'd.m.Y' => date('d.m.Y'),
        'm.d.Y' => date('m.d.Y'),
        'jS M Y' => date('jS M Y'),
        'M jS Y' => date('M jS Y'),
        'D, M d, Y' => date('D, M d, Y'),
        'D, d M, Y' => date('D, d M, Y'),
        'D, M jS Y' => date('D, M jS Y'),
        'D, jS M Y' => date('D, jS M Y'),
        'F j, Y' => date('F j, Y'),
        'd F, Y' => date('d F, Y'),
        'jS F, Y' => date('jS F, Y'),
        'l jS F Y' => date('l jS F Y'),
        'l, F j, Y' => date('l, F j, Y'),

    ];
}

function getTimeInFormat($format)
{
    $now = new DateTime();
    $hours = $now->format('H');
    $minutes = $now->format('i');
    $seconds = $now->format('s');
    $milliseconds = $now->format('v');
    $totalSecondsSinceMidnight = ($hours * 3600) + ($minutes * 60) + $seconds;

    switch ($format) {
        case "H:i":
            return "$hours:$minutes";
        case "H:i:s":
            return "$hours:$minutes:$seconds";
        case "g:i A":
            $ampm = $hours >= 12 ? 'PM' : 'AM';
            $formattedHours = $hours % 12 || 12;
            return "$formattedHours:$minutes $ampm";
        case "H:i:s T":
            return "$hours:$minutes:$seconds UTC";
        case "H:i:s.v":
            return "$hours:$minutes:$seconds.$milliseconds";
        case "U":
            return $now->getTimestamp();
        case "u":
            return $milliseconds * 1000;
        case "G.i":
            return $hours + $minutes / 60;
        case "@BMT":
            $swatchBeat = floor($totalSecondsSinceMidnight / 86.4);
            return "@{$swatchBeat}BMT";
        default:
            return "Invalid format";
    }
}

function timeFormatList()
{
    $timeFormats = [
        "H:i",
        "H:i:s",
        "g:i A",
        "H:i:s T",
        "H:i:s.v",
        "U",
        "u",
        "G.i",
        "@BMT"
    ];

    return array_map(function ($format) {
        return ['format' => $format, 'time' => getTimeInFormat($format)];
    }, $timeFormats);
}

function dateAgoFormate($date, $type2 = '')
{
    if ($date == null || $date == '0000-00-00 00:00:00') {
        return '-';
    }

    $diff_time1 = \Carbon\Carbon::createFromTimeStamp(strtotime($date))->diffForHumans();
    $datetime = new \DateTime($date);
    $la_time = new \DateTimeZone(\Auth::check() ? \Auth::user()->time_zone ?? 'UTC' : 'UTC');
    $datetime->setTimezone($la_time);
    $diff_date = $datetime->format('Y-m-d H:i:s');

    $diff_time = \Carbon\Carbon::parse($diff_date)->isoFormat('LLL');

    if ($type2 != '') {
        return $diff_time;
    }

    return $diff_time1 . ' on ' . $diff_time;
}

function timeAgoFormate($date)
{
    if ($date == null) {
        return '-';
    }

    $diff_time = \Carbon\Carbon::createFromTimeStamp(strtotime($date))->diffForHumans();

    return $diff_time;
}

function duration($start = '', $end = '', $type = '')
{
    $start = \Carbon\Carbon::parse($start);
    $end = \Carbon\Carbon::parse($end);

    if ($type) {
        $diff_in_minutes = $start->diffInMinutes($end);
        return $diff_in_minutes;
    } else {
        $diff = $start->diff($end);
        return $diff->format('%H:%I');
    }
}

function removeArrayValue($array = [], $find = [])
{
    foreach (array_keys($array, $find) as $key) {
        unset($array[$key]);
    }

    return array_values($array);
}

function handymanNames($collection)
{
    return $collection->mapWithKeys(function ($item) {
        return [$item->handyman_id => optional($item->handyman)->display_name];
    })->values()->implode(',');
}

function languagesArray($ids = [])
{
    $language = [
        ['title' => 'Abkhaz', 'id' => 'ab'],
        ['title' => 'Afar', 'id' => 'aa'],
        ['title' => 'Afrikaans', 'id' => 'af'],
        ['title' => 'Akan', 'id' => 'ak'],
        ['title' => 'Albanian', 'id' => 'sq'],
        ['title' => 'Amharic', 'id' => 'am'],
        ['title' => 'Arabic', 'id' => 'ar'],
        ['title' => 'Aragonese', 'id' => 'an'],
        ['title' => 'Armenian', 'id' => 'hy'],
        ['title' => 'Assamese', 'id' => 'as'],
        ['title' => 'Avaric', 'id' => 'av'],
        ['title' => 'Avestan', 'id' => 'ae'],
        ['title' => 'Aymara', 'id' => 'ay'],
        ['title' => 'Azerbaijani', 'id' => 'az'],
        ['title' => 'Bambara', 'id' => 'bm'],
        ['title' => 'Bashkir', 'id' => 'ba'],
        ['title' => 'Basque', 'id' => 'eu'],
        ['title' => 'Belarusian', 'id' => 'be'],
        ['title' => 'Bengali', 'id' => 'bn'],
        ['title' => 'Bihari', 'id' => 'bh'],
        ['title' => 'Bislama', 'id' => 'bi'],
        ['title' => 'Bosnian', 'id' => 'bs'],
        ['title' => 'Breton', 'id' => 'br'],
        ['title' => 'Bulgarian', 'id' => 'bg'],
        ['title' => 'Burmese', 'id' => 'my'],
        ['title' => 'Catalan; Valencian', 'id' => 'ca'],
        ['title' => 'Chamorro', 'id' => 'ch'],
        ['title' => 'Chechen', 'id' => 'ce'],
        ['title' => 'Chichewa; Chewa; Nyanja', 'id' => 'ny'],
        ['title' => 'Chinese', 'id' => 'zh'],
        ['title' => 'Chuvash', 'id' => 'cv'],
        ['title' => 'Cornish', 'id' => 'kw'],
        ['title' => 'Corsican', 'id' => 'co'],
        ['title' => 'Cree', 'id' => 'cr'],
        ['title' => 'Croatian', 'id' => 'hr'],
        ['title' => 'Czech', 'id' => 'cs'],
        ['title' => 'Danish', 'id' => 'da'],
        ['title' => 'Divehi; Dhivehi; Maldivian;', 'id' => 'dv'],
        ['title' => 'Dutch', 'id' => 'nl'],
        ['title' => 'English', 'id' => 'en'],
        ['title' => 'Esperanto', 'id' => 'eo'],
        ['title' => 'Estonian', 'id' => 'et'],
        ['title' => 'Ewe', 'id' => 'ee'],
        ['title' => 'Faroese', 'id' => 'fo'],
        ['title' => 'Fijian', 'id' => 'fj'],
        ['title' => 'Finnish', 'id' => 'fi'],
        ['title' => 'French', 'id' => 'fr'],
        ['title' => 'Fula; Fulah; Pulaar; Pular', 'id' => 'ff'],
        ['title' => 'Galician', 'id' => 'gl'],
        ['title' => 'Georgian', 'id' => 'ka'],
        ['title' => 'German', 'id' => 'de'],
        ['title' => 'Greek, Modern', 'id' => 'el'],
        ['title' => 'Guaraní', 'id' => 'gn'],
        ['title' => 'Gujarati', 'id' => 'gu'],
        ['title' => 'Haitian; Haitian Creole', 'id' => 'ht'],
        ['title' => 'Hausa', 'id' => 'ha'],
        ['title' => 'Hebrew (modern)', 'id' => 'he'],
        ['title' => 'Herero', 'id' => 'hz'],
        ['title' => 'Hindi', 'id' => 'hi'],
        ['title' => 'Hiri Motu', 'id' => 'ho'],
        ['title' => 'Hungarian', 'id' => 'hu'],
        ['title' => 'Interlingua', 'id' => 'ia'],
        ['title' => 'Indonesian', 'id' => 'id'],
        ['title' => 'Interlingue', 'id' => 'ie'],
        ['title' => 'Irish', 'id' => 'ga'],
        ['title' => 'Igbo', 'id' => 'ig'],
        ['title' => 'Inupiaq', 'id' => 'ik'],
        ['title' => 'Ido', 'id' => 'io'],
        ['title' => 'Icelandic', 'id' => 'is'],
        ['title' => 'Italian', 'id' => 'it'],
        ['title' => 'Inuktitut', 'id' => 'iu'],
        ['title' => 'Japanese', 'id' => 'ja'],
        ['title' => 'Javanese', 'id' => 'jv'],
        ['title' => 'Kalaallisut, Greenlandic', 'id' => 'kl'],
        ['title' => 'Kannada', 'id' => 'kn'],
        ['title' => 'Kanuri', 'id' => 'kr'],
        ['title' => 'Kashmiri', 'id' => 'ks'],
        ['title' => 'Kazakh', 'id' => 'kk'],
        ['title' => 'Khmer', 'id' => 'km'],
        ['title' => 'Kikuyu, Gikuyu', 'id' => 'ki'],
        ['title' => 'Kinyarwanda', 'id' => 'rw'],
        ['title' => 'Kirghiz, Kyrgyz', 'id' => 'ky'],
        ['title' => 'Komi', 'id' => 'kv'],
        ['title' => 'Kongo', 'id' => 'kg'],
        ['title' => 'Korean', 'id' => 'ko'],
        ['title' => 'Kurdish', 'id' => 'ku'],
        ['title' => 'Kwanyama, Kuanyama', 'id' => 'kj'],
        ['title' => 'Latin', 'id' => 'la'],
        ['title' => 'Luxembourgish, Letzeburgesch', 'id' => 'lb'],
        ['title' => 'Luganda', 'id' => 'lg'],
        ['title' => 'Limburgish, Limburgan, Limburger', 'id' => 'li'],
        ['title' => 'Lingala', 'id' => 'ln'],
        ['title' => 'Lao', 'id' => 'lo'],
        ['title' => 'Lithuanian', 'id' => 'lt'],
        ['title' => 'Luba-Katanga', 'id' => 'lu'],
        ['title' => 'Latvian', 'id' => 'lv'],
        ['title' => 'Manx', 'id' => 'gv'],
        ['title' => 'Macedonian', 'id' => 'mk'],
        ['title' => 'Malagasy', 'id' => 'mg'],
        ['title' => 'Malay', 'id' => 'ms'],
        ['title' => 'Malayalam', 'id' => 'ml'],
        ['title' => 'Maltese', 'id' => 'mt'],
        ['title' => 'Māori', 'id' => 'mi'],
        ['title' => 'Marathi (Marāṭhī)', 'id' => 'mr'],
        ['title' => 'Marshallese', 'id' => 'mh'],
        ['title' => 'Mongolian', 'id' => 'mn'],
        ['title' => 'Nauru', 'id' => 'na'],
        ['title' => 'Navajo, Navaho', 'id' => 'nv'],
        ['title' => 'Norwegian Bokmål', 'id' => 'nb'],
        ['title' => 'North Ndebele', 'id' => 'nd'],
        ['title' => 'Nepali', 'id' => 'ne'],
        ['title' => 'Ndonga', 'id' => 'ng'],
        ['title' => 'Norwegian Nynorsk', 'id' => 'nn'],
        ['title' => 'Norwegian', 'id' => 'no'],
        ['title' => 'Nuosu', 'id' => 'ii'],
        ['title' => 'South Ndebele', 'id' => 'nr'],
        ['title' => 'Occitan', 'id' => 'oc'],
        ['title' => 'Ojibwe, Ojibwa', 'id' => 'oj'],
        ['title' => 'Oromo', 'id' => 'om'],
        ['title' => 'Oriya', 'id' => 'or'],
        ['title' => 'Ossetian, Ossetic', 'id' => 'os'],
        ['title' => 'Panjabi, Punjabi', 'id' => 'pa'],
        ['title' => 'Pāli', 'id' => 'pi'],
        ['title' => 'Persian', 'id' => 'fa'],
        ['title' => 'Polish', 'id' => 'pl'],
        ['title' => 'Pashto, Pushto', 'id' => 'ps'],
        ['title' => 'Portuguese', 'id' => 'pt'],
        ['title' => 'Quechua', 'id' => 'qu'],
        ['title' => 'Romansh', 'id' => 'rm'],
        ['title' => 'Kirundi', 'id' => 'rn'],
        ['title' => 'Romanian, Moldavian, Moldovan', 'id' => 'ro'],
        ['title' => 'Russian', 'id' => 'ru'],
        ['title' => 'Sanskrit (Saṁskṛta)', 'id' => 'sa'],
        ['title' => 'Sardinian', 'id' => 'sc'],
        ['title' => 'Sindhi', 'id' => 'sd'],
        ['title' => 'Northern Sami', 'id' => 'se'],
        ['title' => 'Samoan', 'id' => 'sm'],
        ['title' => 'Sango', 'id' => 'sg'],
        ['title' => 'Serbian', 'id' => 'sr'],
        ['title' => 'Scottish Gaelic; Gaelic', 'id' => 'gd'],
        ['title' => 'Shona', 'id' => 'sn'],
        ['title' => 'Sinhala, Sinhalese', 'id' => 'si'],
        ['title' => 'Slovak', 'id' => 'sk'],
        ['title' => 'Slovene', 'id' => 'sl'],
        ['title' => 'Somali', 'id' => 'so'],
        ['title' => 'Southern Sotho', 'id' => 'st'],
        ['title' => 'Spanish; Castilian', 'id' => 'es'],
        ['title' => 'Sundanese', 'id' => 'su'],
        ['title' => 'Swahili', 'id' => 'sw'],
        ['title' => 'Swati', 'id' => 'ss'],
        ['title' => 'Swedish', 'id' => 'sv'],
        ['title' => 'Tamil', 'id' => 'ta'],
        ['title' => 'Telugu', 'id' => 'te'],
        ['title' => 'Tajik', 'id' => 'tg'],
        ['title' => 'Thai', 'id' => 'th'],
        ['title' => 'Tigrinya', 'id' => 'ti'],
        ['title' => 'Tibetan Standard, Tibetan, Central', 'id' => 'bo'],
        ['title' => 'Turkmen', 'id' => 'tk'],
        ['title' => 'Tagalog', 'id' => 'tl'],
        ['title' => 'Tswana', 'id' => 'tn'],
        ['title' => 'Tonga (Tonga Islands)', 'id' => 'to'],
        ['title' => 'Turkish', 'id' => 'tr'],
        ['title' => 'Tsonga', 'id' => 'ts'],
        ['title' => 'Tatar', 'id' => 'tt'],
        ['title' => 'Twi', 'id' => 'tw'],
        ['title' => 'Tahitian', 'id' => 'ty'],
        ['title' => 'Uighur, Uyghur', 'id' => 'ug'],
        ['title' => 'Ukrainian', 'id' => 'uk'],
        ['title' => 'Urdu', 'id' => 'ur'],
        ['title' => 'Uzbek', 'id' => 'uz'],
        ['title' => 'Venda', 'id' => 've'],
        ['title' => 'Vietnamese', 'id' => 'vi'],
        ['title' => 'Volapük', 'id' => 'vo'],
        ['title' => 'Walloon', 'id' => 'wa'],
        ['title' => 'Welsh', 'id' => 'cy'],
        ['title' => 'Wolof', 'id' => 'wo'],
        ['title' => 'Western Frisian', 'id' => 'fy'],
        ['title' => 'Xhosa', 'id' => 'xh'],
        ['title' => 'Yiddish', 'id' => 'yi'],
        ['title' => 'Yoruba', 'id' => 'yo'],
        ['title' => 'Zhuang, Chuang', 'id' => 'za']
    ];
    if (!empty($ids)) {
        $language = collect($language)->whereIn('id', $ids)->values();
    }
    return $language;
}

function flattenToMultiDimensional(array $array, $delimiter = '.')
{
    $result = [];
    foreach ($array as $notations => $value) {
        // extract keys
        $keys = explode($delimiter, $notations);
        // reverse keys for assignments
        $keys = array_reverse($keys);

        // set initial value
        $lastVal = $value;
        foreach ($keys as $key) {
            // wrap value with key over each iteration
            $lastVal = [
                $key => $lastVal
            ];
        }

        // merge result
        $result = array_merge_recursive($result, $lastVal);
    }

    return $result;
}

// function createLangFile($lang=''){
//     $langDir = resource_path().'/lang/';
//     $enDir = $langDir.'en';
//     $currentLang = $langDir . $lang;
//     if(!File::exists($currentLang)){
//        File::makeDirectory($currentLang);
//        File::copyDirectory($enDir,$currentLang);
//     }
// }
function createLangFile($languages = [])
{
    $langDir = resource_path('lang/');
    $enDir = $langDir . 'en';
    foreach ($languages as $lang) {
        $currentLangDir = $langDir . $lang;
        if (!File::exists($currentLangDir)) {
            File::makeDirectory($currentLangDir, 0755, true);
            File::copyDirectory($enDir, $currentLangDir);
        }
    }
}
function deleteLangFile($selectedLanguages)
{
    $langDir = resource_path('lang/');
    $allDirs = File::directories($langDir);

    foreach ($allDirs as $dir) {
        $dirName = basename($dir);
        if (!in_array($dirName, $selectedLanguages)) {
            File::deleteDirectory($dir);
        }
    }
}

// function convertToHoursMins($time, $format = '%02d:%02d') {
//     if ($time < 1) {
//         return sprintf($format, 0, 0);
//     }
//     $hours = floor($time / 60);
//     $minutes = ($time % 60);
//     return sprintf($format, $hours, $minutes);
// }

function convertToHoursMins($time, $format = '%02d:%02d:%02d')
{
    if ($time < 1) {
        return sprintf($format, 0, 0, 0);
    }

    // duration_diff is now stored in seconds, so use it directly
    $timeInSeconds = $time;
    
    $hours = floor($timeInSeconds / 3600); // Total hours
    $minutes = floor(($timeInSeconds % 3600) / 60); // Remaining minutes after hours
    $seconds = $timeInSeconds % 60; // Remaining seconds after minutes

    return sprintf($format, $hours, $minutes, $seconds);
}

function getSettingKeyValue($key = "", $radius_type = "")
{
    $setting_data = \App\Models\Setting::where('key', $key)->first();
    $radious_distance = $setting_data ? json_decode($setting_data->value) : null;
    $radious = $radious_distance->radious;
    $distance_type = $radious_distance->distance_type;

    if ($radious_distance != null) {
        switch ($radius_type) {
            case 'distance_type':
                return $distance_type;
            case 'radious':
                return $radious;
            default:
                return getDefaultSetting($radius_type);
        }
    } else {

        switch ($radius_type) {
            case 'distance_type':
                return 'km';
                break;
            case 'radious':
                return 50;
                break;
            default:
                break;
        }
    }
}

function countUnitvalue($unit)
{
    switch ($unit) {
        case 'mile':
            return 3956;
            break;
        default:
            return 6371;
            break;
    }
}

function imageExtention($media)
{
    $extention = null;
    if ($media != null) {
        $path_info = pathinfo($media);
        $extention = $path_info['extension'];
    }
    return $extention;
}

function verify_provider_document($provider_id)
{
    $documents = \App\Models\Documents::where('is_required', 1)->where('status', 1)->withCount([
        'providerDocument',
        'providerDocument as is_verified_document' => function ($query) use ($provider_id) {
            $query->where('is_verified', 1)->where('provider_id', $provider_id);
        }
    ])
        ->get();

    $is_verified = $documents->where('is_verified_document', 1);

    if (count($documents) == count($is_verified)) {
        return true;
    } else {
        return false;
    }
}

function calculate_commission($total_amount = 0, $provider_commission = 0, $commission_type = 'percent', $type = '', $totalEarning = 0, $count = 0)
{
    if ($total_amount === 0) {
        return [
            'value' => '-',
            'number_format' => 0
        ];
    }
    switch ($type) {
        case 'provider':
            $earning = ($provider_commission * $count);
            if ($commission_type === 'percent') {
                $earning =  ($total_amount) * $provider_commission / 100;
            }
            $final_amount = $earning - $totalEarning;

            if (abs($final_amount) < 1) { // treat values less than 0.0001 as 0
                $final_amount = 0;
            }


            break;
        default:
            $earning = $total_amount - $provider_commission * $count;
            if ($commission_type === 'percent') {
                $earning = ($total_amount) * (100 - $provider_commission) / 100;
            }
            $final_amount = $earning;
            break;
    }
    return [
        'value' => getPriceFormat($final_amount),
        'number_format' => $final_amount
    ];
}

function get_provider_commission($bookings)
{
    $all_booking_total = $bookings->map(function ($booking) {
        return $booking->total_amount;
    })->toArray();

    $all_booking_tax = $bookings->map(function ($booking) {
        return $booking->getTaxesValue();
    })->toArray();

    $total = array_reduce($all_booking_total, function ($value1, $value2) {
        return $value1 + $value2;
    }, 0);

    $tax = array_reduce($all_booking_tax, function ($tax1, $tax2) {
        return $tax1 + $tax2;
    }, 0);

    $total_amount = $total;

    return [
        'total_amount' => $total_amount,
        'tax' => $tax,
        'total' => $total,
        'all_booking_tax' => $all_booking_tax,
        'all_booking_total' => $all_booking_total,
    ];
}

function get_handyman_provider_commission($handyman_id)
{
    $hadnymantype_id = !empty($handyman_id) ? $handyman_id : 1;
    $get_commission = \App\Models\HandymanType::withTrashed()->where('id', $hadnymantype_id)->first();
    if ($get_commission) {
        $commission_value = $get_commission->commission;
        $commission_type = $get_commission->type;

        $commission = getPriceFormat($commission_value);
        if ($commission_type === 'percent') {
            $commission = $commission_value . '%';
        }

        return $commission;
    }
    return '-';
}

function adminEarning()
{
    // Get commission earnings grouped by month
    $commissionData = \App\Models\CommissionEarning::selectRaw('sum(commission_amount) as total, DATE_FORMAT(updated_at, "%m") as month')
        ->whereYear('updated_at', date('Y'))
        ->whereIn('commission_status', ['paid', 'unpaid'])
        ->groupBy('month')
        ->get()
        ->keyBy('month')
        ->toArray();

    // Get cancellation charges grouped by month
    $cancellationData = \App\Models\Booking::selectRaw('sum(cancellation_charge_amount) as total, DATE_FORMAT(updated_at, "%m") as month')
        ->whereYear('updated_at', date('Y'))
        ->where('status', 'cancelled')
        ->groupBy('month')
        ->get()
        ->keyBy('month')
        ->toArray();

    $total_subscription_amout_data = \App\Models\SubscriptionTransaction::selectRaw('sum(amount) as total, DATE_FORMAT(updated_at, "%m") as month')
        ->whereYear('updated_at', date('Y'))
        ->where('payment_status', 'paid')
        ->groupBy('month')
        ->get()
        ->keyBy('month')
        ->toArray();

    // Get promotional banner earnings grouped by month
    $promotionalBannerData = \App\Models\PromotionalBanner::selectRaw('sum(total_amount) as total, DATE_FORMAT(start_date, "%m") as month')
        ->whereYear('start_date', date('Y'))
        ->where('payment_status', 'paid') // Assuming only paid banners count in earnings
        ->groupBy('month')
        ->get()
        ->keyBy('month')
        ->toArray();

    // Prepare data for the graph
    $data['revenueData'] = [];
    $data['revenueLabelData'] = [];

    for ($i = 1; $i <= 12; $i++) {
        $month = str_pad($i, 2, '0', STR_PAD_LEFT); // Format month as two digits
        $commission = $commissionData[$month]['total'] ?? 0;
        $cancellation = $cancellationData[$month]['total'] ?? 0;
        $total_subscription_amout = $total_subscription_amout_data[$month]['total'] ?? 0;
        $promotional = $promotionalBannerData[$month]['total'] ?? 0;

        // Add all earnings including promotional banners
        $data['revenueData'][] = $commission + $cancellation + $total_subscription_amout + $promotional;
        $data['revenueLabelData'][] = $month;
    }

    return $data['revenueData'];
}



function getTimeZone()
{
    $timezone = \App\Models\AppSetting::first();
    return $timezone->time_zone ?? 'UTC';
}

function get_plan_expiration_date($plan_start_date = '', $plan_type = '', $left_days = 0, $plan_duration = 1)
{
    $start_at = new \Carbon\Carbon($plan_start_date);
    $end_date = '';
    if ($plan_type === 'weekly') {
        // Weekly plan: duration is in weeks, convert to days (1 week = 7 days)
        $days = ((int)$plan_duration * 7); // + (int)$left_days;
        $end_date = $start_at->addDays($days);
    }
    if ($plan_type === 'monthly') {
        $end_date = $start_at->addMonths((int)$plan_duration); //->addDays((int)$left_days)
    }
    if ($plan_type === 'yearly') {
        $end_date =  $start_at->addYears((int)$plan_duration); //->addDays((int)$left_days)
    }
    return $end_date->format('Y-m-d H:i:s');
}

// Follow change get latest any plan beacuse is_subscribe is remain 1 till plan not expire
function get_user_active_plan($user_id)
{
    $get_provider_plan  =  \App\Models\ProviderSubscription::where('user_id', $user_id)->orderBy('id', 'desc')->first();
    // $get_provider_plan  =  \App\Models\ProviderSubscription::where('user_id', $user_id)->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))->first();
    $activeplan = null;
    if (!empty($get_provider_plan)) {
        $activeplan = new App\Http\Resources\API\ProviderSubscribeResource($get_provider_plan);
    }
    return $activeplan;
}

//  This code comment after change follow of is_subscribed 0 when plan is expire not cancel time
function is_subscribed_user($user_id)
{   
    $value = \App\Models\User::where('id', $user_id)->value('is_subscribe');
    // $user_subscribed = \App\Models\ProviderSubscription::where('user_id', $user_id)->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))->first();
    // $value = 0;
    // if ($user_subscribed) {
    //     $value = 1;
    // }

    return $value ?? 0;
}

function check_days_left_plan($old_plan, $new_plan)
{
    $previous_plan_start = $old_plan->start_at;
    $previous_plan_end = new \Carbon\Carbon($old_plan->end_at);
    $new_plan_start = new \Carbon\Carbon(date('Y-m-d H:i:s'));
    $left_days = $previous_plan_end->diffInDays($new_plan_start);
    return $left_days;
}

function user_last_plan($user_id)
{
    $user_subscribed = \App\Models\ProviderSubscription::where('user_id', $user_id)
        ->where('status', config('constant.SUBSCRIPTION_STATUS.INACTIVE'))->orderBy('id', 'desc')->first();
    $inactivePlan = null;
    if (!empty($user_subscribed)) {
        $inactivePlan = new App\Http\Resources\API\ProviderSubscribeResource($user_subscribed);
    }
    return $inactivePlan;
}

function is_any_plan_active($user_id)
{
    // Check if user has active subscription OR cancelled subscription that hasn't expired yet
    $user_subscribed = \App\Models\ProviderSubscription::where('user_id', $user_id)
        ->where(function($query) {
            $query->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                  ->orWhere(function($q) {
                      $q->where('status', config('constant.SUBSCRIPTION_STATUS.CANCELLED'))
                        ->where('end_at', '>=', now()->format('Y-m-d'));
                  });
        })
        ->first();
    $value = 0;
    if ($user_subscribed) {
        $value = 1;
    }
    return $value;
}

function default_earning_type()
{
    $gettype = \App\Models\Setting::where('type', 'earning-setting')->where('key', 'earning-setting')->first();
    if ($gettype !== null) {
        $earningtype = $gettype->value ?? 'commission';
    } else {
        $earningtype = 'commission';
    }
    return $earningtype;
}

function is_auto_assign_free_plan_enabled()
{
    $setting = \App\Models\Setting::where('type', 'OTHER_SETTING')->where('key', 'OTHER_SETTING')->first();
    if ($setting) {
        $value = json_decode($setting->value, true);
        return isset($value['auto_assign_free_plan']) && $value['auto_assign_free_plan'] == 1;
    }
    return false;
}

function get_provider_plan_limit($provider_id, $type, $excluding_service_id = null)
{
    $limit_array = array();

    if (is_any_plan_active($provider_id) == 1) {
        $exceed = '';
        $get_current_plan = get_user_active_plan($provider_id);
        if ($get_current_plan->plan_type === 'limited') {
            $get_plan_limit = $get_current_plan->plan_limitation;
            $plan_start_date =  date('Y-m-d', strtotime($get_current_plan->start_at));

            if ($type === 'service') {
                $limit_array = $get_plan_limit['service'];
                // Count only ACTIVE services (status = 1)
                $provider_service_count = \App\Models\Service::where('provider_id', $provider_id)
                    ->where('status', 1)
                    ->whereDate('created_at', '>=', $plan_start_date)
                    ->count();
                if ($limit_array['is_checked'] == 'on' && $limit_array['limit'] != null) {
                    if ($provider_service_count >= $limit_array['limit']) {
                        $exceed = 1; // 1 for exceed limit;
                    }
                } elseif ($limit_array['is_checked'] === 'on' && $limit_array['limit'] == null) {
                    $exceed = 0;
                }
            }
            
            if ($type === 'featured_service') {
                // Check featured service limit separately
                $limit_array = $get_plan_limit['featured_service'] ?? $get_plan_limit['service'];
                
                \Log::info('Featured Service Validation - START', [
                    'provider_id' => $provider_id,
                    'type' => $type,
                    'limit_array' => $limit_array,
                    'is_checked' => $limit_array['is_checked'] ?? 'NOT SET',
                    'limit_value' => $limit_array['limit'] ?? 'NOT SET',
                    'limit_type' => gettype($limit_array['limit'] ?? null)
                ]);
                
                // Count ALL services with is_featured = 1, REGARDLESS of status
                // This is because inactive featured services still count toward the limit
                $query = \App\Models\Service::where('provider_id', $provider_id)
                    ->where('is_featured', 1)
                    ->whereDate('created_at', '>=', $plan_start_date);
                
                // Exclude current service if editing
                if ($excluding_service_id !== null) {
                    $query->where('id', '!=', $excluding_service_id);
                }
                
                $featured_service_count = $query->count();
                
                // Check if feature is enabled and has a valid limit
                // is_checked = 'off' OR limit = 0 OR limit = null means feature is NOT allowed
                if ($limit_array['is_checked'] === 'off' || empty($limit_array['is_checked'])) {
                    $exceed = 0; // 0 for not in plan (feature disabled)
                    \Log::info('Featured Service Validation - BLOCKED: is_checked=off', ['exceed' => $exceed]);
                } elseif ($limit_array['is_checked'] == 'on' && ($limit_array['limit'] === null || $limit_array['limit'] === 0 || $limit_array['limit'] === '0')) {
                    $exceed = 0; // 0 for not in plan (limit is 0 or null means not allowed)
                    \Log::info('Featured Service Validation - BLOCKED: limit=0 or null', ['exceed' => $exceed, 'limit' => $limit_array['limit']]);
                } elseif ($limit_array['is_checked'] == 'on' && $limit_array['limit'] != null && $limit_array['limit'] > 0) {
                    if ($featured_service_count >= $limit_array['limit']) {
                        $exceed = 1; // 1 for exceed limit
                        \Log::info('Featured Service Validation - BLOCKED: limit exceeded', ['exceed' => $exceed, 'count' => $featured_service_count, 'limit' => $limit_array['limit']]);
                    } else {
                        \Log::info('Featured Service Validation - ALLOWED: within limit', ['count' => $featured_service_count, 'limit' => $limit_array['limit']]);
                    }
                } else {
                    \Log::info('Featured Service Validation - NO MATCH', ['is_checked' => $limit_array['is_checked'], 'limit' => $limit_array['limit']]);
                }
                
                \Log::info('Featured Service Validation - END', ['exceed' => $exceed ?? 'NOT SET']);
            }
            
            if ($type === 'handyman') {
                $limit_array = $get_plan_limit['handyman'];
                $handyman_count = \App\Models\User::where('provider_id', $provider_id)->whereDate('created_at', '>=', $plan_start_date)->count();
                if ($limit_array['is_checked'] == 'on' && $limit_array['limit'] != null) {
                    if ($handyman_count >= (int)$limit_array['limit']) {
                        $exceed = 1; // 1 for exceed limit;
                    }
                } elseif ($limit_array['is_checked'] === 'on' && $limit_array['limit'] == null) {
                    $exceed = 0;
                }
            }
        } else {
            return;
        }
    } else {
        return 1;
    }
    return $exceed;
}

function sendNotification($type, $user, $data)
{

    $othersetting = \App\Models\Setting::where('type', 'OTHER_SETTING')->first();

    $decodedata = $othersetting ? json_decode($othersetting['value']) : null;
    $firebase_notification = $decodedata->firebase_notification;

    if ($firebase_notification == 1) {

        $projectID = isset($decodedata->project_id) ? $decodedata->project_id : null;

        $apiUrl = 'https://fcm.googleapis.com/v1/projects/' . $projectID . '/messages:send';
        $access_token = getAccessToken();
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ];

        $heading   = '#' . $data['id'] . ' ' . str_replace("_", " ", $data['subject']);
        $content   = $data['message'];


        $firebase_data = [
            'topic' => 'user_' . $user->id,
            'collapse_key' => 'type_a',
            'notification' => [
                'body' =>   $content,
                'title' => $heading,
            ],
            'data' => [
                'type' => $data['type'],
                'id' => $data['id']
            ],
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firebase_data));

        $response = curl_exec($ch);

        curl_close($ch);
    }
    $childData = array(
        "id" => $data['id'],
        "type" => $data['type'],
        "subject" => $data['subject'],
        "message" => $data['message'],
        'notification-type' => $data['notification-type']
    );

    $notification = \App\Models\Notification::create(
        array(
            'id' => Illuminate\Support\Str::random(32),
            'type' => $data['type'],
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => json_encode($childData)
        )
    );
}

// function getServiceTimeSlot($provider_id){
//     $sitesetup = App\Models\Setting::where('type','site-setup')->where('key', 'site-setup')->first();
//     $admin = json_decode($sitesetup->value);
//     date_default_timezone_set($admin->time_zone ?? 'UTC');

//     $current_time = \Carbon\Carbon::now();
//     $time = $current_time->toTimeString();
//     $current_day = strtolower(date('D'));

//     $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

//     $handyman_count = \App\Models\User::where('provider_id', $provider_id)->where('is_available', 1)->count() + 1;

//     $providerSlots = \App\Models\ProviderSlotMapping::where('provider_id', $provider_id)
//         ->whereIn('days', $days)
//         ->orderBy('start_at', 'asc')
//         ->get();

//     $bookings = \App\Models\Booking::where('provider_id', $provider_id)->get();
//     $booking_count = count($bookings);

//     $slotsArray = [];

//     foreach ($days as $value) {
//         $slot = $providerSlots->where('days', $value);

//         if ($current_day === $value) {
//             $slot = $slot->where('start_at', '>', $time);
//         }

//         $filteredSlots = $slot->pluck('start_at')->toArray();

//         if ($handyman_count == $booking_count) {
//             $filteredSlots = array_diff($filteredSlots, $bookings->pluck('start_at')->toArray());
//         }

//         $obj = [
//             "day" => $value,
//             "slot" => $filteredSlots,
//         ];

//         array_push($slotsArray, $obj);
//     }

//     return $slotsArray;
// }
function getServiceTimeSlot($provider_id)
{

    $sitesetup = App\Models\Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
    $admin = json_decode($sitesetup->value);
    date_default_timezone_set($admin->time_zone ?? 'UTC');

    $current_time = \Carbon\Carbon::now();
    $time = $current_time->toTimeString();
    $current_day = strtolower(date('D'));

    $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    $handyman_count = \App\Models\User::where('provider_id', $provider_id)->where('is_available', 1)->count() + 1;

    $providerSlots = \App\Models\ProviderSlotMapping::where('provider_id', $provider_id)
        ->whereIn('days', $days)
        ->orderBy('start_at', 'asc')
        ->get();
    $slotsArray = [];

    $bookings = \App\Models\Booking::where('provider_id', $provider_id)
        ->where('date', '>', \Carbon\Carbon::today())
        ->where('status', '!=', 'cancelled')
        ->get(['date', 'booking_slot', 'id']);
    $groupedBySlot = $bookings->groupBy('booking_slot');

    foreach ($days as $value) {
        $bookingsOnDaySlot = 0;
        $results = [];
        $slot = $providerSlots->where('days', $value);
        if ($current_day === $value) {
            $slot = $slot->where('start_at', '>', $time);
        }

        $filteredSlots = $slot->pluck('start_at')->toArray();
        foreach ($groupedBySlot as $slot => $bookingsInSlot) {
            $groupedByDay = $bookingsInSlot->groupBy(function ($booking) {
                return \Carbon\Carbon::parse($booking->date)->format('D');
            });
            $dayCounts = [];
            $carbonDayFormat = ucfirst($value); {
                foreach ($groupedByDay as $day => $bookingsOnDay) {
                    if ($day == $carbonDayFormat) {
                        $bookingsOnDaySlot = $bookingsOnDay->count();
                        // $dayCounts[$day] = [
                        //     'count' => $bookingsOnDay->count(),
                        //     'day' => $value
                        // ];
                        // $results[$slot] = $dayCounts;
                        if ($handyman_count <= $bookingsOnDaySlot) {
                            $filteredSlots = array_diff($filteredSlots, (array) $slot);
                        }
                    }
                }
            }
        }
        $obj = [
            "day" => $value,
            "slot" => $filteredSlots,
        ];

        array_push($slotsArray, $obj);
    }
    return $slotsArray;
}

function bookingstatus($status)
{
    switch ($status) {
        case 'Pending':
            $html = '<span class="badge text-warning bg-warning-subtle ">' . $status . '</span>';

            break;

        case 'Accepted':
            $html = '<span class="badge text-success bg-success-subtle">' . $status . '</span>';

            break;


        case 'Ongoing':
            $html = '<span class="badge text-warning bg-warning-subtle">' . $status . '</span>';

            break;

        case 'In Progress':
            $html = '<span class="badge text-info bg-info-subtle">' . $status . '</span>';

            break;

        case 'Hold':
            $html = '<span class="badge text-dark bg-dark-subtle text-white">' . $status . '</span>';

            break;

        case 'Cancelled':
            $html = '<span class="badge text-dark bg-light border-dark">' . $status . '</span>';

            break;

        case 'Rejected':
            $html = '<span class="badge text-dark bg-dark-subtle border-dark">' . $status . '</span>';

            break;

        case 'Completed':
            $html = '<span class="badge text-success bg-success-subtle">' . $status . '</span>';

            break;

        default:
            $html = '<span class="badge text-danger bg-danger-subtle">' . $status . '</span>';
            break;
    }
    return $html;
}

function total_cash_in_hand($user_id)
{
    $amount = 0;

    // Get the first role of the user
    $role = auth()->user()->getRoleNames()->first();
    $payment_history = App\Models\PaymentHistory::query();
    // Only proceed if the role is handyman or provider
    if (in_array($role, ['handyman', 'provider'])) {

        // Define role-specific actions and exclusion logic
        $validActions = $role === 'handyman'
            ? ['handyman_approved_cash', 'handyman_send_provider']
            : ['provider_approved_cash', 'provider_send_admin'];

        $excludeAction = $role === 'handyman'
            ? 'provider_approved_cash'
            : 'admin_approved_cash';
        // Base query for payment history
        $amount = $payment_history->where('receiver_id', $user_id)
            ->whereIn('action', $validActions)
            ->whereNotIn('booking_id', function ($subQuery) use ($excludeAction, $user_id) {
                $subQuery->select('booking_id')
                    ->from('payment_histories')
                    ->where('action', $excludeAction)
                    ->where('sender_id', $user_id);
            })
            ->sum('total_amount'); // Sum the valid total amounts
    }

    return $amount;
}

function admin_id()
{
    $user = \App\Models\User::getUserByKeyValue('user_type', 'admin');
    return $user->id;
}

function get_user_name($user_id)
{
    $name = '';
    $user = \App\Models\User::getUserByKeyValue('id', $user_id);
    if ($user !== null) {
        $name = $user->display_name;
    }
    return $name;
}

function set_admin_approved_cash($payment_id)
{
    $payment_status_check =  \App\Models\PaymentHistory::where('payment_id', $payment_id)
        ->where('action', 'provider_send_admin')->where('status', 'pending_by_admin')->first();
    if ($payment_status_check !== null) {
        $status = '<a class="btn-sm text-white btn-success "  href=' . route('cash.approve', $payment_id) . '><i class="fa fa-check"></i>Approve</a>';
    } else {
        $status = '-';
    }
    return $status;
}

function last_status($payment_id)
{
    $payment_status_check =  \App\Models\PaymentHistory::orderBy('id', 'desc')->where('payment_id', $payment_id)->first();
    if ($payment_status_check !== null) {
        $status = '<span class="text-center badge bg-success-subtle">' . str_replace('_', " ", ucfirst($payment_status_check->status)) . '</span>';
    } else {
        $status = '<span class="text-center d-block">-</span>';
    }
    return $status;
}

/**
 * Human-readable payment status for booking UIs. Cash flows store granular state on payment_histories
 * (pending_by_provider / pending_by_handyman / pending_by_admin) while payments.payment_status may stay "pending".
 */
function booking_payment_status_label($payment, $booking = null): string
{
    // Check if service is free by checking booking->service->price
    if ($booking && $booking->service && $booking->service->price == 0) {
        return __('messages.free');
    }

    // Check if booking total_amount is 0 (free service)
    if ($booking && $booking->total_amount == 0) {
        return __('messages.free');
    }

    if (!$payment) {
        return __('messages.pending');
    }

    // Check payment total_amount if no booking provided
    if (!$booking && $payment->total_amount == 0) {
        return __('messages.free');
    }

    if (empty($payment->payment_status)) {
        return __('messages.pending');
    }

    $status = $payment->payment_status;

    if ($status === 'paid') {
        return __('messages.paid');
    }
    if ($status === 'advanced_paid') {
        return __('messages.advanced_paid');
    }
    if ($status === 'failed') {
        return __('messages.failed');
    }
    if ($status === 'Advanced Refund') {
        return $status;
    }

    $type = strtolower((string) ($payment->payment_type ?? ''));
    if ($type === 'cash' && !in_array($status, ['paid', 'failed'], true)) {
        $latestCashFlowRow = \App\Models\PaymentHistory::where('payment_id', $payment->id)
            ->whereIn('status', [
                'pending_by_provider',
                'pending_by_handyman',
                'pending_by_admin',
                'approved_by_handyman',
                'approved_by_provider',
                'approved_by_admin',
            ])
            ->orderByDesc('id')
            ->first();
        if ($latestCashFlowRow !== null) {
            return __('messages.' . $latestCashFlowRow->status);
        }
    }

    if (in_array($status, [
        'pending_by_provider',
        'pending_by_handyman',
        'pending_by_admin',
        'approved_by_handyman',
        'approved_by_provider',
        'approved_by_admin',
    ], true)) {
        return __('messages.' . $status);
    }

    if ($status === 'pending') {
        return __('messages.pending');
    }

    return str_replace('_', ' ', ucfirst((string) $status));
}

function providerpayout_rezopayX($data)
{

    $rezorpay_data = \App\Models\PaymentGateway::where('type', 'razorPayX')->first();


    if ($rezorpay_data) {

        $is_test = $rezorpay_data['is_test'];

        if ($is_test == 1) {

            $json_data = $rezorpay_data['value'];
        } else {

            $json_data = $rezorpay_data['live_value'];
        }
        $setting = App\Models\Setting::getValueByKey('site-setup', 'site-setup');
        // $sitesetup = App\Models\Setting::where('type','site-setup')->where('key', 'site-setup')->first();
        // $sitesetupdata = $sitesetup ? json_decode($sitesetup->value) : null;

        $currency_country_id = $setting ? $setting->default_currency : "231";

        $country_data = \App\Models\Country::where('id', $currency_country_id)->first();

        $currency = $country_data['currency_code'];

        $razopayX_credentials = json_decode($json_data, true);

        $url = $razopayX_credentials['razorx_url'];
        $key = $razopayX_credentials['razorx_key'];
        $secret = $razopayX_credentials['razorx_secret'];
        $RazorpayXaccount = $razopayX_credentials['razorx_account'];


        $provider_id = isset($data['provider_id']) ? $data['provider_id'] : (isset($data['user_id']) ? $data['user_id'] : null);
        $payout_amount = $data['amount'];

        $bank_id = $data['bank'];

        $providers_details = \App\Models\User::where('id', $provider_id)->first();

        $email = $providers_details['email'];
        $first_name = $providers_details['first_name'];
        $last_name = $providers_details['last_name'];
        $contact_number = $providers_details['contact_number'];
        $user_type = $providers_details['user_type'];

        $bank_details = \App\Models\Bank::where('id', $bank_id)->first();

        $bank_name = $bank_details['bank_name'];
        $account_number = $bank_details['account_no'];
        $ifsc = $bank_details['ifsc_no'];

        $payout_data = array(
            "account_number" => $RazorpayXaccount,
            "amount" => (int)$payout_amount * 100,
            "currency" => $currency,
            "mode" => "NEFT",
            "purpose" => "payout",
            "fund_account" => array(
                "account_type" => "bank_account",
                "bank_account" => array(
                    "name" => $first_name . $last_name,
                    "ifsc" => $ifsc,
                    "account_number" => $account_number
                ),
                "contact" => array(
                    "name" => $first_name . $last_name,
                    "email" =>  $email,
                    "contact" => $contact_number,
                    "type" => "vendor",
                )
            ),
            "queue_if_low_balance" => true,

        );

        // Convert data to JSON
        $json_data = json_encode($payout_data);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($key . ':' . $secret)
        ));

        $response = curl_exec($ch);

        return $response;
    } else {

        return $response = '';
    }
}

function providerpayout_stripe($data)
{
 
    //Stripe Payment

    $stripe_data = \App\Models\PaymentGateway::where('type', 'stripe')->first();

    if ($stripe_data->value != null) {

        $is_test = $stripe_data['is_test'];

        if ($is_test == 1) {

            $json_data = $stripe_data['value'];
        } else {

            $json_data = $stripe_data['live_value'];
        }

        $stripe_credentials = json_decode($json_data, true);

        $secret_key = $stripe_credentials['stripe_key'];

        $setting = App\Models\Setting::getValueByKey('site-setup', 'site-setup');
        // $sitesetup = App\Models\Setting::where('type','site-setup')->where('key', 'site-setup')->first();
        // $sitesetupdata = $sitesetup ? json_decode($sitesetup->value) : null;

        $currency_country_id = $setting ? $setting->default_currency : "231";

        $country_data = \App\Models\Country::where('id', $currency_country_id)->first();

        $country = $country_data['code'];

        $currency = strtolower($country_data['currency_code']);


        $provider_id = $data['provider_id'];
        $payout_amount = $data['amount'];
        $bank_id = $data['bank'];

        $bank_details = \App\Models\Bank::where('id', $bank_id)->first();

        $bank_name = $bank_details['bank_name'];
        $account_number = $bank_details['account_no'];
        $ifsc = $bank_details['ifsc_no'];
        $stripe_account = $bank_details['stripe_account'];

        if ($stripe_account == '') {

            $providers_details = \App\Models\User::where('id', $provider_id)->first();
            $email = $providers_details['email'];
            $first_name = $providers_details['first_name'];
            $last_name = $providers_details['last_name'];
            $contact_number = $providers_details['contact_number'];
            $user_type = $providers_details['user_type'];

            $current_datetime = time();

            $ip_address = file_get_contents('https://api.ipify.org');

            try {

                $stripe = new \Stripe\StripeClient($secret_key);

                $stripedata = $stripe->accounts->create(
                    [
                        'country' => $country,
                        'type' => 'custom',
                        'bank_account' => [
                            'account_number' => $account_number,
                            'country' => $country,
                            'account_holder_name' => $first_name . $last_name,
                            'routing_number' => $ifsc
                        ],

                        'capabilities' => [
                            'transfers' => [
                                'requested' => true
                            ]
                        ],
                        'business_type' => 'individual',
                        'country' => $country,
                        'email' => $email,
                        'individual' => [
                            'first_name' => $first_name,
                            'last_name' => $last_name
                        ],
                        'business_profile' => [
                            'name' => $first_name . $last_name,
                            'url' => 'demo.com'
                        ],
                        'tos_acceptance' => [
                            'date' => $current_datetime,
                            'ip' => $ip_address
                        ]
                    ]
                );

                $stripe_account = $stripedata['id'];

                \App\Models\Bank::where('id', $bank_id)->update(['stripe_account' => $stripe_account]);
            } catch (Stripe\Exception\ApiErrorException $e) {

                $error = $e->getError();

                if ($error == '') {

                    return $response = '';
                } else {

                    $error['status'] = 400;

                    return $error;
                }
            }
        }

        $data = [

            'secret_key' => $secret_key,
            'amount' => $payout_amount,
            'currency' => $currency,
            'stripe_account' => $stripe_account
        ];



        $bank_transfer = create_stripe_transfer($data);

        return $bank_transfer;
    } else {

        return $response = '';
    }
}

function create_stripe_transfer($data)
{
    try {


        \Stripe\Stripe::setApiKey($data['secret_key']);

        $transfer = \Stripe\Transfer::create([
            "amount" => $data['amount'] * 100,
            "currency" =>  $data['currency'],
            "destination" => $data['stripe_account'],
        ]);

        $payout = create_bank_tranfer($data);

        return $payout;
    } catch (Stripe\Exception\ApiErrorException $e) {


        $error = $e->getError();

        $error['status'] = 400;

        if ($error == '') {

            return $response = '';
        } else {

            $error['status'] = 400;
            return $error;
        }
    }
}

function create_bank_tranfer($data)
{

    try {

        \Stripe\Stripe::setApiKey($data['secret_key']);

        $payout = \Stripe\Payout::create([
            'amount' => $data['amount'] * 100,
            'currency' => $data['currency'],
        ], [
            'stripe_account' => $data['stripe_account'],

        ]);

        return $payout;
    } catch (Stripe\Exception\ApiErrorException $e) {


        $error = $e->getError();


        if ($error == '') {

            return $response = '';
        } else {

            $error['status'] = 400;
            return $error;
        }
    }
}

function calculateReadingTime($content, $wpm = 100)
{
    $wordCount = str_word_count(strip_tags($content));

    $readingTime = intval($wordCount / $wpm);

    return $readingTime;
}

function formatCurrency($number, $noOfDecimal, $currencyPosition, $currencySymbol)
{

    $formattedNumber = number_format($number, $noOfDecimal, '.', '');
    $parts = explode('.', $formattedNumber);
    $integerPart = $parts[0];
    $decimalPart = isset($parts[1]) ? $parts[1] : '';

    $currencyString = '';

    if ($currencyPosition == 'left') {
        $currencyString .= $currencySymbol;

        $currencyString .= $integerPart;

        if ($noOfDecimal > 0) {
            $currencyString .= '.' . $decimalPart;
        }
    }

    if ($currencyPosition == 'right') {

        if ($noOfDecimal > 0) {
            $currencyString .= $integerPart . '.' . $decimalPart;
        }

        $currencyString .= $currencySymbol;
    }

    return $currencyString;
}

function  getPaymentMethodkey($type)
{

    $pyament_gateway = App\Models\PaymentGateway::query();

    $payment_geteway_value = null;

    switch ($type) {

        case 'stripe':

            $pyament_gateway_data = $pyament_gateway->where('type', $type)->first();

            if ($pyament_gateway_data) {

                if ($pyament_gateway_data->is_test == 1) {

                    $payment_geteway_value = json_decode($pyament_gateway_data->value, true);
                } else {

                    $payment_geteway_value = json_decode($pyament_gateway_data->live_value, true);
                }
            }

            break;
    }

    return $payment_geteway_value;
}

function getstripepayments($data)
{
    $baseURL = env('APP_URL');

    $stripe_key_data = getPaymentMethodkey($data['payment_type']);

    $stripe_secret = $stripe_key_data['stripe_key'];

    $booking = App\Models\Booking::where('id', $data['booking_id'])->with('service')->first();

    try {
        $stripe = new \Stripe\StripeClient($stripe_secret);
        $checkout_session = $stripe->checkout->sessions->create([

            'success_url' => $baseURL . '/save-stripe-payment/' . $data['booking_id'] . '?type=' . $data['type'],
            'payment_method_types' => ['card'],
            'billing_address_collection' => 'required',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $data['currency_code'],
                        'product_data' => [
                            'name' => $booking->service->name,
                        ],
                        'unit_amount' => $data['total_amount'] * 100,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
        ]);
    } catch (\Exception $e) {
        $message = $e->getMessage();

        $checkout_session = [
            'message' => $message,
            'status' => false,
        ];
    }

    return $checkout_session;
}

function getstripePaymnetId($stripe_session_id, $payment_type)
{
    $stripe_key_data = getPaymentMethodkey($payment_type);

    $stripe_secret = $stripe_key_data['stripe_key'];

    $stripe = new \Stripe\StripeClient($stripe_secret);
    $session_object = $stripe->checkout->sessions->retrieve($stripe_session_id, []);

    return $session_object;
}

function default_user_name()
{
    return __('messages.unknown_user');
}


function addWalletAmount($data)
{

    $baseURL = env('APP_URL');

    // Retrieve the Stripe secret key
    $stripe_key_data = getPaymentMethodkey($data['payment_type']);
    $stripe_secret = $stripe_key_data['stripe_key'];

    // Retrieve wallet details
    $wallet = App\Models\Wallet::where('user_id', $data['customer_id'])->first();

    try {
        // Create the Stripe checkout session
        $stripe = new \Stripe\StripeClient($stripe_secret);
        $checkout_session = $stripe->checkout->sessions->create([
            'success_url' => $baseURL . '/save-wallet-stripe-payment/' . $data['customer_id'] . '?amount=' . $data['amount'], // Use the route name
            'payment_method_types' => ['card'],
            'billing_address_collection' => 'required',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $data['currency_code'],
                        'unit_amount' => $data['amount'] * 100, // Amount in cents
                        'product_data' => [
                            'name' => 'Wallet Top-Up', // Change this if needed
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
        ]);
    } catch (\Exception $e) {
        // Handle exceptions
        $message = $e->getMessage();
        $checkout_session = [
            'message' => $message,
            'status' => false,
        ];
    }

    return $checkout_session;
}

function fcm($fields)
{
    $otherSetting = \App\Models\Setting::where('type', 'OTHER_SETTING')->first();
    $other = json_decode($otherSetting->value);
    $projectID = $other->project_id;
    $access_token = getAccessToken();

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ];
    $ch = curl_init('https://fcm.googleapis.com/v1/projects/' . $projectID . '/messages:send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    $response = curl_exec($ch);

    Log::info($response);
    curl_close($ch);
}

function getAccessToken()
{
    $directory = storage_path('app/data');
    $credentialsFiles = File::glob($directory . '/*.json');


    if (!empty($credentialsFiles)) {

        try {
            $client = new \Google\Client();
            $client->setAuthConfig($credentialsFiles[0]);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $token = $client->fetchAccessTokenWithAssertion();

            return $token['access_token'];
        } catch (\Exception $e) {

            return null;
        }
    } else {

        return null;
    }
}


function countrySymbol()
{
    $setting = App\Models\Setting::getValueByKey('site-setup', 'site-setup');
    // $sitesetup = App\Models\Setting::where('type','site-setup')->where('key', 'site-setup')->first();
    // $sitesetupdata = $sitesetup ? json_decode($sitesetup->value) : null;

    $currencyId = $setting ? $setting->default_currency : "231";
    $country = \App\Models\Country::find($currencyId);
    $symbol = '$';
    if (!empty($country)) {
        $symbol = $country->symbol;
    }
    return $symbol;
}
function provider_total_calculate($total_amount = 0, $provider_commission = 0, $commission_type = 'percent', $type = '', $totalEarning = 0, $count = 0)
{
    if ($total_amount === 0) {
        return [
            'value' => '-',
            'number_format' => 0
        ];
    }
    switch ($type) {
        case 'provider':
            // dump($provider_commission * $count);
            $earning =   ($total_amount) - ($provider_commission * $count);
            if ($commission_type === 'percent') {
                $earning = ($total_amount) * (100 - $provider_commission) / 100;
            }
            //   dump($earning);
            $final_amount = $earning;
            break;
    }
    return [
        'value' => getPriceFormat($final_amount),
        'number_format' => $final_amount
    ];
}

if (!function_exists('isActive')) {

    function isActive($route, $className = 'active')
    {
        $currentRoute = Route::currentRouteName();

        if (is_array($route)) {
            return in_array($currentRoute, $route) ? $className : '';
        }

        return $currentRoute == $route ? $className : '';
    }
}

function dbConnectionStatus(): bool
{
    try {
        DB::connection()->getPdo();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
function formatString($input)
{
    // Replace underscores with spaces, capitalize each word, and remove spaces
    return ucfirst(str_replace('_', ' ', $input));
}

if (!function_exists('getFooterSettings')) {
    function getFooterSettings()
    {
        $settings = \App\Models\Setting::whereIn('type', ['general-setting', 'social-media', 'site-setup'])
            ->whereIn('key', ['general-setting', 'social-media', 'site-setup'])
            ->get()
            ->keyBy('type');

        $generalSetting = $settings->has('general-setting') ? json_decode($settings['general-setting']->value) : null;
        $socialMedia = $settings->has('social-media') ? json_decode($settings['social-media']->value) : null;
        $appSetting = $settings->has('site-setup') ? json_decode($settings['site-setup']->value) : null;

        $copyrightText = $appSetting ? $appSetting->site_copyright : null;
        $position = strpos($copyrightText, 'by');
        $firstPart = $position !== false ? substr($copyrightText, 0, $position + 2) : $copyrightText;
        $secondPart = $position !== false ? substr($copyrightText, $position + 2) : '';
        $footerSection = App\Models\FrontendSetting::where('key', 'footer-setting')->first();
        $sectionData = $footerSection ? json_decode($footerSection->value, true) : null;
        $categories = [];
        $primary_locale = session()->get('locale', 'en');
        if (isset($sectionData['category_id']) && is_array($sectionData['category_id'])) {
            $categories = \App\Models\Category::whereIn('id', $sectionData['category_id'])
                ->where('status', 1)
                ->get();
            $categories->transform(function ($category) use ($primary_locale) {
                $category->name = $category->translations
                    ->firstWhere('locale', $primary_locale)?->value
                    ?? $category->translations->firstWhere('locale', 'en')?->value
                    ?? $category->name;

                return $category;
            });
        }
        if (isset($sectionData['service_id']) && is_array($sectionData['service_id'])) {
            $services = \App\Models\Service::whereIn('id', $sectionData['service_id'])
                ->where('status', 1)
                ->with('media');
            // Eager load the media relationship

            if (session()->has('user_lat') && session()->has('user_lng')) {
                $lat = $request->latitude ?? session('user_lat');
                $lng = $request->longitude ?? session('user_lng');
            }


            if (isset($lat) && !empty($lat) && isset($lng) && !empty($lng)) {



                $serviceZone = App\Models\ServiceZone::all();

                if (count($serviceZone) > 0) {

                    try {
                        $zoneTrait = new class {
                            use \App\Traits\ZoneTrait;
                        };
                        $matchingZoneIds = $zoneTrait->getMatchingZonesByLatLng($lat, $lng);



                        $services->whereHas('serviceZoneMapping', function ($services) use ($matchingZoneIds) {
                            $services->whereIn('zone_id', $matchingZoneIds);
                        });
                    } catch (\Exception $e) {
                        $services = $services;
                    }
                } else {

                    $get_distance = getSettingKeyValue('site-setup', 'radious') ?? 50;
                    $get_unit = getSettingKeyValue('site-setup', 'distance_type') ?? 'km';

                    $locations = $services->locationService($lat, $lng, $get_distance, $get_unit);
                    $service_in_location =  App\Models\ProviderServiceAddressMapping::whereIn('provider_address_id', $locations)->get()->pluck('service_id');
                    $services->with('providerServiceAddress')->whereIn('id', $service_in_location);
                }
            }

            $services = $services->get();


            $services->transform(function ($service) use ($primary_locale) {
                $service->name = $service->translations
                    ->firstWhere('locale', $primary_locale)?->value
                    ?? $service->translations->firstWhere('locale', 'en')?->value
                    ?? $service->name;

                return $service;
            });
        }
        return [
            'generalSetting' => $generalSetting,
            'socialMedia' => $socialMedia,
            'appSetting' => $appSetting,
            'copyright' => [
                'first_part' => $firstPart,
                'second_part' => $secondPart,
            ],
            'sectionData' => $sectionData,
            'categories' => $categories,
            'services' => $services,
        ];
    }

    function isProviderBannerEnabled(): bool
    {
        $setting = \App\Models\Setting::where('type', 'provider-banner')
            ->where('key', 'provider-banner')
            ->first();

        if (!$setting) {
            return false;
        }

        $settings = json_decode($setting->value, true);

        return isset($settings['promotion_enable']) && $settings['promotion_enable'] === 1;
    }
}


function getSettingValue($property)
{
    $setting = \App\Models\Setting::where('key', 'OTHER_SETTING')->first();
    if ($setting) {
        $data = json_decode($setting->value, true);
        return isset($data[$property]) ? $data[$property] : null;
    }
    return null;
}

/**
 * Get meta tags for the current page (category, subcategory, service, or fallback)
 * @return array
 */
function getMetaTagsForPage()
{
    $route = request()->route();
    $routeName = $route ? $route->getName() : null;
    $meta = [
        'meta_title' => config('app.name', 'Laravel'),
        'meta_description' => '',
        'meta_keywords' => '',
        'og_image' => '',
    ];
    $locale = session()->get('locale', 'en');

    $globalSeoSetting = \App\Models\SeoSetting::first();
    // dd($globalSeoSetting);
    $global = [
        'meta_title' => '',
        'meta_description' => '',
        'meta_keywords' => '',
        'og_image' => '',
    ];

    if ($globalSeoSetting) {
        $global['meta_title'] = $globalSeoSetting->translate('meta_title', $locale) ?: $globalSeoSetting->meta_title ?? '';
        $global['meta_description'] = $globalSeoSetting->translate('meta_description', $locale) ?: $globalSeoSetting->meta_description ?? '';
        $global['og_image'] = $globalSeoSetting->getFirstMediaUrl('seo_image') ?? '';

        $translated = $globalSeoSetting->translate('meta_keywords', $locale);
        $default = $globalSeoSetting->meta_keywords;

        $metaKeywords = collect([$translated, $default])
            ->filter()
            ->map(function ($val) {
                if (is_array($val)) {
                    return implode(',', $val);
                }

                if (is_string($val)) {
                    $decoded = json_decode($val, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return implode(',', $decoded);
                    }
                    return $val;
                }

                return '';
            })
            ->first(fn($val) => !empty($val), '');

        $global['meta_keywords'] = $metaKeywords;
    }

    $module = [
        'meta_title' => '',
        'meta_description' => '',
        'meta_keywords' => '',
        'og_image' => '',
    ];

    $moduleType = null;
    // dd($routeName);
    if ($routeName) {
        if (strpos($routeName, 'category.') === 0 || in_array($routeName, ['category.detail', 'category.index', 'category.list', 'category.data'])) {
            $moduleType = 'category';
        } elseif (strpos($routeName, 'subcategory.') === 0 || in_array($routeName, ['subcategory.detail', 'subcategory.index', 'subcategory.list', 'subcategory.data'])) {
            $moduleType = 'subcategory';
        } elseif (strpos($routeName, 'service.') === 0 || in_array($routeName, ['service.detail', 'service.view', 'service.list', 'service.data'])) {
            $moduleType = 'service';
        }
    }
    // dd($moduleType);
    switch ($moduleType) {
        case 'category':
            $category = null;
            $id = request()->route('id');
            if ($id) {
                $category = \App\Models\Category::find($id);
            } elseif (isset($GLOBALS['category'])) {
                $category = $GLOBALS['category'];
            }
            if ($category && $category->seo_enabled == 1) {
                $module['meta_title'] = $category->translate('meta_title', $locale) ?: $category->meta_title;
                $module['meta_description'] = $category->translate('meta_description', $locale) ?: $category->meta_description;
                if (is_array($category->meta_keywords)) {
                    $module['meta_keywords'] = implode(',', ($category->translate('meta_keywords', $locale) ?: $category->meta_keywords));
                } elseif (is_string($category->meta_keywords)) {
                    $decoded = json_decode(($category->translate('meta_keywords', $locale) ?: $category->meta_keywords), true);
                    $module['meta_keywords'] = is_array($decoded) ? implode(',', $decoded) : $category->meta_keywords;
                } else {
                    $module['meta_keywords'] = '';
                }
                $module['og_image'] = $category->getFirstMediaUrl('seo_image') ?: '';
            }
            break;
        case 'subcategory':
            $subcategory = null;
            $id = request()->route('id');
            if ($id) {
                $subcategory = \App\Models\SubCategory::find($id);
            } elseif (isset($GLOBALS['subcategory'])) {
                $subcategory = $GLOBALS['subcategory'];
            }
            if ($subcategory && $subcategory->seo_enabled == 1) {
                $module['meta_title'] = $subcategory->translate('meta_title', $locale) ?: $subcategory->meta_title;
                $module['meta_description'] = $subcategory->translate('meta_description', $locale) ?: $subcategory->meta_description;
                // $module['meta_keywords'] = is_array($subcategory->meta_keywords) ? implode(',', $subcategory->meta_keywords) : $subcategory->meta_keywords;
                if (is_array($subcategory->meta_keywords)) {
                    $module['meta_keywords'] = implode(',', ($subcategory->translate('meta_keywords', $locale) ?: $subcategory->meta_keywords));
                } elseif (is_string($subcategory->meta_keywords)) {
                    $decoded = json_decode(($subcategory->translate('meta_keywords', $locale) ?: $subcategory->meta_keywords), true);
                    $module['meta_keywords'] = is_array($decoded) ? implode(',', $decoded) : $subcategory->meta_keywords;
                } else {
                    $module['meta_keywords'] = '';
                }
                $module['og_image'] = $subcategory->getFirstMediaUrl('seo_image') ?: '';
            }
            break;
        case 'service':
            $service = null;
            $id = request()->route('id');
            if ($id) {
                $service = \App\Models\Service::find($id);
            } elseif (isset($GLOBALS['service'])) {
                $service = $GLOBALS['service'];
            }
            if ($service && $service->seo_enabled == 1) {
                $module['meta_title'] = $service->translate('meta_title', $locale) ?: $service->meta_title;
                $module['meta_description'] = $service->translate('meta_description', $locale) ?: $service->meta_description;
                // $module['meta_keywords'] = is_array($service->meta_keywords) ? implode(',', $service->meta_keywords) : $service->meta_keywords;
                if (is_array($service->meta_keywords)) {
                    $module['meta_keywords'] = implode(',', ($service->translate('meta_keywords', $locale) ?: $service->meta_keywords) ?? []);
                } elseif (is_string($service->meta_keywords)) {
                    $decoded = json_decode(($service->translate('meta_keywords', $locale) ?: $service->meta_keywords), true);
                    $module['meta_keywords'] = is_array($decoded) ? implode(',', $decoded) : $service->meta_keywords;
                } else {
                    $module['meta_keywords'] = '';
                }
                $module['og_image'] = $service->getFirstMediaUrl('seo_image') ?: '';
            }
            break;
    }

    $meta['meta_title'] = isFilled($module['meta_title']) ? $module['meta_title'] : (isFilled($global['meta_title']) ? $global['meta_title'] : $meta['meta_title']);
    $meta['meta_description'] = isFilled($module['meta_description']) ? $module['meta_description'] : (isFilled($global['meta_description']) ? $global['meta_description'] : $meta['meta_description']);
    $meta['meta_keywords'] = isFilled($module['meta_keywords']) ? $module['meta_keywords'] : (isFilled($global['meta_keywords']) ? $global['meta_keywords'] : $meta['meta_keywords']);
    $meta['og_image'] = isFilled($module['og_image']) ? $module['og_image'] : (isFilled($global['og_image']) ? $global['og_image'] : $meta['og_image']);
    // dd($meta);
    return $meta;
}

function isFilled($value)
{
    return isset($value) && trim($value) !== '';
}


/**
 * Check if provider has recently downgraded their plan
 * Returns downgrade banner data if downgrade detected
 *
 * @param int $userId
 * @return array
 */
function get_provider_downgrade_banner($userId)
{
    try {
        // Get current active subscription (including cancelled but not expired)
        $currentSubscription = \App\Models\ProviderSubscription::where('user_id', $userId)
            ->where(function($query) {
                $query->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                      ->orWhere(function($q) {
                          $q->where('status', config('constant.SUBSCRIPTION_STATUS.CANCELLED'))
                            ->where('end_at', '>=', now()->format('Y-m-d'));
                      });
            })
            ->first();

        if (!$currentSubscription) {
            return null;
        }

        // Get previous subscription (last record before current)
        $previousSubscription = \App\Models\ProviderSubscription::where('user_id', $userId)
            ->where('id', '!=', $currentSubscription->id)
            ->whereIn('status', ['active', 'inactive', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$previousSubscription) {
            // No previous subscription, this is first subscription
            return null;
        }

        // Compare plan limitations
        $previousLimits = is_array($previousSubscription->plan_limitation) 
            ? $previousSubscription->plan_limitation 
            : json_decode($previousSubscription->plan_limitation, true) ?? [];
        
        $currentLimits = is_array($currentSubscription->plan_limitation) 
            ? $currentSubscription->plan_limitation 
            : json_decode($currentSubscription->plan_limitation, true) ?? [];

        if (empty($previousLimits) || empty($currentLimits)) {
            return null;
        }

        // Check if any limit has been reduced
        $isDowngrade = false;
        $limitTypes = ['service', 'featured_service', 'handyman'];

        foreach ($limitTypes as $limitType) {
            if (!isset($previousLimits[$limitType]) || !isset($currentLimits[$limitType])) {
                continue;
            }

            $prevLimit = $previousLimits[$limitType];
            $currLimit = $currentLimits[$limitType];

            // Both must be checked/enabled to compare
            $prevChecked = isset($prevLimit['is_checked']) && in_array($prevLimit['is_checked'], ['on', 1, '1', true], true);
            $currChecked = isset($currLimit['is_checked']) && in_array($currLimit['is_checked'], ['on', 1, '1', true], true);

            if (!$prevChecked || !$currChecked) {
                continue;
            }

            // Get numeric limits (treat null/empty/0 as unlimited)
            $prevLimitValue = (is_null($prevLimit['limit'] ?? null) || $prevLimit['limit'] === '' || $prevLimit['limit'] === '0') 
                ? PHP_INT_MAX 
                : (int)$prevLimit['limit'];
            
            $currLimitValue = (is_null($currLimit['limit'] ?? null) || $currLimit['limit'] === '' || $currLimit['limit'] === '0') 
                ? PHP_INT_MAX 
                : (int)$currLimit['limit'];

            // Detect downgrade: current limit is lower than previous
            if ($currLimitValue < $prevLimitValue) {
                $isDowngrade = true;
                break;
            }
        }

        if (!$isDowngrade) {
            return null;
        }

        // Return downgrade banner
        return [
            'title' => __('messages.plan_downgrade_title', [
                'plan' => $currentSubscription->title,
                'previous_plan' => $previousSubscription->title,
                'current_plan' => $currentSubscription->title
            ]),
            'description' => __('messages.plan_downgrade_description'),
        ];

    } catch (\Exception $e) {
        \Log::error('Error checking provider downgrade banner: ' . $e->getMessage(), [
            'user_id' => $userId
        ]);
        return null;
    }
}

/**
 * Get upgrade banner for provider
 * Detects if provider has upgraded plan by comparing plan limitations
 * Similar to downgrade detection but checks if limits increased
 * 
 * @param int $userId
 * @return array|null
 */
function get_provider_upgrade_banner($userId)
{
    try {
        // Get current active subscription (including cancelled but not expired)
        $currentSubscription = \App\Models\ProviderSubscription::where('user_id', $userId)
            ->where(function($query) {
                $query->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                      ->orWhere(function($q) {
                          $q->where('status', config('constant.SUBSCRIPTION_STATUS.CANCELLED'))
                            ->where('end_at', '>=', now()->format('Y-m-d'));
                      });
            })
            ->first();

        if (!$currentSubscription) {
            return null;
        }

        // Get previous subscription (last record before current)
        $previousSubscription = \App\Models\ProviderSubscription::where('user_id', $userId)
            ->where('id', '!=', $currentSubscription->id)
            ->whereIn('status', ['active', 'inactive', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$previousSubscription) {
            // No previous subscription, this is first subscription
            return null;
        }

        // If previous subscription doesn't have plan_type, fetch it from Plans table
        if (is_null($previousSubscription->plan_type) || $previousSubscription->plan_type === '') {
            $plan = \App\Models\Plans::find($previousSubscription->plan_id);
            if ($plan) {
                $previousSubscription->plan_type = $plan->plan_type;
            }
        }

        // Check if plan_type changed from unlimited to limited (free to paid upgrade)
        // Use lowercase comparison to handle case-insensitivity
        $isPreviousUnlimited = strtolower($previousSubscription->plan_type) === 'unlimited';
        $isCurrentLimited = strtolower($currentSubscription->plan_type) === 'limited';
        
        if ($isPreviousUnlimited && $isCurrentLimited) {
            // This is an upgrade from free (unlimited) to paid (limited) plan
            return [
                'description' => __('messages.plan_upgrade_description'),
            ];
        }

        // Compare plan limitations for other upgrade scenarios
        $previousLimits = is_array($previousSubscription->plan_limitation) 
            ? $previousSubscription->plan_limitation 
            : json_decode($previousSubscription->plan_limitation, true) ?? [];
        
        $currentLimits = is_array($currentSubscription->plan_limitation) 
            ? $currentSubscription->plan_limitation 
            : json_decode($currentSubscription->plan_limitation, true) ?? [];

        if (empty($previousLimits) || empty($currentLimits)) {
            return null;
        }

        // Check if any limit has been increased (for limited to higher limited plan upgrades)
        $isUpgrade = false;
        $limitTypes = ['service', 'featured_service', 'handyman'];

        foreach ($limitTypes as $limitType) {
            if (!isset($previousLimits[$limitType]) || !isset($currentLimits[$limitType])) {
                continue;
            }

            $prevLimit = $previousLimits[$limitType];
            $currLimit = $currentLimits[$limitType];

            // Both must be checked/enabled to compare
            $prevChecked = isset($prevLimit['is_checked']) && in_array($prevLimit['is_checked'], ['on', 1, '1', true], true);
            $currChecked = isset($currLimit['is_checked']) && in_array($currLimit['is_checked'], ['on', 1, '1', true], true);

            if (!$prevChecked || !$currChecked) {
                continue;
            }

            // Get numeric limits (treat null/empty/0 as unlimited)
            $prevLimitValue = (is_null($prevLimit['limit'] ?? null) || $prevLimit['limit'] === '' || $prevLimit['limit'] === '0') 
                ? PHP_INT_MAX 
                : (int)$prevLimit['limit'];
            
            $currLimitValue = (is_null($currLimit['limit'] ?? null) || $currLimit['limit'] === '' || $currLimit['limit'] === '0') 
                ? PHP_INT_MAX 
                : (int)$currLimit['limit'];

            // Detect upgrade: current limit is higher than previous
            if ($currLimitValue > $prevLimitValue) {
                $isUpgrade = true;
                break;
            }
        }

        if (!$isUpgrade) {
            return null;
        }

        // Return upgrade banner
        return [
            'title' => __('messages.plan_upgrade_title'),
            'description' => __('messages.plan_upgrade_description'),
        ];

    } catch (\Exception $e) {
        \Log::error('Error checking provider upgrade banner: ' . $e->getMessage(), [
            'user_id' => $userId
        ]);
        return null;
    }
}


/**
 * Check if provider can activate more services based on plan limitation
 * Returns true if can activate, false if limit exceeded
 *
 * @param int $provider_id
 * @param string $type ('service', 'featured_service', 'handyman')
 * @param int $excluding_id (optional) - ID to exclude from count (for editing)
 * @return bool
 */
function can_activate_resource($provider_id, $type = 'service', $excluding_id = null)
{
    try {
        if (is_any_plan_active($provider_id) != 1) {
            return true; // No active plan, allow activation
        }

        $active_plan = get_user_active_plan($provider_id);
        if (!$active_plan || $active_plan->plan_type !== 'limited') {
            return true; // Unlimited plan, allow activation
        }

        $plan_limitation = is_array($active_plan->plan_limitation) 
            ? $active_plan->plan_limitation 
            : json_decode($active_plan->plan_limitation, true) ?? [];

        // Determine which limit to check
        $check_type = $type;
        
        // For featured_service, check the featured_service limit if it exists, otherwise fall back to service limit
        if ($type === 'featured_service') {
            if (!isset($plan_limitation['featured_service'])) {
                $check_type = 'service';
            }
        }

        if (!isset($plan_limitation[$check_type])) {
            return true;
        }

        $limit_config = $plan_limitation[$check_type];
        
        // If not checked or no limit set, allow
        if ($limit_config['is_checked'] !== 'on' || !isset($limit_config['limit'])) {
            return true;
        }

        $limit_value = (int)$limit_config['limit'];
        
        // Get active count based on type
        $active_count = 0;
        
        if ($type === 'service') {
            // Count ALL active services
            $query = \App\Models\Service::where('provider_id', $provider_id)
                ->where('status', 1);
            if ($excluding_id) {
                $query->where('id', '!=', $excluding_id);
            }
            $active_count = $query->count();
        } elseif ($type === 'featured_service') {
            // Count ALL services with is_featured = 1, REGARDLESS of status
            // Inactive featured services still count toward the limit
            $query = \App\Models\Service::where('provider_id', $provider_id)
                ->where('is_featured', 1);
            if ($excluding_id) {
                $query->where('id', '!=', $excluding_id);
            }
            $active_count = $query->count();
        } elseif ($type === 'handyman') {
            // IMPORTANT: Filter by user_type = 'handyman' to only count handymen, not other users
            // Count ALL active handymen (do NOT exclude the one being activated - it's currently inactive)
            $active_count = \App\Models\User::where('provider_id', $provider_id)
                ->where('user_type', 'handyman')
                ->where('status', 1)
                ->count();
        }

        // Return true only if we have room for at least one more
        return $active_count < $limit_value;

    } catch (\Exception $e) {
        \Log::error('Error checking resource activation limit: ' . $e->getMessage(), [
            'provider_id' => $provider_id,
            'type' => $type
        ]);
        return true; // Allow on error
    }
}

/**
 * Get remaining limit for a resource type
 *
 * @param int $provider_id
 * @param string $type ('service', 'featured_service', 'handyman')
 * @return int|null
 */
function get_remaining_limit($provider_id, $type = 'service')
{
    try {
        if (is_any_plan_active($provider_id) != 1) {
            return null;
        }

        $active_plan = get_user_active_plan($provider_id);
        if (!$active_plan || $active_plan->plan_type !== 'limited') {
            return null;
        }

        $plan_limitation = is_array($active_plan->plan_limitation) 
            ? $active_plan->plan_limitation 
            : json_decode($active_plan->plan_limitation, true) ?? [];

        // Determine which limit to check
        $check_type = $type;
        
        // For featured_service, check the featured_service limit if it exists, otherwise fall back to service limit
        if ($type === 'featured_service') {
            if (!isset($plan_limitation['featured_service'])) {
                $check_type = 'service';
            }
        }

        if (!isset($plan_limitation[$check_type])) {
            return null;
        }

        $limit_config = $plan_limitation[$check_type];
        
        if ($limit_config['is_checked'] !== 'on' || !isset($limit_config['limit'])) {
            return null;
        }

        $limit_value = (int)$limit_config['limit'];
        
        // Get active count
        $active_count = 0;
        
        if ($type === 'service') {
            // Count ALL active services
            $active_count = \App\Models\Service::where('provider_id', $provider_id)
                ->where('status', 1)
                ->count();
        } elseif ($type === 'featured_service') {
            // Count ALL services with is_featured = 1, REGARDLESS of status
            // Inactive featured services still count toward the limit
            $active_count = \App\Models\Service::where('provider_id', $provider_id)
                ->where('is_featured', 1)
                ->count();
        } elseif ($type === 'handyman') {
            // IMPORTANT: Filter by user_type = 'handyman' to only count handymen
            $active_count = \App\Models\User::where('provider_id', $provider_id)
                ->where('user_type', 'handyman')
                ->where('status', 1)
                ->count();
        }

        return max(0, $limit_value - $active_count);

    } catch (\Exception $e) {
        \Log::error('Error getting remaining limit: ' . $e->getMessage(), [
            'provider_id' => $provider_id,
            'type' => $type
        ]);
        return null;
    }
}

/**
 * Enforce plan limits by deactivating excess resources
 * Used when plan is downgraded or to ensure compliance
 *
 * @param int $provider_id
 * @param string $type ('service', 'featured_service', 'handyman')
 * @return array
 */
function enforce_plan_limits($provider_id, $type = 'service')
{
    try {
        if (is_any_plan_active($provider_id) != 1) {
            return ['success' => true, 'deactivated' => 0];
        }

        $active_plan = get_user_active_plan($provider_id);
        if (!$active_plan || $active_plan->plan_type !== 'limited') {
            return ['success' => true, 'deactivated' => 0];
        }

        $plan_limitation = is_array($active_plan->plan_limitation) 
            ? $active_plan->plan_limitation 
            : json_decode($active_plan->plan_limitation, true) ?? [];

        // For featured_service, check against service limit (featured services count towards total service limit)
        $check_type = ($type === 'featured_service') ? 'service' : $type;

        if (!isset($plan_limitation[$check_type])) {
            return ['success' => true, 'deactivated' => 0];
        }

        $limit_config = $plan_limitation[$check_type];
        
        if ($limit_config['is_checked'] !== 'on' || !isset($limit_config['limit'])) {
            return ['success' => true, 'deactivated' => 0];
        }

        $limit_value = (int)$limit_config['limit'];
        $deactivated_count = 0;

        if ($type === 'service' || $type === 'featured_service') {
            // Both service and featured_service count towards the total service limit
            $active_services = \App\Models\Service::where('provider_id', $provider_id)
                ->where('status', 1)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($active_services->count() > $limit_value) {
                // Deactivate newest services beyond the limit (keep oldest)
                $services_to_deactivate = $active_services->slice($limit_value);
                
                foreach ($services_to_deactivate as $service) {
                    $service->update([
                        'status' => 0,
                        'is_featured' => 0,
                        'updated_at' => now()
                    ]);
                    $deactivated_count++;
                }
            }
        } elseif ($type === 'handyman') {
            // Get active handymen - IMPORTANT: Filter by user_type = 'handyman'
            $active_handymen = \App\Models\User::where('provider_id', $provider_id)
                ->where('user_type', 'handyman')
                ->where('status', 1)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($active_handymen->count() > $limit_value) {
                // Deactivate newest handymen beyond the limit (keep oldest)
                $handymen_to_deactivate = $active_handymen->slice($limit_value);
                
                foreach ($handymen_to_deactivate as $handyman) {
                    $handyman->update([
                        'status' => 0,
                        'updated_at' => now()
                    ]);
                    $deactivated_count++;
                }
            }
        }

        if ($deactivated_count > 0) {
            \Log::info('Plan limits enforced: ' . $deactivated_count . ' ' . $type . ' deactivated', [
                'provider_id' => $provider_id,
                'type' => $type,
                'limit' => $limit_value
            ]);
        }

        return ['success' => true, 'deactivated' => $deactivated_count];

    } catch (\Exception $e) {
        \Log::error('Error enforcing plan limits: ' . $e->getMessage(), [
            'provider_id' => $provider_id,
            'type' => $type
        ]);
        return ['success' => false, 'deactivated' => 0, 'error' => $e->getMessage()];
    }
}


/**
 * Get deactivated resources banner for provider after plan upgrade
 * Shows message when services/handymen are deactivated due to plan limits
 * 
 * @param int $userId
 * @return array|null
 */
function get_provider_deactivated_resources_banner($userId)
{
    try {
        // Check if there are deactivated services or handymen
        $deactivatedServices = \App\Models\Service::where('provider_id', $userId)
            ->where('status', 0)
            ->exists();
        
        $deactivatedHandymen = \App\Models\User::where('provider_id', $userId)
            ->where('status', 0)
            ->exists();

        // Only show banner if there are deactivated resources
        if (!$deactivatedServices && !$deactivatedHandymen) {
            return null;
        }

        // Return deactivated resources banner
        return [
            'title' => __('messages.deactivated_resources_title'),
            'description' => __('messages.deactivated_resources_description'),
        ];

    } catch (\Exception $e) {
        \Log::error('Error checking provider deactivated resources banner: ' . $e->getMessage(), [
            'user_id' => $userId
        ]);
        return null;
    }
}

/**
 * Detect if provider has upgraded plan
 * Similar to downgrade detection but checks if limits increased
 * Shows message when plan is upgraded (Free->Basic, Basic->Premium, etc)
 * 
 * @param int $userId
 * @return array|null
 */
function detect_provider_plan_upgrade($userId)
{
    try {
        // Get current active subscription (including cancelled but not expired)
        $currentSubscription = \App\Models\ProviderSubscription::where('user_id', $userId)
            ->where(function($query) {
                $query->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                      ->orWhere(function($q) {
                          $q->where('status', config('constant.SUBSCRIPTION_STATUS.CANCELLED'))
                            ->where('end_at', '>=', now()->format('Y-m-d'));
                      });
            })
            ->first();

        if (!$currentSubscription) {
            return null;
        }

        // Get previous subscription (last record before current)
        $previousSubscription = \App\Models\ProviderSubscription::where('user_id', $userId)
            ->where('id', '!=', $currentSubscription->id)
            ->whereIn('status', ['active', 'inactive', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$previousSubscription) {
            // No previous subscription, this is first subscription
            return null;
        }

        // Compare plan limitations
        $previousLimits = is_array($previousSubscription->plan_limitation) 
            ? $previousSubscription->plan_limitation 
            : json_decode($previousSubscription->plan_limitation, true) ?? [];
        
        $currentLimits = is_array($currentSubscription->plan_limitation) 
            ? $currentSubscription->plan_limitation 
            : json_decode($currentSubscription->plan_limitation, true) ?? [];

        if (empty($previousLimits) || empty($currentLimits)) {
            return null;
        }

        // Check if any limit has been increased
        $isUpgrade = false;
        $limitTypes = ['service', 'featured_service', 'handyman'];

        foreach ($limitTypes as $limitType) {
            if (!isset($previousLimits[$limitType]) || !isset($currentLimits[$limitType])) {
                continue;
            }

            $prevLimit = $previousLimits[$limitType];
            $currLimit = $currentLimits[$limitType];

            // Both must be checked/enabled to compare
            $prevChecked = isset($prevLimit['is_checked']) && $prevLimit['is_checked'] === 'on';
            $currChecked = isset($currLimit['is_checked']) && $currLimit['is_checked'] === 'on';

            if (!$prevChecked || !$currChecked) {
                continue;
            }

            // Get numeric limits (treat null/empty/0 as unlimited)
            $prevLimitValue = (is_null($prevLimit['limit'] ?? null) || $prevLimit['limit'] === '' || $prevLimit['limit'] === '0') 
                ? PHP_INT_MAX 
                : (int)$prevLimit['limit'];
            
            $currLimitValue = (is_null($currLimit['limit'] ?? null) || $currLimit['limit'] === '' || $currLimit['limit'] === '0') 
                ? PHP_INT_MAX 
                : (int)$currLimit['limit'];

            // Detect upgrade: current limit is higher than previous
            if ($currLimitValue > $prevLimitValue) {
                $isUpgrade = true;
                break;
            }
        }

        if (!$isUpgrade) {
            return null;
        }

        // Plan upgrade detected - return upgrade info
        // Message will show regardless of deactivated resources
        return [
            'is_upgrade' => true,
            'previous_plan' => $previousSubscription->title,
            'current_plan' => $currentSubscription->title,
            'has_deactivated_resources' => true
        ];

    } catch (\Exception $e) {
        \Log::error('Error detecting provider plan upgrade: ' . $e->getMessage(), [
            'user_id' => $userId
        ]);
        return null;
    }
}


/**
 * Get current active subscription for a user
 * 
 * @param int $userId
 * @param int|null $subscriptionId Optional specific subscription ID
 * @return \App\Models\ProviderSubscription|null
 */
function getCurrentSubscription($userId, $subscriptionId = null)
{
    $query = \App\Models\ProviderSubscription::where('user_id', $userId)
        ->where('status', 'active');
    
    if ($subscriptionId !== null) {
        $query->where('id', $subscriptionId);
    }
    
    return $query->first();
}

/**
 * Get previous subscription (last record before current)
 * Ensures plan_type is populated from Plans table if not set
 * 
 * @param int $userId
 * @param int $currentSubscriptionId
 * @return \App\Models\ProviderSubscription|null
 */
function getPreviousSubscription($userId, $currentSubscriptionId)
{
    $previousSubscription = \App\Models\ProviderSubscription::where('user_id', $userId)
        ->where('id', '!=', $currentSubscriptionId)
        ->whereIn('status', ['active', 'inactive', 'cancelled'])
        ->orderBy('created_at', 'desc')
        ->first();

    // If previous subscription exists but doesn't have plan_type, fetch it from Plans table
    if ($previousSubscription && (is_null($previousSubscription->plan_type) || $previousSubscription->plan_type === '')) {
        $plan = \App\Models\Plans::find($previousSubscription->plan_id);
        if ($plan) {
            $previousSubscription->plan_type = $plan->plan_type;
        }
    }

    return $previousSubscription;
}

/**
 * Parse plan_limitation JSON to array
 * 
 * @param mixed $limitation
 * @return array
 */
function parsePlanLimitation($limitation)
{
    if (is_array($limitation)) {
        return $limitation;
    }

    if (is_string($limitation)) {
        $decoded = json_decode($limitation, true);
        return is_array($decoded) ? $decoded : [];
    }

    return [];
}

/**
 * Get numeric limit value, treating null/empty/0 as unlimited
 * 
 * @param mixed $limit
 * @return int
 */
function getPlanLimitValue($limit)
{
    if (is_null($limit) || $limit === '' || $limit === '0' || $limit === 0) {
        return PHP_INT_MAX; // Unlimited
    }

    return (int) $limit;
}

/**
 * Check if this is an upgrade from free plan to paid plan
 * Condition: plan_type changes from "unlimited" to "limited"
 * 
 * @param \App\Models\ProviderSubscription $previousSubscription
 * @param \App\Models\ProviderSubscription $currentSubscription
 * @return bool
 */
function isUpgradeFromFreePlan($previousSubscription, $currentSubscription)
{
    // Check if plan_type changed from unlimited to limited (Free → Basic/Premium)
    // Use lowercase comparison to handle case-insensitivity
    $isPreviousUnlimited = strtolower($previousSubscription->plan_type) === 'unlimited';
    $isCurrentLimited = strtolower($currentSubscription->plan_type) === 'limited';

    return $isPreviousUnlimited && $isCurrentLimited;
}

/**
 * Validate if provider can create a new resource based on plan limits
 * 
 * @param int $providerId Provider user ID
 * @param string $resourceType Type of resource: 'service', 'featured_service', 'handyman'
 * @param int|null $excludingId ID to exclude from count (for updates)
 * @param bool $checkActiveOnly For handyman: true = count only active (for activation), false = count all (for creation)
 * @return array ['can_create' => bool, 'message' => string, 'current_count' => int, 'limit' => int]
 */
function validatePlanLimit($providerId, $resourceType, $excludingId = null, $checkActiveOnly = false)
{
    \Log::info('validatePlanLimit called', [
        'provider_id' => $providerId,
        'resource_type' => $resourceType,
        'excluding_id' => $excludingId
    ]);
    
    // Check if subscription system is enabled
    if (default_earning_type() !== 'subscription') {
        \Log::info('Subscription system not enabled');
        return [
            'can_create' => true,
            'message' => 'Subscription system not enabled',
            'current_count' => 0,
            'limit' => PHP_INT_MAX
        ];
    }

    // Get current active subscription
    $subscription = getCurrentActiveSubscription($providerId);
    
    \Log::info('Subscription check', [
        'provider_id' => $providerId,
        'subscription_found' => $subscription ? 'yes' : 'no',
        'subscription_id' => $subscription ? $subscription->id : null
    ]);
    
    if (!$subscription) {
        return [
            'can_create' => false,
            'message' => __('messages.no_active_subscription'),
            'current_count' => 0,
            'limit' => 0
        ];
    }

    // Free/Unlimited plans should not enforce `plan_limitation.is_checked/limit`.
    // Your seeders often store free plans with NULL `plan_type`, so treat NULL as unlimited too.
    $planType = strtolower((string) ($subscription->plan_type ?? ''));
    if ($planType === 'unlimited') {
        return [
            'can_create' => true,
            'message' => 'Within limit',
            'current_count' => 0,
            'limit' => PHP_INT_MAX,
        ];
    }

    // Parse plan limitations
    $limits = parsePlanLimitation($subscription->plan_limitation);
    
    \Log::info('Plan limitations parsed', [
        'provider_id' => $providerId,
        'limits' => $limits,
        'resource_type' => $resourceType,
        'resource_exists' => isset($limits[$resourceType]) ? 'yes' : 'no'
    ]);
    
    if (empty($limits) || !isset($limits[$resourceType])) {
        return [
            'can_create' => true,
            'message' => 'No limit defined for this resource',
            'current_count' => 0,
            'limit' => PHP_INT_MAX
        ];
    }

    $resourceLimit = $limits[$resourceType];
    
    \Log::info('Resource limit details', [
        'provider_id' => $providerId,
        'resource_type' => $resourceType,
        'resource_limit' => $resourceLimit,
        'is_checked' => $resourceLimit['is_checked'] ?? 'not set',
        'is_checked_type' => gettype($resourceLimit['is_checked'] ?? null)
    ]);
    
    // IMPORTANT: Check if resource is enabled in plan FIRST
    // is_checked can be: 1, "1", "on", true
    $isChecked = $resourceLimit['is_checked'] ?? null;
    $isEnabled = in_array($isChecked, [1, '1', 'on', true, 'true'], true) || $isChecked == 1;

    // If feature is disabled (is_checked='off'), block it regardless of limit value
    if (!$isEnabled) {
        \Log::info('Resource not enabled in plan', [
            'provider_id' => $providerId,
            'resource_type' => $resourceType,
            'is_checked_value' => $isChecked,
            'is_enabled' => $isEnabled
        ]);

        return [
            'can_create' => false,
            'message' => __('messages.resource_not_in_plan', ['resource' => ucfirst(str_replace('_', ' ', $resourceType))]),
            'current_count' => 0,
            'limit' => 0
        ];
    }

    // Now check the limit value (only if feature is enabled)
    // null or 0 limit means unlimited when feature is enabled
    $rawLimit = $resourceLimit['limit'] ?? null;
    if ($rawLimit === null || $rawLimit === '' || $rawLimit === 0 || $rawLimit === '0') {
        return [
            'can_create' => true,
            'message' => 'Within limit',
            'current_count' => 0,
            'limit' => PHP_INT_MAX,
        ];
    }

    // Get limit value (PHP_INT_MAX for unlimited)
    $limit = getPlanLimitValue($resourceLimit['limit'] ?? null);
    
    // Count current active resources
    $currentCount = 0;
    
    switch ($resourceType) {
        case 'service':
            $query = \App\Models\Service::where('provider_id', $providerId)
                ->where('status', config('constant.SERVICE_STATUS.ACTIVE'));
            
            if ($excludingId) {
                $query->where('id', '!=', $excludingId);
            }
            
            $currentCount = $query->count();
            break;
            
        case 'featured_service':
            $query = \App\Models\Service::where('provider_id', $providerId)
                ->where('is_featured', 1);
            
            if ($excludingId) {
                $query->where('id', '!=', $excludingId);
            }
            
            $currentCount = $query->count();
            break;
            
        case 'handyman':
            // For handyman limits, count only ACTIVE handymen.
            // Inactive handymen should not consume plan slots.
            $query = \App\Models\User::where('provider_id', $providerId)
                ->where('user_type', config('constant.USER_TYPE.HANDYMAN'));
            
            $query->where('status', config('constant.USER_STATUS.ACTIVE'));
            
            if ($excludingId) {
                $query->where('id', '!=', $excludingId);
            }
            
            $currentCount = $query->count();
            
            \Log::info('Handyman validation count', [
                'provider_id' => $providerId,
                'excluding_id' => $excludingId,
                'check_active_only' => $checkActiveOnly,
                'current_count' => $currentCount,
                'limit' => $limit,
                'can_create' => $currentCount < $limit
            ]);
            break;
    }

    // Check if limit is exceeded
    // For activation: currentCount is ACTIVE handymen (excluding the one being activated)
    // After activation, total will be currentCount + 1
    // We should allow if (currentCount + 1) <= limit
    // Which is the same as: currentCount < limit
    $canCreate = $currentCount < $limit;
    
    \Log::info('Final validation check', [
        'resource_type' => $resourceType,
        'current_count' => $currentCount,
        'limit' => $limit,
        'can_create' => $canCreate,
        'logic' => "$currentCount < $limit = " . ($currentCount < $limit ? 'true' : 'false')
    ]);
    
    $message = $canCreate 
        ? 'Within limit' 
        : __('messages.resource_limit_exceeded', [
            'resource' => ucfirst(str_replace('_', ' ', $resourceType)),
            'limit' => $limit === PHP_INT_MAX ? 'unlimited' : $limit
        ]);

    return [
        'can_create' => $canCreate,
        'message' => $message,
        'current_count' => $currentCount,
        'limit' => $limit === PHP_INT_MAX ? 'unlimited' : $limit
    ];
}

/**
 * Get current active subscription for provider
 * 
 * @param int $providerId
 * @return \App\Models\ProviderSubscription|null
 */
function getCurrentActiveSubscription($providerId)
{
    // Get subscription that is either active OR cancelled but not yet expired
    // This allows providers to use their plan features until end_at date even after cancellation
    return \App\Models\ProviderSubscription::where('user_id', $providerId)
        ->where(function($query) {
            $query->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                  ->orWhere(function($q) {
                      $q->where('status', config('constant.SUBSCRIPTION_STATUS.CANCELLED'))
                        ->where('end_at', '>=', now()->format('Y-m-d'));
                  });
        })
        ->orderBy('id', 'desc')
        ->first();
}

/**
 * Check if provider can activate a service based on plan limits
 * 
 * @param int $providerId
 * @param int $serviceId Service ID to activate
 * @param bool $isFeatured Whether service will be featured
 * @return array ['can_activate' => bool, 'message' => string]
 */
function canActivateService($providerId, $serviceId, $isFeatured = false)
{
    // Check service limit
    $serviceValidation = validatePlanLimit($providerId, 'service', $serviceId);
    
    if (!$serviceValidation['can_create']) {
        return [
            'can_activate' => false,
            'message' => $serviceValidation['message']
        ];
    }

    // If service is featured, also check featured limit
    if ($isFeatured) {
        $featuredValidation = validatePlanLimit($providerId, 'featured_service', $serviceId);
        
        if (!$featuredValidation['can_create']) {
            return [
                'can_activate' => false,
                'message' => $featuredValidation['message']
            ];
        }
    }

    return [
        'can_activate' => true,
        'message' => 'Service can be activated'
    ];
}

/**
 * Check if provider can create a handyman based on plan limits
 * 
 * @param int $providerId
 * @param int|null $handymanId Handyman ID to exclude (for updates)
 * @return array ['can_create' => bool, 'message' => string]
 */
function canCreateHandyman($providerId, $handymanId = null)
{
    $validation = validatePlanLimit($providerId, 'handyman', $handymanId);
    
    return [
        'can_create' => $validation['can_create'],
        'message' => $validation['message']
    ];
}


/**
 * Enforce plan limits for all providers when switching to subscription mode
 * Deactivates extra services, handymen, and featured services based on each provider's plan limits
 * 
 * @return void
 */
function enforcePlanLimitsForAllProviders()
{
    // Get all providers with active status
    $providers = \App\Models\User::where('user_type', 'provider')
        ->where('status', 1)
        ->get();
    
    foreach ($providers as $provider) {
        // Get provider's active subscription
        $subscription = \App\Models\ProviderSubscription::where('user_id', $provider->id)
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first();
        
        if (!$subscription) {
            continue; // Skip if no active subscription
        }
        
        $planLimitation = is_array($subscription->plan_limitation) 
            ? $subscription->plan_limitation 
            : json_decode($subscription->plan_limitation, true);
        
        if (!$planLimitation) {
            continue; // Skip if no plan limitation data
        }
        
        // 1. Enforce SERVICE limit
        if (isset($planLimitation['service'])) {
            $serviceData = $planLimitation['service'];
            $isChecked = $serviceData['is_checked'] ?? 'off';
            $limit = isset($serviceData['limit']) ? (int)$serviceData['limit'] : 0;
            
            // Get all services for this provider
            $services = \App\Models\Service::where('provider_id', $provider->id)
                ->orderBy('id', 'asc')
                ->get();
            
            if ($isChecked === 'off') {
                // Feature disabled - deactivate ALL services
                foreach ($services as $service) {
                    $service->update(['status' => 0]);
                }
            } elseif ($isChecked === 'on') {
                if ($limit == 0) {
                    // Limit is 0 - deactivate ALL services (no services allowed)
                    foreach ($services as $service) {
                        $service->update(['status' => 0]);
                    }
                } else {
                    // Limit is set - keep first N active, deactivate rest
                    foreach ($services as $index => $service) {
                        if ($index < $limit) {
                            $service->update(['status' => 1]);
                        } else {
                            $service->update(['status' => 0]);
                        }
                    }
                }
            }
        }
        
        // 2. Enforce HANDYMAN limit
        if (isset($planLimitation['handyman'])) {
            $handymanData = $planLimitation['handyman'];
            $isChecked = $handymanData['is_checked'] ?? 'off';
            $limit = isset($handymanData['limit']) ? (int)$handymanData['limit'] : 0;
            
            // Get all handymen for this provider
            $handymen = \App\Models\User::where('user_type', 'handyman')
                ->where('provider_id', $provider->id)
                ->orderBy('id', 'asc')
                ->get();
            
            if ($isChecked === 'off') {
                // Feature disabled - deactivate ALL handymen
                foreach ($handymen as $handyman) {
                    $handyman->update(['status' => 0]);
                }
            } elseif ($isChecked === 'on') {
                if ($limit == 0) {
                    // Limit is 0 - deactivate ALL handymen (no handymen allowed)
                    foreach ($handymen as $handyman) {
                        $handyman->update(['status' => 0]);
                    }
                } else {
                    // Limit is set - keep first N active, deactivate rest
                    foreach ($handymen as $index => $handyman) {
                        if ($index < $limit) {
                            $handyman->update(['status' => 1]);
                        } else {
                            $handyman->update(['status' => 0]);
                        }
                    }
                }
            }
        }
        
        // 3. Enforce FEATURED SERVICE limit
        if (isset($planLimitation['featured_service'])) {
            $featuredData = $planLimitation['featured_service'];
            $isChecked = $featuredData['is_checked'] ?? 'off';
            $limit = isset($featuredData['limit']) ? (int)$featuredData['limit'] : 0;
            
            // Get all featured services for this provider
            $featuredServices = \App\Models\Service::where('provider_id', $provider->id)
                ->where('is_featured', 1)
                ->orderBy('id', 'asc')
                ->get();
            
            if ($isChecked === 'off') {
                // Feature disabled - remove featured status from ALL services
                foreach ($featuredServices as $service) {
                    $service->update(['is_featured' => 0]);
                }
            } elseif ($isChecked === 'on') {
                if ($limit == 0) {
                    // Limit is 0 - remove featured status from ALL services (no featured services allowed)
                    foreach ($featuredServices as $service) {
                        $service->update(['is_featured' => 0]);
                    }
                } else {
                    // Limit is set - keep first N featured, remove featured status from rest
                    foreach ($featuredServices as $index => $service) {
                        if ($index >= $limit) {
                            $service->update(['is_featured' => 0]);
                        }
                    }
                }
            }
        }
    }
}
