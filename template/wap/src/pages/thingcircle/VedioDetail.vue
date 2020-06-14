<template>
  <div class="thingcircle-vediodetail">
    <div class="swipe-box" :style="maxHeight">
      <van-swipe
        class="swipe"
        :show-indicators="false"
        :vertical="true"
        :style="maxHeight"
        :touchable="touchable"
        @change="onChange"
      >
        <van-swipe-item v-for="(item,index) in list" :key="index">
          <PlayVedio
            :src="item.img_temp_array.length > 0 ? item.img_temp_array[0].pic_cover : ''"
            :poster="item.poster"
            ref="plVedio"
          />
          <DetailHeadNavbar :head_info="item" class="header-wrap" />
          <div class="vedio-fixed">
            <div class="vedio-prt">
              <div class="vedio-goods-wrap">
                <div class="goods" @click="showPopupGoods(item.recommend_goods_list)">
                  <van-icon name="cart" size="10px" />
                  <span>推荐商品</span>
                </div>
              </div>
              <VedioCellContent :items="item" />
              <VedioCellBottomAction
                :items="item"
                @openSend="openSend(item.id,item.topic_id)"
                @openComment="openComment(item.id,item.topic_id)"
              />
            </div>
          </div>
        </van-swipe-item>
      </van-swipe>
    </div>
    <VedioCellPopGoods :goods_list="goods_list" ref="popupGoods" />

    <VedioCellPopComment :tid="tid" :topic_id="topic_id" ref="popupComment" />

    <van-popup v-model="is_send" position="bottom" :overlay="true">
      <div class="msg-input">
        <van-field
          type="text"
          :placeholder="placeholder"
          maxlength="200"
          v-model="message"
          ref="inputMsg"
        />
        <div class="btn-s" @click="addComment">发送</div>
      </div>
    </van-popup>
  </div>
</template>

<script>
import DetailHeadNavbar from "./component/detail/HeadNavbar";
import VedioCellPopGoods from "./component/detail/vedio/CellPopGoods";
import VedioCellContent from "./component/detail/vedio/CellContent";
import VedioCellBottomAction from "./component/detail/vedio/CellBottomAction";
import VedioCellPopComment from "./component/detail/vedio/CellPopComment";
import PlayVedio from "./component/detail/vedio/PlayVedio";
import { filterUriParams } from "@/utils/util";
import { _encode } from "@/utils/base64";
import { Swipe, SwipeItem } from "vant";
import {
  GET_THINGCIRCLEVEDIODETAIL,
  ADD_THINGCIRCLECOMMENT,
  GET_SHAREINFO
} from "@/api/thingcircle";
import sfc from "@/utils/create";
export default sfc({
  name: "thingcircle-vediodetail",
  data() {
    return {
      detail: {},
      list: [],
      goods_list: [],
      is_send: false,
      is_goods: false,
      tid: null,
      topic_id: null,
      touchable: true,

      message: "",
      placeholder: "爱评论的人粉丝多~"
    };
  },
  computed: {
    maxHeight() {
      return {
        height: document.body.offsetHeight + "px"
      };
    },
    thing_id() {
      return this.$route.params.thingid;
    },
    uid() {
      let uid = null;
      if (this.$store.state.member.info) {
        uid = this.$store.state.member.info.uid;
      }
      return uid;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      let params = {
        thing_id: $this.thing_id,
        page_index: 1,
        page_size: 10
      };
      if ($this.$route.query.uid) {
        params.uid = $this.$route.query.uid;
      }
      GET_THINGCIRCLEVEDIODETAIL(params).then(({ data }) => {
        data.data.forEach((e)=>{
          e.poster = typeof e.video_img != 'object'?'':e.video_img.pic_cover
        })
        $this.list = data.data;
        $this.getShareInfo();
      });
    },
    getShareInfo() {
      const $this = this;
      let uid = encodeURIComponent(_encode($this.uid));
      let params = {
        thing_id: $this.thing_id
      };
      GET_SHAREINFO(params).then(({ data }) => {
        $this.onShare({
          title: data.thing_title ? data.thing_title : "好物圈",
          desc: data.thing_describe
            ? data.thing_describe
            : `我刚刚在${$this.$store.getters.config.mall_name}发现了一个很不错的好物圈，赶快来看看吧。`,
          imgUrl: data.thing_pic,
          link:
            $this.$store.state.domain +
            "/wap" +
            $this.$route.path +
            "?uid=" +
            uid
        });
      });
    },
    openSend(id, topic_id) {
      this.is_send = true;
      this.tid = id;
      this.topic_id = topic_id;
      setTimeout(() => {
        this.$refs.inputMsg.focus();
      }, 1);
    },
    openComment(tid, topic_id) {
      this.$refs.popupComment.isShow = true;
      this.tid = tid;
      this.topic_id = topic_id;
    },
    showPopupGoods(data) {
      this.$refs.popupGoods.isShow = true;
      this.goods_list = data;
    },
    //发送评论
    addComment() {
      const $this = this;
      let params = {
        thing_id: $this.tid,
        topic_id: $this.topic_id,
        content: $this.message
      };

      if (!$this.message) {
        return false;
      }
      ADD_THINGCIRCLECOMMENT(params).then(({ data }) => {
        $this.$Toast("发表成功");
        $this.message = "";
        $this.placeholder = "说点什么...";
        $this.is_send = false;
        $this.$refs.popupComment.nextLoad();
      });
    },
    onChange(index) {
      this.list.forEach((e, i) => {
        this.$refs.plVedio[i].stop();
      });
      this.$refs.plVedio[index].start();
    }
  },
  components: {
    DetailHeadNavbar,
    VedioCellPopGoods,
    VedioCellContent,
    VedioCellBottomAction,
    VedioCellPopComment,
    PlayVedio,
    [Swipe.name]: Swipe,
    [SwipeItem.name]: SwipeItem
  }
});
</script>

<style scoped>
.thingcircle-vediodetail {
  background-color: #fff;
}
.swipe-box {
  position: relative;
  overflow: hidden;
}

.swipe img {
  width: 100%;
  height: auto;
  display: block;
}
.list {
  position: relative;
}
.header-wrap {
  height: auto !important;
}
.header-wrap >>> .head-nav__left .van-icon {
    color: #fff;
}
.header-wrap >>> .head-nav-fixed {
  background-color: rgba(225, 225, 225, 0);
  color: #fff;
}
.header-wrap >>> .van-button--plain {
  color: #fff;
  background-color: #f44;
  border: 1px solid #f44;
}
.vedio-fixed {
  position: fixed;
  bottom: 0;
  width: 100%;
  left: 0;
  height: auto;
  z-index: 20;
}
.vedio-prt {
  position: relative;
}
.vedio-goods-wrap {
  overflow: hidden;
  color: #ffffff;
}

.goods {
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  width: 90px;
  height: 20px;
  font-size: 12px;
  padding: 0px 10px;
  display: flex;
  align-items: center;
  text-align: center;
  margin-left: 14px;
}
.goods >>> .van-icon {
  background-color: #f44;
  border-radius: 4px;
  padding: 2px;
  margin-right: 4px;
}

.msg-input {
  display: flex;
  width: 100%;
  align-items: center;
}
.msg-input >>> .van-field__control {
  border: 1px solid #dadada;
  border-radius: 20px;
}

.btn-s {
  width: 40px;
  height: 100%;
  color: #1989fa;
}
.msg-input >>> .van-field__control {
  padding-left: 10px;
}
</style>