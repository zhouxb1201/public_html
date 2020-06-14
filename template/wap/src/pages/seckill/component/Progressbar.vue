<template>
  <div class="progress-outer">
    <div class="progress" :style="{background:bg}">
      <div
        class="progress-bar"
        :class="type && 'progress-bar' + type"
        role="progressbar"
        :aria-valuenow="value"
        :aria-valuemin="0"
        :aria-valuemax="max"
        :style="{width:(percent<100?percent:100)+'%',background:barBg}"
      ></div>
    </div>
    <div class="progress-text">
      <slot name="progress-text">
        <span>已抢 {{num}} 件</span>
        <span>{{valueText}}</span>
      </slot>
    </div>
  </div>
</template>
<script>
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Number,
      required: true
    },
    max: {
      type: Number,
      default: 100
    },
    type: {
      type: String,
      default: ""
    },
    num: {
      type: [String, Number],
      default: 0
    },
    bg: String,
    barBg: String
  },
  computed: {
    percent() {
      return parseInt((this.value * 100) / this.max, 10);
    },
    valueText() {
      return this.percent + "%";
    }
  }
};
</script>
<style scoped>
.progress-outer {
  width: 80%;
  height: 20px;
  position: relative;
  font-size: 12px;
}

.progress {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
  border-radius: 10px;
  background: rgba(255, 69, 78, 0.4);
}

.progress .progress-bar {
  position: absolute;
  width: 0;
  height: 100%;
  top: 0;
  left: 0;
  transition: width 0.6s ease;
  background: #ff454e;
  border-radius: 10px;
  background-size: cover !important;
}

.progress-text {
  position: absolute;
  z-index: 10;
  left: 0;
  top: 0;
  bottom: 0;
  right: 0;
  color: #ffffff;
  font-size: inherit;
  padding: 0 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
