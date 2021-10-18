<?php
namespace app\index\controller;

use think\facade\View;
use think\facade\Db;
use app\model\ArticleMod;

class Article
{
    public function index(ArticleMod $articleMod) {
    	$list = $articleMod->base_list();
    	return success($list, true);
    }
}
