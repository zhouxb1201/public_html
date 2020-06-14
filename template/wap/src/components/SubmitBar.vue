<template>
  <div class="van-submit-bar">
    <slot name="top"/>
    <div v-if="tip || $slots.tip" class="van-submit-bar__tip">
      {{ tip }}
      <slot name="tip"/>
    </div>
    <div class="van-submit-bar__bar">
      <slot/>
      <div class="van-submit-bar__text">
        <slot name="label" v-if="hasPrice">
          <span>{{ label }}</span>
          <span class="van-submit-bar__price">{{ price | yuan }}</span>
        </slot>
      </div>
      <van-button
        class="action-btn"
        square
        size="large"
        :type="buttonType"
        :disabled="disabled"
        :loading="loading"
        :loading-text="loadingText"
        @click="$emit('submit')"
      >{{ loading ? loadingText : buttonText }}</van-button>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    tip: String,
    price: Number,
    label: {
      type: String,
      default: "合计："
    },
    loading: Boolean,
    loadingText: String,
    disabled: Boolean,
    buttonText: String,
    currency: {
      type: String,
      default: "¥"
    },
    buttonType: {
      type: String,
      default: "danger"
    }
  },

  computed: {
    hasPrice() {
      return typeof this.price === "number";
    }
  },

  filters: {
    format(price) {
      return ((price ? price : 0) / 100).toFixed(2);
    }
  }
};
</script>
<style scoped>
@import url("vant/lib/submit-bar");
.action-btn {
  font-size: 14px;
  height: 40px;
  line-height: 38px;
  margin: 5px;
  border-radius: 40px;
}
</style>
