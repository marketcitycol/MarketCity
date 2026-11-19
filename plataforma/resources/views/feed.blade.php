{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
  <title>MarketCity</title>
  <link>{{ url('/') }}</link>
  <description>Catálogo de MarketCity para Google Merchant Center y el catálogo de Meta</description>
@foreach ($products as $p)
  <item>
    <g:id>{{ $p->sku ?: 'P'.$p->id }}</g:id>
    <g:title>{{ \Illuminate\Support\Str::limit($p->name, 150, '') }}</g:title>
    <g:description>{{ \Illuminate\Support\Str::limit(trim(strip_tags($p->short_description ?: $p->description ?: $p->name)), 4900, '') }}</g:description>
    <g:link>{{ route('product', $p) }}</g:link>
@if ($img = $p->imageUrl())
    <g:image_link>{{ str_starts_with($img, 'http') ? $img : url($img) }}</g:image_link>
@endif
    <g:availability>{{ $p->inStock() ? 'in_stock' : 'out_of_stock' }}</g:availability>
@if ($p->isOnSale())
    <g:price>{{ $p->regular_price }} COP</g:price>
    <g:sale_price>{{ $p->price }} COP</g:sale_price>
@else
    <g:price>{{ $p->price }} COP</g:price>
@endif
    <g:condition>new</g:condition>
@if ($p->brand)
    <g:brand>{{ $p->brand->name }}</g:brand>
@else
    <g:identifier_exists>no</g:identifier_exists>
@endif
@if ($p->category)
    <g:product_type>{{ $p->category->name }}</g:product_type>
@endif
    <g:custom_label_0>{{ $p->store?->name }}</g:custom_label_0>
  </item>
@endforeach
</channel>
</rss>
