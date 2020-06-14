<template>
  <van-field
    label="短信验证码"
    v-model="code"
    type="number"
    maxlength="6"
    :left-icon="showLeftIcon?'v-icon-validate':''"
    placeholder="请输入验证码"
  >
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
import { validMobile } from "@/utils/validator";
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
    mobile: [String, Number],
    showLeftIcon: {
      type: [String, Boolean],
      default: true
    },
    type: String
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
      let mobile = $this.mobile;
      if (!validMobile(mobile)) {
        return false;
      }
      $this.$store
        .dispatch("getMsgcode", {
          type: this.type,
          form: { mobile }
        })
        .then(res => {
          $this.$emit("send-success");
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
