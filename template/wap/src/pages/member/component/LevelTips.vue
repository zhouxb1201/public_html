<template>
  <div class="tips-wrap">
    <h4>如何获得成长值？</h4>
    <div>
      1、单次购物满
      <span class="cr-red">{{parseFloat(info.order_money)}}</span>元增加
      <span class="cr-red">{{info.pay_num}}</span>点成长值。
    </div>
    <div>
      2、充值
      <span class="cr-red">{{parseFloat(info.recharge_money)}}</span>元增加
      <span class="cr-red">{{info.recharge_num}}</span>点成长值。
    </div>
    <div>
      3、订单完成增加
      <span class="cr-red">{{info.complete_num}}</span>点成长值。
    </div>
    <div>4、参与商城不定期的活动也可以获得额外的成长值奖励。</div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    info: [Array, Object]
  }
};
</script>

<style scoped>
.tips-wrap {
  position: relative;
  padding: 10px;
  font-size: 12px;
  color: #333333;
  line-height: 22px;
  background-color: #ffffff;
  margin-top: 10px;
}
.tips-wrap h4 {
  font-weight: normal;
  font-size: 14px;
}
.cr-red {
  color: #eb3c3c;
}
</style>