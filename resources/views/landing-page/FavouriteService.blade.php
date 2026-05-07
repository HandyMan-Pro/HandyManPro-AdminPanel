@extends('landing-page.layouts.default')

@section('content')
<style>
  /* Wishlist: ensure "No data available" message is visible when table is empty */
  .dataTables_wrapper td.dataTables_empty {
    min-height: 120px !important;
    padding: 2rem !important;
    vertical-align: middle !important;
  }
</style>
<div class="section-padding">
    <div class="container">
        <service-page  link="{{ route('favouriteservice.data') }}" :is-empty="{{ $isEmpty ? 'true' : 'false' }}"/>
    </div>
</div>
@endsection
