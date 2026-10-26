<?php
namespace App\Libraries\Platform;
class ProductPricing
{
    public static function eligible(?array $user): bool
    {
        return !empty($user['active']) && in_array($user['role']??'', ['admin','affiliate','downline_team','external_team'],true);
    }
    public static function publicDescription(string $description,bool $eligible): string
    {
        if($eligible){return $description;}
        return preg_replace('/^\h*ราคาสมาชิก\h*[:：][^\r\n]*(?:\R|$)/mu','',$description)??'';
    }
    public static function publicDiscount(array $product): int
    {
        $price=max(0,(int)($product['price_satang']??0));
        $value=max(0,(int)($product['discount_value']??0));
        return match($product['discount_kind']??'') {
            'percent'=>intdiv($price*min(10000,$value),10000),
            'fixed'=>min($price,$value),
            default=>0,
        };
    }
    public static function discountLabel(array $product): string
    {
        $value=(int)($product['discount_value']??0);
        if(($product['discount_kind']??'')==='percent'){
            return rtrim(rtrim(number_format($value/100,2), '0'),'.').'%';
        }
        return number_format($value/100,2).' บาท';
    }
    public static function apply(array $product,bool $member): array
    {
        $basePrice=max(0,(int)$product['price_satang']);
        $memberPrice=$member && isset($product['member_price_satang']) && !AnnualWebsitePackage::matches($product);
        $publicDiscount=self::publicDiscount($product);
        $product['original_price_satang']=$basePrice;
        $product['public_discount_satang']=$publicDiscount;
        $product['public_price_satang']=$basePrice-$publicDiscount;
        $product['is_member_price']=$memberPrice;
        $product['discount_satang']=$memberPrice?0:$publicDiscount;
        $product['price_satang']=$memberPrice?(int)$product['member_price_satang']:$product['public_price_satang'];
        return $product;
    }
}
