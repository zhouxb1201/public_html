<template>
  <van-cell-group>
    <van-cell>
      <div class="text">
        <div>
          <span class="name">CPU</span>
          <span class="explain">{{info.cpuUsed}} ms / {{info.cpuMax}} ms</span>
        </div>
        <div>
          <span class="name">{{info.totalCpu}} EOS</span>
        </div>
      </div>
      <div>
        <van-progress class="progress" :percentage="info.cpuRatio" :show-pivot="false" />
      </div>
    </van-cell>
    <van-cell>
      <div class="text">
        <div>
          <span class="name">NET</span>
          <span class="explain">{{info.netUsed}} kb / {{info.netMax}} kb</span>
        </div>
        <div>
          <span class="name">{{info.totalNet}} EOS</span>
        </div>
      </div>
      <div>
        <van-progress class="progress" :percentage="info.netRatio" :show-pivot="false" />
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
import { Progress } from "vant";
export default {
  props: {
    info: Object
  },
  components: {
    [Progress.name]: Progress
  }
};
</script>

<style scoped>
.text {
  display: flex;
  justify-content: space-between;
}

.text .explain {
  font-size: 12px;
  color: #909399;
  padding: 0 4px;
}

.progress {
  height: 10px;
}
</style>

