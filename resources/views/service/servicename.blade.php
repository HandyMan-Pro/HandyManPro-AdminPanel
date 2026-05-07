<div class="d-flex align-items-center">
    @if($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $name }}" class="me-2 rounded-circle" width="40" height="40" style="object-fit: cover; border-radius: 6px;">
    @endif
    <span>{{ $name }}</span>
</div>
