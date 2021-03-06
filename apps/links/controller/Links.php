<?php
// +----------------------------------------------------------------------
// | Yzncms [ 御宅男工作室 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2007 http://yzncms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 御宅男 <530765310@qq.com>
// +----------------------------------------------------------------------
namespace app\links\controller;

use app\common\controller\Adminbase;
use think\Db;

/**
 * 友情链接管理
 * @author 御宅男  <530765310@qq.com>
 */
class Links extends Adminbase
{
    /**
     * [友情链接列表]
     * @author 御宅男  <530765310@qq.com>
     */
    public function index()
    {
        $list = $this->lists('Links', array(), array("id" => "DESC"));
        $Terms = Db::name('Terms')->where(array("module" => "links"))->column("id,name");
        $this->assign("Terms", $Terms);
        $this->assign("list", $list);
        return $this->fetch();
    }

    /**
     * [新增友情链接]
     * @author 御宅男  <530765310@qq.com>
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //验证器
            $rule = [
                'name' => 'require',
                'url' => 'require',
            ];
            $msg = [
                'name.require' => '网站名称不得为空',
                'url.require' => '网站链接不得为空',
            ];
            $validate = new \think\Validate($rule, $msg);
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $status = Db::name('Links')->insert($data);
            if ($status) {
                $this->success("添加成功！", url("Links/index"));
            } else {
                $this->error("添加失败！");
            }
        } else {
            $Terms = Db::name('Terms')->where(array("module" => "links"))->column("id,name");
            $this->assign("Terms", $Terms);
            return $this->fetch();

        }
    }

    /**
     * [编辑友情链接]
     * @author 御宅男  <530765310@qq.com>
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //验证器
            $rule = [
                'name' => 'require',
                'url' => 'require',
            ];
            $msg = [
                'name.require' => '网站名称不得为空',
                'url.require' => '网站链接不得为空',
            ];
            $validate = new \think\Validate($rule, $msg);
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $status = Db::name('Links')->update($data);
            if ($status) {
                $this->success("编辑成功！", url("Links/index"));
            } else {
                $this->error("编辑失败！");
            }

        } else {
            $id = $this->request->param('id', 0);
            $data = Db::name("Links")->where(array("id" => $id))->find();
            if (!$data) {
                $this->error("该信息不存在！");
            }

            $Terms = Db::name('Terms')->where(array("module" => "links"))->column("id,name");
            $this->assign("Terms", $Terms);
            $this->assign("data", $data);
            return $this->fetch();

        }

    }

    /**
     * [删除友情链接]
     * @param  integer $ids [description]
     * @author 御宅男  <530765310@qq.com>
     */
    public function delete($ids = 0)
    {
        empty($ids) && $this->error('参数错误！');
        if (is_array($ids)) {
            $map['id'] = array('in', $ids);
        } elseif (is_numeric($ids)) {
            $map['id'] = $ids;
        }
        $res = Db::name('Links')->where($map)->delete();
        if ($res !== false) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }

    }

    /**
     * [友情链接排序]
     * @author 御宅男  <530765310@qq.com>
     */
    public function listorder()
    {
        $id = $this->request->param('id/d', 0);
        $listorder = $this->request->param('value/d', 0);
        $rs = Db::name('Links')->where(['id' => $id])->setField(['listorder' => $listorder]);
        if ($rs !== false) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序失败！");
        }
    }

    /**
     * [分类管理]
     * @author 御宅男  <530765310@qq.com>
     */
    public function terms()
    {
        $data = Db::name('Terms')->where(array("module" => "links"))->select();
        $this->assign("data", $data);
        return $this->fetch();
    }

    /**
     * [新增分类]
     * @author 御宅男  <530765310@qq.com>
     */
    public function addTerms()
    {
        if ($this->request->isPost()) {
            $name = $this->request->param('name');
            if (empty($name)) {
                $this->error("分类名称不能为空！");
            }
            $res = Db::name('Terms')->where(array("name" => $name, "module" => "links"))->find();
            if ($res) {
                $this->error("该分类已经存在！");
            }
            $status = Db::name('Terms')->insert(array("name" => $name, "module" => "links"));
            if ($status) {
                $this->success('添加成功！', url("Links/terms"));
            } else {
                $this->error("添加失败！");
            }
        } else {
            return $this->fetch();
        }
    }

    /**
     * [分类编辑]
     * @author 御宅男  <530765310@qq.com>
     */
    public function termsedit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $status = Db::name('Terms')->update($data);
            if ($status) {
                $this->success('更新成功！', url("Links/terms"));
            } else {
                $this->error("更新失败！");
            }
        } else {
            $id = $this->request->param('id', 0);
            $data = Db::name('Terms')->where(array("id" => $id, "module" => "links"))->find();
            if (!$data) {
                $this->error("该分类不存在！");
            }
            $this->assign("data", $data);
            return $this->fetch();
        }
    }

    /**
     * [分类删除]
     * @author 御宅男  <530765310@qq.com>
     */
    public function termsdelete()
    {
        $id = $this->request->param('id/d', 0);
        $res = Db::name('Terms')->where(array("id" => $id, "module" => "links"))->delete();
        if ($res !== false) {
            Db::name('Links')->where(array("termsid" => $id))->delete();
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }

    }

}
