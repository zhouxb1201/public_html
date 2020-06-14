<template>
  <div class="head card-group-box">
    <img :src="$BASEIMGPATH + 'signin-head-bg.png'" class="bg" />
    <div class="box">
      <div class="info">
        <div class="img">
          <img :src="info.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar" />
        </div>
        <div class="text">
          <div class="name">{{info.nick_name}}</div>
          <div class="name">
            连续签到
            <span class="strong">{{info.continuous}}</span>
          </div>
          <router-link to="/signin/log" class="strong fs-12">签到明细 ></router-link>
        </div>
      </div>
      <div class="btn-group flex-auto-center">
        <van-button
          round
          hairline
          size="small"
          class="btn"
          :loading="isLoading"
          :disabled="signinStateDisabled"
          @click="bindMobile('onSignin')"
        >{{signinStateText}}</van-button>
      </div>
    </div>
  </div>
</template>

<script>
import { SET_SIGNIN } from "@/api/signin";
import { formatDate } from "@/utils/util";
import { bindMobile } from "@/mixins";
export default {
  data() {
    return {
      isLoading: false
    };
  },
  props: {
    info: Object
  },
  mixins: [bindMobile],
  computed: {
    signinStateDisabled() {
      return this.info.is_signin ? true : false;
    },
    signinStateText() {
      return this.info.is_signin ? "已签" : "签到";
    }
  },
  methods: {
    onSignin() {
      this.isLoading = true;
      SET_SIGNIN()
        .then(() => {
          this.info.is_signin = 1;
          this.isLoading = false;
          this.$emit("success", formatDate(new Date().getTime()));
        })
        .catch(() => {
          this.isLoading = false;
        });
    }
  }
};
</script>

<style scoped>
.head {
  position: relative;
  overflow: hidden;
}

.head .bg {
  max-width: 100%;
  height: auto;
  display: block;
}

.head .box {
  position: absolute;
  left: 0;
  top: 0;
  z-index: 1;
  display: flex;
  justify-content: space-between;
  width: 100%;
  height: 100%;
  padding: 0 20px;
}

.head .info {
  display: flex;
  align-items: center;
  z-index: 1;
}

.head .info .img {
  width: 50px;
  height: 50px;
  overflow: hidden;
  border-radius: 50%;
  border: 2px solid #ffffff;
}

.head .info .img img {
  width: 100%;
  height: 100%;
}

.head .info .text {
  margin-left: 10px;
  color: #ffffff;
  line-height: 1.4;
}

.head .btn-group .btn {
  color: #ffe49c;
  border: 1px solid #ffe49c;
  background: transparent;
}

.head .strong {
  color: #ffe49c;
}
</style>
