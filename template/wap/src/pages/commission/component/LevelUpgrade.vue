<template>
  <div class="tips-wrap" v-if="info.levelname">
    <h4>
      升级
      <span class="cr-red">{{info.levelname}}</span>条件
      <span class="h4-text">（{{info.upgrade_condition == 1 ? "需满足以下所有条件" : "需满足以下其中一条"}}）</span>
    </h4>
    <div v-for="(item,index) in info.result" :key="index">
      <span>{{index + 1}}、</span>
      <span v-if="item.condition_type == 12">{{item.condition_name}}{{item.distributor_name}}满</span>
      <span v-else-if="item.condition_type == 6">
        <label>购买商品</label>
        <label class="cr-bule">{{item.condition_name}}</label>
      </span>
      <span v-else>{{item.condition_name}}</span>
      <span
        class="cr-red"
        v-if="item.condition_type !== 6"
      >{{parseFloat(item.up_number) ? parseFloat(item.up_number) : 0}}</span>
      <span>{{item.condition_type !== 6 ? item.unit : ''}}</span>
      <span
        class="cr-999"
      >（{{parseFloat(item.number) ? parseFloat(item.number) : 0}} / {{parseFloat(item.up_number) ? parseFloat(item.up_number) : 0}}）</span>
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