<?php
namespace App\Controllers;
use App\Libraries\Platform\{Access,AnnualWebsitePackage};
/** Compatibility for old admin bookmarks: prices are now managed on the product. */
class AnnualWebsitePromotion extends Platform
{
    public function index()
    {
        if(!$this->user || !Access::isAdmin($this->user)){return $this->response->setStatusCode(403)->setBody('เฉพาะ Admin เท่านั้น');}
        $product=$this->store->one('SELECT id FROM products WHERE sku=?',[AnnualWebsitePackage::SKU]);
        return redirect()->to(site_url($product?'admin/products/edit/'.$product['id']:'admin/products'));
    }
}
