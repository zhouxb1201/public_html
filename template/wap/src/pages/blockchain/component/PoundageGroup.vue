<template>
  <van-cell-group class="cell-group">
    <van-cell :title="title" class="cell-panel">
      <div
        class="fs-12 text-right"
        :class="data.loadingFlag ? 'text-maintone' : 'text-secondary'"
      >{{poundageText}}</div>
    </van-cell>
    <van-cell>
      <div class="slider-box">
        <van-icon class="icon" name="v-icon-motorcycle" />
        <div class="slider">
          <van-slider class="slider-line" v-model="value" :min="1" :step="1" @change="slider" />
        </div>
        <van-icon class="icon" name="v-icon-car" />
      </div>
      <div class="text">
        <span class="text-maintone">{{value}} {{unit}}</span>
      </div>
      <div class="text">
        <span class="tip">{{tip}}</span>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
import { Slider } from "vant";
import { yuan, bi } from "@/utils/filter";
export default {
  data() {
    return {
      value: 1
    };
  },
  props: {
    data: Object,

    type: String,
    title: {
      type: String,
      default: "手续费"
    },
    unit: {
      type: String,
      default: "gwei"
    },
    tip: {
      type: String,
      default: "手续费由以太坊收取，费用越高处理速度越快手续费用由会员承当"
    }
  },
  computed: {
    poundageText() {
      const type = this.type.toUpperCase();
      const { gasFee, gasPrice, loadingFlag, loadingInitText } = this.data;

      if (!loadingFlag) {
        return loadingInitText || "获取手续费中...";
      }
      if (parseFloat(gasFee) >= 0 && parseFloat(gasPrice) >= 0) {
        return gasFee + " " + type + " ≈ ¥ " + gasPrice;
      }
    }
  },
  methods: {
    formatValue(e) {
      return e / 1;
    },
    slider(e) {
      this.$emit("change", e);
    }
  },
  components: {
    [Slider.name]: Slider
  }
};
</script>

<style scoped>
.cell-group {
  margin: 10px 0;
}

.slider-box {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 40px;
}

.slider-box .icon {
  font-size: 24px;
  color: #323233;
  width: 30px;
  height: 30px;
  line-height: 30px;
  text-align: center;
}

.slider-box .slider {
  flex: 1;
  height: 40px;
  display: flex;
  align-items: center;
  overflow: hidden;
  /* margin-left: 15px; */
  padding-left: 15px;
  padding-right: 15px;
}

.slider-line {
  flex: 1;
  /* margin-left: -18px; */
}

.btn-slider {
  border-radius: 50%;
}

.text {
  text-align: center;
}

.text .tip {
  font-size: 12px;
  color: #606266;
  display: inline-block;
  padding: 10px 30px;
  line-height: 1.4;
}
</style>
