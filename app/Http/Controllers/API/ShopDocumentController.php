<?php

namespace App\Http\Controllers\API;

use App\Models\ShopDocument;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ShopDocumentResource;

class ShopDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getShopDocumentList(Request $request)
    {
        $provider_id = $request->provider_id ?? auth()->user()->id;

        $shopDocuments = ShopDocument::with('shop', 'document')
            ->whereHas('shop', function ($q) use ($provider_id) {
                $q->where('provider_id', $provider_id);
            });

        // Include trashed only for admin/demo_admin
        if (auth()->user()->hasRole(['admin', 'demo_admin'])) {
            $shopDocuments = ShopDocument::with('shop', 'document')->withTrashed();
            if ($request->has('provider_id') && !empty($request->provider_id)) {
                $shopDocuments = ShopDocument::with('shop', 'document')
                    ->whereHas('shop', function ($q) use ($provider_id) {
                        $q->where('provider_id', $provider_id);
                    })->withTrashed();
            }
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $shopDocuments->count();
            }
        }

        $shopDocuments = $shopDocuments->orderBy('created_at', 'desc')->paginate($per_page);

        $items = ShopDocumentResource::collection($shopDocuments);

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
        ];

        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
