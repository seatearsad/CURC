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
use think\Db;
use think\Loader;
use think\Request;
use think\Url;

/**
 * 后台栏目管理
 */
class Category extends Adminbase
{
    //初始化
    protected function _initialize()
    {
        parent::_initialize();
        //取得当前内容模型模板存放目录
        $this->filepath = TEMPLATE_PATH . (empty(self::$Cache["Config"]['theme']) ? "default" : self::$Cache["Config"]['theme']) . "/content/";
        //取得栏目频道模板列表
        $this->tp_category = str_replace($this->filepath . "index/", '', glob($this->filepath . 'index/category*'));
        //取得栏目列表模板列表
        $this->tp_list = str_replace($this->filepath . "index/", '', glob($this->filepath . 'index/list*'));
        //取得内容页模板列表
        $this->tp_show = str_replace($this->filepath . "index/", '', glob($this->filepath . 'index/show*'));
        //取得单页模板
        $this->tp_page = str_replace($this->filepath . "index/", '', glob($this->filepath . 'index/page*'));
    }

    //栏目列表
    public function index()
    {
        $models = cache('Model');
        $result = cache('Category');
        $categorys = array();
        $types = array(0 => '内部栏目', 1 => '<font color="blue">单网页</font>', 2 => '<font color="red">外部链接</font>');
        if (!empty($result)) {
            foreach ($result as $r) {
                $r = getCategory($r['catid']);
                $r['str_manage'] = '';
                $r['str_manage'] .= '<a class="btn red" href="javascript:if(confirm(\'您确定要删除吗?\')){location.href=\'' . Url::build("Category/delete", array("catid" => $r['catid'])) . '\'};"><i class="icon iconfont icon-shanchu"></i>删除</a>';
                $r['str_manage'] .= '<span class="btn"><em><i class="icon iconfont icon-shezhi"></i>设置<i class="arrow"></i></em><ul>';
                if ($r['type'] != 2) {
                    if ($r['type'] == 1) {
                        $r['str_manage'] .= '<li><a href="' . Url::build("Category/singlepage", array("parentid" => $r['catid'])) . '">添加下级栏目</a></li>';
                    } else {
                        $r['str_manage'] .= '<li><a href="' . Url::build("Category/add", array("parentid" => $r['catid'])) . '">添加下级栏目</a></li>';
                    }
                }
                $r['str_manage'] .= '<li><a href="' . Url::build("Category/edit", array("catid" => $r['catid'])) . '">编辑栏目信息</a></li>';
                $r['str_manage'] .= '</ul></span>';

                $r['modelname'] = $models[$r['modelid']]['name'];
                $r['typename'] = $types[$r['type']];
                $r['display_icon'] = $r['ismenu'] ? '' : '&nbsp;<i class="icon iconfont icon-shezhi itip" alt="不在导航显示"></i>';
                $r['help'] = '';
                if ($r['url']) {
                    $r['url'] = "<a href='" . $r['url'] . "' target='_blank'>访问</a>";
                } else {
                    $r['url'] = "<a href='" . Url::build("Category/public_cache") . "'><font color='red'>更新缓存</font></a>";
                }
                $categorys[$r['catid']] = $r;
            }
        }
        $str = "<tr>
                <td width='50' align='center' class='sort'><span alt='可编辑' column_id='\$id' fieldname='gc_sort' nc_type='inline_edit' class='itip editable'>\$listorder</span></td>
                <td width='150' align='center'>\$str_manage</td>
                <td width='60' align='center'>\$id</td>
                <td width='250' align='left'>\$spacer\$catname\$display_icon</td>
                <td width='70' align='center'>\$typename</td>
        		<td width='70' align='center'>\$modelname</td>
        		<td width='100' align='center' align='center'>\$url</td>
                <td width='100' align='center'>\$help</td>
        		</tr>";
        if (!empty($categorys) && is_array($categorys)) {
            $tree = new \Tree();
            $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
            $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
            $tree->init($categorys);
            $categorydata = $tree->get_tree(0, $str);
        } else {
            $categorydata = '';
        }
        $this->assign('categorys', $categorydata);
        return $this->fetch();
    }

    //新增栏目
    public function add()
    {
        if (Request::instance()->isPost()) {
            $Category = Loader::model("content/Category");
            $catid = $Category->addCategory(Request::instance()->post());

            if ($catid) {
                $this->success("添加成功！", Url::build("Category/index"));
            } else {
                $error = $Category->getError();
                $this->error($error ? $error : '栏目添加失败！');
            }
        } else {
            $parentid = Request::instance()->param('parentid/d', 0);
            if (!empty($parentid)) {
                $Ca = getCategory($parentid);
                if (empty($Ca)) {
                    $this->error("父栏目不存在！");
                }
                /*if ($Ca['child'] == '0') {
            $this->error("终极栏目不能添加子栏目！");
            }*/
            } else { $Ca = null;}

            //输出可用模型
            $modelsdata = cache("Model");
            $models = array();
            foreach ($modelsdata as $v) {
                if ($v['disabled'] == 0 && $v['type'] == 0) {
                    $models[] = $v;
                }
            }

            //栏目列表 可以用缓存的方式
            $array = cache("Category");
            foreach ($array as $k => $v) {
                $array[$k] = getCategory($v['catid']);
            }
            if (!empty($array) && is_array($array)) {
                $tree = new \Tree();
                $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
                $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
                $str = "<option value='\$catid' \$selected>\$spacer \$catname</option>";
                $tree->init($array);
                $categorydata = $tree->get_tree(0, $str, $parentid);
            } else {
                $categorydata = '';
            }
            $this->assign("tp_category", $this->tp_category);
            $this->assign("tp_list", $this->tp_list);
            $this->assign("tp_show", $this->tp_show);
            $this->assign("tp_page", $this->tp_page);

            $this->assign("category", $categorydata);
            $this->assign("models", $models);
            $this->assign('parentid_modelid', $Ca['modelid']);
            return $this->fetch();

        }

    }

    //编辑栏目
    public function edit()
    {
        if (Request::instance()->isPost()) {
            $catid = Request::instance()->param('catid/d', 0);
            if (empty($catid)) {
                $this->error('请选择需要修改的栏目！');
            }
            $Category = Loader::model("content/Category");
            $status = $Category->editCategory(Request::instance()->post());
            if ($status) {
                $this->success("修改成功！", Url::build("Category/index"));
            } else {
                $error = $Category->getError();
                $this->error($error ? $error : '栏目修改失败！');
            }
        } else {
            $catid = Request::instance()->param('catid/d', 0);
            $array = cache("Category");
            foreach ($array as $k => $v) {
                $array[$k] = getCategory($v['catid']);
                if ($array[$k]['child'] == "0") {
                    $array[$k]['disabled'] = "disabled";
                } else {
                    $array[$k]['disabled'] = "";
                }
            }
            $data = getCategory($catid);
            $setting = $data['setting'];
            //输出可用模型
            $modelsdata = cache("Model");
            $models = array();
            foreach ($modelsdata as $v) {
                if ($v['disabled'] == 0 && $v['type'] == 0) {
                    $models[] = $v;
                }
            }

            if (!empty($array) && is_array($array)) {
                $tree = new \Tree();
                $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
                $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
                $str = "<option value='\$catid' \$selected>\$spacer \$catname</option>";
                $tree->init($array);
                $categorydata = $tree->get_tree(0, $str, $data['parentid']);
            } else {
                $categorydata = '';
            }

            $this->assign("tp_category", $this->tp_category);
            $this->assign("tp_list", $this->tp_list);
            $this->assign("tp_show", $this->tp_show);
            $this->assign("tp_page", $this->tp_page);

            $this->assign("category", $categorydata);
            $this->assign("models", $models);
            $this->assign("data", $data);
            $this->assign("setting", $setting);

            if ($data['type'] == 1) {
                //单页栏目
                return $this->fetch("singlepage_edit");
            } else if ($data['type'] == 2) {
                //外部栏目
                return $this->fetch("wedit");
            } else {
                return $this->fetch();
            }
        }

    }

    //添加外部链接栏目
    public function wadd()
    {
        return $this->add();
    }

    //添加单页
    public function singlepage()
    {
        return $this->add();
    }

    //删除栏目
    public function delete()
    {
        $catid = Request::instance()->param('catid/d');
        if (!$catid) {
            $this->error("请指定需要删除的栏目！");
        }
        //这里需增加栏目条数item直接判断
        if (false == Loader::model("content/Category")->deleteCatid($catid)) {
            $this->error("栏目含有信息，无法删除！");
        }
        $this->cache();
        $this->success("栏目删除成功！");
    }

    //栏目排序
    public function listorder()
    {
        $id = Request::instance()->param('id/d', 0);
        $listorder = Request::instance()->param('value/d', 0);
        $rs = Db::name('category')->update(['listorder' => $listorder, 'catid' => $id]);
        //删除缓存
        getCategory($id, '', true);
        $this->cache();
        if ($rs) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序失败！");
        }
    }

    //清除栏目缓存
    protected function cache()
    {
        cache('Category', null);
    }

    //更新栏目缓存并修复
    public function public_cache()
    {
        $this->repair();
        $this->cache();
        $this->success("更新缓存成功！", Url::build("category/index"));
    }

    /**
     * 修复栏目数据
     */
    private function repair()
    {
        $this->categorys = $categorys = array();
        $data = Db::name('category')->where(array('module' => 'content'))->order('listorder ASC, catid ASC')->select();
        foreach ($data as $v) {
            $categorys[$v['catid']] = $v;
        }
        $this->categorys = $categorys;
        if (is_array($this->categorys)) {
            foreach ($this->categorys as $catid => $cat) {
                if ($cat['type'] == 2) {
                    continue;
                }

                $arrparentid = $this->get_arrparentid($catid); //父栏目组
                $setting = unserialize($cat['setting']); //栏目配置
                $arrchildid = $this->get_arrchildid($catid); //子栏目组
                $child = is_numeric($arrchildid) ? 0 : 1; //是否有子栏目
                //检查所有父id 子栏目id 等相关数据是否正确，不正确更新
                if ($categorys[$catid]['arrparentid'] != $arrparentid || $categorys[$catid]['arrchildid'] != $arrchildid || $categorys[$catid]['child'] != $child) {
                    Db::name("category")->where(array('catid' => $catid))->update(array('arrparentid' => $arrparentid, 'arrchildid' => $arrchildid, 'child' => $child));
                }
                $parentdir = $this->get_categorydir($catid); //父栏目路径
                $catname = iconv('utf-8', 'gbk', $cat['catname']); //获取栏目名称
                //在Linux下不能使用
                //$letters = gbk_to_pinyin($catname);
                $letter = strtolower(implode('', "")); //获取拼音
                $listorder = $cat['listorder'] ? $cat['listorder'] : $catid;

                $save = array();

                //取得栏目相关地址和分页规则
                $this->Url = new \util\Url;
                $category_url = $this->Url->category_url($catid);
                if (false == $category_url) {
                    return false;
                }
                $url = $category_url['url'];
                //更新URL
                if ($cat['url'] != $url) {
                    $save['url'] = $url;
                }
                if ($categorys[$catid]['parentdir'] != $parentdir || $categorys[$catid]['letter'] != $letter || $categorys[$catid]['listorder'] != $listorder) {
                    $save['parentdir'] = $parentdir;
                    $save['letter'] = $letter;
                    $save['listorder'] = $listorder;
                }
                if (count($save) > 0) {
                    Db::name("category")->where(array('catid' => $catid))->update($save);
                }
                getCategory($catid, '', true);
            }

        }
        //删除父栏目是不存在的栏目
        foreach ($this->categorys as $catid => $cat) {
            if ($cat['parentid'] != 0 && !isset($this->categorys[$cat['parentid']])) {
                Db::name("category")->delete(array('catid' => $catid));
            }
        }
        return true;
    }

    /**
     *
     * 获取父栏目ID列表
     * @param integer $catid              栏目ID
     * @param array $arrparentid          父目录ID
     * @param integer $n                  查找的层次
     */
    private function get_arrparentid($catid, $arrparentid = '', $n = 1)
    {
        if ($n > 5 || !is_array($this->categorys) || !isset($this->categorys[$catid])) {
            return false;
        }

        $parentid = $this->categorys[$catid]['parentid']; //当前父栏目
        $arrparentid = $arrparentid ? $parentid . ',' . $arrparentid : $parentid; //父栏目组
        if ($parentid) {
            $arrparentid = $this->get_arrparentid($parentid, $arrparentid, ++$n);
        } else {
            $this->categorys[$catid]['arrparentid'] = $arrparentid;
        }
        $parentid = $this->categorys[$catid]['parentid'];
        return $arrparentid;
    }

    /**
     *
     * 获取子栏目ID列表
     * @param $catid 栏目ID
     */
    private function get_arrchildid($catid)
    {
        $arrchildid = $catid;
        if (is_array($this->categorys)) {
            foreach ($this->categorys as $id => $cat) {
                if ($cat['parentid'] && $id != $catid && $cat['parentid'] == $catid) {
                    $arrchildid .= ',' . $this->get_arrchildid($id);
                }
            }
        }
        return $arrchildid;
    }

    /**
     * 根据栏目ID获取父栏目路径
     * @param $catid
     * @param $dir
     */
    public function get_categorydir($catid, $dir = '')
    {
        //检查这个栏目是否有父栏目ID
        if (getCategory($catid, 'parentid')) {
            //取得父栏目目录
            $dir = getCategory(getCategory($catid, 'parentid'), 'catdir') . '/' . $dir;
            return $this->get_categorydir(getCategory($catid, 'parentid'), $dir);
        } else {
            return $dir;
        }
    }

}
