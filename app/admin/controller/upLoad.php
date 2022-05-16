<?php
namespace app\admin\controller;

use app\api\controller\Base;
use support\Request;

class upLoad extends Base{
    public function upLoad(Request $request){
        $file = $request->file('upload');
        $id = $request->input('id',"");
        if ($file && $file->isValid()) {
            $tt = time().rand(1111,999999);
            $path = public_path()."/order_pic/$tt".".".$file->getUploadExtension();
            $file->move($path);
            $model = \app\api\model\Order::find($id);
            $model->order_pic = "/order_pic/$tt".".".$file->getUploadExtension();
            $model->save();
            return $this->success("");
        }
    }
}