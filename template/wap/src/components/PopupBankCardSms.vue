<template>
  <van-popup v-model="value" :close-on-click-overlay="false" class="popup-bank-card-sms">
    <van-cell-group class="cell-group">
      <van-cell>
        <div class="title">{{title}}</div>
        <van-icon name="close" size="16px" class="btn-close" @click="close" />
      </van-cell>
      <van-field
        label="手机号码"
        type="number"
        maxlength="11"
        placeholder="请输入您的手机号码"
        v-model="params.mobile"
        readonly
      />
      <van-field
        label="验证码"
        type="number"
        maxlength="6"
        placeholder="请输入验证码"
        v-model="params.smscode"
      >
        <van-button
          slot="button"
          size="mini"
          plain
          class="btn-code"
          type="danger"
          :disabled="isDisabled"
          @click="agentSend"
        >{{codeTxt}}</van-button>
      </van-field>
      <van-cell>
        <van-button size="normal" round type="danger" block :loading="isLoading" @click="confirm">确定</van-button>
      </van-cell>
    </van-cell-group>
  </van-popup>
</template>

<script>
import { validMobile, validMsgcode } from "@/utils/validator";
import { focusout } from "@/mixins";

export default {
  data() {
    return {
      isLoading: false,

      isDisabled: false,
      codeTime: 0,
      codeTxt: "获取验证码"
    };
  },
  props: {
    type: {
      type: String,
      required: true
    },
    value: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: "银行卡安全验证"
    },
    params: Object
  },
  watch: {
    value(e) {
      if (e) {
        this.startTimer();
      }
    }
  },
  mixins: [focusout],
  methods: {
    close() {
      this.$emit("input", false);
      this.$emit("close");
    },
    // 重新获取
    agentSend() {
      let params = {};
      let method = "";
      if (this.type == "signing") {
        method = "getSigningSms";
        params = this.params;
      } else if (this.type == "pay") {
        method = "getBankPaySms";
        params = {
          out_trade_no: this.params.out_trade_no,
          thpinfo: this.params.thpinfo
        };
      }
      this.$store.dispatch(method, params).then(() => {
        this.startTimer();
      });
    },
    // 签约/支付
    confirm() {
      let method = "";
      if (this.type == "signing") {
        method = "signingBankCard";
      } else if (this.type == "pay") {
        method = "payBankCard";
      }
      if (!validMsgcode(this.params.smscode)) {
        return false;
      }
      this.isLoading = true;
      this.$store
        .dispatch(method, this.params)
        .then(() => {
          this.isLoading = false;
          this.$emit("input", false);
          this.$emit("success");
        })
        .catch(() => {
          this.isLoading = false;
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
      this.codeTxt = "重新获取";
    }
  },
  components: {}
};
</script>

<style scoped>
.popup-bank-card-sms {
  width: 80%;
  border-radius: 10px;
}

.title {
  font-weight: 800;
  text-align: center;
  position: relative;
}

.text-agree {
  color: #909399;
  font-size: 12px;
  display: flex;
}

.btn-close {
  position: absolute;
  right: 0;
  top: 0;
}
</style>
