<template>
  <div class="invite-wechat" v-if="isShowWechat">
    <van-cell center class="cell-group">
      <div slot="icon" class="img">
        <img :src="$store.getters.config.logo" :onerror="$ERRORPIC.noSquare" />
      </div>
      <div class="text">
        <div class="title1 text-nowrap">{{wechatSet.default_title1}}</div>
        <div class="title2 text-nowrap">{{wechatSet.default_title2}}</div>
      </div>
      <div slot="right-icon" class="btn-box">
        <van-button
          type="danger"
          class="btn text-nowrap"
          size="mini"
          @click="onInvite"
        >{{wechatSet.btn_text}}</van-button>
      </div>
    </van-cell>
    <PopupAdvertise
      v-model="showQr"
      hideOnBlur
      :imgSrc="wechatSet.concern_qr"
      v-if="wechatSet.btn_action != 1"
    />
  </div>
</template>

<script>
import { getSession } from "@/utils/storage";
import PopupAdvertise from "./PopupAdvertise";
export default {
  data() {
    return {
      showQr: false
    };
  },
  computed: {
    isShowWechat() {
      return (
        this.$store.state.custom.isShowWechat &&
        !this.$store.state.member.info.is_subscribe
      );
    },
    wechatSet() {
      let set = this.$store.state.custom.wechat;
      if (getSession("getSupCode")) {
        set.default_title1 = set.invite_title1
          ? set.invite_title1
          : set.default_title1;
        set.default_title2 = set.invite_title2
          ? set.invite_title2
          : set.default_title2;
      }
      return set;
    }
  },
  methods: {
    onInvite() {
      const { btn_action, concern_code } = this.wechatSet;
      if (btn_action == "1") {
        if (!concern_code) return false;
        window.open(
          "https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=" +
            concern_code +
            "&scene=110#wechat_redirect"
        );
      } else {
        this.showQr = true;
      }
    }
  },
  components: {
    PopupAdvertise
  }
};
</script>

<style scoped>
.cell-group {
  background: #ffcc80;
}

.cell-group .img {
  width: 40px;
  height: 40px;
}

.cell-group .img img {
  width: 100%;
  height: 100%;
  display: block;
}

.cell-group .text {
  color: #ff3300;
  line-height: 1.5;
  padding: 0 10px;
}

.cell-group .text .title2 {
  font-size: 10px;
}

.cell-group .btn-box .btn {
  width: 58px;
}
</style>
