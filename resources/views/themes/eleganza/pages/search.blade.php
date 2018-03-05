@extends('themes.'.$theme->slug.'.index')

@section('header-style')
    {!! HTML::style('themes/'.$theme->slug.'/css/jquery.toastmessage.css') !!}
@endsection

@section('content')
    <div>
        <div class=container>
            <nav aria-label=breadcrumb>
                <ol class=breadcrumb>
                    <li class=breadcrumb-item><a href="{{ url('/') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current=page>Pretraga</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class=page-header>
        <div class="container page-header__container">
            <h2 class=page-header__title>Pretraga</h2>
            Pretraga za termin: {{ $text }}
        </div>
    </div>

    <!-- Page content -->
    <section class="container content products-content">

        @if(count($products)>0)
        <div class="products-container">
            @if(false)
            <div class=results-header>
                <div class="e-select e-select--with-carrot">
                    <label for=order-by>Sortiraj po: </label>
                    <select id=order-by name="sort">
                        <option value=0>Prikaži po</option>
                        @if(request('sort') == 3)
                            <option value=2>Cijena: manja prema veća</option>
                            <option value=3 selected>Cijena: veća prema manjoj</option>
                        @elseif(request('sort') == 2)
                            <option value=2 selected>Cijena: manja prema veća</option>
                            <option value=3>Cijena: veća prema manjoj</option>
                        @else
                            <option value=2>Cijena: manja prema veća</option>
                            <option value=3>Cijena: veća prema manjoj</option>
                        @endif
                    </select>
                </div>
                <div class="e-select e-select--with-carrot">
                    <label for=how-many>Prikaži: </label>
                    <select id=how-many name="limit">
                        @if(request('limit') == 18)
                            <option value=9>9</option>
                            <option value=18 selected>18</option>
                        @else
                            <option value=9>9</option>
                            <option value=18>18</option>
                        @endif
                    </select>
                </div>
                {!! Form::close() !!}
                @if(false) {{ $products->appends(\Illuminate\Support\Facades\Input::all())->links( "pagination::bootstrap-4") }} @endif
            </div>
            @endif
            <ul class="product-list">
                @foreach($products as $product)
                    @php $link = url(\App\Product::getProductLink($product->id)); @endphp
                    <li class="product-item product-list__item product-list__item--search with-shadow">
                        <a href="{{ $link }}">
                            <div class=product-item__img-box>
                                {!! HTML::Image($product->image, $product->title) !!}
                                <ul class=product-item__actions>
                                    <li class="icon-btn icon-btn--inverse"><a href="{{ url('add-to-cart/'.$product->id) }}" class="addCart" style="z-index: 1;"><i class="fas fa-shopping-cart"></i></a> </li>
                                    <li class="icon-btn icon-btn--inverse"> <a href="{{ url('add-to-wishlist/'.$product->id) }}" class="addWish" style="z-index: 1;"><i class="fas fa-heart"></i></a> </li>
                                    <li class="icon-btn icon-btn--inverse"> <a href="{{ $link }}" style="z-index: 1;"><i class="fas fa-search"></i></a> </li>
                                </ul>
                            </div>
                            <div class=product-item__info-box>
                                <span class=product-item__brand>@if(isset($product->brand)) {{ $product->brand->title }} @endif</span>
                                <h2 class=product-item__name>{{ $product->title }}</h2>
                                @if($product->discount != null && $product->discount > 0)
                                    <span class=product-item__price>{{ $product->price_outlet }}</span>
                                @else
                                    <span class=product-item__price>{{ $product->price_small }}</span>
                                @endif
                            </div>
                            <button class="e-btn e-btn--primary e-btn--block">saznaj više</button>
                        </a>
                        @if($product->discount != null && $product->discount > 0)
                            <div class="status status--sale">popust {{ $product->discount }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

    </section>
    <!-- ./Page content -->

    @include('themes.'.$theme->slug.'.partials.newsletter')

@endsection

@section('footer_scripts')
    {!! HTML::script('themes/'.$theme->slug.'/js/jquery-2.2.4.min.js') !!}
    {!! HTML::script('themes/'.$theme->slug.'/js/jquery.toastmessage.js') !!}
    <script>
        $(function () {

            $('input[type="checkbox"]').click(function(){
                $('#page').val(1);
                $('#moja').submit();
            });

            $('.clean').parent().parent().parent().remove();

            $('select[name="sort"]').change(function(){
                $('#page').val(1);
                $('#moja').submit();
            });

            $('select[name="limit"]').change(function(){
                $('#page').val(1);
                $('#moja').submit();
            });

            $('#primeni').click(function(e){
                e.preventDefault();
                $('#page').val(1);
                $('#moja').submit();
            });

            $('.filter').not('.cijena').not('.promjer').each(function () {
                var count = $(this).find('input[type="checkbox"]').length;
                if(count == 0){
                    $(this).remove();
                }
            });

            $('.addWish').click(function(e){
                e.preventDefault();
                var link = $(this).attr('href');
                $.post(link, {_token: '{{ csrf_token() }}' }, function(data){
                    $().toastmessage('showSuccessToast', "proizvod je dodat u listu želja");
                });
            });

            $('.addCart').click(function(e){
                e.preventDefault();
                var link = $(this).attr('href');
                $.post(link, {_token: '{{ csrf_token() }}' }, function(data){
                    $().toastmessage('showSuccessToast', "proizvod je dodat u košaricu");
                });
            });
        });
    </script>
@endsection