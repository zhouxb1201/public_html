<template>
  <van-row type="flex" class="head" justify="space-between" v-if="status === 0">
    <van-col span="16">
      <div class="text pl-15">
        <div class="fs-16">等待买家付款</div>
        <div class="fs-12">逾期未付款，订单将自动取消</div>
      </div>
    </van-col>
    <van-col span="8">
      <div class="img">
        <img :src="$BASEIMGPATH+'order-detail-'+status+'.png'">
      </div>
    </van-col>
  </van-row>
  <van-row
    type="flex"
    class="head"
    justify="space-between"
    v-else-if="status === 1 || status === 2 || status === 3"
  >
    <van-col span="16">
      <van-steps :active="status-1" class="steps" active-color="#ffcc64">
        <van-step class="item">已付款</van-step>
        <van-step class="item">已发货</van-step>
        <van-step class="item">已签收</van-step>
      </van-steps>
    </van-col>
    <van-col span="8">
      <div class="img">
        <img :src="$BASEIMGPATH+'order-detail-'+status+'.png'">
      </div>
    </van-col>
  </van-row>
  <van-row type="flex" class="head" justify="center" v-else-if="status === 4 || status === 5">
    <van-col span="8">
      <div class="img">
        <img :src="$BASEIMGPATH+'order-detail-'+status+'.png'">
      </div>
      <div class="text text-center">{{status === 4 ? '交易完成' : '订单关闭'}}</div>
    </van-col>
  </van-row>
  <van-row type="flex" class="head" justify="center" v-else-if="status === -1">
    <van-col span="8">
      <div class="img">
        <img :src="$BASEIMGPATH+'order-detail-'+status+'.png'">
      </div>
      <div class="text text-center">售后中</div>
    </van-col>
  </van-row>
  <van-row type="flex" class="head" v-else></van-row>
</template>

<script>
import { Step, Steps } from "vant";
export default {
  data() {
    return {};
  },
  props: {
    status: {
      type: [Number, String]
    }
  },
  components: {
    [Step.name]: Step,
    [Steps.name]: Steps
  }
};
</script>

<style scoped>
.head {
  height: 100px;
  align-items: center;
  background: #ff454e;
  color: #fff;
}

.pl-15 {
  padding-left: 15px;
}

.text {
  line-height: 1.6;
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

.steps {
  background: none;
  padding: 0 15px;
}

.steps .item {
  color: #fff;
}

.steps >>> .van-steps__items {
  padding-bottom: 30px;
}

.steps .item >>> .van-step__circle-container {
  background: none;
}

.van-step >>> .van-step__circle {
  background: #fff;
}

.steps
  .item.van-step--horizontal.van-step--process
  >>> .van-step__circle-container {
  top: 21px;
}

.steps .item.van-step--horizontal.van-step--finish >>> .van-step__circle {
  background-color: #ffcc64;
}

.steps .item.van-step--horizontal.van-step--finish >>> .van-step__line {
  background-color: #ffcc64;
}

.van-step--horizontal:last-child {
  right: 1px;
}

.steps .item.van-step--horizontal.van-step--process >>> .van-icon {
  font-size: 18px;
  border-radius: 50%;
  background: #fff;
}
</style>
