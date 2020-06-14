<template>
  <van-field label="邮箱验证码" v-model="code" type="number" maxlength="6" placeholder="请输入邮箱验证码">
    <van-button
      slot="button"
      size="mini"
      plain
      class="btn-code"
      type="danger"
      :disabled="isDisabled"
      @click="onSend"
    >{{codeTxt}}</van-button>
  </van-field>
</template>

<script>
import store from "@/store";
import { validEmail } from "@/utils/validator";
export default {
  data() {
    return {
      isDisabled: false,
      codeTime: 0,
      codeTxt: "获取验证码"
    };
  },
  props: {
    value: [String, Number],
    email: [String, Number]
  },
  computed: {
    code: {
      get() {
        return this.value;
      },
      set(e) {
        this.$emit("input", e);
      }
    }
  },
  methods: {
    // 发送验证码
    onSend() {
      const $this = this;
      const email = $this.email;
      if (!validEmail(email)) {
        return false;
      }
      const params = {};
      params.email = email;
      params.type = "bind_email";

      $this.$store.dispatch("getEmailcode", params).then(res => {
        $this.startTimer();
      });
    },
    startTimer() {
      this.codeTime = 60;
      this.isDisabled = true;
      this.codeTimer();
    },
    codeTimer() {
      if (this.codeTime > 0) {
        this.codeTime--;
        this.codeTxt = this.codeTime + "s后获取";
        setTimeout(this.codeTimer, 1000);
      } else {
        this.endTimer();
      }
    },
    endTimer() {
      this.codeTime = 0;
      this.isDisabled = false;
      this.codeTxt = "获取验证码";
    }
  }
};
</script>
