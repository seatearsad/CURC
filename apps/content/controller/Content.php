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
namespace app\content\controller;

use app\common\controller\Adminbase;
use app\content\logic\Content as ContentLogic;
use think\Db;
use think\Loader;
use think\Request;
use think\Url;

/**
 * 内容管理
 */
class Content extends Adminbase
{
    public $catid = 0; //当前栏目id
    //模型缓存
    protected $model = array();

    protected function _initialize()
    {
        parent::_initialize();
        $this->catid = Request::instance()->param('catid', 0, 'intval');
        $this->Content = new ContentLogic;
        $this->model = cache('Model');
    }

    //显示内容管理首页
    public function index()
    {
        return $this->fetch();
    }

    //内容添加
    public function add()
    {
        if (Request::instance()->isPost()) {
            $data = $this->request->param();
            //栏目ID
            $catid = intval($data['info']['catid']);
            if (empty($catid)) {
                $this->error("请指定栏目ID！");
            }
            if (trim($data['info']['title']) == '') {
                $this->error("标题不能为空！");
            }

            //获取当前栏目配置
            $category = getCategory($catid);
            //栏目类型为0
            if ($category['type'] == 0) {
                //模型ID
                $modelid = getCategory($catid, 'modelid');
                //检查模型是否被禁用
                if ($this->model[$modelid]['disabled'] == 1) {
                    $this->error("模型被禁用！");
                }

                $logic = logic($modelid);
                $res = $logic->add();
                if ($res) {
                    $this->success("添加成功！");
                } else {
                    $error = $logic->getError();
                    $this->error($error ? $error : '添加失败！');
                }
            } else if ($category['type'] == 1) {
                //单页栏目
                $db = Loader::model('content/Page');
                if ($db->savePage($data)) {
                    $this->success('操作成功！');
                } else {
                    $error = $db->getError();
                    $this->error($error ? $error : '操作失败！');
                }

            } else {
                $this->error("该栏目类型无法发布！");
            }

        } else {
            $category = getCategory($this->catid);
            if (empty($category)) {
                $this->error('该栏目不存在！');
            }
            //内部模型
            if ($category['type'] == 0) {
                $modelid = $category['modelid'];
                //检查模型是否被禁用
                if (getModel($modelid, 'disabled') == 1) {
                    $this->error('该模型已被禁用！');
                }
                //实例化表单类 传入 模型ID 栏目ID 栏目数组
                $content_form = new \content_form($modelid, $this->catid);
                //生成对应字段的输入表单
                $forminfos = $content_form->get();

                $this->assign("catid", $this->catid);
                $this->assign("content_form", $content_form);
                $this->assign("forminfos", $forminfos);
                $this->assign("category", $category);
                return $this->fetch();

            } else if ($category['type'] == 1) {
                //单网页模型
                $info = Loader::model('content/page')->getPage($this->catid);
                if ($info && $info['style']) {
                    $style = explode(';', $info['style']);
                    $info['style_color'] = $style[0];
                    if ($style[1]) {
                        $info['style_font_weight'] = $style[1];
                    }
                }
                $extend = $category['setting']['extend'];

                $this->assign("catid", $this->catid);
                $this->assign("setting", $setting);
                $this->assign('extend', $extend);
                $this->assign('info', $info);
                $this->assign("category", $category);
                return $this->fetch('singlepage');

            }

        }
    }

    //内容编辑
    public function edit()
    {
        $this->catid = (int) $_POST['info']['catid'] ?: $this->catid;
        $Categorys = getCategory($this->catid);
        //信息ID
        $id = Request::instance()->param('id/d', 0);
        //模型ID
        //检查模型是否被禁用
        if ($this->model[$Categorys['modelid']]['disabled'] == 1) {
            $this->error("模型被禁用！");
        }

        if (Request::instance()->isPost()) {
            if (trim($_POST['info']['title']) == '') {
                $this->error("标题不能为空！");
            }
            $modelid = getCategory($this->catid, 'modelid');
            $logic = logic($modelid);
            $res = $logic->edit();
            if ($res) {
                $this->success("修改成功！");
            } else {
                $error = $logic->getError();
                $this->error($error ? $error : '修改失败！');
            }
        } else {
            $modelid = $Categorys['modelid'];
            if (empty($Categorys)) {
                $this->error("该栏目不存在！");
            }
            $tablename = $this->model[$modelid]['tablename'];
            $r = Db::name($tablename)->where(['id' => $id])->find();
            $r2 = Db::name($tablename . '_data')->where(['id' => $id])->find();
            $data = array_merge($r, $r2);
            //引入输入表单处理类
            $content_form = new \content_form($modelid, $this->catid);
            //字段内容
            $forminfos = $content_form->get($data);
            $this->assign("category", $Categorys);
            $this->assign("data", $data);
            $this->assign("catid", $this->catid);
            $this->assign("id", $id);
            $this->assign("content_form", $content_form);
            $this->assign("forminfos", $forminfos);
            return $this->fetch();

        }

    }

    //显示栏目菜单列表
    public function public_categorys()
    {
        $categorys = cache('Category');
        foreach ($categorys as $rs) {
            $rs = getCategory($rs['catid']);
            if ($rs['type'] == 2 && $rs['child'] == 0) {
                continue;
            }

            $data = array(
                'catid' => $rs['catid'],
                'parentid' => $rs['parentid'],
                'catname' => $rs['catname'],
                'type' => $rs['type'],
            );

            //终极栏目
            if ($rs['child'] == 0) {
                $data['target'] = 'right';
                $data['url'] = Url::build('Content/classlist', array('catid' => $rs['catid']));
            } else {
                $data['isParent'] = true;
            }

            //单页
            if ($rs['type'] == 1 && $rs['child'] == 0) {
                $data['url'] = Url::build('Content/add', array('catid' => $rs['catid']));
            }

            $json[] = $data;
        }
        $this->assign('json', json_encode($json));
        return $this->fetch();
    }

    //栏目信息列表
    public function classlist()
    {
        $catid = Request::instance()->param('catid/d', 0);
        //当前栏目信息
        $catInfo = getCategory($catid);
        if (empty($catInfo)) {
            $this->error('该栏目不存在！');
        }
        //查询条件
        $where = array();
        $where['catid'] = array('EQ', $catid);
        $where['status'] = array('EQ', 99);
        //栏目所属模型
        $modelid = $catInfo['modelid'];
        //栏目扩展配置
        $setting = $catInfo['setting'];
        //检查模型是否被禁用
        if (getModel($modelid, 'disabled')) {
            $this->error('模型被禁用！');
        }
        $modelCache = cache("Model");
        $tableName = $modelCache[$modelid]['tablename'];
        //$data = Db::name(ucwords($tableName))->where($where)->order(array("id" => "DESC"))->select();
        $data = $this->lists(ucwords($tableName), $where, 'id desc');

        $this->assign('data', $data);
        $this->assign('catid', $this->catid);
        return $this->fetch();
    }

    //删除
    public function delete($ids = 0)
    {
        if (empty($ids) || !$this->catid) {
            $this->error('参数错误！');
        }
        if (!is_array($ids)) {
            $ids = array(0 => $ids);
        }
        $modelid = getCategory($this->catid, 'modelid');
        $logic = logic($modelid);
        foreach ($ids as $id) {
            $logic->rmove($id, $this->catid);
        }
        $this->success('删除成功！');
    }

    //文章排序
    public function listorder()
    {
        $id = Request::instance()->param('id/d', 0);
        $listorder = Request::instance()->param('value/d', 0);

        $modelid = getCategory($this->catid, 'modelid');
        if (empty($modelid)) {
            $return = 0;
            exit(json_encode(array('result' => $return)));
        }
        $db = ContentLogic::getInstance($modelid);
        $rs = $db->update(['listorder' => $listorder, 'id' => $id]);
        if ($rs) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序失败！");
        }
    }

    //检测标题是否存在
    public function public_check_title()
    {
        $title = Request::instance()->param('data');
        $catid = $this->catid;
        if (empty($title)) {
            return json(array('status' => 1, 'info' => '标题没有重复！'));
        }
        $modelid = getCategory($this->catid, 'modelid');
        $logic = logic($modelid);
        $rs = $logic->where(array('title' => $title))->find();
        if ($rs) {
            return json(array('status' => 0, 'info' => '标题有重复！'));
        } else {
            return json(array('status' => 1, 'info' => '标题没有重复！'));
        }

    }

}
