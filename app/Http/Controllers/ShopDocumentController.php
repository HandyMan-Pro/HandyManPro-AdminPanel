<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Documents;
use App\Models\ShopDocument;
use Illuminate\Http\Request;

use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\ShopDocumentRequest;

class ShopDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index_data(DataTables $datatable, Request $request)
    {
        $provider_id = $request->provider_id;

        if (auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            $query = ShopDocument::with(['shop', 'document'])
                ->whereHas('shop', function ($q) use ($provider_id) {
                    $q->where('provider_id', $provider_id);
                })->withTrashed();
        } else {
            $query = ShopDocument::with(['shop', 'document'])
                ->whereHas('shop', function ($q) use ($provider_id) {
                    $q->where('provider_id', $provider_id);
                });
        }

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('is_verified', $filter['column_status']);
            }
        }

        return $datatable::of($query)
            ->addColumn('check', function ($query) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $query->id . '"  name="datatable_ids[]" value="' . $query->id . '" data-type="shopdocument" onclick="dataTableRowCheck(' . $query->id . ',this)">';
            })
            ->editColumn('shop_id', function ($query) {
                return view('shopdocument.shop', compact('query'))->render();
            })
            ->filterColumn('shop_id', function ($query, $keyword) {
                $query->whereHas('shop', function ($q) use ($keyword) {
                    $q->where('shop_name', 'like', '%' . $keyword . '%');
                });
            })
            // Show document name directly
            ->editColumn('document_id', function ($query) {
                return $query->document->name ?? '';
            })
            ->filterColumn('document_id', function ($query, $keyword) {
                $query->whereHas('document', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('document_id', function ($query, $order) {
                $query->select('shop_documents.*')
                    ->join('documents', 'documents.id', '=', 'shop_documents.document_id')
                    ->orderBy('documents.name', $order);
            })

            // Example: Verified badge
            ->editColumn('is_verified', function ($query) {
                $disabled = $query->trashed() ? 'disabled' : '';
                if (auth()->user()->hasAnyRole(['provider', 'demo_provider'])) {
                    if ($query->is_verified == 0) {
                        $status = '<span class="badge text-danger bg-danger-subtle">' . __('messages.not_verified') . '</span>';
                    } else {
                        $status = '<span class="badge text-success bg-success-subtle">' . __('messages.verified') . '</span>';
                    }
                    return $status;
                }
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input bg-primary change_status" data-type="shop_is_verified" data-name="shop_is_verified" ' . ($query->is_verified ? "checked" : "") . ' ' . $disabled . '  value="' . $query->id . '" id="' . $query->id . '" data-id="' . $query->id . '">
                        <label class="custom-control-label" for="' . $query->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })

            // Show action buttons
            ->editColumn('action', function ($query) {
                return view('shopdocument.action', compact('query'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['check', 'shop_id', 'document_id', 'action', 'is_verified'])
            ->toJson();
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if (!auth()->user()->can('shopdocument add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        $auth_user = authSession();
        $id = $request->id;
        $providerdocument = $request->providerdocument;

        if ($providerdocument != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('home'))
                ->withErrors(trans('messages.demo_permission_denied'));
        }

        $shopDocument = null;

        if ($request->id) {
            $shopDocument = ShopDocument::with('shop', 'document')->findOrFail($request->id);
        }

        $providerdata = User::findOrFail($providerdocument);

        $shop_list = Shop::where('provider_id', $providerdocument)->where('is_active', 1)->get();

        $pageTitle = trans('messages.add_button_form', ['form' => trans('messages.shop_document')]);

        return view('shopdocument.create', compact('pageTitle', 'shop_list', 'auth_user', 'providerdata', 'shopDocument'));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(ShopDocumentRequest $request)
    {
        $validated = $request->validated();

        $validated['is_verified'] = $validated['is_verified'] ?? 0;

        if ($request->id) {
            $shopDocument = ShopDocument::findOrFail($request->id);
            $shopDocument->update($validated);
        } else {
            $shopDocument = ShopDocument::create($validated);
        }

        if ($request->hasFile('shop_document')) {
            storeMediaFile($shopDocument, $request->shop_document, 'shop_document');
        }

        $message = $shopDocument->wasRecentlyCreated
            ? __('messages.save_form', ['form' => __('messages.shop_document')])
            : __('messages.update_form', ['form' => __('messages.shop_document')]);

        if ($request->is('api/*')) {
            return comman_message_response($message);
        }

        return redirect()
            ->route('shopdocument.show', $request->provider_id)
            ->with('success', $message);
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $auth_user = authSession();

        // Permission check
        if ($id != auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
            return redirect(route('home'))
                ->withErrors(trans('messages.demo_permission_denied'));
        }

        // Provider details
        $providerdata = User::findOrFail($id);

        $pageTitle = trans('messages.list_form_title', ['form' => trans('messages.shop_document')]);

        $filter = [
            'is_verified' => $request->is_verified,
        ];

        if (empty($providerdata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
            return redirect(route('provider.index'))->withError($msg);
        }

        return view('shopdocument.view', compact('pageTitle', 'providerdata', 'auth_user', 'filter'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-featured':
                $branches = ShopDocument::whereIn('id', $ids)->update(['is_verified' => $request->is_verified]);
                $message = 'Bulk ShopDocument Featured Updated';
                break;

            case 'delete':
                ShopDocument::whereIn('id', $ids)->delete();
                $message = 'Bulk Shop Document Deleted';
                break;

            case 'restore':
                ShopDocument::whereIn('id', $ids)->restore();
                $message = 'Bulk Shop Document Restored';
                break;

            case 'permanently-delete':
                ShopDocument::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Shop Document Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false, 'is_verified' => false, 'message' => 'Action Invalid']);
                break;
        }

        return response()->json(['status' => true, 'is_verified' => true, 'message' => $message]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShopDocument $shopDocument)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $shop_document = ShopDocument::findOrFail($id);

        $shop_document->delete();

        $msg = __('messages.msg_deleted', ['name' => __('messages.shop_document')]);

        return comman_custom_response([
            'message' => $msg,
            'status'  => true,
        ]);
    }



    public function action(Request $request)
    {
        $id = $request->id;

        $shop_document = ShopDocument::onlyTrashed()->find($id);

        if (!$shop_document) {
            return comman_custom_response([
                'message' => __('messages.not_found_entry', ['name' => __('messages.shop_document')]),
                'status'  => false,
            ]);
        }

        switch ($request->type) {
            case 'restore':
                $shop_document->restore();
                $msg = __('messages.msg_restored', ['name' => __('messages.shop_document')]);
                break;

            case 'forcedelete':
                $shop_document->forceDelete();
                $msg = __('messages.msg_forcedelete', ['name' => __('messages.shop_document')]);
                break;

            default:
                $msg = __('messages.invalid_action');
                return comman_custom_response(['message' => $msg, 'status' => false]);
        }

        return comman_custom_response(['message' => $msg, 'status' => true]);
    }

    /**
     * Load documents list via AJAX
     */
    public function loadDocuments(Request $request)
    {

            $shop_id = $request->get('shop_id');
            $document_id = $request->get('document_id');

            $documents = Documents::where('status', 1)
                ->where('type', 'shop_document')
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'is_required']);

            if ($shop_id) {
                $assignedDocumentIds = ShopDocument::where('shop_id', $shop_id)
                    ->when($document_id, function ($query) use ($document_id) {
                        return $query->where('document_id', '!=', $document_id);
                    })
                    ->pluck('document_id')
                    ->toArray();

                $documents = $documents->filter(function ($document) use ($assignedDocumentIds) {
                    return !in_array($document->id, $assignedDocumentIds);
                })->values();
            }

            return response()->json([
                'status' => true,
                'data' => $documents
            ]);

    }
}
