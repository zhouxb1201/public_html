<template>
  <div @touchmove.prevent>
    <transition name="van-fade">
      <div class="mask" v-show="value" @click="hide"></div>
    </transition>
    <transition
      name="custom-classes-transition"
      enter-active-class="animated zoomIn"
      leave-active-class="animated zoomOut"
    >
      <div class="dialog" v-show="value">
        <slot>
          <div class="box" @click="toLink">
            <img :src="imgSrc" />
          </div>
          <div class="close">
            <van-icon name="close" class="close-btn" @click="close" />
          </div>
        </slot>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    // 弹窗是否可见
    value: {
      type: Boolean,
      default: false
    },
    // 是否在点击遮罩时自动关闭弹窗
    hideOnBlur: Boolean,
    imgSrc: String,
    link: {
      type: [String, Object]
    }
  },
  methods: {
    close() {
      this.$emit("input", false);
      this.$emit("close");
    },
    hide() {
      if (this.hideOnBlur) {
        this.$emit("input", false);
      }
    },
    toLink() {
      if (this.link) {
        this.$router.push(this.link);
      }
    }
  }
};
</script>

<style scoped>
@import url("../../static/css/animate.min.css");
.animated {
  animation-duration: 0.8s;
}

.mask {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  z-index: 3000;
}

.dialog {
  position: fixed;
  display: table;
  z-index: 5000;
  width: 80%;
  max-width: 300px;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  margin: auto;
  text-align: center;
  border-radius: 3px;
  overflow: hidden;
}

.close {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 20px;
}

.close-btn {
  padding: 4px;
  font-size: 22px;
  color: #fff;
  display: block;
  width: 30px;
  height: 30px;
  border-radius: 50%;
}

.box {
  width: 100%;
  max-height: 380px;
  overflow: hidden;
  border-radius: 4px;
}

.box img {
  display: block;
  max-width: 100%;
  max-height: 100%;
  margin: 0 auto;
}
</style>
