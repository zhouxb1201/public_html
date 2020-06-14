<template>
  <van-popup
    v-model="show"
    class="popup"
    :class="isFullScreen"
    position="bottom"
    :close-on-click-overlay="false"
    @click-overlay="close"
  >
    <div>
      <slot name="title">
        <div class="van-hairline--top-bottom van-actionsheet__header" v-if="title">
          <div>{{title}}</div>
          <van-icon name="close" @click="close" />
        </div>
      </slot>
      <div class="popup-content" :style="[contentStyle]">
        <slot />
      </div>
      <slot name="footer">
        <div class="fixed-foot-btn-group" v-if="showFootClose">
          <van-button size="normal" block round type="default" @click="close">关闭</van-button>
        </div>
      </slot>
    </div>
  </van-popup>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    title: String,
    contentHeight: String, //内容高度
    fullScreen: {
      //是否满屏
      type: Boolean,
      default: false
    },
    showFootClose: {
      //显示底部关闭按钮
      type: Boolean,
      default: false
    }
  },
  computed: {
    show: {
      get() {
        return this.value;
      },
      set(e) {
        this.$emit("input", e);
      }
    },
    contentStyle() {
      return {
        height: this.contentHeight,
        paddingBottom: this.showFootClose ? "70px" : ""
      };
    },
    isFullScreen() {
      return this.fullScreen ? "full-screen" : "";
    }
  },
  methods: {
    close() {
      this.$emit("input", false);
    }
  }
};
</script>

<style scoped>
.popup {
  background: #fff;
}

.popup-content {
  min-height: 200px;
  height: auto;
  max-height: 60vh;
  overflow-y: auto;
}

.full-screen {
  height: 100%;
  border-radius: 0;
}

.full-screen .popup-content {
  height: 100vh;
  max-height: 100vh;
}
</style>