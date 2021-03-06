@extends('admin.index')

@section('bredcrumb')
    <ol class="breadcrumb">
        <li><a href="{{ url('home') }}">Home</a></li>
        <li><a href="{{ url('admin/insta-shops') }}">Insta Shop</a></li>
        <li class="active">Izmena</li>
    </ol>
@endsection

@section('header')
    {!! HTML::style('admin/plugins/select2/css/select2.min.css') !!}
@endsection

@section('content')
    <div class="row">
        {!! Form::open(['action' => ['InstaShopsController@update', $instaShop->id], 'method' => 'PUT', 'class' => 'form-horizontal', 'files' => true]) !!}
        <div class="col-md-7">
            <div class="panel panel-white">
                <div class="panel-heading clearfix">
                    <h4 class="panel-title"><i class="fa fa-instagram" style="margin-right: 5px"></i>Podaci o Insta Shopu</h4>
                </div>
                <div class="panel-body">
                    @include('admin.partials.errors')

                        <div class="form-group">
                            <label for="title" class="col-sm-2 control-label">Naziv <span class="crvena-zvezdica">*</span></label>
                            <div class="col-sm-10">
                                {!! Form::text('title', $instaShop->title, array('class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="image" class="col-sm-2 control-label">Slika</label>
                            @if($instaShop->image != null && $instaShop->image != '')
                                <div class="col-sm-10">
                                    <div class="place">
                                        <div class="pinner js-pinner" style="background-image: url({{ url($instaShop->image) }})">
                                            {{--<img src="{{ url($instaShop->image) }}" alt="{{ $instaShop->title }}" style="width: 100%; height: auto; margin-bottom: 10px">--}}
                                        </div>
                                        {!! Form::file('image') !!}
                                    </div>
                                </div>
                            @else
                                <div class="col-sm-10">
                                    {!! Form::file('image') !!}
                                </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="desc" class="col-sm-2 control-label">Opis</label>
                            <div class="col-sm-10">
                                {!! Form::text('desc', $instaShop->desc, array('class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="order" class="col-sm-2 control-label">Redosled <span class="crvena-zvezdica">*</span></label>
                            <div class="col-sm-10">
                                {!! Form::text('order', $instaShop->order, array('class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="featured" class="col-sm-2 control-label">Istaknuto</label>
                            <div class="col-sm-10">
                                {!! Form::checkbox('featured', 1, $instaShop->featured, ['class' => 'switch-state', 'data-on-color' => 'success', 'data-off-color' => 'danger', 'data-on-text' => 'DA', 'data-off-text' => 'NE', 'id' => 'active']) !!}
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="publish" class="col-sm-2 control-label">Vidljivo</label>
                            <div class="col-sm-10">
                                {!! Form::checkbox('publish', 1, $instaShop->publish, ['class' => 'switch-state', 'data-on-color' => 'success', 'data-off-color' => 'danger', 'data-on-text' => 'DA', 'data-off-text' => 'NE', 'id' => 'active']) !!}
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <div class="col-sm-12">
                                <input type="submit" class="btn btn-success pull-right" value="Izmeni">
                            </div>
                        </div>

                </div>
            </div>
        </div><!-- .col-md-8 -->
        <div class="col-md-5">
            <div class="panel panel-white">
                <div class="panel-heading clearfix">
                    <h4 class="panel-title"><i class="fa fa-instagram" style="margin-right: 5px"></i>Koordinate</h4>
                </div>
                <div class="panel-body" id="place">
                    @if(count($instaShop->coordinate)>0)
                        @foreach($instaShop->coordinate as $key => $coordinate)
                            <div style="padding: 10px 0">
                                <div class="form-group">
                                    <label for="publish" class="col-sm-2 control-label">Pin {{ $key }}</label>
                                    <div class="col-sm-8">
                                        {!! Form::select('products[]', $productIds, $coordinate->product_id, ['class' => 'form-control products']) !!}
                                        {!! Form::hidden('x[]', $coordinate->x) !!}
                                        {!! Form::hidden('y[]', $coordinate->y) !!}
                                    </div>
                                    <div class="col-md-2">
                                        <span class="fa fa-remove" data-index="{{ $key }}" style="padding: 11px 0; cursor: pointer;"></span>
                                    </div>
                                </div>
                                <hr>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </div><!-- .row -->

@endsection

@section('footer')
    {!! HTML::script('admin/plugins/select2/js/select2.min.js') !!}
    {!! HTML::script('themes/eleganza/js/pinner.js') !!}
@endsection

@section('footer_scripts')
    var br=0;

    $('.switch-state').bootstrapSwitch();

    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

    $(".products").select2({
        'placeholder': 'Izaberi proizvod',
    });

    function save(){
        toastr["success"]("Izmenjeno");
    }

    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }


    @if (Session::has('done'))
        toastr["success"]("{{ Session::get('done') }}");

        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": false,
            "progressBar": false,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }
    @endif

    pinner.init({!! $instaShop->coordinate !!}, onAdd);

    function onAdd(coords) {
        var place = $('#place');
        var pin = $('.products').length + 1;
        $.post('{{ url('admin/insta-shops/coordinate') }}', {'_token': '{{ csrf_token() }}', 'x': coords.x, 'y': coords.y, 'pin': pin}, function(data){
            place.append(data);
            $('.products').last().select2();
        });
    }

    $('.fa-remove').click(function(){
        var index = $(this).attr('data-index');
        console.log(index);
        $(this).parent().parent().parent().remove();
        pinner.removePin(parseInt(index));
    });
@endsection