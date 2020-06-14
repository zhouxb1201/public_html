<template>
  <van-cell>
    <div class="item">
      <div class="info">
        <div class="img">
          <img :src="item.user_headimg" />
        </div>
        <div class="name">{{item.user_name}}</div>
      </div>
      <div class="detail">
        <div class="detail-text">
          <div>
            还差
            <span class="num">{{item.group_num - item.now_num}}人</span>拼成
          </div>
          <div class="time-box">
            剩余
            <CountDown :time="item.finish_time * 1000" @callback="callback" done-text="00:00:00">
              <div class="time-end">
                <span>{%d}</span>
                <i>:</i>
                <span>{%h}</span>
                <i>:</i>
                <span>{%m}</span>
                <i>:</i>
                <span>{%s}</span>
              </div>
            </CountDown>
          </div>
        </div>
        <van-button class="detail-btn" type="danger" round size="small" @click="click">去拼单</van-button>
      </div>
    </div>
  </van-cell>
</template>

<script>
import CountDown from "@/components/CountDown";
export default {
  data() {
    return {};
  },
  props: {
    item: {
      type: Object
    }
  },
  methods: {
    callback() {
      this.$emit("callback", this.item.record_id);
    },
    click() {
      this.$emit("show-detail", this.item.record_id);
    }
  },
  components: {
    CountDown
  }
};
</script>
<style scoped>
.item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info {
  display: flex;
  align-items: center;
  flex: 1;
}

.info .img {
  width: 50px;
  height: 50px;
  overflow: hidden;
  border-radius: 50%;
  margin-right: 10px;
}

.info .img img {
  width: 100%;
  height: 100%;
  display: block;
}

.item .info .name {
  max-width: 50%;
  line-height: 1.2;
  max-height: 32px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.item .detail {
  display: flex;
  align-items: center;
  flex: 1;
  justify-content: flex-end;
}

.item .detail .detail-text {
  text-align: right;
  line-height: 1.2;
  padding-right: 10px;
  font-size: 12px;
}

.item .detail .detail-text .num {
  color: #ff454e;
  padding: 0 4px;
}

.time-box {
  white-space: nowrap;
  display: flex;
}

.time-end {
  padding-left: 4px;
}

.time-end i {
  font-style: normal;
}
</style>
