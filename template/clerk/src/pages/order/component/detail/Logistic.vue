<template>
  <van-cell-group v-if="(status === 2 || status === 3 || status === 4) && info.mailNo">
    <van-cell
      icon="v-icon-logistic6"
      class="nowrap"
      :title="info.expTextName+' '+info.mailNo"
      is-link
      value="更多"
      :to="{name:'order-logistics',params:{orderid}}"
    />
    <van-cell v-if="info.newestInfo">
      <div class="text">{{info.newestInfo.context}}</div>
      <div class="fs-12 text-regular">{{info.newestInfo.time}}</div>
    </van-cell>
  </van-cell-group>
  <van-cell-group v-else>
    <van-cell>
      <div class="empty">暂无物流信息</div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    status: {
      type: [Number, String]
    },
    orderid: {
      type: [Number, String]
    },
    info: {
      type: Object
    }
  }
};
</script>

<style scoped>
.nowrap {
  white-space: nowrap;
}

.text {
  color: chocolate;
  line-height: 1.4;
  margin-bottom: 4px;
}
</style>
