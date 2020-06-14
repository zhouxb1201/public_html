<template>
  <van-circle
    v-model="currentRate"
    size="50px"
    color="#ff454e"
    layer-color="#e8c7c9"
    :stroke-width="60"
    fill="#fff"
    :rate="rate"
    class="receive-rate"
  >
    <div class="rate-text" :class="isDisabled?'disabled':''" v-html="rateTextHtml"/>
  </van-circle>
</template>

<script>
import { Circle } from "vant";
export default {
  data() {
    return {
      currentRate: 0
    };
  },
  props: {
    items: Object,
    rate: [Number, String],
    isDisabled: Boolean
  },
  computed: {
    rateTextHtml() {
      const count = parseInt(this.items.count);
      const rate = this.currentRate;
      const rateText = rate.toFixed(0) + "%";
      let textHtml = "";
      if (rate >= 100) {
        textHtml = "<span>已抢光</span>";
      } else {
        textHtml = `<span>已抢</span><span>${rateText}</span>`;
      }
      return count > 0 ? textHtml : "<span>无限制</span>";
    }
  },
  components: {
    [Circle.name]: Circle
  }
};
</script>
<style scoped>
.receive-rate {
  margin: 0 auto;
}

.rate-text {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  font-size: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  color: #ff454e;
}

.rate-text.disabled {
  color: #999;
}
</style>
