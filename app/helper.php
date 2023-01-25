<?php

function product_variant_one($id){
    $product_variant = App\Models\ProductVariant::where('id',$id)->first();
    return $product_variant;
}
function product_variant_two($id){
    $product_variant = App\Models\ProductVariant::where('id',$id)->first();
    return $product_variant;
}
function product_variant_three($id){
    $product_variant = App\Models\ProductVariant::where('id',$id)->first();
    return $product_variant;
}




function dateFormat($date,$format){
    return Carbon\Carbon::parse($date)->format($format);
}
