<template>
  <div class="tips-wrap" v-if="info.levelname">
    <h4>
      降级
      <span class="cr-red">{{info.levelname}}</span>条件
      <span
        class="h4-text"
      >（{{info.starttime}}至{{info.endtime}}，{{info.upgrade_condition == 1 ? "满足所有" : "满足一条"}}）</span>
    </h4>
    <div v-for="(item,index) in info.result" :key="index">
      <span>{{index + 1}}、</span>
      <span v-if="item.condition_type == 1">
        <label>团队订单量在</label>
        <label class="cr-red">{{item.days}}</label>天内，少于
        <label class="cr-red">{{item.down_number}}</label>单
      </span>
      <span v-if="item.condition_type == 2">
        <label>团队订单金额在</label>
        <label class="cr-red">{{item.days}}</label>天内，少于
        <label
          class="cr-red"
        >{{parseFloat(item.down_number) ? parseFloat(item.down_number) : 0}}</label>元
      </span>
      <span v-if="item.condition_type == 3">
        <label class="cr-red">{{item.days}}</label>天内，需要消费
        <label
          class="cr-red"
        >{{parseFloat(item.down_number) ? parseFloat(item.down_number) : 0}}</label>元
      </span>
      <span
        class="cr-999"
      >（{{parseFloat(item.number) ? parseFloat(item.number) : 0}} / {{parseFloat(item.down_number) ? parseFloat(item.down_number) : 0}}）</span>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    info: [Array, Object]
  },
  computed: {}
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
.cr-999 {
  color: #999;
}
.cr-bule {
  color: #1989fa;
}
.h4-text {
  color: #999;
  font-size: 12px;
}
</style>