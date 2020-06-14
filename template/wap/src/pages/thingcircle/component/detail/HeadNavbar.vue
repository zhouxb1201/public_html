<template>
  <div>
    <div class="head-nav">
      <div class="head-nav-fixed">
        <div class="head-nav__left" @click="goBack()">
          <van-icon name="arrow-left" size="16px" />
        </div>
        <div class="head-info">
          <img
            :src="head_info.user_headimg ? head_info.user_headimg :  $ERRORPIC.noAvatar"
            :onerror="$ERRORPIC.noAvatar"
            class="img"
          />
          <span
            class="name"
          >{{head_info.thing_user_name ? head_info.thing_user_name : "匿名"}}</span>
        </div>
        <div class="head-nav__right">
          <van-button
            plain
            round
            size="small"
            type="danger"
            v-if="uid != head_info.user_id"
            @click="sensitiveOthers"
            :class="head_info.is_attention ? btn_on : ''"
          >{{head_info.is_attention ? '已关注' : '关注'}}</van-button>
          <van-icon name="share" size="20px" @click="toShare()" />
        </div>
      </div>
    </div>
    <!--分享-->
    <PopupShare :isShow="isShare" @click.native="closeShare"></PopupShare>
  </div>
</template>

<script>
import { GET_THINGCIRCLEFOLLOW } from "@/api/thingcircle";
import { setSession } from "@/utils/storage";
import { filterUriParams } from "@/utils/util";
import PopupShare from "../../../wheelsurf/component/PopupShare";
export default {
  data() {
    return {
      btn_on: "btn-gray",
      isShare: false //是否弹出分享层
    };
  },
  props: {
    head_info: [Object, Array]
  },
  computed: {
    uid() {
      let uid = null;
      if (this.$store.state.member.info) {
        uid = this.$store.state.member.info.uid;
      }
      return uid;
    }
  },
  mounted() {},
  methods: {
    goBack() {
      this.$router.back();
    },
    sensitiveOthers() {
      const $this = this;
      $this.isLoign();
      let param = {
        thing_auid: $this.head_info.user_id
      };
      GET_THINGCIRCLEFOLLOW(param).then(res => {
        if (res.code == 1) {
          if (res.message == "关注成功") {
            $this.head_info.is_attention = 1;
          } else {
            $this.head_info.is_attention = 0;
          }
        }
      });
    },
    isLoign() {
      const $this = this;
      if (!$this.$store.getters.token) {
        setSession("toPath", $this.$router.currentRoute.fullPath);
        $this.$router.replace({ name: "login" });
      } else {
        return false;
      }
    },
    toShare() {
      const $this = this;
      if (this.$store.state.isWeixin) {
        $this.isShare = true;
      } else {
        $this.$Toast("请点击下方工具栏“分享”按钮进行分享");
      }
    },
    //关闭分享
    closeShare() {
      this.isShare = false;
    }
  },
  components:{
    PopupShare
  }
};
</script>

<style scoped>
.head-nav {
  height: 46px;
}
.head-nav-fixed {
  top: 0;
  left: 0;
  width: 100%;
  position: fixed;
  z-index: 999;
  text-align: center;
  height: 46px;
  line-height: 46px;
  background-color: #fff;
}
.head-nav__left {
  bottom: 0;
  font-size: 14px;
  position: absolute;
  left: 15px;
  display: flex;
  align-items: center;
  height: 100%;
}
.head-nav__left >>> .van-icon {
  color: #666;
  font-size: 16px;
  font-weight: 800;
}
.head-info {
  display: flex;
  margin-left: 40px;
  height: 100%;
  align-items: center;
  width: 60%;
}
.head-info .img {
  width: 36px;
  height: 36px;
  display: block;
  border-radius: 50%;
  margin-right: 10px;
}
.head-info .name {
  font-size: 16px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.head-nav__right {
  bottom: 0;
  font-size: 14px;
  position: absolute;
  right: 15px;
  display: flex;
  align-items: center;
  height: 100%;
}
.head-nav__right >>> .van-icon {
  margin-left: 10px;
}
.head-nav__right >>> .van-button--small {
  height: 24px;
  line-height: 22px;
}
.btn-gray {
  color: #999;
  border: 1px solid #e5e5e5;
}
</style>