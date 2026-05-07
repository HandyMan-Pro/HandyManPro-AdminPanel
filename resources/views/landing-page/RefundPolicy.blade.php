@extends('landing-page.layouts.default')


@section('content')
<style>
   [data-bs-theme="dark"] .refund-policy-content,
   [data-bs-theme="dark"] .refund-policy-content *,
   body.dark .refund-policy-content,
   body.dark .refund-policy-content * {
      color: var(--bs-body-color) !important;
      background-color: transparent !important;
   }

   [data-bs-theme="dark"] .refund-policy-content a,
   body.dark .refund-policy-content a {
      color: var(--bs-link-color) !important;
   }

   [data-bs-theme="dark"] .refund-policy-content [style*="color:"],
   [data-bs-theme="dark"] .refund-policy-content [style*="background"],
   body.dark .refund-policy-content [style*="color:"],
   body.dark .refund-policy-content [style*="background"] {
      color: var(--bs-body-color) !important;
      background-color: transparent !important;
   }
</style>

<div class="my-5">
   <h4 class="text-center text-capitalize fw-bold my-5">{{__('landingpage.refund_policy')}}</h4>
   <div class="container refund-policy-content">
      {!! $refund_content !!}
   </div>
 </div>
@endsection
