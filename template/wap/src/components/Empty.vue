<template>
  <div class="empty" :style="{bottom:bottom+'px',top:top+'px'}">
    <div>
      <slot name="content">
        <div class="img">
          <img :src="imgPath" />
        </div>
      </slot>
      <div class="message" v-html="message"></div>
      <slot class="foot" name="footer" v-if="showFoot">
        <van-button size="small" round type="danger" class="btn" :text="btnText" @click="onClick" />
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
    pageType: {
      type: String
    },
    top: {
      type: [String, Number]
    },
    bottom: {
      type: [String, Number]
    },
    message: {
      type: String,
      default: "没有相关数据"
    },
    showFoot: {
      type: Boolean,
      default: true
    },
    btnText: {
      type: String
    },
    btnLink: {
      type: [String, Object]
    },
    event: {
      type: Boolean,
      default: false
    },
    diyImgPath: String
  },
  computed: {
    imgPath() {
      if (this.diyImgPath) {
        return this.diyImgPath;
      }
      const type = this.pageType;
      const base = this.$BASEIMGPATH;
      let path = "";
      if (type) {
        path = "empty-" + type + ".png";
        if (
          type === "integralgoods" ||
          type === "microshoppreview" ||
          type === "microshopchoose"
        ) {
          path = "empty-goods.png";
        }
      }
      return base + path;
    }
  },
  methods: {
    onClick() {
      if (this.event) {
        this.$emit("click");
      } else if (this.btnLink) {
        this.$router.push(this.btnLink);
      }
    }
  }
};
</script>

<style scoped>
.empty {
  background: #ffffff;
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty > div {
  width: 100%;
}

.empty .img {
  margin: 20px 0;
}

.empty .img img {
  max-width: 65%;
  display: block;
  margin: 0 auto;
  height: auto;
}

.empty .message {
  color: #606266;
  margin: 20px 0;
}

.empty .foot {
  display: flex;
  justify-content: center;
}

.btn {
  margin: 0 20px;
  width: 100px;
}
</style>
