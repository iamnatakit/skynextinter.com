<?php
namespace App\Controllers;
use App\Libraries\Platform\{Access,Admin};
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;
use Throwable;
class ProductEditor extends Platform
{
    public function edit(string $id)
    {
        if(!$this->user || !Access::isAdmin($this->user)){return $this->response->setStatusCode(403)->setBody('เฉพาะ Admin เท่านั้น');}
        $product=$this->store->one('SELECT * FROM products WHERE id=?',[(int)$id]);
        if(!$product){throw PageNotFoundException::forPageNotFound();}
        if($this->post()){
            $newImage=null;
            try{
                $input=$this->request->getPost();
                $photo=$this->request->getFile('photo');
                if($photo && $photo->getError()!==UPLOAD_ERR_NO_FILE){
                    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$photo->getMimeType()]??null;
                    if(!$photo->isValid() || !$ext || $photo->getSize()>5*1024*1024 || !getimagesize($photo->getTempName())){throw new RuntimeException('ใช้ภาพ JPG, PNG หรือ WebP ไม่เกิน 5 MB');}
                    $newImage=bin2hex(random_bytes(16)).'.'.$ext;
                    $photo->move(FCPATH.'platform/uploads',$newImage);$input['image']='platform/uploads/'.$newImage;
                }
                (new Admin($this->store))->save('products','save',(int)$id,$input,$this->user);
                $view=$this->input('after')==='view' && $this->input('active')==='1';
                return redirect()->to(site_url($view?'shop/product/'.$id:'admin/products/edit/'.$id))->with('success','บันทึกสินค้าเรียบร้อยแล้ว');
            }catch(Throwable $e){
                if($newImage && is_file(FCPATH.'platform/uploads/'.$newImage)){unlink(FCPATH.'platform/uploads/'.$newImage);}
                return $this->fail($e,'admin/products/edit/'.$id)->withInput();
            }
        }
        return $this->page('product_editor',['title'=>'แก้ไขสินค้า · '.$product['name'],'product'=>$product]);
    }
}
