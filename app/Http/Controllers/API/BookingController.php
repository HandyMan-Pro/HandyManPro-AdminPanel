<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\PaymentController;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\BookingRating;
use App\Models\HandymanRating;
use App\Models\BookingActivity;
use App\Models\Payment;
use App\Models\PaymentHistory;
use App\Models\Wallet;
use App\Models\LiveLocation;
use App\Models\User;
use App\Models\BookingHandymanMapping;
use App\Models\ServiceProof;
use App\Models\CommissionEarning;
use App\Http\Resources\API\BookingResource;
use App\Http\Resources\API\BookingDetailResource;
use App\Http\Resources\API\BookingRatingResource;
use App\Http\Resources\API\ServiceResource;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\HandymanResource;
use App\Http\Resources\API\HandymanRatingResource;
use App\Http\Resources\API\ServiceProofResource;
use App\Http\Resources\API\PostJobRequestResource;
use App\Http\Resources\API\ShopResource;
use App\Models\BookingServiceAddonMapping;
use App\Traits\NotificationTrait;
use App\Traits\EarningTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\PaymentGateway;
use App\Models\Setting;

class BookingController extends Controller
{
    use NotificationTrait;
    use EarningTrait;
    public function getBookingList(Request $request)
    {
        $booking = Booking::myBooking()->with('customer', 'provider', 'service', 'payment', 'handymanAdded', 'shop');

        if ($request->has('provider_id') && !empty($request->provider_id)) {
            $provider_ids = is_array($request->provider_id) ? $request->provider_id : explode(',', $request->provider_id);
            $booking->whereIn('provider_id', $provider_ids);
        }

        if ($request->has('handyman_id') && !empty($request->handyman_id)) {
            $handyman_ids = is_array($request->handyman_id) ? $request->handyman_id : explode(',', $request->handyman_id);
            $booking->whereHas('handymanAdded', function ($query) use ($handyman_ids) {
                $query->whereIn('handyman_id', $handyman_ids);
            });
        }

        if ($request->has('service_id') && !empty($request->service_id)) {
            $service_ids = is_array($request->service_id) ? $request->service_id : explode(',', $request->service_id);
            $booking->whereIn('service_id', $service_ids);
        }

        if ($request->has('customer_id') && !empty($request->customer_id)) {
            $customer_ids = is_array($request->customer_id) ? $request->customer_id : explode(',', $request->customer_id);
            $booking->whereIn('customer_id', $customer_ids);
        }

        if ($request->has('shop_id') && !empty($request->shop_id)) {
            $shop_ids = is_array($request->shop_id) ? $request->shop_id : explode(',', $request->shop_id);
            $booking->whereIn('shop_id', $shop_ids);
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $booking->whereDate('date', '>=', date('Y-m-d', strtotime($request->date_from)));
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $booking->whereDate('date', '<=', date('Y-m-d', strtotime($request->date_to)));
        }

        if ($request->has('status') && isset($request->status)) {

            $status = explode(',', $request->status);
            $booking->whereIn('status', $status);
        }

        if ($request->has('payment_status') && isset($request->payment_status)) {
            $payment_status = explode(',', $request->payment_status);
            $booking->whereHas('payment', function ($query) use ($payment_status) {
                $query->whereIn('payment_status', $payment_status);
            });
        }

        if ($request->has('payment_type') && isset($request->payment_type)) {
            $payment_type = explode(',', $request->payment_type);
            $booking->whereHas('payment', function ($query) use ($payment_type) {
                $query->whereIn('payment_type', $payment_type);
            });
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $booking->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")

                    ->orWhereHas('service', function ($serviceQuery) use ($search) {
                        $serviceQuery->where('name', 'LIKE', "%$search%");
                    })

                    ->orWhereHas('provider', function ($providerQuery) use ($search) {
                        $providerQuery->where(function ($nameQuery) use ($search) {
                            $nameQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"])
                                ->orWhere('email', 'LIKE', "%$search");
                        });
                    })

                    ->orWhereHas('customer', function ($userQuery) use ($search) {
                        $userQuery->where(function ($nameQuery) use ($search) {
                            $nameQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"])
                                ->orWhere('email', 'LIKE', "%$search");
                        });
                    });
            });
        }



        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $booking->count();
            }
        }
        $orderBy = 'desc';
       
        if ($request->has('orderby') && !empty($request->orderby)) {
            $orderBy = $request->orderby;
        }
         
        $booking = $booking->orderBy('id', $orderBy)->paginate($per_page);
        $items = BookingResource::collection($booking);

        // Initialize total earning
        $total_earning = 0;

        // Initialize payment breakdown
        $payment_breakdown = [
            'admin_earned' => 0,
            'handyman_earned' => 0,
            'provider_earned' => 0,
            'tax' => 0,
            'discount' => 0
        ];



        // Calculate totals from the booking items
        foreach ($items as $booking) {

            if ($booking->status != 'cancelled' && $booking->handymanAdded->count() > 0) {

                $total_earning += $booking->total_amount ?? 0;

                // Get commission data for this booking
                if ($booking->commissionsdata) {
                    foreach ($booking->commissionsdata as $commission) {
                        switch ($commission->user_type) {
                            case 'admin':
                            case 'demo_admin':
                                $payment_breakdown['admin_earned'] += $commission->commission_amount ?? 0;
                                break;
                            case 'provider':
                                $payment_breakdown['provider_earned'] += $commission->commission_amount ?? 0;
                                break;
                            case 'handyman':
                                $payment_breakdown['handyman_earned'] += $commission->commission_amount ?? 0;
                                break;
                        }
                    }
                }

                $payment_breakdown['tax'] += $booking->final_total_tax ?? 0;
                $payment_breakdown['discount'] += ($booking->final_discount_amount) ?? 0;
            }
        }

        // Format all numbers to 2 decimal places
        $total_earning = number_format($total_earning, 2, '.', '');
        array_walk($payment_breakdown, function (&$value) {
            $value = number_format($value, 2, '.', '');
        });

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
            'total_earning' => $total_earning,
            'payment_breakdown' => $payment_breakdown,
        ];

        return comman_custom_response($response);
    }


    public function getBookingDetail(Request $request)
    {

        $id = $request->booking_id;

        $booking_data = Booking::with('customer', 'handymanAdded', 'provider', 'service', 'bookingRating', 'bookingPostJob', 'bookingAddonService', 'bookingPackage', 'payment', 'shop')->where('id', $id)->first();

        // $booking_data = Booking::with('customer', 'handymanAdded', 'provider', 'service', 'bookingRating', 'bookingPostJob', 'bookingAddonService', 'bookingPackage', 'payment')->where('id', $id)->first();

        $latest_payment = Payment::where('booking_id', $id)->orderBy('id', 'desc')->first();
        $booking_data->payment = $latest_payment ?? null;

        if ($booking_data == null) {
            $message = __('messages.booking_not_found');
            return comman_message_response($message, 400);
        }
        $booking_detail = new BookingDetailResource($booking_data);

        $rating_data = BookingRatingResource::collection($booking_detail->bookingRating->take(5));
        $service = new ServiceResource($booking_detail->service);
        $customer = new UserResource($booking_detail->customer);
        $provider_data = new UserResource($booking_detail->provider);
        $handyman_data = HandymanResource::collection($booking_detail->handymanAdded);
        $shop_data = !empty($booking_detail->shop) ? new ShopResource($booking_detail->shop) : null;

        $customer_review = null;
        if ($request->customer_id != null) {
            $customer_review = BookingRating::where('customer_id', $request->customer_id)->where('service_id', $booking_detail->service_id)->where('booking_id', $id)->first();
            if (!empty($customer_review)) {
                $customer_review = new BookingRatingResource($customer_review);
            }
        }

        if (auth()->check()) {
            $auth_user = auth()->user();
            if (count($auth_user->unreadNotifications) > 0) {
                $auth_user->unreadNotifications->where('data.id', $id)->markAsRead();
            }
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        $booking_activity = BookingActivity::where('booking_id', $id)->orderBy('id', 'asc')->get();

        // Filter out wallet refund activities if the authenticated user is a provider
        if (auth()->check() && auth()->user()->user_type === 'provider') {
            $booking_activity = $booking_activity->filter(function ($activity) use ($id) {
                // Check if this is a wallet refund activity
                $isWalletRefund = ($activity->activity_type === 'Wallet Refunded' &&
                    isset($activity->activity_message) &&
                    $activity->activity_message === 'Refund for Booking #' . $id);

                // Return false for wallet refund activities (to exclude them)
                return !$isWalletRefund;
            })->values();
        }

        $serviceProof = ServiceProofResource::collection(ServiceProof::with('service', 'handyman', 'booking')->where('booking_id', $id)->get());
        $post_job_object = null;
        if ($booking_data->type == 'user_post_job') {
            $post_job_object = new PostJobRequestResource($booking_data->bookingPostJob);
        }

        $bookingpackage = $booking_data->bookingPackage;
        if ($bookingpackage !== null) {
            $mediaUrl = $bookingpackage->package->getFirstMedia('package_attachment')?->getUrl();
            $bookingpackage->package_image = $mediaUrl ?? asset('images/default.png');
        }

        $response = [
            'booking_detail'    => $booking_detail,
            'service'           => $service,
            'customer'          => $customer,
            'booking_activity'  => $booking_activity,
            'rating_data'       => $rating_data,
            'handyman_data'     => $handyman_data,
            'provider_data'     => $provider_data,
            'coupon_data'       => $booking_detail->couponAdded,
            'customer_review'   => $customer_review,
            'service_proof'     => $serviceProof,
            'post_request_detail' => $post_job_object,
            'shop_data'         => $shop_data,
            // 'bookingpackage'    => $bookingpackage,
        ];
        
        return comman_custom_response($response);
    }

    public function saveBookingRating(Request $request)
    {

        $rating_data = $request->all();
        $result = BookingRating::updateOrCreate(['id' => $request->id], $rating_data);

        $message = __('messages.update_form', ['form' => __('messages.rating')]);
        if ($result->wasRecentlyCreated) {
            $message = __('messages.save_form', ['form' => __('messages.rating')]);
        }

        return comman_message_response($message);
    }

    public function deleteBookingRating(Request $request)
    {
        $user = \Auth::user();

        $book_rating = BookingRating::where('id', $request->id)->where('customer_id', $user->id)->delete();

        $message = __('messages.delete_form', ['form' => __('messages.rating')]);

        return comman_message_response($message);
    }

    public function bookingStatus(Request $request)
    {
        $booking_status = BookingStatus::orderBy('sequence')->get();
        return comman_custom_response($booking_status);
    }

    public function bookingUpdate(Request $request)
    {
        $setting = Setting::getValueByKey('site-setup', 'site-setup');
        $digitafter_decimal_point = $setting ? $setting->digitafter_decimal_point : "2";
        $data = $request->all();

        $id = $request->id;
        $data['start_at'] = isset($request->start_at) ? date('Y-m-d H:i:s', strtotime($request->start_at)) : null;
        $data['end_at'] = isset($request->end_at) ? date('Y-m-d H:i:s', strtotime($request->end_at)) : null;
        $data['cancellation_charge'] = isset($request->cancellation_charge) ? $request->cancellation_charge : 0;
        $data['cancellation_charge_amount'] = isset($request->cancellation_charge_amount) ? $request->cancellation_charge_amount : 0;

        $bookingdata = Booking::find($id);
        $paymentdata = Payment::where('booking_id', $id)->first();
        // App clients may omit payment_status; use DB value so refund / wallet logic still runs.
        if (empty($data['payment_status'] ?? null) && $paymentdata) {
            $data['payment_status'] = $paymentdata->payment_status;
        }
        $user_wallet = Wallet::where('user_id', $bookingdata->customer_id)->first();
        $wallet_amount = $user_wallet ? $user_wallet->amount : 0;
        if ($request->type == 'service_addon') {
            if ($request->has('service_addon') && $request->service_addon != null) {
                foreach ($request->service_addon as $serviceaddon) {
                    $get_addon = BookingServiceAddonMapping::where('id', $serviceaddon)->first();
                    $get_addon->status = 1;
                    $get_addon->update();
                }
                $message = __('messages.update_form', ['form' => __('messages.booking')]);

                if ($request->is('api/*')) {
                    return comman_message_response($message);
                }
            }
        }
        if ($request->has('service_addon') && $request->service_addon != null) {
            foreach ($request->service_addon as $serviceaddon) {
                $get_addon = BookingServiceAddonMapping::where('id', $serviceaddon)->first();
                $get_addon->status = 1;
                $get_addon->update();
            }
        }
        if ($data['status'] === 'hold') {
            // When holding, the frontend sends the TOTAL accumulated duration
            // So we should use it directly, not add to existing duration
            $data['duration_diff'] = $data['duration_diff'] ?? 0;
            $bookingdata['duration_diff'] = $data['duration_diff'];
            
            // Recalculate pricing based on updated duration
            $data['final_total_service_price'] = round($bookingdata->getServiceTotalPrice(), $digitafter_decimal_point);
            $data['final_discount_amount'] = round($bookingdata->getDiscountValue(), $digitafter_decimal_point);
            $data['final_coupon_discount_amount'] = round($bookingdata->getCouponDiscountValue(), $digitafter_decimal_point);
            $subtotal = $bookingdata->getSubTotalValue() + $bookingdata->getServiceAddonValue();
            $data['final_sub_total'] = $subtotal;
            $tax = round($bookingdata->getTaxesValue(), $digitafter_decimal_point);
            $data['final_total_tax'] = $tax;
            // without include extrachage tax caculation
            $totalamount =   $subtotal + $tax;
            $data['total_amount'] = round($totalamount, $digitafter_decimal_point);
        }
        if ($data['status'] === 'pending_approval') {
            // When completing, the frontend sends the TOTAL accumulated duration
            // So we should use it directly, not add to existing duration
            $data['duration_diff'] = $data['duration_diff'] ?? 0;
            $bookingdata['duration_diff'] = $data['duration_diff'];
            
            // Recalculate pricing based on updated duration
            $data['final_total_service_price'] = round($bookingdata->getServiceTotalPrice(), $digitafter_decimal_point);
            $data['final_discount_amount'] = round($bookingdata->getDiscountValue(), $digitafter_decimal_point);
            $data['final_coupon_discount_amount'] = round($bookingdata->getCouponDiscountValue(), $digitafter_decimal_point);
            $subtotal = $bookingdata->getSubTotalValue() + $bookingdata->getServiceAddonValue();
            $data['final_sub_total'] = $subtotal;
            $tax = round($bookingdata->getTaxesValue(), $digitafter_decimal_point);
            $data['final_total_tax'] = $tax;
            // without include extrachage tax caculation
            $totalamount =   $subtotal + $tax;
            $data['total_amount'] = round($totalamount, $digitafter_decimal_point);
        }
        if ($bookingdata->status != $data['status']) {
            $activity_type = 'update_booking_status';
        }
        if ($data['status'] == 'cancelled') {
            $activity_type = 'cancel_booking';
        }

        if ($data['status'] == 'rejected') {
            if ($bookingdata->handymanAdded()->count() > 0) {
                $assigned_handyman_ids = $bookingdata->handymanAdded()->pluck('handyman_id')->toArray();
                $bookingdata->handymanAdded()->delete();
                $data['status'] = 'accept';
            }
        }
        if ($data['status'] == 'pending') {
            if ($bookingdata->handymanAdded()->count() > 0) {
                $bookingdata->handymanAdded()->delete();
                $data['status'] = 'accept';
            }
        }

        // Server-side cancellation fee: % must apply to booking total (after discount), not raw service price.
        if ($data['status'] === 'cancelled') {
            $recalculatedCancellation = $bookingdata->getCancellationCharges();
            $data['cancellation_charge_amount'] = round(
                $recalculatedCancellation,
                (int) $digitafter_decimal_point
            );
        }

        if (($data['status'] == 'rejected' || $data['status'] == 'cancelled') && $data['payment_status'] == 'advanced_paid') {
            $advance_paid_amount = (float) ($bookingdata->advance_paid_amount ?? 0);
            $cancellation_charges = (float) ($data['cancellation_charge_amount'] ?? 0);
            $net_refund = max(0, $advance_paid_amount - $cancellation_charges);

            if ($cancellation_charges > 0) {
                $user_wallet->amount = ($wallet_amount + $advance_paid_amount) - $cancellation_charges;
            } else {
                $user_wallet->amount = $wallet_amount + $advance_paid_amount;
            }

            $user_wallet->update();
            // Wallet history: credit only the net refund (advance minus fee); 0 when advance <= fee.
            if ($net_refund > 0) {
                $paymentData = Payment::where('booking_id', $bookingdata->id)->first();
                if ($paymentData) {
                    $paymentData->payment_status = 'Advanced Refund';
                    $paymentData->update();
                }
                $activity_data = [
                    'activity_type' => 'wallet_refund',
                    'payment_status' => 'Advance Payment',
                    'wallet' => $user_wallet,
                    'booking_id' => $id,
                    'refund_amount' => $net_refund,
                ];
                $this->sendNotification($activity_data);
            }
            if ($cancellation_charges > 0) {
                $activity_data = [
                    'activity_type' => 'cancellation_charges',
                    'wallet' => $user_wallet,
                    'booking_id' => $id,
                    'paid_amount' => $cancellation_charges,
                ];
                $this->sendNotification($activity_data);
            }
        }
        $data['reason'] = isset($data['reason']) ? $data['reason'] : null;

        if ($data['status'] == 'cancelled' && $data['cancellation_charge_amount'] > 0 && $data['payment_status'] !== 'advanced_paid') {
            $cancellation_charges = $data['cancellation_charge_amount'];
            $user_wallet->amount = $wallet_amount - $cancellation_charges;
            $user_wallet->update();
            $activity_data = [
                'activity_type' => 'cancellation_charges',
                'wallet' => $user_wallet,
                'booking_id' => $id,
                'paid_amount' => $cancellation_charges,
            ];
            $this->sendNotification($activity_data);
        }
        $old_status = $bookingdata->status;
        if ($request->has('extra_charges')) {
            if ($bookingdata->bookingExtraCharge()->count() > 0) {
                $bookingdata->bookingExtraCharge()->delete();
            }
            foreach ($request->extra_charges as $extra) {
                $extra_charge = [
                    'title'   => $extra['title'],
                    'price'   => $extra['price'],
                    'qty'   => $extra['qty'],
                    'booking_id'   => $bookingdata->id,
                ];
                $bookingdata->bookingExtraCharge()->insert($extra_charge);
            }
            $subtotal = $bookingdata->getSubTotalValue() + $bookingdata->getServiceAddonValue() + $bookingdata->getExtraChargeValue();

            // without include extrachage tax caculation
            $data['final_sub_total'] = $subtotal;
            $tax = $bookingdata->getTaxesValue();
            $data['final_total_tax'] = round($tax, $digitafter_decimal_point);
            $totalamount =   $subtotal + $tax;
            $data['total_amount'] = round($totalamount, $digitafter_decimal_point);

            // with include extracharge tax caculation
            // $totalamount =   $subtotal + $bookingdata->getExtraChargeValue() + $tax;
            // $data['total_amount'] =round($totalamount,2);
            // $data['final_total_tax'] = round($tax,2);
        }


        $bookingdata->update($data);

        if ($bookingdata && $bookingdata->status === 'completed') {
            $this->addBookingCommission($bookingdata);
        }

        if ($old_status != $data['status']) {
            $bookingdata->old_status = $old_status;
            $activity_data = [
                'activity_type' => $activity_type,
                'booking_id' => $id,
                'booking' => $bookingdata,
            ];
            $this->sendNotification($activity_data);
        }

        if ($bookingdata->payment_id != null) {
            $payment_status = isset($data['payment_status']) ? $data['payment_status'] : 'pending';
            $paymentdata->update(['payment_status' => $payment_status]);
        }

        if ($data['status'] == 'completed' && $data['payment_status'] == 'pending_by_admin') {
            $handyman = BookingHandymanMapping::where('booking_id', $bookingdata->id)->first();
            $user = User::where('id', $handyman->handyman_id)->first();
            
            // Get timezone from site setup
            $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
            $admin = json_decode($sitesetup->value);
            date_default_timezone_set($admin->time_zone ?? 'UTC');
            
            $payment_history = [
                'payment_id' => $paymentdata->id,
                'booking_id' => $paymentdata->booking_id,
                'type' => $paymentdata->payment_type,
                'sender_id' => $bookingdata->customer_id,
                'receiver_id' => $handyman->handyman_id,
                'total_amount' => $paymentdata->total_amount,
                'datetime' => date('Y-m-d H:i:s'),
            ];
            if ($user->user_type == 'provider') {
                $payment_history['status'] = config('constant.PAYMENT_HISTORY_STATUS.APPROVED_PROVIDER');
                $payment_history['action'] = config('constant.PAYMENT_HISTORY_ACTION.PROVIDER_APPROVED_CASH');
                $payment_history['text'] = __('messages.cash_approved', [
                    'amount' => getPriceFormat((float)$paymentdata->total_amount),
                    'name' => get_user_name($handyman->handyman_id)
                ]);
            } else {
                $payment_history['status'] = config('constant.PAYMENT_HISTORY_STATUS.APPRVOED_HANDYMAN');
                $payment_history['action'] = config('constant.PAYMENT_HISTORY_ACTION.HANDYMAN_APPROVED_CASH');
                $payment_history['text'] = __('messages.cash_approved', [
                    'amount' => getPriceFormat((float)$paymentdata->total_amount),
                    'name' => get_user_name($handyman->handyman_id)
                ]);
            }
            if (!empty($paymentdata->txn_id)) {
                $payment_history['txn_id'] = $paymentdata->txn_id;
            }
            if (!empty($paymentdata->other_transaction_detail)) {
                $payment_history['other_transaction_detail'] = $paymentdata->other_transaction_detail;
            }
            $res =  PaymentHistory::create($payment_history);
            $res->parent_id = $res->id;
            $res->update();
        }

        // user earning points after booking completed (cash payment)
        if ($data['status'] == 'completed' && $data['payment_status'] == 'pending_by_admin') {

            $bookingPackage = $bookingdata->bookingPackage == null ? null : $bookingdata->bookingPackage;

            if ($bookingPackage) {
                Booking::earnPointsByPackage($bookingdata->customer_id, $bookingPackage->service_package_id, $bookingdata->id, $bookingdata->final_sub_total);
            } else {
                Booking::earnPointsByService($bookingdata->customer_id, $bookingdata->service_id, $bookingdata->id, $bookingdata->final_sub_total);
            }
        }

        $message = __('messages.update_form', ['form' => __('messages.booking')]);

        if ($request->is('api/*')) {
            return comman_message_response($message);
        }
    }

    public function saveHandymanRating(Request $request)
    {
        $user = auth()->user();
        $rating_data = $request->all();
        $rating_data['customer_id'] = $user->id;
        $result = HandymanRating::updateOrCreate(['id' => $request->id], $rating_data);

        $message = __('messages.update_form', ['form' => __('messages.rating')]);
        if ($result->wasRecentlyCreated) {
            $message = __('messages.save_form', ['form' => __('messages.rating')]);
        }

        return comman_message_response($message);
    }

    public function getHandymanRatingList(Request $request)
    {

        $handymanratings = HandymanRating::orderBy('id', 'desc');

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $handymanratings->count();
            }
        }

        $handymanratings = $handymanratings->paginate($per_page);
        $data = HandymanRatingResource::collection($handymanratings);

        return response([
            'pagination' => [
                'total_ratings' => $data->total(),
                'per_page' => $data->perPage(5),
                'currentPage' => $data->currentPage(),
                'totalPages' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'next_page' => $data->nextPageUrl(),
                'previous_page' => $data->previousPageUrl(),
            ],
            'data' => $data,
        ]);
    }

    public function deleteHandymanRating(Request $request)
    {
        $user = auth()->user();

        $book_rating = HandymanRating::where('id', $request->id)->where('customer_id', $user->id)->delete();

        $message = __('messages.delete_form', ['form' => __('messages.rating')]);

        return comman_message_response($message);
    }
    public function bookingRatingByCustomer(Request $request)
    {
        $customer_review = null;
        if ($request->customer_id != null) {
            $customer_review = BookingRating::where('customer_id', $request->customer_id)->where('service_id', $request->service_id)->where('booking_id', $request->booking_id)->first();
            if (!empty($customer_review)) {
                $customer_review = new BookingRatingResource($customer_review);
            }
        }
        return comman_custom_response($customer_review);
    }
    public function uploadServiceProof(Request $request)
    {
        $booking = $request->all();
        $result = ServiceProof::create($booking);
        if ($request->has('attachment_count')) {
            for ($i = 0; $i < $request->attachment_count; $i++) {
                $attachment = "booking_attachment_" . $i;
                if ($request->$attachment != null) {
                    $file[] = $request->$attachment;
                }
            }
            storeMediaFile($result, $file, 'booking_attachment');
        }
        if ($result->wasRecentlyCreated) {
            $message = __('messages.save_form', ['form' => __('messages.attachments')]);
        }
        return comman_message_response($message);
    }

    public function getUserRatings(Request $request)
    {
        $user = auth()->user();

        if (auth()->user() !== null) {

            if (auth()->user()->hasRole('admin')) {
                $ratings = BookingRating::orderBy('id', 'desc');
            } else {
                $ratings = BookingRating::where('customer_id', $user->id);
            }
        }


        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $ratings->count();
            }
        }

        $ratings = $ratings->paginate($per_page);
        $data = BookingRatingResource::collection($ratings);

        return response([
            'pagination' => [
                'total_ratings' => $data->total(),
                'per_page' => $data->perPage(5),
                'currentPage' => $data->currentPage(),
                'totalPages' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'next_page' => $data->nextPageUrl(),
                'previous_page' => $data->previousPageUrl(),
            ],
            'data' => $data,
        ]);
    }
    public function getRatingsList(Request $request)
    {
        $type = $request->type;

        if ($type === 'user_service_rating') {
            $user = auth()->user();

            if (auth()->user() !== null) {

                if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('demo_admin')) {
                    $ratings = BookingRating::orderBy('id', 'desc');
                } else {
                    $ratings = BookingRating::where('customer_id', $user->id)->orderBy('id', 'desc');
                }
            }
        } elseif ($type === 'handyman_rating') {
            $ratings = HandymanRating::orderBy('id', 'desc');
        } else {
            return response()->json(['message' => 'Invalid type parameter'], 400);
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $ratings->count();
            }
        }

        $ratings = $ratings->paginate($per_page);
        $data = HandymanRatingResource::collection($ratings);

        return response([
            'pagination' => [
                'total_ratings' => $data->total(),
                'per_page' => $data->perPage(5),
                'currentPage' => $data->currentPage(),
                'totalPages' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'next_page' => $data->nextPageUrl(),
                'previous_page' => $data->previousPageUrl(),
            ],
            'data' => $data,
        ]);
    }
    public function deleteRatingsList($id, Request $request)
    {
        $type = $request->type;

        if (demoUserPermission()) {
            $message = __('messages.demo.permission.denied');
            return comman_message_response($message);
        }
        if ($type === 'user_service_rating') {
            $bookingrating = BookingRating::find($id);
            $msg = __('messages.msg_fail_to_delete', ['name' => __('messages.user_ratings')]);

            if ($bookingrating != '') {
                $bookingrating->delete();
                $msg = __('messages.msg_deleted', ['name' => __('messages.user_ratings')]);
            }
        } elseif ($type === 'handyman_rating') {
            $handymanrating = HandymanRating::find($id);
            $msg = __('messages.msg_fail_to_delete', ['name' => __('messages.handyman_ratings')]);

            if ($handymanrating != '') {
                $handymanrating->delete();
                $msg = __('messages.msg_deleted', ['name' => __('messages.handyman_ratings')]);
            }
        } else {
            $msg = "Invalid type parameter";
            return comman_custom_response(['message' => $msg, 'status' => false]);
        }

        return comman_custom_response(['message' => $msg, 'status' => true]);
    }

    public function updateLocation(Request $request)
    {
        $bookingID = $request->input('booking_id');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');


        $data = [
            'booking_id' => $bookingID,
            'latitude' => $latitude,
            'longitude' => $longitude,

        ];
        $locations = LiveLocation::updateOrCreate(['booking_id' => $data['booking_id']], $data);
        $time_zone = getTimeZone();

        $datetime_in_timezone = Carbon::parse($locations->updated_at)->timezone($time_zone);

        $data['datetime'] = $datetime_in_timezone->toDateTimeString();

        $message = __('messages.location_update');
        return response()->json(['data' => $data, 'message' => $message], 200);
    }

    public function getLocation(Request $request)
    {
        $bookingID = $request->input('booking_id');

        $latestLiveLocation = Cache::remember('latest_live_location_' . $bookingID, 30, function () use ($bookingID) {
            return LiveLocation::where('booking_id', $bookingID)
                ->latest()
                ->first();
        });
        if (!$latestLiveLocation) {
            return response()->json(['error' => 'Live location not found for this booking ID'], 404);
        }

        $time_zone = getTimeZone();

        $datetime_in_timezone = Carbon::parse($latestLiveLocation->updated_at)->timezone($time_zone);

        $datetime = $datetime_in_timezone->toDateTimeString();
        $data = [
            'latitude' => $latestLiveLocation->latitude,
            'longitude' => $latestLiveLocation->longitude,
            'datetime' =>  $datetime,
        ];

        $message = __('messages.location_update');
        return response()->json(['data' => $data, 'message' => $message], 200);
    }

    /* ------------------------------------------------------------------
     |  BASE URL HELPERS
     * ------------------------------------------------------------------ */

    protected function getV1BaseUrl($isTest)
    {
        return $isTest
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox'
            : 'https://api.phonepe.com/apis/pg';
    }

    protected function getV2AuthBaseUrl($isTest)
    {
        return $isTest
            ? 'https://api-preprod.phonepe.com/apis/identity-manager'
            : 'https://api.phonepe.com/apis/identity-manager';
    }

    protected function getV2PgBaseUrl($isTest)
    {
        return $isTest
            ? 'https://api-preprod.phonepe.com/apis/pg'
            : 'https://api.phonepe.com/apis/pg';
    }

    protected function generateChecksum($payload, $apiPath, $saltKey, $saltIndex = 1)
    {
        return hash('sha256', $payload . $apiPath . $saltKey) . '###' . $saltIndex;
    }

    /* ------------------------------------------------------------------
     |  V1 INITIATE
     * ------------------------------------------------------------------ */

    public function initiate(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $gateway = PaymentGateway::where('type', 'phonepe')->where('status', 1)->first();
        if (!$gateway) {
            return response()->json(['error' => 'PhonePe not enabled'], 400);
        }

        $config = json_decode($gateway->value, true);

        $merchantId = $config['merchant_id_v1'] ?? null;
        $saltKey    = $config['salt_key_v1'] ?? null;
        $saltIndex  = $config['salt_index_v1'] ?? 1;

        if (!$merchantId || !$saltKey) {
            return response()->json(['error' => 'PhonePe V1 config missing'], 400);
        }

        $txnId  = 'TXN' . time();
        $amount = (int) round($request->amount * 100);

        $payload = [
            'merchantId'            => $merchantId,
            'merchantTransactionId' => $txnId,
            'amount'                => $amount,
            'redirectUrl'           => url('/api/phonepe/callback'),
            'redirectMode'          => 'POST',
            'callbackUrl'           => url('/api/phonepe/callback'),
            'paymentInstrument'     => ['type' => 'PAY_PAGE'],
        ];

        $base64   = base64_encode(json_encode($payload));
        $apiPath = '/pg/v1/pay';

        $checksum = $this->generateChecksum($base64, $apiPath, $saltKey, $saltIndex);

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'X-VERIFY'      => $checksum,
            'X-MERCHANT-ID' => $merchantId,
        ])->post($this->getV1BaseUrl($gateway->is_test) . $apiPath, [
            'request' => $base64
        ]);

        return response()->json($response->json(), $response->status());
    }

    /* ------------------------------------------------------------------
     |  V2 OAUTH TOKEN
     * ------------------------------------------------------------------ */

    protected function getAccessTokenV2($gateway, array $config)
    {
        try {
            $authBaseUrl = $this->getV2AuthBaseUrl($gateway->is_test);

            $response = Http::asForm()->post(
                $authBaseUrl . '/v1/oauth/token',
                [
                    'client_id'      => $config['client_id_v2'],
                    'client_secret'  => $config['client_secret_v2'],
                    'client_version' => 1,
                    'grant_type'     => 'client_credentials',
                ]
            );

            if (!$response->successful()) {
                \Log::error('PhonePe V2 OAuth failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json()['access_token'] ?? null;
        } catch (\Exception $e) {
            \Log::error('PhonePe V2 OAuth exception: ' . $e->getMessage());
            return null;
        }
    }

    /* ------------------------------------------------------------------
     |  V2 INITIATE
     * ------------------------------------------------------------------ */

    public function initiateV2(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $gateway = PaymentGateway::where('type', 'phonepe')->where('status', 1)->first();
        if (!$gateway) {
            return response()->json(['error' => 'PhonePe not enabled'], 400);
        }
        $is_test = $gateway->is_test;

        if ($is_test == 1) {

            $json_data = $gateway->value;
        } else {

            $json_data = $gateway->live_value;
        }
        $config = json_decode($json_data, true);

        foreach (['client_id_v2', 'client_secret_v2', 'merchant_id_v2'] as $key) {
            if (empty($config[$key])) {
                return response()->json(['error' => "Missing {$key}"], 400);
            }
        }

        $token = $this->getAccessTokenV2($gateway, $config);
        if (!$token) {
            return response()->json(['error' => 'OAuth token generation failed'], 401);
        }

        $orderId = 'TXN_' . time();
        $amount  = (int) round($request->amount * 100);

        $payload = [
            'merchantOrderId' => $orderId,
            'amount'          => $amount,
            'expireAfter'     => 1200,
            'paymentFlow'     => [
                'type'    => 'PG_CHECKOUT',
                'message' => 'Payment for ' . $orderId,
                'merchantUrls' => [
                    'redirectUrl' => url('/api/phonepe/callback-v2?merchantOrderId=' . $orderId),
                ],
            ],
        ];

        $pgBaseUrl = $this->getV2PgBaseUrl($gateway->is_test);

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
        ])->post($pgBaseUrl . '/checkout/v2/pay', $payload);

        return response()->json($response->json(), $response->status());
    }

    /* ------------------------------------------------------------------
     |  V2 STATUS CHECK
     * ------------------------------------------------------------------ */

    protected function verifyPaymentStatusV2($gateway, $token, $orderId)
    {
        $pgBaseUrl = $this->getV2PgBaseUrl($gateway->is_test);

        $response = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $token,
        ])->get($pgBaseUrl . "/checkout/v2/order/{$orderId}/status");

        return $response->successful() ? $response->json() : null;
    }

    /* ------------------------------------------------------------------
     |  V2 CALLBACK
     * ------------------------------------------------------------------ */

    public function callbackV2(Request $request)
    {
        $orderId = $request->query('merchantOrderId');
        if (!$orderId) {
            return redirect('/payment/failed');
        }

        $gateway = PaymentGateway::where('type', 'phonepe')->where('status', 1)->first();
        if (!$gateway) {
            return redirect('/payment/failed');
        }
        $is_test = $gateway->is_test;

        if ($is_test == 1) {

            $json_data = $gateway->value;
        } else {

            $json_data = $gateway->live_value;
        }
        $config = json_decode($json_data, true);

        $token  = $this->getAccessTokenV2($gateway, $config);

        if (!$token) {
            return redirect('/payment/failed');
        }

        $status = $this->verifyPaymentStatusV2($gateway, $token, $orderId);
        if (!$status) {
            return redirect('/payment/failed');
        }

        if (($status['state'] ?? '') === 'COMPLETED') {
            return redirect('/payment/success?transaction_id=' . $orderId);
        }

        return redirect('/payment/failed?transaction_id=' . $orderId);
    }

    /**
     * Get available time slots for a specific date
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * Query Parameters:
     * - date (required): Date in Y-m-d format (e.g., 2026-03-23)
     * - service_id (required): Filter by specific service ID
     * 
     * Response:
     * {
     *   "status": true,
     *   "data": {
     *     "date": "2026-03-23",
     *     "service_id": 153,
     *     "booked_slots": ["10:00", "13:00"],
     *     "available_slots": ["09:00", "11:00", "12:00", "14:00", "15:00", "16:00", "17:00", "18:00"],
     *     "total_slots": 10,
     *     "booked_count": 2,
     *     "available_count": 8
     *   },
     *   "message": "Available slots retrieved successfully"
     * }
     */
    public function getAvailableSlots(Request $request)
    {
        try {
            // Validate request - service_id is now required
            $request->validate([
                'date' => 'required|date_format:Y-m-d',
                'service_id' => 'required|integer|exists:services,id'
            ]);

            $date = $request->input('date');
            $serviceId = $request->input('service_id');

            // Use BookingSlotService to get slot information
            $slotService = new \App\Services\BookingSlotService();
            $slotInfo = $slotService->getSlotInfo($date, $serviceId);

            return response()->json([
                'status' => true,
                'data' => $slotInfo,
                'message' => __('messages.available_slots_retrieved')
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to get available slots', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => __('messages.something_went_wrong'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
