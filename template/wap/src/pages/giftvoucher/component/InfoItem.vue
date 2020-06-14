<template>
  <div class="item">
    <div class="info">
      <div class="img">
        <img :src="items.pic_cover_mid" :onerror="$ERRORPIC.noSquare">
      </div>
      <div class="text">
        <div class="name">{{items.giftvoucher_name}}</div>
        <div class="time">{{items.start_time | formatDate}} ~ {{items.end_time | formatDate}}</div>
        <div class="detail">
          <slot name="foot"/>
        </div>
      </div>
    </div>
    <div class="icon-bg" v-if="items.state != 1">
      <van-icon :name="items.state == 2 ? 'v-icon-coupon-use' : 'v-icon-overdue'"/>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    items: Object
  }
};
</script>

<style scoped>
.item {
  display: flex;
  align-items: center;
  background: #ffffff;
  border-radius: 4px;
  margin: 15px;
  padding: 20px 15px;
  position: relative;
  overflow: hidden;
}

.item::after,
.item::before {
  content: "";
  display: block;
  position: absolute;
  width: 16px;
  height: 16px;
  background: #f8f8f8;
  border-radius: 50%;
  top: 50%;
  margin-top: -8px;
}

.item::after {
  right: -8px;
}

.item::before {
  left: -8px;
}

.item .info {
  flex: 1;
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  height: 50px;
}

.item .info .img {
  width: 60px;
  height: 60px;
  margin-right: 10px;
}

.item .info .img img {
  width: 100%;
  height: 100%;
}

.item .info .text {
  flex: 1;
}

.item .info .text .name {
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  margin-bottom: 8px;
  line-height: 1.4;
}

.item .info .text .time {
  font-size: 12px;
  color: #909399;
}

.item .info .text .detail {
  color: #666;
}

.item .icon-bg {
  position: absolute;
  z-index: 9;
  right: 10px;
  font-size: 50px;
}

.item .icon-bg >>> .van-icon {
  display: block;
}
</style>
