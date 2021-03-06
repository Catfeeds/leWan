/**
 * 系统初始化
 */
$(function(){
	initWindow();
	
	//收缩菜单
	$('.lockmenu').click(function(){
		status = $(this).attr('status');
		post({'status':(parseInt(status)+1)%2}, '/system/ajax/togglemenu',function(res){
			if(res.status == 1){
				if(status == 1){
					$('.lockmenu').attr('title', '解锁');
					$('.lockmenu').attr('status', '0');
					$('.lockmenu').find('span').html('解锁');
					$('#leftbox').css({'width':'50px'});
					$('#rightbox').css({'left':'50px'});
				}else{
					$('.lockmenu').attr('title', '锁定');
					$('.lockmenu').attr('status', 1);
					$('.lockmenu').find('span').html('锁定');
					$('#leftbox').css({'width':'160px'});
					$('#rightbox').css({'left':'160px'});
				}
			}
		})
	})
});

window.onresize = function(){
	initWindow();
}

function initWindow(){
	windowWidth = $(window).width();
	windowHeight = $(window).height();
	
	bodyHeight = (parseInt(windowHeight)-125);
	$('#rightbox').css({height:bodyHeight+'px'});
}


/**
 * 退出登录
 */
function logout(){
	sure('确定退出系统？', '/system/index/logout');
}

/**
 * comfirm对话框
 * @param title
 * @param url
 */
function sure(title, url){
	layer.confirm(title, {icon: 3, title:'系统提示'}, function(index){
		window.location.href=url;
		layer.close(index);
	});
}

/**
 * 请除缓存
 */
function clearCache(){
	post({}, '/system/index/clearCache', function(obj){
	   layer.msg(obj.info, {icon: 1});
	});
}

/**
 * 执行ajax操作
 * @param data
 * @param addr
 * @param callback
 */
function post(data, addr, callback){
	$.ajax({
	   type: "POST",
	   url: addr,
	   data: data,
	   dataType:'json',
	   success: function(res){
		 callback(res)
	   }
	});
}


/**
 * 实时保存单个值
 * @param _this
 */
function realsave(_this){
	itemid = $(_this).attr('itemid');
	url = $(_this).attr('url');
	table = $(_this).attr('tbname');
	_field = $(_this).attr('filed');
	_val = $(_this).val();
    if(itemid > 0){
  	  post({value:_val, id:itemid, tbname:table, code:_field}, url, function(obj){
  		  if(obj.status == 1){
  			  layer.msg(obj.info, {icon: 1});
  		  }else{
  			  layer.msg(obj.info, {icon: 2});
  		  }
	  });
    }
}


/**
 * 执行批量删除
 * @param _this
 */
function batchDel(url,msg){
	if (!msg){
        msg = '确定执行批量删除操作？';
	}
	layer.confirm(msg, {icon: 3, title:'系统提示'}, function(index){
		var ids = '';
		var child = $('input[lay-filter="idchoose"]');
		child.each(function(index, item){
			if(item.checked){
				ids += item.value+',';
			}
	    });
		post({idstr:ids}, url, function(obj){
		   if(obj.code == 1){
			  location.reload();
		   }else{
			  layer.msg(obj.msg, {icon: 2});
		   }
		})
	});
}


function batchDoSomeThing(url,msg, tt){
    if (!msg){
        msg = '确定执行批量删除操作？';
    }
    layer.confirm(msg, {icon: 3, title:'系统提示'}, function(index){
        var ids = '';
        var child = $('input[lay-filter="idchoose"]');
        child.each(function(index, item){
            if(item.checked){
                ids += item.value+',';
            }
        });
        if(ids == ''){
        	if(tt == 1){
                return;
			}
		}
        if(tt == 1){
            newurl = url+'/ids/'+ids;
		}else{
            newurl = url+'/tt/2';
		}
        bstarttime = $('#bstarttime').val();
        bendtime = $('#bendtime').val();
        var tkstatus = $('input[lay-filter="tkstatus"]');
        if(bstarttime != ''){
            newurl = newurl+'/bstarttime/'+bstarttime;
		}
        if(bendtime != ''){
            newurl = newurl+'/bendtime/'+bendtime;
        }
        if(tkstatus[0].checked){
            newurl = newurl+'/tkstatus/1';
		}
        window.open(newurl);
        layer.closeAll();
    });
}