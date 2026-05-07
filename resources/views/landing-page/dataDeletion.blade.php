@extends('landing-page.layouts.default')


@section('content')
<style>
   [data-bs-theme="dark"] .data-deletion-content,
   [data-bs-theme="dark"] .data-deletion-content *,
   body.dark .data-deletion-content,
   body.dark .data-deletion-content * {
      color: var(--bs-body-color) !important;
      background-color: transparent !important;
   }

   [data-bs-theme="dark"] .data-deletion-content a,
   body.dark .data-deletion-content a {
      color: var(--bs-link-color) !important;
   }

   [data-bs-theme="dark"] .data-deletion-content [style*="color:"],
   [data-bs-theme="dark"] .data-deletion-content [style*="background"],
   body.dark .data-deletion-content [style*="color:"],
   body.dark .data-deletion-content [style*="background"] {
      color: var(--bs-body-color) !important;
      background-color: transparent !important;
   }
</style>

<div class="my-5">
   <h4 class="text-center text-capitalize fw-bold my-5">{{__('landingpage.data_deletion_request')}}</h4>
   <div class="container data-deletion-content">
      {!! $deletion_content!!}
   </div>
 </div>
@endsection
