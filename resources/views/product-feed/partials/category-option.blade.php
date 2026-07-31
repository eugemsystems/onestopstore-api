<option value="{{ $category->slug }}">
    {{ $prefix }}{{ $category->name }}
</option>

@foreach($category->subcategories as $child)
    @include('product-feed.partials.category-option', [
      'category' => $child,
      'prefix'   => $prefix.'— ',
    ])
@endforeach
