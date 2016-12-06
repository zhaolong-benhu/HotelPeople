/**
 * 点击报名之前检验是否已经关注
 */
function checkSubscribeBeforeSignup(obj){
	// $.get('index.php?a=getSubscribe',{},function(data){
	// 	if(data!=0){
	// 		show_tit_box_(4);
	// 		window.setTimeout(function(){
	// 			window.location = data;
	// 		},2000);
	// 	}else{
			window.location = $(obj).data('url');
	// 	}
	// });
}
/**
 * 弹出信息提示框
 * @param show_tb_num1-投票成功2-请勿重复报名3-选手搜不到4-请先关注
 */
function show_tit_box_(show_tb_num){
   $(".tit_box_"+show_tb_num).fadeIn(1000,function(){
      setTimeout($(this).fadeOut(1000),2000);
   })
}
/**
 * 给指定选手投一票(已关注的情况下)
 */
function giveAVote(number,obj){
  $(this).find("i").css("color","#9a0668");
	// $.get('index.php?a=getSubscribe',{},function(data){
	// 	if(data!=0){
	// 		show_tit_box_(4);
	// 		window.setTimeout(function(){
	// 			window.location = data;
	// 		},2000);
	// 	}else{
			$.get('index.php?a=giveAVote',{number:number},function(data){
				if(data==1){
					show_tit_box_(1);
					$(obj).find("i").css("color","#FFCC00");
					var $span = $(obj).next('div').children('span');
					$span.html(parseInt($span.html())+1);
				}else if(data==0){
					show_tit_box_(2);
				}else if(data==2){
					show_tit_box_(5);
				}else if(data==3){
					show_tit_box_(6);
				}else if(data==4){
					show_tit_box_(7);
				}else if(data==5){
					show_tit_box_(8);
				}
			});
	// 	}
	// });
}
/**
 * 点击input触发表单提交上传图片
 */
function upload(ele){
	$(ele).parent('a').parent('form').submit();
	window.setTimeout(function(){
		var src = 'upload/tmp/'+$(ele).attr('name')+vdata.openid;
		$(ele).siblings('div').find("img").attr('src',vdata.domain+'/activity/hotelpeople/'+src+'?r='+Math.random());
		//var wxSrc = 'index.php?a=mediaSrc'+'&r='+Math.random();
		//$(ele).siblings('div').find("img").attr('src',vdata.domain+'/activity/hotelpeople/'+wxSrc);
		$(ele).attr('class','input-uploaded');
		$(ele).parent('a').removeClass('upload_btn').addClass('upload_btn2');
		$(ele).siblings('i').addClass('dis_n');
		$(ele).siblings('div').removeClass('dis_n');
		$('#regForm input[name="p_'+$(ele).attr('name')+'"]').val(src);
	},1000);
}
/**
 * 点击i删除上传的图片
 */
function cancelUpload(tagName){
	$('#regForm input[name="p_'+tagName+'"]').val('');
	$('input[name="'+tagName+'"]').attr('class','input-upload');
	$('input[name="'+tagName+'"]').siblings('div').addClass('dis_n');
	$('input[name="'+tagName+'"]').siblings('i').removeClass('dis_n');
	$('input[name="'+tagName+'"]').parent('a').removeClass('upload_btn2').addClass('upload_btn');
}
$(window).scroll(function(){
	var mytop = $(this).scrollTop();
	var mytop_of_static = $(".search_box").offset().top - 10;
	if(mytop > mytop_of_static){
    $(".back_btn").fadeIn();
  }
	if(mytop <= mytop_of_static){
    $(".back_btn").fadeOut();
  }
});


$(function(){
	/**
	 * 分享(已关注的情况下)
	 */
	$("#share").click(function(){
		// $.get('index.php?a=getSubscribe',{},function(data){
		// 	if(data!=0){
		// 		window.location = data;
		// 	}
		// });
    var creatediv=$("<div class='alert-bgbox'></div>");
    $("body").append(creatediv);
    creatediv.click(function(){
    	$(this).hide();
    });
	});
	$(".back_btn").click(function(){
		$('body,html').animate({scrollTop:0,},1000);
	});
	$(".rank_tab tbody tr:odd").addClass("bgcol_02");
  $(".rank_tab tbody tr:even").addClass("bgcol_07");
	
	$("input[type=text],textarea").focus(function(){$(".bottom_box").hide()});
	$("input[type=text],textarea").blur(function(){$(".bottom_box").show()});
	
});