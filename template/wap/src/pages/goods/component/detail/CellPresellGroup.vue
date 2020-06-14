<template>
  <div>
    <van-cell is-link @click="show=true">
      <div slot="icon" class="title" :style="{color:titleColor}">预售</div>
      <div class="value">
        <div class="value-title">每人限购{{info.max_buy}}件</div>
        <div>尾款时间：{{info.pay_start_time | formatDate('YYYY-mm-dd')}} ~ {{info.pay_end_time | formatDate('YYYY-mm-dd')}}</div>
        <div>发货时间：{{info.pay_end_time | formatDate('YYYY-mm-dd')}}</div>
      </div>
    </van-cell>
    <div>
      <PopupBottom v-model="show" title="预售规则">
        <van-cell-group class="list">
          <van-cell class="item" v-for="(item,index) in list" :key="index">
            <div class="title">{{item.title}}</div>
            <div class="name">{{item.value}}</div>
          </van-cell>
        </van-cell-group>
      </PopupBottom>
    </div>
  </div>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
import { formatDate } from "@/utils/util";
import { yuan } from "@/utils/filter";
export default {
  data() {
    return {
      show: false
    };
  },
  props: {
    titleColor: {
      type: String,
      default: "#606266"
    },
    info: Object
  },
  computed: {
    list() {
      const info = this.info;
      let list = [
        {
          title: "定金",
          value: `定金支付：${formatDate(info.start_time,'YYYY-mm-dd')}至${formatDate(
            info.end_time,'YYYY-mm-dd'
          )}支付定金${yuan(info.firstmoney)}，下单后请在${
            info.pay_limit_time
          }分钟内支付，超时将自动取消订单。`
        },
        {
          title: "尾款",
          value: `尾款支付：${formatDate(info.pay_start_time,'YYYY-mm-dd')}至${formatDate(
            info.pay_end_time,'YYYY-mm-dd'
          )}支付尾款，超时订单关闭，且定金不予退还。`
        },
        {
          title: "发货",
          value: `发货时间：${formatDate(info.send_goods_time,'YYYY-mm-dd')}开始发货。`
        }
      ];
      return list;
    }
  },
  components: {
    PopupBottom
  }
};
</script>

<style scoped>
.title {
  width: 50px;
  color: #606266;
}

.value {
  display: flex;
  flex-flow: column;
  color: #606266;
  font-size: 12px;
  line-height: 1.4;
}

.value-title {
  line-height: 24px;
}

.item .title {
  width: auto;
  font-size: 14px;
  color: #ff454e;
}

.item .name {
  color: #606266;
  font-size: 12px;
  line-height: 1.4;
}
</style>