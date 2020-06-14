<template>
  <van-popup v-model="value" :close-on-click-overlay="false" class="popup" @click-overlay="onClose">
    <div class="popup-box">
      <div class="title">
        参与
        <span>{{item.user_name}}</span>的拼单
      </div>
      <div class="tips">
        仅剩
        <span class="num">{{item.group_num - item.now_num}}</span>个名额，
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
        </CountDown>后结束
      </div>
      <div class="img-box">
        <div class="img">
          <img :src="item.user_headimg" />
          <van-tag round type="danger">团长</van-tag>
        </div>
        <div class="help">?</div>
      </div>
      <div class="btn">
        <van-button type="danger" size="small" round block @click="onJoin">参与拼单</van-button>
      </div>
    </div>
  </van-popup>
</template>

<script>
import CountDown from "@/components/CountDown";
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    item: {
      type: Object
    }
  },
  methods: {
    callback() {
      this.$emit("callback", this.item.record_id);
    },
    onJoin() {
      this.$emit("confirm", this.item.record_id);
    },
    onClose() {
      this.$emit("input", false);
    }
  },
  components: {
    CountDown
  }
};
</script>
<style scoped>
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

.popup {
  width: 80%;
  border-radius: 5px;
}

.popup-box {
  padding: 20px;
  text-align: center;
  line-height: 1.6;
}

.popup-box .title {
  margin-bottom: 10px;
  font-size: 16px;
}

.popup-box .tips {
  font-size: 12px;
  color: #606266;
  display: flex;
  justify-content: center;
}

.popup-box .tips .num {
  color: #ff454e;
}

.popup-box .img-box {
  display: flex;
  justify-content: center;
  margin: 20px 0;
}

.popup-box .img-box .img {
  position: relative;
  width: 50px;
  height: 50px;
  margin-right: 10px;
}

.popup-box .img-box .img img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: block;
}

.popup-box .img-box .img span {
  position: absolute;
  right: 0;
  top: 0;
}

.popup-box .img-box .help {
  display: block;
  width: 50px;
  height: 50px;
  line-height: 50px;
  font-size: 30px;
  color: #999;
  border-radius: 50%;
  border: 1px dashed #999;
  margin-left: 10px;
}
</style>
