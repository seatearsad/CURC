$(function () {
    var width = $(window).width();
    var height = $(window).height();
    var headerHeight = 130;
    var footerHeight = 100;

    var menu_left = $('.nav_url').children('.active').position().left;
    $('.white_line').css("left",menu_left);
    var menu_width = $('.nav_url').children('li').width();
    $('.white_line').css("width",menu_width);

    $('.main').css("height", height);
    $('.main_show').css("height", height - footerHeight - headerHeight);
    $('.main_show').children('.show_tag').children('div').css("height", height - footerHeight - headerHeight - 80);
    $('.main_show').children('.show_tag').children('div').children('span').css("height", height - footerHeight - headerHeight - 100);
    $('#scroll_bar').css("height", height - footerHeight - headerHeight - 100);
    // $('.main_show').children('.show_tag').children('span').css("height", height - footerHeight - headerHeight - 100);

    //$('.bottom_second_div').find("li").click(changeMemo);
    $('.nav_url').children('li.active').children('dl').children("dd").click(changeMemo);
    $('.nav_url').children('li').children('dl').children("dd").click(function () {
        if($(this).attr('class').indexOf("dd_page") >= 0){
            //if($(this).parents("li").attr("class") != " active") {
            //var url = $(this).parents("li").children('a').attr("href") + "#" + $(this).index();
            var url = $(this).parents("li").children('a').data("id") + "#" + $(this).index();
            //}
        }else {
            var url = "/home/index/lists/catid/"+$(this).attr("id");
        }
        window.location.href = url;
    });

    $('.nav_url').children('li').children('dl').mouseover(function () {
        $(this).show();
    });

    $('.nav_url').children('li').children('dl').mouseout(function () {
        $(this).hide();
    });

    $('.nav_url').children('li').mouseout(function () {
        $(this).children('dl').hide();
        $('.white_line').css("left",menu_left);
    });

    $('.nav_url').children('li').mouseover(function () {
        $(this).children('dl').show();
        var new_left = $(this).position().left;
        $('.white_line').css("left",new_left);
    });

    var url = window.location.href;
    var arr = url.split('#');
    if(arr.length > 1) {
        var initNum = Number(arr[1]);
        if (initNum > $('.main_show').children('.memo').length - 1) {
            initNum = 0;
        }
        $('.main_show').children('.memo').each(function () {
            if($(this).index() == initNum){
                $('.show_tag').children('h1').html($(this).children('.memo_title').html());
                $('.show_tag div').children('span').html($(this).children('.memo_content').html());
            }
        });
    }

    $('.content_down').hide();

    var nScrollHeight = 0; //滚动距离总长(注意不是滚动条的长度)
    var nScrollTop = 0;   //滚动到的当前位置
    var nDivHight = $('.main_show .show_tag div').children('span').height();
    var contentHeight = $('.main_show .show_tag div').children('span').scroll();

    $('.main_show .show_tag div').children('span').scroll(function () {
        $('#scroll_bar').show();
        nScrollHeight = $(this)[0].scrollHeight;
        nScrollTop = $(this)[0].scrollTop;
        console.log(nDivHight+'--'+nScrollTop+'--'+nScrollHeight);
        var lineHeight = (nDivHight/nScrollHeight)*(height - footerHeight - headerHeight - 80);
        var marginTop = (nScrollTop/nScrollHeight)*nDivHight;
        $('#scroll_bar_line').css({'height':lineHeight,'margin-top':marginTop});

        // if(nScrollTop + nDivHight >= nScrollHeight)
        //     $('.content_down').hide();
        // else
        //     $('.content_down').show();
    });

    $('.main_show .show_tag div').children('span').scrollTop(1);

    $('.language_label').click(function () {
        window.location.href = "/home/index/lists/catid/15";
    });
});

function changeMemo() {
    //$(this).addClass("active");
    var curr_num = $(this).index();
    //alert(curr_num);
    //$('.bottom_second_div').find("li").each(function () {
    // $(this).parent().find("dd").each(function () {
    //     if($(this).index() != curr_num){
    //         $(this).removeClass('active');
    //     }
    // });
    $('.main_show').children('.memo').each(function () {
        if($(this).index() == curr_num){
            //$(this).addClass("active");
            //alert($(this).children('.memo_content').html());
            $('.show_tag').children('h1').html($(this).children('.memo_title').html());
            $('.show_tag div').children('span').html($(this).children('.memo_content').html());
        }
        $('.main_show .show_tag div').children('span').scrollTop(1);
    });
}

