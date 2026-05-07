<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HandymanType;
use App\Http\Requests\HandymanTypeRequest;
use Yajra\DataTables\DataTables;
use App\Traits\TranslationTrait;
use Illuminate\Support\Facades\Auth;

class HandymanTypeController extends Controller
{
    use TranslationTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.handymantype')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('handymantype.index', compact('pageTitle','auth_user','assets','filter'));
    }




    public function index_data(DataTables $datatable,Request $request)
    {
        $query = HandymanType::with('translations');
        $filter = $request->filter;
        $user = auth()->user();
        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if ($user->hasAnyRole(['admin'])) {
            $query->withTrashed();
        }
        if ($user->hasAnyRole(['provider'])) {
            $adminIds = \App\Models\User::whereIn('user_type', ['admin','demo_Admin'])->pluck('id')->toArray();
            $idsToFilter = array_merge([$user->id], $adminIds);
            $query->whereIn('created_by',$idsToFilter)->withTrashed();
        }
        $primary_locale = app()->getLocale() ?? 'en';
        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })

            ->editColumn('name', function($query) use($user,$primary_locale){
                // Show translation for current locale, fall back to English (main column) for display
                $name = $query->getTranslatedAttribute('name', $primary_locale);
                if ($name === '') {
                    $name = $query->name; // English fallback for list display only
                }
                if($query->created_by === $user->id || $user->hasRole(['admin', 'demo_admin'])){
                // if (auth()->user()->can('handymantype edit')) {
                    $link = '<a class="btn-link btn-link-hover" href='.route('handymantype.create', ['id' => $query->id]).'>'.$name.'</a>';
                } else {
                    $link = $name;
                }
                return $link;
            })
            ->filterColumn('name',function($query,$keyword){
                // Search in main name column AND all translations
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'LIKE', '%'.$keyword.'%')
                        ->orWhereHas('translations', function($query) use ($keyword) {
                            // Search in ALL language translations
                            $query->where('attribute', 'name')
                                  ->where('value', 'LIKE', '%'.$keyword.'%');
                        });
                });
            })

            ->editColumn('commission' , function ($query){
                $commission = getPriceFormat($query->commission);
                if($query->type === 'percent'){
                    $commission = $query->commission. '%';
                }
                return $commission;
            })
            ->editColumn('type', function($query){
                return ucfirst($query->type);
            })
            ->editColumn('status' , function ($query) use ($user){
                if($query->created_by === $user->id || $user->hasRole(['admin', 'demo_admin'])){
                $disabled = $query->trashed() ? 'disabled': '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="handyman_type_status" '.($query->status ? "checked" : "").'  '.$disabled.' value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
                }else{
                    return '-';
                }
            })
            ->addColumn('created_by', function($handymantype) use($user){
                $display_name = $handymantype->created_by ? optional($handymantype->user)->first_name . ' ' . optional($handymantype->user)->last_name : '-';
                return $display_name;
            })
            ->filterColumn('created_by',function($query,$keyword) use($primary_locale){
                $query->whereHas('user',function ($q) use($keyword){
                    $q->where('display_name','like','%'.$keyword.'%');
                });
            })
            ->addColumn('action', function($handymantype) use($user){
                if($handymantype->created_by === $user->id || $user->hasRole(['admin', 'demo_admin'])){
                return view('handymantype.action',compact('handymantype'))->render();
                }else{
                    return '-';
                }
            })
            ->addIndexColumn()
            ->rawColumns(['check','name','action','status'])
            ->toJson();
    }

    /* bulck action method */
    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';
        switch ($actionType) {
            case 'change-status':
                $branches = HandymanType::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Handyman Type Status Updated';
                break;

            case 'delete':
                HandymanType::whereIn('id', $ids)->delete();
                $message = 'Bulk Handyman Type Deleted';
                break;

            case 'restore':
                HandymanType::whereIn('id', $ids)->restore();
                $message = 'Bulk Handyman Type Restored';
                break;

            case 'permanently-delete':
                HandymanType::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Handyman Type Permanently Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $id = $request->id;
        $auth_user = authSession();
        $primary_locale = app()->getLocale() ?? 'en';
        $language_array = $this->languagesArray();
        $handymantypedata = HandymanType::find($id);
        $pageTitle = trans('messages.update_form_title',['form'=>trans('messages.handymantype')]);

        if($handymantypedata == null){
            $pageTitle = trans('messages.add_button_form',['form' => trans('messages.handymantype')]);
            $handymantypedata = new HandymanType;
        }else{
            if ($handymantypedata->created_by !== auth()->user()->id && !auth()->user()->hasRole(['admin', 'demo_admin'])) {
                return redirect(route('handymantype.index'))->withErrors(trans('messages.demo_permission_denied'));
            }
        }
        
        return view('handymantype.create', compact('pageTitle' ,'handymantypedata' ,'auth_user','language_array' ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(HandymanTypeRequest $request)
    {
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->all();
        
        $defaultLanguage = getDefaultLanguage();
        
      
        
        // Additional validation for web requests to check translations
        if (!$request->is('api/*')) {
            $hasName = false;
            $defaultLanguage = getDefaultLanguage();
            
            // Check if default language name is provided
            if (!empty($request->name)) {
                $hasName = true;
            }
            
            // Check if any translation has a name
            if (!$hasName && isset($request->translations) && is_array($request->translations)) {
                foreach ($request->translations as $lang => $fields) {
                    if (!empty($fields['name'])) {
                        $hasName = true;
                        break;
                    }
                }
            }
            
            if (!$hasName) {
                return redirect()->back()
                    ->withErrors(['name' => __('messages.name_required_in_any_language')])
                    ->withInput();
            }
            
            // Validate uniqueness for the name
            $nameToCheck = $request->name;
            if (empty($nameToCheck) && isset($request->translations[$defaultLanguage]['name'])) {
                $nameToCheck = $request->translations[$defaultLanguage]['name'];
            }
            
            if (!empty($nameToCheck)) {
                $createdBy = Auth::id();
                $existingType = HandymanType::where('name', $nameToCheck)
                    ->where('created_by', $createdBy)
                    ->where('id', '!=', $request->id ?? 0)
                    ->first();
                    
                if ($existingType) {
                    return redirect()->back()
                        ->withErrors(['name' => __('validation.unique', ['attribute' => 'name'])])
                        ->withInput();
                }
            }
        }

        if($request->has('type') && $request->type == 'percentage'){
            $data['type'] = 'percent';
        }
        $language_option = sitesetupSession('get')->language_option ?? ["ar","nl","en","fr","de","hi","it"];

        $defaultLanguage = getDefaultLanguage();
        $translatableAttributes = ['name'];
        $primary_locale = app()->getLocale() ?? 'en';

        // Prepare data for main table - exclude translations and translatable attributes
        $mainData = $request->except(array_merge(['translations'], $translatableAttributes));

        // Main table always stores English (bare 'name' field = English tab).
        // On UPDATE: preserve existing English value if English field is empty.
        foreach ($translatableAttributes as $attr) {
            $submitted = $request->input($attr, '');
            if ($request->id && trim($submitted) === '') {
                $existing = HandymanType::find($request->id);
                $mainData[$attr] = $existing ? $existing->$attr : '';
            } else {
                $mainData[$attr] = $submitted;
            }
        }
        
       
        
        $result = HandymanType::updateOrCreate(['id' => $mainData['id'] ?? null], $mainData);
        
        if ($request->is('api/*')) {
            $data['translations'] = json_decode($data['translations'] ?? '{}', true);
        }
        if (!isset($data['translations']) || !is_array($data['translations'])) {
            $data['translations'] = [];
        }

        $result->saveTranslations($data, $translatableAttributes, $language_option, $primary_locale);
        
        $message = trans('messages.update_form',['form' => trans('messages.handymantype')]);
        if($result->wasRecentlyCreated){
            $message = trans('messages.save_form',['form' => trans('messages.handymantype')]);
        }
        if($request->is('api/*')) {
            return comman_message_response($message);
		}
        return redirect(route('handymantype.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $handymantype = HandymanType::find($id);
        $msg= __('messages.msg_fail_to_delete',['item' => __('messages.handymantype')] );

        if($handymantype != '') {
            $handymantype->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.handymantype')] );
        }
        if(request()->is('api/*')) {
            return comman_message_response($msg);
		}
        return comman_custom_response(['message'=> $msg, 'status' => true]);
    }
    public function action(Request $request){
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $id = $request->id;
        $handyman_type  = HandymanType::withTrashed()->where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.handymantype')] );
        if($request->type == 'restore') {
            $handyman_type->restore();
            $msg = __('messages.msg_restored',['name' => __('messages.handymantype')] );
        }
        if($request->type === 'forcedelete'){
            $handyman_type->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.handymantype')] );
        }
        if($request->is('api/*')) {
            return comman_message_response($msg);
		}

        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }
}
