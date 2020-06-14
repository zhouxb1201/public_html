<template>
  <div class="thingcircle-detail">
    <HeadNavbar :head_info="detail" />
    <div class="swipe-box" :style="maxHeight">
      <van-swipe class="swipe" :autoplay="3000" :style="maxHeight" v-if="bannerImg.length > 0">
        <van-swipe-item v-for="(item,index) in bannerImg" :key="index">
          <img :src="item.pic_cover" :onerror="$ERRORPIC.noGoods" />
        </van-swipe-item>
      </van-swipe>
      <img src :onerror="$ERRORPIC.noGoods" v-else />
    </div>
    <GoodsCard :goods_list="goods_list" />
    <ThemeContent :theme="detail" />
    <CellComment @click-answer="onAnswer" :user_id="user_id" ref="commmet" />
    <BottomAction :editorial="editorial" ref="btmAction" @clickActive="clickActive" />
  </div>
</template>

<script>
import GoodsCard from "./component/detail/grap/GoodsCard";
import ThemeContent from "./component/detail/grap/ThemeContent";
import BottomAction from "./component/detail/grap/BottomAction";
import CellComment from "./component/detail/CellComment";
import HeadNavbar from "./component/detail/HeadNavbar";
import {
  GET_THINGCIRCLEDETAIL,
  GET_THINGCIRCLEREPLY,
  GET_SHAREINFO
} from "@/api/thingcircle";
import { filterUriParams } from "@/utils/util";
import { _encode } from "@/utils/base64";
import { Swipe, SwipeItem } from "vant";
import sfc from "@/utils/create";
export default sfc({
  name: "thingcircle-grapdetail",
  data() {
    return {
      bannerImg: [],
      goods_list: [],

      editorial: {},
      reply: [],
      comment_list: [],
      user_id: "",

      detail: {},
      is_send: true
    };
  },
  computed: {
    maxHeight() {
      return {
        maxHeight: document.body.offsetWidth + "px"
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
        thing_id: $this.thing_id
      };
      if ($this.$route.query.uid) {
        params.uid = $this.$route.query.uid;
      }
      GET_THINGCIRCLEDETAIL(params).then(({ data }) => {
        $this.bannerImg = data.img_temp_array;
        $this.goods_list = data.recommend_goods_list
          ? data.recommend_goods_list
          : [];

        $this.editorial = {
          thing_id: data.id,
          topic_id: data.topic_id,
          likes: data.likes,
          collects: data.collects,
          is_like: data.is_like,
          is_collect: data.is_collect
        };

        $this.user_id = data.user_id;

        $this.detail = {
          title: data.title,
          content: data.content,
          create_time: data.create_time,
          location: data.location,
          reading_volumes: data.reading_volumes,
          topic_title: data.topic_title,
          user_headimg: data.user_headimg,
          thing_user_name: data.thing_user_name,
          user_id: data.user_id,
          is_attention: data.is_attention,
          id: data.id
        };

        $this.getShareInfo();
      });
    },
    onAnswer(params) {
      this.editorial.to_uid = params.to_uid;
      this.editorial.comment_pid = params.comment_pid;
      this.editorial.people = params.people;
      this.editorial.hash = params.hash;
      this.$refs.btmAction.onPopMessage();
    },
    clickActive() {
      this.$refs.commmet.loadList("init");
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
    }
  },
  components: {
    HeadNavbar,
    GoodsCard,
    ThemeContent,
    CellComment,
    BottomAction,
    [Swipe.name]: Swipe,
    [SwipeItem.name]: SwipeItem
  }
});
</script>

<style scoped>
.thingcircle-detail {
  background-color: #fff;
}
.thingcircle-detail >>> .empty {
  position: relative;
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
</style>