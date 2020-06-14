<template>
  <div>
    <van-row type="flex" justify="center" class="box">
      <van-col span="8">
        <div class="img">
          <img :src="$BASEIMGPATH+'result-'+stateType+'.png'">
        </div>
        <div class="message text-center">{{text}}</div>
      </van-col>
    </van-row>
    <slot/>
  </div>
</template>

<script>
export default {
  props: {
    /**
     * 状态类型
     * pay-success      ==>  支付成功
     * pay-fail         ==>  支付失败
     * recharge-success ==>  充值成功
     * recharge-fail    ==>  充值失败
     * refund-success   ==>  同意退款
     * refund-fail      ==>  拒绝退款
     * refund-finish    ==>  完成退款
     */
    stateType: String,
    message: String
  },
  computed: {
    text() {
      let text = "";
      if (!this.message) {
        switch (this.stateType) {
          case "pay-success":
            text = "支付成功";
            break;
          case "pay-fail":
            text = "支付失败";
            break;
          case "recharge-success":
            text = "充值成功";
            break;
          case "recharge-fail":
            text = "充值失败";
            break;
          case "refund-success":
            text = "同意退款";
            break;
          case "refund-fail":
            text = "拒绝退款";
            break;
          case "refund-finish":
            text = "完成退款";
            break;
        }
      } else {
        text = this.message;
      }
      return text;
    }
  }
};
</script>

<style scoped>
.box {
  height: 100px;
  align-items: center;
  background: #ff454e;
  color: #fff;
}

.img {
  width: 60%;
  text-align: center;
  margin: 5px auto;
}

.img img {
  width: 100%;
  height: auto;
  display: block;
}
</style>

