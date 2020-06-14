<template>
  <div class="nav-bar" v-if="!$store.state.isWeixin">
    <van-nav-bar
      :title="changeTitle"
      :z-index="999"
      :left-arrow="isShowLeft"
      :left-text="leftText"
      fixed
      @click-left="onClickLeft"
      @click-right="showRightMenu = !showRightMenu"
    >
      <van-icon name="v-icon-menu1" slot="right" v-if="isMenu"/>
      <slot name="slotRight" slot="right" v-if="!isMenu"></slot>
    </van-nav-bar>
    <transition name="van-fade">
      <div class="nav-bar-menu" v-show="showRightMenu" v-if="isMenu">
        <router-link class="item" v-for="(item,index) in rightMenu" :key="index" :to="item.path">
          <van-icon :name="item.icon"/>
          {{item.name}}
        </router-link>
      </div>
    </transition>
  </div>
</template>

<script>
import { NavBar } from "vant";
export default {
  name: "nav-bar",
  data() {
    return {
      rightMenu: [
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
          icon: "cart",
          name: "购物车",
          path: "/mall/cart"
        },
        {
          icon: "contact",
          name: "会员中心",
          path: "/member/centre"
        }
      ],
      showRightMenu: false
    };
  },
  props: {
    title: {
      type: String
    },
    isMenu: {
      type: Boolean,
      default: true
    },
    isShowLeft: {
      type: Boolean,
      default: true
    },
    leftText: String
  },
  computed: {
    changeTitle() {
      let title = this.title ? this.title : this.$route.meta.title;
      if (title) document.title = title;
      return title;
    }
  },
  methods: {
    onClickLeft() {
      if (this.leftText) {
        this.$emit("click-left");
      } else {
        if (window.history.length <= 1) {
          this.$router.push("/");
          return false;
        } else {
          this.$router.back();
        }
      }
    }
  },
  deactivated() {
    this.showRightMenu = false;
  },
  destroyed() {
    this.showRightMenu = false;
  },
  components: {
    [NavBar.name]: NavBar
  }
};
</script>


<style scoped>
.nav-bar {
  height: 46px;
}

.van-nav-bar >>> .van-icon {
  color: #666666;
}
</style>
