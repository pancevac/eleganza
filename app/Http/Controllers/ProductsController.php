<?php namespace App\Http\Controllers;

use App\Attribute;
use App\Brand;
use App\Cart;
use App\Category;
use App\Helper;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Images;
use App\Language;
use App\Osobina;
use App\Product_group;
use App\Property;
use App\Relation;
use App\Setting;
use Carbon\Carbon;
use Illuminate\Support\Str;
use File;
use App\Set;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Intervention\Image\ImageServiceProvider;

class ProductsController extends Controller {

	public function __construct(){
		$this->middleware('menager', ['except' => ['removeToSessionAjaxFront', 'addToSessionFront', 'addToWishlistFront', 'removeToWishlist', 'imageUpload']]);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$slug = 'products';
		$products = Product::newFilteredAdminProducts(Session::get('title'), Session::get('cat'), Product::$list_limit, 1, Session::get('od'), Session::get('do'));
		$catids = Category::pluck('title', 'id');

		return view('admin.products.index', compact('products', 'slug', 'catids'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$slug = 'products';
		$catids = array();
		$brands = Brand::where('publish', 1)->pluck('title', 'id')->prepend('Bez brenda', 0);
        $sets = Set::where('publish', 1)->pluck('title', 'id');

        $setting = Setting::first();
        if($setting->colorDependence){
            $boja = Property::where('title', 'Boja')->first();
            if(isset($boja)){
                $colors = $boja->attribute()->pluck('title', 'id')->prepend('Nema boja', 0);
            }else{
                $colors = [0 => 'Nema boja'];
            }
        }
        if($setting->materialDependence){
            $materials = [0 => 'Nema materijala'];
        }

		//$cats = Category::where('publish', 1)->pluck('id', 'id');
		//$products = Product::where('publish', 1)->pluck('code', 'id');

		return view('admin.products.create', compact('slug', 'catids', 'brands', 'colors', 'cats', 'products', 'setting', 'materials', 'sets'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Requests\CreateProductRequest $request)
	{
		$product = \Auth::user()->product()->save(new Product(request()->except('image')));

		$product->slug = str_slug(request('title'));
        $product->publish = request('publish')?: 0;
        $product->price_outlet = Product::calculateDiscount($product->price_small, request('discount'));
        $product->update(request()->except('image', 'tmb', 'price_outlet'));

        $product->category()->sync(request('kat'));

        $product->update(['image' => $product->storeImage()]);

		return redirect('admin/products/'.$product->id.'/edit')->with('done', 'Proizvod je kreiran.');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id){

		$product = Product::find($id);
		$set = Category::getSetByTopCategory($product);
		$slug = 'products';
		$catids = $product->category->pluck('id')->toArray();
        $brands = Brand::where('publish', 1)->pluck('title', 'id')->prepend('Bez brenda', 0);
        $sets = Set::where('publish', 1)->pluck('title', 'id');
		$cats = Category::where('publish', 1)->pluck('title', 'id')->toArray();
		$products = Product::where('publish', 1)->pluck('code', 'id')->toArray();
		$property = Property::where('title', 'Boja')->first();
        $category = Category::getLastCategoryObject($product);
		if(isset($property) && false){ $colors = $property->attribute()->pluck('title', 'id'); }else{ $colors = []; }

        $setting = Setting::first();
        if($setting->colorDependence){
            $boja = Property::where('title', 'Boja')->first();
            if(isset($boja)){
                $colors = $boja->attribute()->pluck('title', 'id')->prepend('Nema boja', 0);
            }else{
                $colors = [0 => 'Nema boja'];
            }
        }
        if($setting->materialDependence){
            $materials = [0 => 'Nema materijala'];
        }
        $attributeIds = $product->attribute()->pluck('attributes.id')->toArray();
        if($product->brand_id > 0){
            $brand = Brand::find($product->brand_id);
            $brand? $collection = $brand->attribute()->where('publish', 1)->orderBy('order', 'ASC')->get() : $collection = [];
        }else{
            $collection = [];
        }
		return view('admin.products.edit', compact('slug', 'product', 'catids', 'brands', 'colors', 'cats', 'products', 'prod_ids', 'cat_ids', 'colors', 'category', 'primaries', 'languages', 'setting', 'materials', 'attributeIds', 'collection', 'set', 'sets'));
	}

	public function cloneProduct($id)
	{
		$product = Product::find($id);

		$new = new Product();
		$new->user_id = $product->user_id;
		$new->brand_id = $product->brand_id;
		$new->set_id = $product->set_id;
		$new->title = $product->title;
		$new->slug = str_slug($product->title);
		$new->short = $product->short;
		$new->body = $product->body;
		$new->body2 = $product->body2;
		$new->price_small = $product->price_small;
		$new->amount = $product->amount;
		$new->sold = $product->sold;
		$new->publish_at = $product->publish_at;
		$new->publish = $product->publish;
		$new->save();

		$new = Product::orderBy('id', 'DESC')->first();
		if(count($product->category)>0){
		    $ids = $product->category->pluck('id')->toArray();
		    $new->category()->sync($ids);
        }
        if(count($product->attribute)>0){
            $ids = $product->attribute->pluck('id')->toArray();
            $new->attribute()->sync($ids);
        }

		return redirect('admin/products/'.$new->id.'/edit')->with('done', 'Proizvod je kloniran');
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Requests\UpdateProductRequest $request, $id)
	{
		$product = Product::find($id);
		$product->update(request()->except('image'));

        $product->featured = request('featured')?: 0;
        $product->publish = request('publish')?: 0;
		$product->price_outlet = Product::calculateDiscount($product->price_small, request('discount'));

		$product->update($request->except('publish', 'image', 'tmb', 'price_outlet'));

        $product->category()->sync($request->input('kat'));

        $product->update(['image' => $product->storeImage()]);

		//Product::setSlug($product->id);
		return redirect('admin/products/'.$id.'/edit')->with('done', 'Proizvod je izmenjen.');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

	public function delete($id)
	{
		$product = Product::findOrFail($id);
		$images = Product::where('image', $product->image)->where('id', '<>', $product->id)->get();
		if(count($images)==0) File::delete($product->image);
		$tmbs = Product::where('tmb', $product->image)->where('id', '<>', $product->id)->get();
		if(count($tmbs)==0) File::delete($product->tmb);
		/*$relation = Relation::where('product_id', $product->id)->first();
		if(isset($relation)){ $relation->delete(); }*/
		$product->delete();
		return redirect('admin/products')->with('done', 'Proizvod je obrisan.');
	}

	public function deletepostimg($id)
	{
		$product = Product::find($id);
		if(isset($product)){
			File::delete($product->image);
			$product->update(array('image' => ''));
			return 'done';
		}else{
			return 'error';
		}
	}

	public function deleteimg($id)
	{
		$product = Product::findOrFail($id);
		File::delete($product->image);
        File::delete($product->tmb);
		$product->update(array('image' => null, 'tmb' => null));
		return view('admin.products.image_append');
	}

	public function deleteimgtmb($id)
	{
		$product = Product::findOrFail($id);
		File::delete($product->tmb);
		$product->update(array('tmb' => ''));
		return view('admin.products.image_append_tmb');
	}

	public function removeAll(Request $request){
		$ids = $request->input('all');
		if(isset($ids)){
			foreach($ids as $i){
				$product = Product::find($i);
				File::delete($product->image);
				$product->delete();
			}
		}
		return redirect()->back()->with('done', 'Proizvodi su obrisani.');
	}

	public function publish($id){
		$val = Input::get('val');
		if($val == 'true'){ $primary = 1; }else{ $primary = 0; }
		$product = Product::find($id)->update(array('publish' => $primary));
		if(isset($product)){ return 'da'; }else{ return 'ne'; }
	}

	public function nocodepublish($id){
		$product = Product::find($id)->update(array('publish' => 1));
		if(isset($product)){ return 'da'; }else{ return 'ne'; }
	}

	public function nocodeunpublish($id){
		$product = Product::find($id)->update(array('publish' => 0));
		if(isset($product)){ return 'da'; }else{ return 'ne'; }
	}

	public function search(Request $request){
		$title = $request->input('title');
		$od = $request->input('od');
		$do = $request->input('do');
		$cat = $request->input('category_id');
		\Session::put('title', $title); \Session::put('od', $od); \Session::put('do', $do); \Session::put('cat', $cat);
		return redirect('admin/products');
	}

	public function search2(Request $request, $id){
		$title = $request->input('title');
		$od = $request->input('od');
		$do = $request->input('do');
		$cat = $request->input('category_id');
		Session::put('title', $title); Session::put('od', $od); Session::put('do', $do); Session::put('cat', $cat);
		return redirect('admin/carts/add/'.$id);
	}

	public function clear()
	{
		Session::put('title', '');
		Session::put('cat', 0);
		Session::put('od', '');
		Session::put('do', '');
		return redirect('admin/products');
	}

	public function zelja(){
		return 'zelja';
	}

	public function addToCart(Request $request, $id)
	{
		$cart = Cart::find($id);
		if($request->input('all') != null){
			$cart->product()->attach($request->input('all'));
			return redirect()->back()->with('done', 'Dodato u korpu.');
		}else{
			return redirect()->back()->with('error', 'Proizvod nije prinadjen.');
		}

	}

	public function cart(){
		/*\Session::forget('korpa');*/
		$catids = Category::where('publish', 1)->pluck('title', 'id');
		$osobinas = Osobina::all();
		$products = Product::filteredProducts(Session::get('title'), Session::get('cat'), Session::get('od'), Session::get('do'));
		return view('admin.products.cart', compact('catids', 'osobinas', 'products'));
	}

	public function cartsearch(Request $request){
		/*Session::forget('filter');*/
		$cat = $request->input('category_id'); Session::put('cat', $cat);
		$f = $request->input('filter'); Session::put('filter', $f);
		$br = count($request->input('filter'));
		$catids = Category::where('publish', 1)->pluck('title', 'id');
		$osobinas = Osobina::all();
		$products = Product::filteredCartProducts($cat, $f); $c = false;
		if($cat > 0){ $c = true;}
		if($br > 0){
			$products = Product::filtriraj($products, $c, $br);
		}
		return view('admin.products.cart', compact('catids', 'osobinas', 'products', 'br'));
	}

	public function addToSession(Request $request, $id)
	{
		Cart::addToSession($request->input('all'));
		return redirect()->back();
	}

	public function addToSessionFront(Request $request, $id)
	{
		$product = Product::find($id);
		$request->input('qty')? $qty = $request->input('qty') : $qty = 1;
		$request->input('size')? $size = $request->input('size') : $size = 0;
		$request->input('color')? $color = $request->input('color') : $color = 0;
		$request->input('material')? $material = $request->input('material') : $material = 0;
		if($product){
			Cart::addToSession($product->id, $qty, $size, $color, $material);
			return array(url('admin/products/removetosession/'.$product->id), $product->title, count(\Session::get('korpa')));
		}else{
			return 'error';
		}
	}

	public function addToWishlistFront(Request $request, $id)
	{
		$product = Product::find($id);
		$request->input('qty')? $qty = $request->input('qty') : $qty = 1;
		$request->input('size')? $size = $request->input('size') : $size = 0;
		$request->input('color')? $color = $request->input('color') : $color = 0;
		if($product){
			Cart::addToWishlist($product->id, $qty, $size, $color);
			return array(url('admin/products/removetowishlist/'.$product->id), $product->title);
		}else{
			return 'error';
		}
	}

	public function removeToSession(Request $request)
	{
		if(\Session::has('korpa') && is_array(\Session::get('korpa'))) {
			Cart::removeToSession($request->input('all'));
		}
		return redirect()->back();
	}

	public function removeToSessionAjax(Request $request, $id)
	{
		if(\Session::has('korpa') && is_array(\Session::get('korpa'))) {
			Cart::removeToSession(array($id));
		}
		return redirect()->back();
	}

	public function removeToSessionAjaxFront(Request $request, $id)
	{
		$product = Product::find($id);
		if(\Session::has('korpa') && is_array(\Session::get('korpa'))) {
			Cart::removeToSession(array($id));
		}
		return array(url('admin/products/addtosession/'.$id), $product->title, count(\Session::get('korpa')));
	}

	public function removeToWishlist(Request $request, $id){
		$product = Product::find($id);
		$cookie = \App::make('CodeZero\Cookie\Cookie');
		if(count($cookie->get('korpa')) > 0) {
			Cart::removeToWishlist($id);
		}
		return redirect()->back()->with('done', 'REMOVED FROM WISHLIST');
	}

	public function addkol($id){
		$p = Product::find($id);
		$sum = Input::get('kol');
		return $p->price_small * $sum;
	}

	public function image($id){
		$slug = 'products';
		$setting = Setting::first();
		$images = Images::where('product_id', $id)->get();
		$product = Product::find($id);
		$property = Property::where('id', '36')->first(); //boja
		$material = Property::find(14); //materijal
		isset($property)? $colors = Product::getColorsList() : $colors = [];
		isset($material)? $materials = Product::getMaterialsList() : $materials = [];
		return view('admin.products.image', compact('slug', 'images', 'product', 'colors', 'materials', 'setting'));
	}

	public function deleteImage($id){
		$img = Images::find($id);
		if(isset($img)){
			File::delete($img->file_path);
			$img->delete();
			return 'done';
		}else{
			return 'error';
		}
	}

	public function changeColor($id){
		$color = Input::get('color');
		$image = Input::get('slika');
		$image = Images::find($image);
		$image->color = $color;
		$image->update();
		return 'da';
	}

	public function changeMainColor($id){
		$color = Input::get('color');
		$product = Product::find($id);
		$product->color = $color;
		$product->update();
		return 'da';
	}

	public function changeMaterial($id){
		$material = Input::get('material');
		$slika = Input::get('slika');
		$image = Images::find($slika);
		$image->material = $material;
		$image->update();
		return 'da';
	}

	public function updateAttribute(Request $request, $id){
	    $product = Product::find($id);
	    if(count(request('attributes')) == 0){
	        $product->attribute()->sync([]);
        }else{
            $product->attribute()->sync(request('attributes'));
        }
        if(!empty(request('diameter'))){
            $product->diameter = request('diameter');
            $product->update();
        }
        if(!empty(request('water'))){
            $product->water = request('water');
            $product->update();
        }
        return redirect()->back()->with('done', 'Atributi su izmenjeni');
    }

    public function clearSearch(){
        \Session::forget('title'); \Session::forget('od'); \Session::forget('do'); \Session::forget('cat');
        return redirect('admin/products');
    }

    public function checkUpdate(Requests\CheckUploadRequest $request){
        if($request->file('file')){
            $fileName = 'check.' . $request->file('file')->getClientOriginalExtension();
            $request->file('file')->move(base_path() . '/public/trash/', $fileName);
        };
        $rows = \Excel::load('trash/'.$fileName, function($reader) {
            //$reader->select(['iznos', 'poziv_na_broj'])->get();
            $reader->get();
        })->get();

        if(count($rows)>0){
            $n=0; $o=0;
            foreach($rows as $row){
                if(!empty($row->sifra_artikla)){
                    $old = Product::where('code', $row->sifra_artikla)->first();
                    if(!empty($old)){
                        $old->user_id = auth()->user()->id;
                        $old->set_id = $row->set_id;
                        $old->brand_id = $row->brand_id;
                        $old->code = $row->sifra_artikla;
                        $old->title = $row->naziv_artikla;
                        $old->slug = str_slug($row->naziv_artikla);
                        $old->short = $row->kratak_opis;
                        $old->body = $row->opis;
                        $old->amount = $row->amount;
                        $old->price_small = $row->price;
                        $old->price_outlet = $row->price_outlet;
                        $old->publish = $row->publish;
                        $old->publish_at = Carbon::parse($row->publish_at)->format('Y-m-d H:m:s');
                        $old->update();

                        $atts = Helper::getExcelAttributes($row);
                        $cats = Helper::getExcelCategories($row);

                        $old->attribute()->sync($atts);
                        $old->category()->sync($cats);
                        $o++;
                    }else{
                        $new = new Product();
                        $new->user_id = auth()->user()->id;
                        $new->set_id = $row->set_id;
                        $new->brand_id = $row->brand_id;
                        $new->code = $row->sifra_artikla;
                        $new->title = $row->naziv_artikla;
                        $new->slug = str_slug($row->naziv_artikla);
                        $new->short = $row->kratak_opis;
                        $new->body = $row->opis;
                        $new->amount = $row->amount;
                        $new->price_small = $row->price;
                        $new->price_outlet = $row->price_outlet;
                        $new->publish = $row->publish;
                        $new->publish_at = Carbon::parse($row->publish_at)->format('Y-m-d H:m:s');
                        $new->save();

                        $atts = Helper::getExcelAttributes($row);
                        $cats = Helper::getExcelCategories($row);
                        $new->attribute()->sync($atts);
                        $new->category()->sync($cats);
                        $n++;
                    }
                }
            }
            return back()->with('done', 'Novih: ' . $n . ' | Izmenjenih: ' . $o);
        }else{
            return back()->with('error', 'Nema proizvoda');
        }
    }

    public function upload($product_id){
//        $product = Product::find($product_id);
//        $exploaded = explode(',', request('image'));
//        $data = base64_decode($exploaded[1]);
//        $filename = $product->slug . '-' . $product->id . '-' . str_random(2) . '.jpg';
//        $path = public_path('images/products/');
//        file_put_contents($path . $filename, $data);
//        $product->image = 'images/products/' . $filename;
//
//        //Helper::setProductTmbImage($product);
//        //$product->tmb = 'images/products/tmb/' . $filename;
//        $product->update();

        $product = Product::find($product_id);
        $product->update(['image' => $product->storeImage('file')]);

        return response()->json([
            'image' => url(\Imagecache::get($product->image, '50x73')->src),
        ]);
    }

    public function table(){
        $primary = Language::getPrimary();
        app()->setLocale($primary->locale);
        $slug = 'products';
        $brands = Brand::join('brand_translations', 'brands.id', '=', 'brand_translations.brand_id')
            ->where('brands.publish', 1)->where('brand_translations.locale', 'sr')
            ->pluck('brand_translations.title', 'brands.id')->prepend('Bez brenda', 0);
        $sets = Set::join('set_translations', 'sets.id', '=', 'set_translations.set_id')
            ->where('sets.publish', 1)->where('set_translations.locale', 'sr')
            ->pluck('set_translations.title', 'sets.id');

        $setting = Setting::first();

        return view('admin.products.table', compact('slug', 'brands', 'setting', 'sets'));
    }

    public function tableUpdate(){
        return request()->all();
    }

    public function discount(){
        $primary = Language::getPrimary();
        app()->setLocale($primary->locale);
        $slug = 'products';
        $products = Product::newFilteredDiscountAdminProducts(request('title'), request('category_id'),  request('brand_id'));
        $catids = Category::join('category_translations', 'categories.id', '=', 'category_translations.category_id')
            ->pluck('category_translations.title', 'categories.id');
        $brandIds = Brand::join('brand_translations', 'brands.id', '=', 'brand_translations.brand_id')
            ->pluck('brand_translations.title', 'brands.id')->prepend('Svi brendovi', 0);

        return view('admin.products.discount', compact('products', 'slug', 'catids', 'brandIds'));
    }

    public function discountUpdate(Requests\UpdateGroupDiscountRequest $request){
        $products = Product::whereIn('id', request('all'))->get();
        if(count($products)>0){
            foreach ($products as $product){
                $product->discount = request('discount');
                $product->price_outlet = Product::calculateDiscount(request('discount'), $product->price_small);
                $product->update();
            }
        }
        return redirect()->back()->with('done', 'Popusti su primenjeni');
    }

}
