/**
 * 瀑布插件
 * added by gdp2015@outlook.com at 2015-5-19
 */
;(function(){
    $.waterfall = {
        settings : {
            container: '#J_waterfall', //容器
            item_unit: '#J_waterfall li', //WATERFALL_CONFIG.item_unit
            ajax_url: null, //数据来源data-uri
            distance: 50, //默认触发高度data-distance
            max_spage: 5,//默认多少页WATERFALL_CONFIG.waterfall_spage_max
            spage: 1,//当前页码            
        },
        init: function(options){
            options && $.extend($.waterfall.settings, options);
            var s = $.waterfall.settings;
            s.ajax_url = $(s.container).attr('data-uri');
            
            var distance = $(s.container).attr('data-distance');
            if(distance != void(0)){
                s.distance = distance;
            }
            $(s.container)[0] && $(s.container).imagesLoaded( function(){
            	$(s.container).masonry({
            		singleMode: true,
        			animate: true,
        			gutterWidth : 28,
                    itemSelector: s.item_unit
                });
                $(s.item_unit).animate({opacity: 1});
            });
            $.waterfall.is_loading = !1;
            //绑定下拉事件
            $(window).bind('scroll', $.waterfall.lazy_load);
        },
        is_loading: !0,
        lazy_load: function(){
            var s = $.waterfall.settings,
                st = $(document).height() - $(window).scrollTop() - window.innerHeight;
            if (!$.waterfall.is_loading && st <= s.distance) {
                $.waterfall.is_loading = !0;
                $.waterfall.loader();
            }
        },
        loader: function(){
            var s = $.waterfall.settings;
            $.ajax({
                url: s.ajax_url,
                data: {sp: s.spage},
                type: 'GET',
                dataType: 'json',
                success: function(result){
                    if(result.status == 1){
                        var html = $(result.data.html).css({opacity: 0});

                        html.find('.J_img').imagesLoaded(function(){
                            // html.animate({opacity: 1});
                            setTimeout(function () {
                                $(s.container).append(html).masonry('appended', html, true, function(){
                                    s.spage += 1; //页码加1
                                    if(s.spage >= s.max_spage || result.data.isend){
                                        $(window).unbind('scroll', $.waterfall.lazy_load);
                                    }
                                    $('html, body').scrollTop($(document).scrollTop() - 1);
                                    $.waterfall.is_loading = !1;
                                });
                            }, 200)
                            html.animate({opacity: 1});
                        });
                    }else{
                        alert('waterfall fail');
                    }
                }
            });
        }
    }
    $.waterfall.init({item_unit:WATERFALL_CONFIG.item_unit,max_spage:WATERFALL_CONFIG.spage_max});
})(jQuery);