<template>
  <div>
    <van-cell-group class="cell-group">
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
    <div class="foot">
      <van-button class="btn" size="normal" square @click="cancel">取消</van-button>
      <van-button class="btn btn-confirm" size="normal" square @click="onSave">确定</van-button>
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
    cancel: Function
  },
  methods: {
    onClick(action) {
      this.action = action;
    },
    onSave() {
      const $this = this;
      if (
        !validPaymentPassword($this.payment_password) ||
        !validCheckPassword($this.payment_password, $this.check_password)
      ) {
        return false;
      }
      UPDATE_PAYMENTPASSWORD($this.payment_password)
        .then(res => {
          if (res.code === 0) {
            $this.$Toast(res.message);
            $this.$emit("fail");
          } else {
            $this.$Toast.success("设置成功");
            $this.$emit("success");
            $this.check_password = "";
            $this.payment_password = "";
          }
        })
        .catch(() => {
          $this.$emit("fail");
        });
    }
  }
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
