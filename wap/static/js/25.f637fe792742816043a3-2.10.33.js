webpackJsonp([25],{"2Jpt":function(t,a){},"3T4Z":function(t,a){},"3o28":function(t,a,e){"use strict";var s={data:function(){return{}},props:{id:[String,Number],image:String,name:String,total_count:[String,Number],buttomText:String,showbtn:{type:Boolean,default:!0}},methods:{}},i={render:function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("div",{staticClass:"card-group-box"},[e("div",{staticClass:"van-cell"},[e("div",{staticClass:"van-cell__value van-cell__value--alone"},[e("div",{staticClass:"card"},[e("div",{staticClass:"card__thumb"},[e("img",{attrs:{src:t.image,onerror:t.$ERRORPIC.noGoods}})]),t._v(" "),e("div",{staticClass:"card__content"},[e("div",{staticClass:"card__title"},[t._v(t._s(t.name?t.name:""))]),t._v(" "),e("div",{staticClass:"card__bottom"},[t.total_count?e("div",{staticClass:"card__price-group"},[e("span",{staticClass:"card__price"},[t._v("共"+t._s(t.total_count)+"节")])]):t._e(),t._v(" "),t.showbtn?e("div",{staticClass:"card__btn"},[e("van-button",{attrs:{type:"danger",size:"small",to:t.id}},[t._v(t._s(t.buttomText))])],1):t._e()])])])])])])},staticRenderFns:[]};var n=e("VU/8")(s,i,!1,function(t){e("4hfm")},"data-v-dda1788e",null);a.a=n.exports},"4hfm":function(t,a){},Gy3e:function(t,a){},SPhW:function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0});var s,i=e("bOdI"),n=e.n(i),o=(e("4yKu"),e("wolx")),r=e("eKM9"),c=e("msXN"),d=e("h0S9"),l=e("3o28"),u=Object(r.a)({name:"course-list",data:function(){return{params:{page_index:1,page_size:14,search_text:""}}},mixins:[c.e],mounted:function(){this.loadList()},methods:{onSearch:function(){this.loadList("init")},loadList:function(t){var a=this;t&&"init"===t&&a.initList(),Object(d.c)(a.params).then(function(e){var s=e.data,i=s.knowledge_payment_list;a.pushToList(i,s.page_count,t)}).catch(function(){a.loadError()})}},components:(s={},n()(s,o.a.name,o.a),n()(s,"Card",l.a),s)}),v={render:function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("div",{staticClass:"course-list bg-f8"},[e("Navbar"),t._v(" "),e("van-search",{attrs:{placeholder:"课程名称"},on:{search:t.onSearch},model:{value:t.params.search_text,callback:function(a){t.$set(t.params,"search_text",a)},expression:"params.search_text"}}),t._v(" "),e("List",{attrs:{finished:t.finished,error:t.error,"is-empty":t.isListEmpty,empty:{pageType:"goods",message:"暂无课程",showFoot:!0,top:t.$store.state.isWeixin?46:90,btnLink:"/",btnText:"返回首页"}},on:{"update:error":function(a){t.error=a},load:t.loadList},model:{value:t.loading,callback:function(a){t.loading=a},expression:"loading"}},[e("div",{staticClass:"list"},t._l(t.list,function(t,a){return e("Card",{key:a,attrs:{id:"/course/detail/"+t.goods_id,image:t.goods_picture,name:t.goods_name,total_count:t.total_count,buttomText:"前往学习"}})}))])],1)},staticRenderFns:[]};var _=e("VU/8")(u,v,!1,function(t){e("Z6E1")},"data-v-e15b7ce2",null);a.default=_.exports},VmDO:function(t,a){},Z6E1:function(t,a){},kmKo:function(t,a){},"zxO+":function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0});var s=e("eKM9"),i=e("h0S9"),n=e("Jv72"),o={data:function(){return{audioOverlayShow:!1}},computed:{options:function(){return{height:"300px",autoplay:!1,sources:[{type:"video/mp4",src:this.data.content}],playsinline:!0}}},mounted:function(){this.isSee()},watch:{"data.is_see":{handler:function(t,a){t>0?this.audioOverlayShow=!1:this.data.is_buy||-1!=t?this.audioOverlayShow=!1:this.audioOverlayShow=!0}}},methods:{currentTime1:function(t){var a=60*this.data.is_see;!this.data.is_buy&&this.data.is_see>0&&t>a&&(this.options.sources[0].src="",this.audioOverlayShow=!0)},isSee:function(){console.log("isSee"),this.data.is_buy||-1!=this.data.is_see||(this.audioOverlayShow=!0,this.options.sources[0].src="")}},props:{data:[Object,Array],goodsId:[String,Number]},components:{VideoPlayer:n.a}},r={render:function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("div",{staticClass:"vedio-wrap"},[e("VideoPlayer",{ref:"videoPlayer",attrs:{options:t.options},on:{currentTime:t.currentTime1}}),t._v(" "),e("div",{staticClass:"title"},[t.data.is_see>0&&!t.data.is_buy?e("van-tag",{staticClass:"tag",attrs:{round:"",size:"medium",color:"#FAE9E6","text-color":"#ff454e"}},[t._v("试学"+t._s(t.data.is_see)+"分钟")]):t._e(),t._v("\n      "+t._s(t.data.name)+"\n  ")],1),t._v(" "),e("div",{directives:[{name:"show",rawName:"v-show",value:t.audioOverlayShow,expression:"audioOverlayShow"}],staticClass:"audio-overlay"},[e("div",{staticClass:"audio-overlay-text"},[t._v("试学结束，购买后查看完整版")]),t._v(" "),e("van-button",{attrs:{type:"danger",size:"small",to:"/goods/detail/"+t.goodsId}},[t._v("立即购买")])],1)],1)},staticRenderFns:[]};var c=e("VU/8")(o,r,!1,function(t){e("Gy3e")},"data-v-1cbd1a0f",null).exports,d=e("pFYg"),l=e.n(d);function u(t){var a=void 0===t?"undefined":l()(t);if("number"===a||"string"===a){t=parseInt(t);var e=Math.floor(t/3600);t-=3600*e;var s=Math.floor(t/60);return t-=60*s,e+":"+("0"+s).slice(-2)+":"+("0"+t).slice(-2)}return"0:00:00"}var v={data:function(){return{sliderTime:0,audio:{playing:!1,currentTime:0,maxTime:0,minTime:0,step:.1},audioOverlayShow:!1}},props:{data:[Object,Array],goodsId:[String,Number]},mounted:function(){this.isSee()},methods:{startPlayOrPause:function(){return this.audio.playing?this.pause():this.play()},play:function(){this.$refs.audio.play()},pause:function(){this.$refs.audio.pause()},onPlay:function(){this.audio.playing=!0},onPause:function(){this.audio.playing=!1},handleFocus:function(){},onLoadedmetadata:function(t){this.audio.maxTime=parseInt(t.target.duration)},onTimeupdate:function(t){this.audio.currentTime=t.target.currentTime,this.sliderTime=parseInt(this.audio.currentTime/this.audio.maxTime*100)},formatProcessToolTip:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0;return"进度条: "+u(t=parseInt(this.audio.maxTime/100*t))},handleTouchStart:function(t){this.setValue(t.touches[0]),document.addEventListener("touchmove",this.handleTouchMove),document.addEventListener("touchup",this.handleTouchEnd),document.addEventListener("touchend",this.handleTouchEnd),document.addEventListener("touchcancel",this.handleTouchEnd)},handleTouchMove:function(t){this.setValue(t.changedTouches[0])},handleTouchEnd:function(t){this.setValue(t.changedTouches[0]),document.removeEventListener("touchmove",this.handleTouchMove),document.removeEventListener("touchup",this.handleTouchEnd),document.removeEventListener("touchend",this.handleTouchEnd),document.removeEventListener("touchcancel",this.handleTouchEnd)},setValue:function(t){var a=this.$refs.audioContent,e=this.audio,s=e.maxTime,i=e.minTime,n=e.step,o=(t.clientX-a.getBoundingClientRect().left)/a.offsetWidth*(s-i);o=Math.round(o/n)*n+i,(o=parseFloat(o.toFixed(5)))>s?o=s:o<i&&(o=i),this.$refs.audio.currentTime=o},changeCurrentTime:function(t){this.$refs.audio.currentTime=parseInt(t/100*this.audio.maxTime)},isSee:function(){this.data.is_buy||-1!=this.data.is_see||(this.audioOverlayShow=!0)}},filters:{transPlayPause:function(t){return t?"pause-circle-o":"play-circle-o"},formatSecond:function(){return u(arguments.length>0&&void 0!==arguments[0]?arguments[0]:0)}},watch:{"audio.currentTime":{handler:function(t,a){var e=60*this.data.is_see;!this.data.is_buy&&this.data.is_see>0&&t>e&&(this.pause(),this.audioOverlayShow=!0)}},"data.is_see":{handler:function(t,a){t>0?this.audioOverlayShow=!1:this.data.is_buy||-1!=t?this.audioOverlayShow=!1:this.audioOverlayShow=!0}}}},_={render:function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("div",{staticClass:"audio-box"},[e("div",{staticClass:"audio-img"},[e("img",{attrs:{src:t.data.goods_picture}})]),t._v(" "),e("div",{ref:"audioContent",staticClass:"audio-content"},[e("audio",{ref:"audio",staticStyle:{display:"none"},attrs:{src:t.data.content,controls:"controls"},on:{pause:t.onPause,play:t.onPlay,timeupdate:t.onTimeupdate,loadedmetadata:t.onLoadedmetadata}}),t._v(" "),e("div",[e("div",{staticClass:"title"},[t.data.is_see>0&&!t.data.is_buy?e("van-tag",{staticClass:"tag",attrs:{round:"",size:"medium",color:"#FAE9E6","text-color":"#ff454e"}},[t._v("试学"+t._s(t.data.is_see)+"分钟")]):t._e(),t._v("\n                "+t._s(t.data.name)+"\n            ")],1),t._v(" "),e("div",{staticClass:"slider",on:{touchstart:t.handleTouchStart}},[e("div",{staticClass:"slider-track"}),t._v(" "),e("div",{staticClass:"slider-fill",style:"width:"+t.sliderTime+"%"}),t._v(" "),e("div",{staticClass:"slider-thumb",style:"left:"+t.sliderTime+"%"})]),t._v(" "),e("div",{staticClass:"flex-pack-justify"},[e("div",{staticClass:"fs-10"},[t._v(t._s(t._f("formatSecond")(t.audio.currentTime)))]),t._v(" "),e("div",{staticClass:"startPlayOrPause"},[e("a",{on:{click:t.startPlayOrPause}},[e("van-icon",{attrs:{name:t._f("transPlayPause")(t.audio.playing),size:"28px",color:"#ff454e"}})],1)]),t._v(" "),e("div",{staticClass:"pr-12 fs-10"},[t._v(t._s(t._f("formatSecond")(t.audio.maxTime)))])])])]),t._v(" "),e("div",{directives:[{name:"show",rawName:"v-show",value:t.audioOverlayShow,expression:"audioOverlayShow"}],staticClass:"audio-overlay"},[e("div",{staticClass:"audio-overlay-text"},[t._v("试学结束，购买后查看完整版")]),t._v(" "),e("van-button",{attrs:{type:"danger",size:"small",to:"/goods/detail/"+t.goodsId}},[t._v("立即购买")])],1)])},staticRenderFns:[]};var m=e("VU/8")(v,_,!1,function(t){e("3T4Z")},"data-v-a63cd478",null).exports,h=e("3o28"),p=e("qsHl"),f=(e("oFuF"),e("ADxc")),g={data:function(){return{evaluate_count:0,imgs_count:0,praise_count:0,center_count:0,bad_count:0,list:[],params:{goods_id:this.data||null,page_index:1,page_size:20,is_image:null,explain_type:null}}},props:{data:[Number,String]},filters:{explainText:function(t){var a="";return 5==t?a="好评":3==t?a="中评":1==t&&(a="差评"),a}},watch:{params:{deep:!0,handler:function(t,a){this.loadData()}}},computed:{tabs:function(){return[{name:"全部",type:null,count:this.evaluate_count},{name:"图片",type:!0,count:this.imgs_count},{name:"好评",type:5,count:this.praise_count},{name:"中评",type:3,count:this.center_count},{name:"差评",type:1,count:this.bad_count}]}},mounted:function(){var t=this;setTimeout(function(){t.loadData()},100)},methods:{loadData:function(){var t=this;Object(p.h)(t.params).then(function(a){var e=a.data;t.list=e.review_list,t.evaluate_count=e.evaluate_count,t.imgs_count=e.imgs_count,t.praise_count=e.praise_count,t.center_count=e.center_count,t.bad_count=e.bad_count})},onTab:function(t){var a=this.tabs[t].type;!0===a?(this.params.is_image=!0,this.params.explain_type=null):(this.params.is_image=null,this.params.explain_type=a)}},components:{ImagePanelPreview:f.a}},y={render:function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("div",{staticClass:"evaluate"},[e("van-tabs",{attrs:{"swipe-threshold":5},on:{change:t.onTab}},t._l(t.tabs,function(t,a){return e("van-tab",{key:a,attrs:{title:t.name+"("+t.count+")"}})})),t._v(" "),t.list.length>0?e("van-cell-group",{staticClass:"items"},t._l(t.list,function(a,s){return e("van-cell",{key:s},[e("van-row",{staticClass:"head",attrs:{type:"flex",justify:"space-between"}},[e("van-col",{staticClass:"info",attrs:{span:"14"}},[e("div",{staticClass:"img"},[e("img",{attrs:{src:a.user_img,onerror:t.$ERRORPIC.noAvatar}})]),t._v(" "),e("div",{staticClass:"user"},[e("div",{staticClass:"name"},[t._v(t._s(a.user_name?a.user_name:a.nick_name?a.nick_name:"匿名"))]),t._v(" "),e("span",{staticClass:"score"},[t._v(t._s(t._f("explainText")(a.explain_type)))])])]),t._v(" "),e("van-col",{staticClass:"time",attrs:{span:"10"}},[t._v(t._s(t._f("formatDate")(a.addtime)))])],1),t._v(" "),e("div",{staticClass:"content-item"},[e("div",{staticClass:"content"},[t._v(t._s(a.content))]),t._v(" "),a.images[0]?e("div",{staticClass:"imgs"},[e("ImagePanelPreview",{attrs:{"show-delete":!1,list:a.images}})],1):t._e()]),t._v(" "),a.explain_first?e("div",{staticClass:"content-item"},[e("div",{staticClass:"title"},[t._v("[商家回复]：")]),t._v(" "),e("div",{staticClass:"content"},[t._v(t._s(a.explain_first))])]):t._e(),t._v(" "),a.again_content||a.again_images[0]?e("div",{staticClass:"content-item"},[e("div",{staticClass:"title"},[t._v("追评：")]),t._v(" "),e("div",{staticClass:"content"},[t._v(t._s(a.again_content))]),t._v(" "),a.again_images[0]?e("div",{staticClass:"imgs"},[e("ImagePanelPreview",{attrs:{"show-delete":!1,list:a.again_images}})],1):t._e()]):t._e(),t._v(" "),a.again_explain?e("div",{staticClass:"content-item"},[e("div",{staticClass:"title"},[t._v("[追评回复]：")]),t._v(" "),e("div",{staticClass:"content"},[t._v(t._s(a.again_explain))])]):t._e()],1)})):e("div",{staticClass:"empty"},[t._v("暂无评价")])],1)},staticRenderFns:[]};var C=e("VU/8")(g,y,!1,function(t){e("2Jpt")},"data-v-2989d4c2",null).exports,b=(e("mMXg"),e("qYlo")),w={name:"getOrder",props:{knowledgePaymentId:[String,Number],showother:{type:Boolean}},data:function(){return{myshow:this.showother,source:{},is_buy:"",params:{goods_id:this.$route.params.id}}},computed:{},mounted:function(){this.loadData()},methods:{close:function(){this.$emit("closeTip",{flg:"1"})},close1:function(t){this.$emit("closeTip",{flg:"1",id:t})},loadData:function(){var t=this;Object(i.b)(this.params).then(function(a){var e=a.data;t.source=e.konwledge_payment_list,t.is_buy=e.is_buy})}},watch:{showother:{handler:function(t,a){this.myshow=t}}},components:{Popup:b.a}},x={render:function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("div",{staticClass:"directory"},[e("van-popup",{attrs:{closeable:"",duration:.3,position:"bottom"},on:{"click-overlay":t.close,close:t.close},model:{value:t.myshow,callback:function(a){t.myshow=a},expression:"myshow"}},[e("div",{staticClass:"van-hairline--top-bottom van-actionsheet__header"},[e("div",[t._v("课程目录")]),t._v(" "),e("van-icon",{attrs:{name:"close"},on:{click:t.close}})],1),t._v(" "),e("van-cell-group",t._l(t.source,function(a,s){return e("van-cell",{key:s,on:{click:function(e){t.close1(a.knowledge_payment_id)}}},[e("van-col",{class:{active:a.knowledge_payment_id==t.knowledgePaymentId},attrs:{span:"18"}},[t._v("\n        "+t._s(a.knowledge_payment_name)+"\n\n      ")]),t._v(" "),t.is_buy?e("van-col",{attrs:{span:"6"}},[a.knowledge_payment_id==t.knowledgePaymentId?e("div",{staticClass:"same"},[e("span",{staticClass:"line1"}),t._v(" "),e("span",{staticClass:"line2"}),t._v(" "),e("span",{staticClass:"line3"}),t._v(" "),e("span",{staticClass:"line4"})]):t._e()]):e("van-col",{attrs:{span:"6"}},[a.knowledge_payment_id==t.knowledgePaymentId?e("div",{staticClass:"same"},[e("span",{staticClass:"line1"}),t._v(" "),e("span",{staticClass:"line2"}),t._v(" "),e("span",{staticClass:"line3"}),t._v(" "),e("span",{staticClass:"line4"})]):t._e(),t._v(" "),-1==a.knowledge_payment_is_see?e("van-tag",{staticClass:"tag",attrs:{round:"",size:"medium",color:"#FAE9E6","text-color":"#ff454e"}},[t._v(" 付费浏览")]):t._e(),t._v(" "),a.knowledge_payment_is_see>0?e("van-tag",{staticClass:"tag",attrs:{round:"",size:"medium",color:"#FAE9E6","text-color":"#ff454e"}},[t._v("试学")]):t._e()],1)],1)}))],1)],1)},staticRenderFns:[]};var S=e("VU/8")(w,x,!1,function(t){e("VmDO")},"data-v-61e11966",null).exports,T=Object(s.a)({name:"course-detail",data:function(){return{imgSource:{},params:{goods_id:this.$route.params.id,knowledge_payment_id:this.$route.params.cid||""},show:!1,update:!0}},computed:{maxHeight:function(){return{maxHeight:document.body.offsetWidth+"px",width:"100%",height:"auto"}}},watch:{},mounted:function(){this.loadDataDetail()},methods:{loadDataDetail:function(){var t=this;Object(i.a)(this.params).then(function(a){var e=a.data;t.imgSource=e,t.$refs.load.success()}).catch(function(){t.$refs.load.fail()})},changeshow:function(t){var a=this;t.flg?this.show=!1:this.show=!this.show,t.id&&(this.imgSource={},Object(i.a)({knowledge_payment_id:t.id}).then(function(t){var e=t.data;a.imgSource=e}).catch(function(){}))}},components:{PlayVedio:c,PlayAudio:m,Card:h.a,Evaluate:C,Directory:S}}),P={render:function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("Layout",{ref:"load",staticClass:"course-detail bg-f8"},[e("Navbar"),t._v(" "),1==t.imgSource.type?e("PlayVedio",{attrs:{data:t.imgSource,goodsId:t.$route.params.id}}):t._e(),t._v(" "),2==t.imgSource.type?e("PlayAudio",{attrs:{data:t.imgSource,goodsId:t.$route.params.id}}):t._e(),t._v(" "),3==t.imgSource.type?e("div",{staticClass:"picture"},[t.imgSource.is_buy||-1!=t.imgSource.is_see?e("div",[e("img",{style:t.maxHeight,attrs:{src:t.imgSource.content}}),t._v(" "),e("div",{staticClass:"source__title"},[t._v(t._s(t.imgSource.name))])]):e("div",[e("img",{style:t.maxHeight,attrs:{src:t.$BASEIMGPATH+"empty-data.png"}}),t._v(" "),e("div",{staticClass:"source__title"},[t._v(t._s(t.imgSource.name))]),t._v(" "),e("div",{staticClass:"audio-overlay"},[e("div",{staticClass:"audio-overlay-text"},[t._v("试学结束，购买后查看完整版")]),t._v(" "),e("van-button",{attrs:{type:"danger",size:"small",to:"/goods/detail/"+t.$route.params.id}},[t._v("立即购买")])],1)])]):t._e(),t._v(" "),e("Card",{attrs:{id:"/goods/detail/"+t.$route.params.id,image:t.imgSource.goods_picture,name:t.imgSource.goods_name,total_count:t.imgSource.total_count,buttomText:"立即购买",showbtn:!t.imgSource.is_buy}}),t._v(" "),e("Evaluate",{attrs:{data:t.$route.params.id}}),t._v(" "),e("div",{staticClass:"d-box",on:{click:t.changeshow}},[e("van-icon",{attrs:{name:"orders-o"}}),t._v(" "),e("div",{staticClass:"d-box-text"},[t._v("目录")])],1),t._v(" "),e("Directory",{attrs:{showother:t.show,knowledgePaymentId:t.imgSource.id},on:{closeTip:t.changeshow}})],1)},staticRenderFns:[]};var E=e("VU/8")(T,P,!1,function(t){e("kmKo")},"data-v-67bcc226",null);a.default=E.exports}});