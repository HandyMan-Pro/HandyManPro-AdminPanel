<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointActivity;

use Yajra\DataTables\Facades\DataTables;
use App\Http\Resources\API\LoyaltyPointActivityResource;

class LoyaltyPointActivityController extends Controller
{
    public function index(Request $request)
    {
        $id = null;
        $type = null;

        return view('loyalty-history.index', compact('id', 'type'));
    }

    public function index_data(Request $request)
    {
        $history = LoyaltyPointActivity::with('user')->orderBy('id', 'desc');

        $auth_user = auth()->user();

        if ($request->type === 'user_points' && $request->filled('id')) {
            $history->where('user_id', $request->id);
        }

        if ($request->filled('filter')) {

            switch ($request->filter) {

                case 'last_week':
                    $history->where('created_at', '>=', now()->subWeek());
                    break;

                case 'last_month':
                    $start = now()->subMonth()->startOfMonth();
                    $end   = now()->subMonth()->endOfMonth();
                    $history->whereBetween('created_at', [$start, $end]);
                    break;

                case 'last_year':
                    $start = now()->subYear()->startOfYear();
                    $end   = now()->subYear()->endOfYear();
                    $history->whereBetween('created_at', [$start, $end]);
                    break;

                default:
                    // no filter
                    break;
            }
        }

        // Get date and time format from settings
        $sitesetup = json_decode(
            Setting::where('type', 'site-setup')->where('key', 'site-setup')->value('value') ?? '{}'
        );
        $date_format = optional($sitesetup)->date_format ?? "F j, Y";
        $time_format = optional($sitesetup)->time_format ?? "g:i A";
        $time_zone = optional($sitesetup)->time_zone ?? "UTC";

        return DataTables::of($history)
            ->filter(function ($history) use ($request) {
                $search = $request->input('search.value');
                if (!empty($search)) {
                    $history->whereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ["%{$search}%"]);
                    });

                    // Convert "Package Booking" → "package_booking"
                    $normalized = strtolower(str_replace(' ', '_', $search));

                    // SOURCE SEARCH (DB column)
                    $history->orWhere('source', 'like', "%{$normalized}%");
                }
            })
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="loyaltyredeemrule" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->addColumn('customer_name', function ($row) {
                $user = $row->user;
                return '<a href="' . route('points.dashboard', $user->id) . '">
                    <div class="d-flex gap-3 align-items-center">
                        <img src="' . getSingleMedia($user, 'profile_image', null) . '"
                        alt="avatar" class="avatar avatar-40 rounded-pill">

                        <div class="text-start">
                            <h6 class="m-0">' . e($user->first_name) . ' ' . e($user->last_name) . '</h6>
                            <span>' . e($user->email ?? '--') . '</span>
                        </div>
                    </div>
                </a>';
            })
            ->addColumn('loyalty_type', function ($row) {

                if ($row->source == 'service_booking' || $row->source == 'package_booking') {

                    return '<a href="' . route('booking.show', $row->related_id) . '">
                        <span class="text-primary">' . ucwords(str_replace('_', ' ', $row->source)) . '</span>
                    </a>';
                } else {
                    return ucwords(str_replace('_', ' ', $row->source));
                }
            })
            ->addColumn('date', function ($row) use ($date_format, $time_format, $time_zone) {
                try {
                    $date = \Carbon\Carbon::parse($row->created_at)->setTimezone($time_zone);
                    return $date->format($date_format . ' ' . $time_format);
                } catch (\Exception $e) {
                    return $row->created_at;
                }
            })
            ->addColumn('loyalty_points', function ($row) {
                if ($row->earn_type == 'credit') {
                    return '<div style="color: green;">+ ' . $row->points . '</div>';
                } else {
                    return '<div style="color: red;">- ' . $row->points . '</div>';
                }
            })
            ->rawColumns(['check', 'customer_name', 'loyalty_points', 'loyalty_type'])
            ->toJson();
    }

    public function getLoyaltyHistory(Request $request)
    {
        $user_id = $request->user_id;

        $perPage = min($request->input('per_page', 10), 100);
        $page    = $request->input('page', 1);

        $query = LoyaltyPointActivity::where('user_id', $user_id)
            ->orderBy('id', 'desc');

        // DATE FILTER
        if ($request->filled('filter')) {

            switch ($request->filter) {

                case 'last_week':
                    $query->where('created_at', '>=', now()->subWeek());
                    break;

                case 'last_month':
                    $start = now()->subMonth()->startOfMonth();
                    $end   = now()->subMonth()->endOfMonth();
                    $query->whereBetween('created_at', [$start, $end]);
                    break;

                case 'last_year':
                    $start = now()->subYear()->startOfYear();
                    $end   = now()->subYear()->endOfYear();
                    $query->whereBetween('created_at', [$start, $end]);
                    break;

                default:
                    // no filter
                    break;
            }
        }

        // PER PAGE CONFIG
        $per_page = config('constant.PER_PAGE_LIMIT');

        if ($request->has('per_page') && !empty($request->per_page)) {

            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }

            if ($request->per_page === 'all') {
                $per_page = $query->count();
            }
        }

        // PAGINATION
        $items = $query->paginate($perPage);

        $data = LoyaltyPointActivityResource::collection($items);

        $response = [
            'pagination' => [
                'total_items'   => $items->total(),
                'per_page'      => $items->perPage(),
                'currentPage'   => $items->currentPage(),
                'totalPages'    => $items->lastPage(),
                'from'          => $items->firstItem(),
                'to'            => $items->lastItem(),
                'next_page'     => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $data,
        ];

        return response()->json($response);
    }


    public function points_dashboard($id)
    {
        $user = User::findOrFail($id);

        $total_referral = User::where('referred_by', $user->id)->count();

        $total_loyalty_points = LoyaltyPointActivity::where('user_id', $user->id)->where('earn_type', 'credit')->sum('points');

        $id = $user->id;
        $type = 'user_points';

        return view('loyalty-history.loyalty-overview', compact('user', 'total_referral', 'total_loyalty_points', 'id', 'type'));
    }
}
