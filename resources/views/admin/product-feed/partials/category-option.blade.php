{{-- Parent Category --}}
<option value="{{ $category->slug }}" class="parent">
    {{ $prefix }}{{ $category->name }}
</option>

{{-- Child Categories --}}
@if($category->subcategories)
    @foreach($category->subcategories as $child)
        @include('admin.product-feed.partials.category-option', [
            'category' => $child,
            'prefix'   => $prefix . '├─ ',
        ])
    @endforeach
@endif

