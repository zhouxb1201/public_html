<template>
  <div class="box" :class="action.dir" @click="onClick">
    <van-icon :name="action.icon"/>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    type: {
      type: String,
      default: "back"
    }
  },
  computed: {
    action() {
      let obj = {
        icon: "arrow-left",
        dir: "left"
      };
      if (this.type == "back") {
      } else if (this.type == "home") {
        obj = {
          icon: "v-icon-home",
          dir: "right"
        };
      } else if (this.type == "share") {
        obj = {
          icon: "share",
          dir: "right"
        };
      }
      return obj;
    }
  },
  methods: {
    onClick(link) {
      if (this.type == "back") {
        if (window.history.length <= 1) {
          this.$router.push("/");
          return false;
        } else {
          this.$router.back();
        }
      } else if (this.type == "home") {
        this.$router.push("/mall/index");
      }
      this.$emit("click");
    }
  }
};
</script>

<style scoped>
.box {
  border-radius: 100%;
  background: rgba(0, 0, 0, 0.3);
  width: 30px;
  height: 30px;
  position: fixed;
  z-index: 100;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  left: 15px;
  top: 15px;
  font-weight: 800;
}
.box.left {
  left: 15px;
  right: initial;
}
.box.right {
  left: initial;
  right: 15px;
}
.box:active {
  background: rgba(0, 0, 0, 0.6);
}
</style>
