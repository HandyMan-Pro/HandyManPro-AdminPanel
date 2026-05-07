<!-- subcategory/category.blade.php -->
@if($category)
  <div class="d-flex gap-3 align-items-center">
    <img src="{{ getSingleMedia($category, 'category_image', null) }}" alt="category image" class="avatar avatar-40 rounded-pill">
    <div class="text-start">
      <p class="m-0">{{ $category->name }}</p>
    </div>
  </div>
@else
  <div class="align-items-center">
    <h6 class="text-center">{{ '-' }}</h6>
  </div>
@endif
