<?php
namespace App\Libraries\Platform;
use RuntimeException;
/** Annual pricing comes from the catalog product; legacy monthly rules are ignored. */
class AnnualWebsiteOffer
{
    public function __construct(private Store $s){}
    public function current(?string $now=null):array
    {
        $product=$this->s->one('SELECT * FROM products WHERE sku=?',[AnnualWebsitePackage::SKU]);
        $amount=$product?(int)ProductPricing::apply($product,false)['price_satang']:Membership::ANNUAL_SATANG;
        if($amount<=0){throw new RuntimeException('กรุณากำหนดราคาบริการรายปีในตัวจัดการสินค้าให้มากกว่า 0 บาท');}
        return ['active'=>false,'amount_satang'=>$amount,'product_id'=>$product['id']??null];
    }
}
