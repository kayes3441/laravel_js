<?php

namespace App\Http\Controllers;


use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use mysql_xdevapi\XSession;
use Validator;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        /**
         * @return $variant
         */
        $variants = Variant::orderBy('id','asc')->with('product_variants')->get();
//        return $variants;
        $results =[];
        foreach ($variants as $variant){
            $item = [];
            foreach ($variant->product_variants as $key=>$value){
                if(!in_array($value->variant,$item)){
                    $item[] = $value->variant;
                }
                else
                    break;
            }
            $results["$variant->title"]=$item;
        }

        //return $results;

        /*
         *
         */
        $products = Product::orderBy('id','desc')->with('product_variant_price')->paginate(5);
//        return $products;
        return view('products.index',compact('products','results'));
    }


    public function search(Request $request){
        //return $request->all();
        $variants = Variant::orderBy('id','asc')->with('product_variants')->get();
//        return $variants;
        $results =[];
        foreach ($variants as $variant) {
            $item = [];
            foreach ($variant->product_variants as $key => $value) {
                if (!in_array($value->variant, $item)) {
                    $item[] = $value->variant;
                } else
                    break;
            }
            $results["$variant->title"] = $item;
        }


        //$product_variant =ProductVariant:: where('variant',$request->variant)->first();

        $price_from = $request->price_from;
        $price_to   = $request->price_to;
        //$variant    = $product_variant;


        $date=dateFormat($request->date,'Y-m-d');

        $array = [$price_from ,$price_to];

        $products = Product::orderBy('id','desc')->with('product_variant_price')
            ->orwhere('title','like','%'.$request->title.'%')
            ->orWhereDate('created_at',$request->date)
//            ->orWhere(function ($query) use ($array) {
//                $query->whereHas('product_variant_price', function ($q) use ($array) {
//                    $from = $array[0];
//                    $to = $array[1];
//                    return $q->where('price','>=',$from)->where('price','<=',$to);
//                });
//            })
//            ->whereHas('product_variant_price',function ($q) use($array){
//                $from = $array[0];
//                $to = $array[1];
//                $q->whereBetween('price',[$from,$to]);
//            })
//            ->orWhereBetween('price',[$request->price_from,$request->price_to])
//            ->whereRaw("(product_variant_one = $product_variant->id or product_variant_two = $product_variant->id) or product_variant_three = $product_variant->id")
            ->paginate(5);

        return view('products.index',compact('products','results'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        //return $request->all();
        $validator = Validator::make($request->all(),[
            'title'             =>'required',
            'sku'               =>'required',
            'description'       =>'required',
            'variant'           =>'required',
            'price'             =>'required',
            'stock'             =>'required',
        ]);

        $product                = new Product();
        $product->title         = $request->product_name;
        $product->sku           = $request->product_sku;
        $product->description   = $request->product_description;
        $product->save();

        $product_image          = new ProductImage();
        if($request->hasFile('product_image')){
            foreach ($request->file('product_image') as $img){
                $img_file                   = $img;
                $image_name                 = $img_file->getClientOriginalName();
                $directory                  ='Product Images/';
                $img_file                   ->move($directory,$image_name);

                $product_image->product_id  =$product->id;
                $product_image->file_path   =$image_name;
                $product_image->save();
            }
        }


        $product_variant                    = new ProductVariant();
        foreach ($request->product_variant as $variant){

            $string = json_encode($variant);
            //return $string;
            $variants = json_decode($string);
            $option = $variants->option;
            foreach ($variants->value as $tag){

                $product_variant->product_id = $product->id;
                $product_variant->variant_id =$option;
                $product_variant->variant    = $tag;
                $product_variant->save();
            }
        }


        foreach ($request->product_preview as $prices){
            $pro_var_price = new ProductVariantPrice();

            $pr            = json_encode($prices);
            $price         = json_decode($pr);
            $attrs         = explode("/",$price->variant);

            $product_variant_ids = [];
            for ($i=0;$i<count($attrs)-1;$i++){
                $product_variant_ids[] = ProductVariant::select('id')->where('variant',$attrs[$i])->latest()->first()->id;
            }
            //return $product_variant_ids;
           // for($i = 1 ;$i<=count($product_variant_ids);$i++){
             foreach ($product_variant_ids as $key=>$value){
                if ($key=1){
                    $pro_var_price->{'product_variant_'.'one'}=$value;
                    break;
                }
                if ( $key=2){
                    $pro_var_price->{'product_variant_'.'two'}  =$value;
                    break;
                }
                elseif ($key=3){
                    $pro_var_price->{'product_variant_'.'three'}=$value;
                    break;
                }
                else
                    break;

            }
            $pro_var_price->price       = $price->price;
            $pro_var_price->stock       = $price->stock;
            $pro_var_price->product_id  = $product->id;
            $pro_var_price->save();


        }

        return redirect()->back();
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
