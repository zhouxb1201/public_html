<template>
  <div>
    <van-cell-group>
      <van-field
        label="支付密码"
        placeholder="请输入支付密码"
        type="password"
        v-model="payment_password"
        @click="onClick(1)"
      />
      <van-field
        label="确认密码"
        placeholder="再次输入新密码"
        type="password"
        v-model="check_password"
        @click="onClick(2)"
      />
    </van-cell-group>
    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onSave">完成</van-button>
    </div>
  </div>
</template>

<script>
import { UPDATE_PAYMENTPASSWORD } from "@/api/member";
import { isEmpty } from "@/utils/util";
import { validPaymentPassword, validCheckPassword } from "@/utils/validator";
export default {
  data() {
    return {
      action: 0,

      check_password: "",
      payment_password: ""
    };
  },
  props: {
    pageType: {
      type: [Number, String]
    }
  },
  methods: {
    onClick(action) {
      const $this = this;
      $this.action = action;
    },
    onSave() {
      const $this = this;
      if (
        !validPaymentPassword($this.payment_password) ||
        !validCheckPassword($this.payment_password, $this.check_password)
      ) {
        return false;
      }
      UPDATE_PAYMENTPASSWORD($this.payment_password).then(res => {
        if (res.code === 0) {
          $this.$Toast(res.message);
          setTimeout(() => {
            $this.$parent.isValid = true;
          }, 500);
        } else {
          $this.$Toast.success("修改成功");
          setTimeout(() => {
            $this.$router.replace("/member/centre");
          }, 500);
        }
      });
    }
  }
};
</script>

<style scoped>
</style>
