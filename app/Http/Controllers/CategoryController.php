<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Booking;
use App\Models\User;
use App\Models\ProviderDocument;
use App\Models\Coupon;
use App\Models\Documents;
use App\Models\Slider;
use App\Models\Blog;
use App\Http\Requests\CategoryRequest;
use App\Models\ProviderType;
use Yajra\DataTables\DataTables;
use League\CommonMark\Node\Block\Document as BlockDocument;
use App\Models\NotificationTemplate;
use App\Traits\TranslationTrait;
use App\Models\ServiceZone;
use App\Models\Shop;
use App\Models\ShopDocument;
use App\Models\LoyaltyEarnRule;
use App\Models\LoyaltyRedeemRule;
use App\Models\LoyaltyRedeemPartialRule;
use App\Models\LoyaltyReferralRule;
use App\Models\SeoSetting;
use App\Models\LoyaltyEarnServiceMapping;
use App\Models\LoyaltyRedeemServiceMapping;
use Illuminate\Support\Str;


class CategoryController extends Controller
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
        $pageTitle = trans('messages.list_form_title', ['form' => trans('messages.category')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        $globalSeoSetting = \App\Models\SeoSetting::first();

        if ($request->loyality_earn_rule_id) {
            $id = $request->loyality_earn_rule_id;
            $type = "loyality_earn_rule";
        } elseif ($request->loyality_redeem_rule_id) {
            $id = $request->loyality_redeem_rule_id;
            $type = "loyality_redeem_rule";
        } else {
            $id = null;
            $type = null;
        }
        return view('category.index', compact('pageTitle','auth_user','assets','filter', 'globalSeoSetting', 'id', 'type'));
    }


    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Category::with('translations');
        $filter = $request->filter;
        $primary_locale = app()->getLocale() ?? 'en';
        if (!$request->order) {
            $query->orderBy('created_at', 'DESC');
        }
        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query->withTrashed();
        }

        if ($request->type === 'loyality_earn_rule' && $request->id) {
            $serviceIds = LoyaltyEarnServiceMapping::where('loyalty_earn_id', $request->id)
                ->pluck('service_id');

            $query->whereIn('id', $serviceIds);
        }

        if ($request->type === 'loyality_redeem_rule' && $request->id) {
            $serviceIds = LoyaltyRedeemServiceMapping::where('loyalty_redeem_id', $request->id)
                ->pluck('service_id');

            $query->whereIn('id', $serviceIds);
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="category" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })

            // ->editColumn('name', function ($query) use ($primary_locale) {
            //     $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?? $query->name;
            //     if (auth()->user()->can('category edit')) {
            //         $link = '<a class="btn-link btn-link-hover" href=' . route('category.create', ['id' => $query->id]) . '>' . $name . '</a>';
            //     } else {
            //         $link = $name;
            //     }
            //     return $link;
            // })

            ->editColumn('name', function ($query) use ($primary_locale) {
                // Get the translated name (fallback to default name if not found)
                $name = $this->getTranslation($query->translations, $primary_locale, 'name', $query->name) ?: $query->name;

                // Check if the user has the 'category edit' permission
                if (auth()->user()->can('category edit')) {
                    // Create a link for category editing
                    $link = '<a class="btn-link btn-link-hover" href=' . route('category.create', ['id' => $query->id]) . '>' . $name . '</a>';
                } else {
                    $link = $name;
                }

                // Add the image column
                $imageUrls = getSingleMedia($query, 'category_image', null);

                // Ensure $imageUrls is an array, even if it's a single string
                if (!is_array($imageUrls)) {
                    $imageUrls = [$imageUrls]; // If it's a string, make it an array with one image
                }

                // Initialize an empty string to hold the image tags
                $imageTags = '';

                // Iterate through the images
                foreach ($imageUrls as $imageUrl) {
                    $imageTags .= '<img src="' . $imageUrl . '" alt="category image" class="avatar avatar-40 rounded-pill mr-2">';
                }

                // Return both the image and name with flexbox for alignment
                return '<div style="display: flex; align-items: center;">' .
                        $imageTags .
                        '<span style="margin-left: 10px;">' . $link . '</span>' .
                    '</div>';
            })


            ->filterColumn('name', function ($query, $keyword) {
                // Search in main name column AND all translations
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhereHas('translations', function ($query) use ($keyword) {
                            // Search in ALL language translations
                            $query->where('attribute', 'name')
                                ->where('value', 'LIKE', '%' . $keyword . '%');
                        });
                });
            })
            ->addColumn('action', function ($data) {
                return view('category.action', compact('data'))->render();
            })
            ->editColumn('is_featured', function ($query) {
                $disabled = $query->trashed() ? 'disabled' : '';

                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="category_featured" data-name="is_featured" ' . ($query->is_featured ? "checked" : "") . '  ' .  $disabled . ' value="' . $query->id . '" id="f' . $query->id . '" data-id="' . $query->id . '">
                        <label class="custom-control-label" for="f' . $query->id . '" data-on-label="' . __("messages.yes") . '" data-off-label="' . __("messages.no") . '"></label>
                    </div>
                </div>';
            })
            ->editColumn('status', function ($query) {
                $disabled = $query->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="category_status" ' . ($query->status ? "checked" : "") . '  ' . $disabled . ' value="' . $query->id . '" id="' . $query->id . '" data-id="' . $query->id . '">
                        <label class="custom-control-label" for="' . $query->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->editColumn('description', function ($query) use ($primary_locale) {
                $description = $this->getTranslation($query->translations, $primary_locale, 'description', $query->description) ?? $query->description;

                return $description ?? '-';
            })

            ->rawColumns(['action', 'status', 'check', 'is_featured', 'name', 'description', 'image'])
            ->toJson();
    }

    /* bulck action method */
    public function bulk_action(Request $request)
    {
        $request->validate([
            'rowIds' => 'required|string',
            'action_type' => 'required|string'
        ]);

        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;

        switch ($actionType) {
            case 'change-status':
                Category::whereIn('id', $ids)->update(['status' => $request->status]);
                return response()->json(['status' => true, 'message' => 'Status updated']);

            case 'change-featured':
                Category::whereIn('id', $ids)->update(['is_featured' => $request->is_featured]);
                return response()->json(['status' => true, 'message' => 'Featured updated']);

            case 'delete':
                if (!auth()->user()->can('category delete')) {
                    return response()->json(['status' => false, 'message' => trans('messages.permission_denied')]);
                }
                $categoriesWithSubcategories = Category::whereIn('id', $ids)
                    ->whereHas('subcategories')
                    ->pluck('id')
                    ->toArray();
                if (!empty($categoriesWithSubcategories)) {
                    return response()->json(['status' => false, 'message' => trans('messages.category_has_subcategories')]);
                }
                Category::whereIn('id', $ids)->delete();
                return response()->json(['status' => true, 'message' => 'Deleted']);

            case 'restore':
                if (!auth()->user()->can('category delete')) {
                    return response()->json(['status' => false, 'message' => trans('messages.permission_denied')]);
                }
                Category::whereIn('id', $ids)->restore();
                return response()->json(['status' => true, 'message' => 'Restored']);

            case 'permanently-delete':
                if (!auth()->user()->can('category delete')) {
                    return response()->json(['status' => false, 'message' => trans('messages.permission_denied')]);
                }
                $categoriesWithSubcategories = Category::withTrashed()->whereIn('id', $ids)
                    ->whereHas('subcategories')
                    ->pluck('id')
                    ->toArray();
                if (!empty($categoriesWithSubcategories)) {
                    return response()->json(['status' => false, 'message' => trans('messages.category_has_subcategories')]);
                }
                Category::whereIn('id', $ids)->forceDelete();
                return response()->json(['status' => true, 'message' => 'Permanently Deleted']);

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
        if (!auth()->user()->can('category add')) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $id = $request->id;
        $auth_user = authSession();
        $primary_locale = app()->getLocale() ?? 'en';
        $language_array = $this->languagesArray();
        $categorydata = Category::find($id);

        $pageTitle = trans('messages.update_form_title', ['form' => trans('messages.category')]);

        if ($categorydata == null) {
            $pageTitle = trans('messages.add_button_form', ['form' => trans('messages.category')]);
            $categorydata = new Category;
        }

        $globalSeoSetting = \App\Models\SeoSetting::first();

        return view('category.create', compact('pageTitle' ,'categorydata' ,'auth_user','language_array', 'globalSeoSetting'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryRequest $request)
    {
        if (demoUserPermission()) {
            return redirect()->back()
                ->withErrors(trans('messages.demo_permission_denied'))
                ->withInput();
        }

        $data = $request->all();
        $language_option = sitesetupSession('get')->language_option ?? ["ar", "nl", "en", "fr", "de", "hi", "it"];
        $defaultLanguage = getDefaultLanguage();
        $translatableAttributes = ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

        // Prepare base data (non-translatable fields)
        $mainData = $request->except(array_merge(['seo_image', 'translations'], $translatableAttributes));
        $mainData['is_featured'] = $request->has('is_featured') ? $request->is_featured : 0;
        $mainData['seo_enabled'] = $request->has('seo_enabled') ? $request->seo_enabled : 0;

        // Main table always stores English. On UPDATE: preserve existing if English field is empty.
        foreach ($translatableAttributes as $attr) {
            $submitted = $request->input($attr, '');
            if ($mainData['id'] && trim($submitted) === '') {
                $existing = Category::find($mainData['id']);
                $mainData[$attr] = $existing ? $existing->$attr : '';
            } else {
                $mainData[$attr] = $submitted;
            }
        }

        if (!empty($mainData['meta_title'])) {
            $mainData['slug'] = Str::slug($mainData['meta_title']);
        }

        // Build the translations array for saveTranslations().
        // The blade already posts every non-English language as translations[locale][field],
        // so we just pass it through as-is.
        if ($request->is('api/*')) {
            $data['translations'] = json_decode($data['translations'] ?? '{}', true);
        } else {
            $data['translations'] = $request->input('translations', []);
        }

        $primary_locale = app()->getLocale() ?? 'en';

        if (!$request->is('api/*') && is_null($request->id) && !$request->hasFile('category_image')) {
            return redirect()->route('category.create')
                ->withErrors(__('validation.required', ['attribute' => 'attachments']))
                ->withInput();
        }

        $result = Category::updateOrCreate(['id' => $mainData['id']], $mainData);

        $result->saveTranslations($data, $translatableAttributes, $language_option, $primary_locale);
        
        if ($request->hasFile('category_image')) {
            storeMediaFile($result, $request->file('category_image'), 'category_image');
        } elseif (!getMediaFileExit($result, 'category_image')) {
            return redirect()->route('category.create', ['id' => $result->id])
                ->withErrors(['category_image' => 'The attachments field is required.'])
                ->withInput();
        }
        
        // Handle SEO image upload via media library only
        if ($request->hasFile('seo_image')) {
            storeMediaFile($result, $request->file('seo_image'), 'seo_image');
        }

        $message = $result->wasRecentlyCreated
            ? trans('messages.save_form', ['form' => trans('messages.category')])
            : trans('messages.update_form', ['form' => trans('messages.category')]);

        if ($request->is('api/*')) {
            return comman_message_response($message);
        }

        return redirect(route('category.index'))->withSuccess($message);
    }




    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $locale = app()->getLocale();
        $category = \App\Models\Category::findOrFail($id);
        $globalSeoSetting = \App\Models\SeoSetting::first();

        // Fallback logic: use category meta if set, else global
        $metaTitle = $category->translate('meta_title', $locale) ?? $category->meta_title ?? $globalSeoSetting->meta_title ?? $category->name;
        $metaDescription = $category->translate('meta_description', $locale) ?? $category->meta_description ?? $globalSeoSetting->meta_description ?? '';
        $metaKeywords = $category->translate('meta_keywords', $locale) ?? $category->meta_keywords ?? $globalSeoSetting->meta_keywords ?? '';
        $slug = $category->translate('slug', $locale) ?? $category->slug ?? $globalSeoSetting->slug ?? '';
        // SEO image: try category's localized image, else global
        $seoImage = $category->getFirstMediaUrl('seo_image_' . $locale);
        if (empty($seoImage) && $globalSeoSetting) {
            $seoImage = $globalSeoSetting->getFirstMediaUrl('seo_image');
        }
        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.category')] );
        return view('category.index', compact('category', 'metaTitle', 'metaDescription', 'metaKeywords', 'slug', 'seoImage', 'pageTitle', 'globalSeoSetting'));
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
        if (demoUserPermission()) {
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        $category = Category::findOrFail($id);

        if ($category->subcategories()->exists()) {
            $msg = __('messages.category_has_subcategories');
            if (request()->is('api/*')) {
                return comman_message_response($msg, 400);
            }
            return comman_custom_response(['message' => $msg, 'status' => false], 400);
        }

        $category->delete();
        $msg = __('messages.msg_deleted', ['name' => __('messages.category')]);

        if (request()->is('api/*')) {
            return comman_message_response($msg);
        }
        return comman_custom_response(['message' => $msg, 'status' => true]);
    }
    // public function action(Request $request)
    // {
    //     $id = $request->id;
    //     $category  = Category::withTrashed()->where('id', $id)->first();
    //     $msg = __('messages.t_found_entry', ['name' => __('messages.category')]);
    //     if ($request->type == 'restore') {
    //         $category->restore();
    //         $msg = __('messages.msg_restored', ['name' => __('messages.category')]);
    //     }
    //     if ($request->type === 'forcedelete') {
    //         $category->forceDelete();
    //         $msg = __('messages.msg_forcedelete', ['name' => __('messages.category')]);
    //     }
    //     if (request()->is('api/*')) {
    //         return comman_message_response($msg);
    //     }
    //     return comman_custom_response(['message' => $msg, 'status' => true]);
    // }

    public function action(Request $request)
    {
        if (!auth()->user()->can('category delete')) {
            return response()->json(['status' => false, 'message' => trans('messages.permission_denied')]);
        }

        try {
            $id = $request->id;
            $category = Category::withTrashed()->findOrFail($id);
            $type = $request->type;

            switch ($type) {
                case 'restore':
                    $category->restore();
                    $message = trans('messages.restored_successfully');
                    break;

                case 'forcedelete':
                    if ($category->subcategories()->exists()) {
                        return response()->json(['status' => false, 'message' => trans('messages.category_has_subcategories')]);
                    }
                    $category->forceDelete();
                    $message = trans('messages.Categories_permanently_deleted');
                    break;

                default:
                    return response()->json(['status' => false, 'message' => trans('messages.invalid_action')]);
            }

            return response()->json(['status' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => trans('messages.something_wrong')]);
        }
    }

    public function check_in_trash(Request $request)
    {
        // Ensure $ids is always an array
        $ids = $request->ids;
        if (!is_array($ids)) {
            $ids = $ids ? [$ids] : [];
        }
        $type = $request->datatype;

        switch ($type) {
            case 'category':
                $InTrash = Category::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'subcategory':
                $InTrash = SubCategory::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'service':
                $InTrash = Service::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'servicepackage':
                $InTrash = ServicePackage::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'booking':
                $InTrash = Booking::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'user':
                $InTrash = User::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'providertype':
                $InTrash = ProviderType::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'providerdocument':
                $InTrash = ProviderDocument::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'coupon':
                $InTrash = Coupon::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'slider':
                $InTrash = Slider::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'document':
                $InTrash = Documents::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'blog':
                $InTrash = Blog::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'notificationtemplate':
                $InTrash = NotificationTemplate::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'servicezone':
                $InTrash = ServiceZone::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'shop':
                $InTrash = Shop::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'shopdocument':
                $InTrash = ShopDocument::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'loyaltyearnrule':
                $InTrash = LoyaltyEarnRule::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'loyaltyredeemrule':
                $InTrash = LoyaltyRedeemRule::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'loyaltyreferrerule':
                $InTrash = LoyaltyReferralRule::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            case 'loyaltyredeempartialrule':
                $InTrash = LoyaltyRedeemPartialRule::withTrashed()->whereIn('id', $ids)->whereNotNull('deleted_at')->get();
                break;
            default:
                break;
        }

        if (count($InTrash) === count($ids)) {
            return response()->json(['all_in_trash' => true]);
        }

        return response()->json(['all_in_trash' => false]);
    }
}
