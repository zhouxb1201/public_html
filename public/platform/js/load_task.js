require(['util'],function(util){
$(function(){
	$.ajax({
		url : __URL(SHOPMAIN+"/task/load_task"),
		type : "post",
		dataType : "json",
		success : function(data) {
		}
	});
});
 });