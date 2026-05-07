@extends('landing-page.layouts.default')


@section('content')
<style>
   [data-bs-theme="dark"] .help-support-content,
   [data-bs-theme="dark"] .help-support-content *,
   body.dark .help-support-content,
   body.dark .help-support-content * {
      color: var(--bs-body-color) !important;
      background-color: transparent !important;
   }

   [data-bs-theme="dark"] .help-support-content a,
   body.dark .help-support-content a {
      color: var(--bs-link-color) !important;
   }

   [data-bs-theme="dark"] .help-support-content [style*="color:"],
   [data-bs-theme="dark"] .help-support-content [style*="background"],
   body.dark .help-support-content [style*="color:"],
   body.dark .help-support-content [style*="background"] {
      color: var(--bs-body-color) !important;
      background-color: transparent !important;
   }
</style>

<div class="my-5">
   <h4 class="text-center text-capitalize fw-bold my-5">{{__('landingpage.help_support')}}</h4>
   <div class="container help-support-content">
      {!! $help_content !!}
   </div>
 </div>
@endsection
