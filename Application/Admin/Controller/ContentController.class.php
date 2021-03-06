<?php
/**                      . .
 *                      '.-:-.`  
 *                      '  :  `
 *                   .-----:     
 *                 .'       `.
 *           ,    /       (o) \   
 *           \`._/          ,__)
 *       ~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *       
 *       Code is far away from bug with the dolphin protecting
 *                       海豚保佑,代码无bug
 */
namespace Admin\Controller;
use Think\Controller;
use Think\Exception;

class ContentController extends CommonController
{
    public function index()
    {
        //对传来数据合法性进行判断
        $conds = array();
        if ($_GET['title']) {
            $conds['title'] = $_GET['title'];
        }
        if ($_GET['catid']) {
            $conds['catid'] = intval($_GET['catid']);
        }
        $conds['status'] = array('neq', -1);
        //开始分页
        $page = $_REQUEST['p'] ? $_REQUEST['p'] : 1;
        $pageSize = 10;
        $startRow = ($page - 1) * $pageSize;
        $limit = $startRow . ',' . $pageSize;
        $count = D('News')->getNewsCount($conds);
        $res = new \Admin\Vendor\Page($count, $pageSize);
        $pageres = $res->show();
        //分页结束
        //获取数据
        $news = D('News')->select($conds, $limit);
        //获取推荐位用于下拉列表显示
        $positions = D('Position')->getNormalPositions();
        //分配到模板
        $this->assign(array(
            'webSiteMenu' => D('Menu')->getBarMenus(),
            'pageres'     => $pageres,
            'news'        => $news,
            'conds'       => $conds,
            'positions'   => $positions,
            'count'       => $count,
            'search'       => $conds['title'],
            ));
        $this->display();
    }
    public function add()
    {
        if ($_POST) {
            if (!isset($_POST['title']) || !$_POST['title']) {
                return show(0, '标题不存在');
            }
            if (!isset($_POST['small_title']) || !$_POST['small_title']) {
                return show(0, '短标题不存在');
            }
            if (!isset($_POST['catid']) || !$_POST['catid']) {
                return show(0, '文章栏目不存在');
            }
            if (!isset($_POST['keywords']) || !$_POST['keywords']) {
                return show(0, '关键字不存在');
            }
            if (!isset($_POST['content']) || !$_POST['content']) {
                return show(0, 'content不存在');
            }
            if ($_POST['news_id']) {
                $this->save($_POST);
            }
            $newId = D('News')->insert($_POST);
            if ($newId) {
                $newsContentData['content'] = $_POST['content'];
                $newsContentData['news_id'] = $newId;
                $cId = D('NewsContent')->insert($newsContentData);
                if ($cId) {
                    return show(1, '新增成功');
                } else {
                    return show(1, '主表插入成功，附表插入失败');
                }
            } else {
                return show(0, '新增失败');
            }
        } else {
            $webSiteMenu = D('Menu')->getBarMenus();
            $titleFontColor = C('TITLE_FONT_COLOR');
            $copyFrom = C('COPY_FROM');
            $this->assign(array(
                'webSiteMenu'    => $webSiteMenu,
                'titleFontColor' => $titleFontColor,
                'copyFrom'       => $copyFrom,
            ));
            $this->display();
        }
    }
    public function edit()
    {
        //对传来数据合法性进行验证
        $newId = $_GET['id'];
        if (!$newId) {
            $this->redirect(U('index'));
        }
        $news = D('News')->find($newId);
        if (!$news) {
            $this->redirect(U('index'));
        }
        $newsContent = D('NewsContent')->find($newId);
        if ($newsContent) {
            $news['content'] = $newsContent['content'];
        }
        //获取菜单列表
        $webSiteMenu = D('Menu')->getBarMenus();
        $this->assign(array(
            'webSiteMenu'    => $webSiteMenu,
            'titleFontColor' => C("TITLE_FONT_COLOR"),
            'copyFrom'       => C("COPY_FROM"),
            'news'           => $news,
            ));
        $this->display();
    }
    public function save($data)
    {
        $newsId = $data['news_id'];
        unset($data['news_id']);
        try {
            $id = D('News')->updateById($newsId, $data);
            $newsContentData['content'] = $data['content'];
            $conId = D('NewsContent')->updateNewsById($newsId, $newsContentData);
            if ($id === false || $conId === false) {
                return show(0, '更新失败');
            } else {
                return show(1, '更新成功');
            }
        } catch(Exception $e) {
            return show(0, $e->getMessage());
        }

    }
    public function listorder()
    {
        return parent::listorder('News');
    }
    //推荐位
    public function push()
    {
        $jumpUrl = $_SERVER['HTTP_REFERER'];
        $positionId = intval($_POST['position_id']);
        $newsId = $_POST['push'];
        if (!$newsId || !is_array($newsId)) {
            return show(0, '请选择推荐内容');
        }
        if (!$positionId) {
            return show(0, '未选择推荐位');
        }
        try {
            $news = D('News')->getNewsByNewsId($newsId);
            if (!$news) {
                return show(0, '没有相关内容');
            }
            foreach ($news as $new) {
                $data = array(
                    'position_id' => $positionId,
                    'title'       => $new['title'],
                    'thumb'       => $new['thumb'],
                    'news_id'      => $new['news_id'],
                    'status'      => 1,
                    'create_time' => $new['create_time'],
                );
                $position = D('Positioncontent')->insert($data);
            }
        } catch(Exception $e) {
            return show(0, $e->getMessage());
        }
        return show(1, '推荐成功', array('jump_url' => $jumpUrl));
    }
    public function setStatus()
    {
        $data = array(
            'id' => intval($_POST['id']),
            'status' => intval($_POST['status']),
            );
        return parent::setStatus($data, 'News');
    }
}