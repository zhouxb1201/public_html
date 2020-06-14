<template>
  <van-cell>
    <div class="presell-info">
      <div class="front-item">
        <span class="sort">1</span>
        <div class="money">
          <span>定金</span>
          {{info.frontMoney | yuan}}
        </div>
      </div>
      <div>
        <span class="sort">2</span>
        <div class="money">
          <span>尾款{{info.tailText}}</span>
          {{info.tailMoney | yuan}}
          <span class="time">{{info.pay_start_time | formatDate}}</span>开始支付
        </div>
      </div>
    </div>
  </van-cell>
</template>

<script>
export default {
  props: {
    info: Object
  }
};
</script>


<style scoped>
.presell-info > div {
  display: flex;
  align-items: center;
  color: #909399;
}

.presell-info > div .sort {
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  background: #909399;
  width: 20px;
  height: 20px;
  margin-right: 6px;
}

.presell-info .front-item {
  color: #ff454e;
}

.presell-info .front-item .sort {
  background: #ff454e;
}

.presell-info > div .money,
.presell-info > div .time {
  padding: 0 4px;
}
</style>
