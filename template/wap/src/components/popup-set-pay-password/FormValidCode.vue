<template>
  <div>
    <van-cell-group class="cell-group">
      <van-field label="手机号码" type="tel" disabled v-model="$store.state.member.info.user_tel" />
      <van-field
        label="短信验证码"
        type="number"
        maxlength="6"
        placeholder="请输入验证码"
        v-model="verification_code"
      >
        <van-button
          slot="button"
          size="mini"
          plain
          class="btn-code"
          type="danger"
          :disabled="code_disabled"
          @click="sendcode"
        >{{code_txt}}</van-button>
      </van-field>
    </van-cell-group>
    <div class="foot">
      <van-button class="btn" size="normal" square @click="cancel">取消</van-button>
      <van-button class="btn btn-confirm" size="normal" square @click="onNext">下一步</van-button>
    </div>
  </div>
</template>

<script>
import { isEmpty } from "@/utils/util";
import { VALID_MSGCODE } from "@/api/member";
import { validMobile, validMsgcode, validImgcode } from "@/utils/validator";
import { setSession, getSession, removeSession } from "@/utils/storage";
import { GET_IMGCODE } from "@/api/user";
export default {
  data() {
    return {
      verification_code: "",

      code_disabled: false,
      code_time: 0,
      code_txt: "获取验证码"
    };
  },
  props: {
    cancel: Function
  },
  methods: {
    // 验证手机验证码
    onNext() {
      const $this = this;
      if (!validMsgcode($this.verification_code)) {
        return false;
      }
      const params = {};
      params.mobile = $this.$store.state.member.info.user_tel;
      params.verification_code = $this.verification_code;

      VALID_MSGCODE(params).then(res => {
        if (res.code === 0) {
          $this.$Toast(res.message);
        } else {
          // 短信验证成功下一步操作;
          $this.$Toast.success("验证通过");
          $this.$emit("next");
          $this.verification_code = "";
        }
      });
    },
    // 发送验证码
    sendcode() {
      const $this = this;
      const mobile = $this.$store.state.member.info.user_tel;
      if (!validMobile(mobile)) {
        return false;
      }
      const params = {};
      params.mobile = mobile;
      params.type = "change_pay_password";

      // console.log(params);
      // return false

      $this.$store.dispatch("getMsgcode", params).then(res => {
        $this.code_time = 60;
        $this.code_disabled = true;
        $this.code_timer();
      });
    },
    code_timer() {
      if (this.code_time > 0) {
        this.code_time--;
        this.code_txt = this.code_time + "s后获取";
        setTimeout(this.code_timer, 1000);
      } else {
        this.code_time = 0;
        this.code_txt = "获取验证码";
        this.code_disabled = false;
      }
    }
  },
  components: {}
};
</script>

<style scoped>
.cell-group {
  text-align: left;
}

.foot {
  position: fixed;
  width: 100%;
  display: flex;
  bottom: 0;
}

.btn-confirm {
  color: #1989fa;
}

.foot .btn {
  flex: 1;
}
</style>