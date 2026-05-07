<!-- serviceaddon/service.blade.php -->
@if($service)
  <div class="d-flex gap-3 align-items-center">
    <img src="{{ getSingleMedia($service, 'service_attachment', null) }}" alt="service image" class="avatar avatar-40 rounded-pill">
    <div class="text-start">
      <p class="m-0">{{ $service->name }}</p>
    </div>
  </div>
@else
  <div class="align-items-center">
    <h6 class="text-center">{{ '-' }}</h6>
  </div>
@endif



