<template>
  <div class="shortcut-entry" :class="showMenuClass" :style="{ bottom }">
    <div class="menu">
      <router-link
        tag="div"
        class="item"
        v-for="(item,index) in items"
        :key="index"
        :to="item.path"
      >
        <van-icon class="icon" :name="item.icon" />
        <span class="text">{{item.name}}</span>
      </router-link>
    </div>
    <div class="box" @click="show = !show">
      <van-icon :name="iconClass" />
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      show: false,
      items: [
        {
          icon: "wap-home",
          name: "首页",
          path: "/mall/index"
        },
        {
          icon: "apps-o",
          name: "分类",
          path: "/goods/category"
        },
        {
          icon: "cart-o",
          name: "购物车",
          path: "/mall/cart"
        },
        {
          icon: "contact",
          name: "我的",
          path: "/member/centre"
        }
      ]
    };
  },
  props: {
    bottom: String
  },
  watch: {
    "$route.name": function(e) {
      this.show = false;
    }
  },
  computed: {
    showMenuClass() {
      return this.show ? "show" : "";
    },
    iconClass() {
      return this.show ? "cross" : "apps-o";
    }
  }
};
</script>

<style scoped>
.shortcut-entry {
  position: fixed;
  background-color: #fff;
  color: #606266;
  z-index: 999;
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.12);
  right: 15px;
  bottom: 75px;
  border-radius: 20px;
  opacity: 0.7;
  transition: 0.3s;
}

.menu {
  display: flex;
  width: 40px;
  flex-direction: column;
}

.menu .item {
  width: 40px;
  height: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  transition: 0.2s ease-out;
}

.shortcut-entry.show {
  opacity: 1;
}

.shortcut-entry.show .menu .item {
  height: 40px;
}

.menu .item .icon {
  font-size: 18px;
}

.menu .item .text {
  font-size: 10px;
  line-height: 1.4;
}

.box {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.12);
  font-size: 24px;
  cursor: pointer;
}
</style>
