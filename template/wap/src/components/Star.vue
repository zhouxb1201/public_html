<template>
  <div>
    <div class="star-box">
      <div class="star">
        <van-icon
          :size="size+'px'"
          :name="'v-icon-rate-'+item"
          v-for="(item,index) in list"
          :key="index"
          class="icon"
          :color="disabled ? disabledColor : item !== 'off' ? color : voidColor"
        />
      </div>
      <span class="num" v-if="showFraction&&value">{{value}}{{unit}}</span>
    </div>
    <slot />
  </div>
</template>

<script>
const CLS_ON = "on"; //满星状态
const CLS_HALF = "half"; //半星状态
const CLS_OFF = "off"; //无星状态

export default {
  props: {
    value: {
      type: [Number, String],
      default: 0
    },
    disabled: Boolean,
    size: {
      type: Number,
      default: 14
    },
    color: {
      type: String,
      default: "#ffd21e"
    },
    voidColor: {
      type: String,
      default: "#c7c7c7"
    },
    disabledColor: {
      type: String,
      default: "#bdbdbd"
    },
    count: {
      type: Number,
      default: 5
    },
    unit: {
      type: String,
      default: "分"
    },
    showFraction: {
      type: Boolean,
      default: true
    }
  },
  computed: {
    list() {
      let result = [];
      let score = Math.floor(parseFloat(this.value) * 2) / 2;
      let hasDecimal = score % 1 !== 0;
      let integer = Math.floor(score);
      for (let i = 0; i < integer; i++) {
        result.push(CLS_ON);
      }
      if (hasDecimal) {
        result.push(CLS_HALF);
      }
      while (result.length < this.count) {
        result.push(CLS_OFF);
      }
      return result;
    }
  }
};
</script>

<style scope>
.star-box {
  display: flex;
}

.icon {
  padding: 0 2px;
}

.num {
  font-size: 10px;
  padding: 0 4px;
  color: #666;
}
</style>
