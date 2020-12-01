<?php
/**
 * 翻页类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use SlimCMS\Abstracts\ModelAbstract;

class Page extends ModelAbstract
{
    /**
     * 生成分页链接
     * @param type $num 总数
     * @param type $perpage 每页数量
     * @param type $curpage 当前页面
     * @param type $mpurl 链接地址
     * @param type $maxpages 最大页数
     * @param type $page
     * @param type $autogoto
     * @param type $shownum
     * @return type
     */
    public static function multi($num, $perpage, $curpage, $mpurl = '', $maxpages = 0, $page = 5, $autogoto = false, $shownum = false)
    {
        $a_name = '';
        if (strpos($mpurl, '#') !== FALSE) {
            $a_strs = explode('#', $mpurl);
            $mpurl = $a_strs[0];
            $a_name = '#' . $a_strs[1];
        }

        $multipage = '';
        $mpurl .= strpos($mpurl, '?') !== FALSE ? '&' : '?';

        if ($page <= 0) {
            $page = 1;
        }
        if ($num > $perpage) {
            $offset = floor($page * 0.5);
            $realpages = @ceil($num / $perpage);
            $curpage = $curpage > $realpages ? $realpages : $curpage;
            $pages = $maxpages && $maxpages < $realpages ? $maxpages : $realpages;

            if ($page > $pages) {
                $from = 1;
                $to = $pages;
            } else {
                $from = $curpage - $offset;
                $to = $from + $page - 1;
                if ($from < 1) {
                    $to = $curpage + 1 - $from;
                    $from = 1;
                    if ($to - $from < $page) {
                        $to = $page;
                    }
                } elseif ($to > $pages) {
                    $from = $pages - $page + 1;
                    $to = $pages;
                }
            };
            $multipage = ($curpage - $offset > 1 && $pages > $page ? '<li class="paginate_button page-item previous"><a href="' . self::url($mpurl . 'page=1') . $a_name . '" class="page-link">首页</a></li>' : '') .
                ($curpage > 1 ? '<li class="paginate_button page-item previous"><a href="' . self::url($mpurl . 'page=' . ($curpage - 1)) . $a_name . '" page="' . ($curpage - 1) . '" class="page-link">上一页</a></li>' : '') .
                ($curpage - $offset > 1 && $pages > $page ? '<li class="paginate_button page-item previous"><a href="' . self::url($mpurl . 'page=1') . $a_name . '" class="page-link">1 ...</a></li>' : '');

            for ($i = $from; $i <= $to; $i++) {
                $multipage .= $i == $curpage ? '<li class="paginate_button page-item active"><a href="#" class="page-link">' . $i . '</a></li>' :
                    '<li class="paginate_button page-item"><a href="' . self::url($mpurl . 'page=' . $i) . $a_name . '" page="' . $i . '" class="page-link">' . $i . '</a></li>';
            }
            $multipage .= ($to < $pages ? '<li class="paginate_button page-item"><a href="' . self::url($mpurl . 'page=' . $pages) . $a_name . '" page="' . $pages . '" class="page-link">... ' . $realpages . '</a></li>' : '') .
                ($curpage < $pages ? '<li class="paginate_button page-item next"><a href="' . self::url($mpurl . 'page=' . ($curpage + 1)) . $a_name . '" page="' . ($curpage + 1) . '" class="page-link">下一页</a></li><li class="paginate_button page-item next"><a href="' . self::url($mpurl . 'page=' . $pages) . $a_name . '" page="' . $pages . '" class="page-link">末页</a></li>' : '') .
                ($autogoto && $pages > $page && $curpage>4 ? ' <li class="paginate_button page-item previous"><input style="border-radius:0rem;" placeholder="快速跳转" type="text" name="custompage" size="7" class="form-control fa-1x p-1" id="custompage" onkeydown="if(event.keyCode==13) {var page=this.value;window.location=\'' . self::url($mpurl . 'page=\'+page+\'') . '\';}" /></li>' : '');

            $multipage = $multipage ? $multipage . ($shownum ? '<li class="paginate_button page-item previous disabled"><a  class="page-link">共' . $pages . '页 | 共' . $num . '条</a></li>
<script>
$(function() {
  $(\'.pagesizeset\').click(function() {
    $(this).parents(\'li\').hide();
    $(\'.pagesizebox\').show();
  })
})
</script>
<li class="page-item pagesizebox" style="display: none;">
<input style="border-radius:0rem;" placeholder="每页显示条数" type="text" name="pagesize" size="12" class="form-control fa-1x p-1" id="pagesize" onkeydown="if(event.keyCode==13) {var pagesize=this.value;window.location=\'' . self::url($mpurl . 'pagesize=\'+pagesize+\'') . '\';}" />
</li>
<li class="page-item"><a class="page-link"><i class="fa-1x fas fa-cog pagesizeset"></i></a></li>
' : '') : '';
            $multipage = $multipage ? '<ul class="pagination">' . $multipage . '</ul>' : '';
        }
        return $multipage;
    }

    /**
     * 生成简单版分页链接
     * @param type $num 总数
     * @param type $perpage 每页数量
     * @param type $curpage 当前页码
     * @param type $mpurl 链接地址
     * @return string
     */
    public static function simplepage($num, $perpage, $curpage, $mpurl, $shownum = false)
    {
        $a_name = '';
        if (strpos($mpurl, '#') !== FALSE) {
            $a_strs = explode('#', $mpurl);
            $mpurl = $a_strs[0];
            $a_name = '#' . $a_strs[1];
        }
        $return = '';
        $stat = $num > 0 ? '<li class="paginate_button page-item previous disabled"><a  class="page-link">' . $curpage . '页 / 共' . ceil($num / $perpage) . '页</a></li> ' : '';
        $next = $num > $perpage * $curpage ? '<li class="paginate_button page-item next" title="下一页"><a class="page-link" href="' . self::url($mpurl . '&page=' . ($curpage + 1)) . $a_name . '">下一页</a></li>' : '<li class="paginate_button page-item next disabled"><a class="page-link">下一页</a></li>';
        $prev = $curpage > 1 ? '<li class="paginate_button page-item previous" title="上一页"><a class="page-link" href="' . self::url($mpurl . '&page=' . ($curpage - 1)) . $a_name . '"><span>上一页</a></li>' : '<li class="paginate_button page-item previous disabled"><a class="page-link">上一页</a></li>';
        if ($next || $prev) {
            $return = '<ul class="pagination">' . $prev . $next . ($shownum ? $stat : '') . '</ul>';
        }
        return $return;
    }
}
