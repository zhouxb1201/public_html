<template>
  <van-cell-group class="cell-group">
    <van-cell center v-if="info.refundStatus">
      <div slot="title" class="title-clock">
        <div>正在赎回</div>
        <div class="clock text-secondary">
          <van-icon name="clock-o" />
          <span>{{timeText}}</span>
        </div>
      </div>
      <div class="text-regular">{{info.refundTotal}}</div>
    </van-cell>
    <van-cell>
      <div slot="title">
        <div>RAM</div>
        <div class="fs-12 text-secondary">1 kb = {{info.netPrice}} {{typeToUpperCase}}</div>
      </div>
      <div>
        <div class="text-regular">{{info.totalRawToEos}} {{typeToUpperCase}}</div>
        <div class="fs-12 text-secondary">{{info.quotaRam}} kb</div>
      </div>
    </van-cell>
    <van-cell is-link center to="/blockchain/resource">
      <div slot="title">
        <div>已抵押资源</div>
        <div class="fs-12 text-secondary">CPU + NET</div>
      </div>
      <div>
        <div class="text-regular">{{info.payEos}} {{typeToUpperCase}}</div>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
import { getServerTime } from "@/utils/util";
export default {
  data() {
    return {
      timeText: "",
      typeToUpperCase: this.type.toUpperCase()
    };
  },
  props: {
    type: String
  },
  computed: {
    info() {
      return this.$store.state.blockchain[this.type] || {};
    }
  },
  created() {
    this.info.refundStatus && this.countDownTime(this.info.refundExpectTime);
  },
  methods: {
    countDownTime(time) {
      if (!time) return;
      getServerTime().then(serverTime => {
        var setTime = new Date(time);
        var restSec = setTime.getTime() - serverTime.getTime();
        var day = parseInt(restSec / (60 * 60 * 24 * 1000));
        var hour = parseInt((restSec / (60 * 60 * 1000)) % 24);
        var minu = parseInt((restSec / (60 * 1000)) % 60);
        var sec = parseInt((restSec / 1000) % 60);
        this.timeText = day + "天" + hour + "时" + minu + "分";
      });
    }
  }
};
</script>

<style scoped>
.cell-group {
  margin: 10px 0;
}

.title-clock {
  display: flex;
  align-items: center;
}

.title-clock .clock {
  display: flex;
  align-items: center;
  margin-left: 8px;
}
.title-clock .clock span {
  padding: 0 4px;
}
</style>
