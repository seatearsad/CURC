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
use app\content\model\ModelField;
use think\Cookie;
use think\Db;
use think\Request;

class Field extends Adminbase
{
    //初始化
    protected function _initialize()
    {
        parent::_initialize();
        //字段类型存放目录
        $this->fields = APP_PATH . 'content/fields/';
        $this->modelfield = new ModelField;

    }

    /**
     * 显示字段列表
     * @author 御宅男  <530765310@qq.com>
     */
    public function index()
    {
        $modelid = $this->request->param('modelid/d', '');
        if (empty($modelid)) {
            $this->error('参数错误！');
        }
        $model = Db::name("Model")->field(true)->where(array("modelid" => $modelid))->find();
        if (empty($model)) {
            $this->error('该模型不存在！');
        }
        // 记录当前列表页的cookie
        Cookie::set('__forward__', $_SERVER['REQUEST_URI']);

        //根据模型读取字段列表
        $data = $this->modelfield->getModelField($modelid);
        //禁止被禁用的字段列表
        $this->assign("forbid_fields", $this->modelfield->forbid_fields);
        //禁止被删除的字段列表
        $this->assign("forbid_delete", $this->modelfield->forbid_delete);

        $this->assign("modelid", $modelid);
        $this->assign("data", $data);
        return $this->fetch();
    }

    /**
     * 增加字段
     * @author 御宅男  <530765310@qq.com>
     */
    public function add()
    {
        $modelid = $this->request->param('modelid/d', '');
        if (empty($modelid)) {
            $this->error('参数错误！');
        }
        if ($this->request->isPost()) {
            //增加字段
            $res = $this->modelfield->addField();
            if (!$res) {
                $this->error($this->modelfield->getError());
            } else {
                $this->success('新增成功', Cookie::get('__forward__'));
            }
        } else {
            //获取并过滤可用字段类型
            $all_field = array();
            foreach ($this->modelfield->getFieldTypeList() as $formtype => $name) {
                if (!$this->modelfield->isAddField($formtype, $formtype, $modelid)) {
                    continue;
                }
                $all_field[$formtype] = $name;
            }
            $this->assign("modelid", $modelid);
            $this->assign("all_field", $all_field);
            return $this->fetch();
        }
    }

    //字段属性配置
    public function public_field_setting()
    {
        //字段类型
        $fieldtype = $this->request->param('fieldtype');
        $fiepath = $this->fields . $fieldtype . '/';
        //载入对应字段配置文件 config.inc.php
        include $fiepath . 'config.php';
        ob_start();
        include $fiepath . "field_add_form.php";
        $data_setting = ob_get_contents();
        ob_end_clean();
        $settings = array('field_basic_table' => $field_basic_table, 'field_minlength' => $field_minlength, 'field_maxlength' => $field_maxlength, 'field_allow_search' => $field_allow_search, 'field_allow_fulltext' => $field_allow_fulltext, 'field_allow_isunique' => $field_allow_isunique, 'setting' => $data_setting);
        echo json_encode($settings);
    }

    /**
     * 修改字段
     * @author 御宅男  <530765310@qq.com>
     */
    public function edit()
    {
        //模型ID
        $modelid = Request::instance()->param('modelid/d', 0);
        //字段ID
        $fieldid = Request::instance()->param('fieldid/d', 0);
        if (empty($modelid)) {
            $this->error('模型ID不能为空！');
        }
        if (empty($fieldid)) {
            $this->error('字段ID不能为空！');
        }
        if (Request::instance()->isPost()) {
            if ($this->modelfield->editField('', $fieldid)) {
                $this->success("更新成功！", Cookie::get('__forward__'));
            } else {
                $error = $this->modelfield->getError();
                $this->error($error ? $error : '更新失败！');
            }
        } else {
            //模型信息
            $modedata = Db::name("Model")->where(array("modelid" => $modelid))->find();
            if (empty($modedata)) {
                $this->error('该模型不存在！');
            }
            //字段信息
            $fieldData = $this->modelfield->where(array("fieldid" => $fieldid, "modelid" => $modelid))->find();
            if (empty($fieldData)) {
                $this->error('该字段信息不存在！');
            }
            //======获取字段类型的表单编辑界面===========
            //字段路径
            $fiepath = $this->fields . "{$fieldData['formtype']}/";
            //字段扩展配置
            $setting = unserialize($fieldData['setting']);
            //打开缓冲区
            ob_start();
            include $fiepath . 'field_edit_form.php';
            $form_data = ob_get_contents();
            //关闭缓冲
            ob_end_clean();
            //======获取字段类型的表单编辑界面===END====
            //字段类型过滤
            $all_field = array();
            foreach ($this->modelfield->getFieldTypeList() as $formtype => $name) {
                if (!$this->modelfield->isEditField($formtype)) {
                    continue;
                }
                $all_field[$formtype] = $name;
            }
            $this->assign('isEditField', $this->modelfield->isEditField($fieldData['field']));
            //附加属性
            $this->assign("form_data", $form_data);
            $this->assign("all_field", $all_field);
            $this->assign("data", $fieldData);
            $this->assign("modelid", $modelid);
            $this->assign("fieldid", $fieldid);
            return $this->fetch();
        }

    }

    /**
     * 删除字段
     * @author 御宅男  <530765310@qq.com>
     */
    public function delete()
    {
        //字段ID
        $fieldid = Request::instance()->param('fieldid/d', '');
        if (empty($fieldid)) {
            $this->error('字段ID不能为空！');
        }
        if ($this->modelfield->deleteField($fieldid)) {
            $this->success("字段删除成功！");
        } else {
            $error = $this->modelfield->getError();
            $this->error($error ? $error : "删除字段失败！");
        }
    }

    /**
     * 字段排序
     * @author 御宅男  <530765310@qq.com>
     */
    public function listorder()
    {
        $id = Request::instance()->param('id/d', 0);
        $listorder = Request::instance()->param('value/d', 0);
        $rs = $this->modelfield->allowField(true)->where(array('fieldid' => $id))->update(array('listorder' => $listorder));
        cache('ModelField', null);
        if ($rs) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序失败！");
        }
    }

}
