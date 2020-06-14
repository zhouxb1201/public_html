<template>
  <transition name="van-fade">
    <div class="floater" v-show="showFloater">
      <ShortcutEntry v-show="isShowShortcutEntry" :bottom="bottom" />
      <BackTop v-if="isShowBackTop" :on-show="onShow" />
    </div>
  </transition>
</template>

<script>
import ShortcutEntry from "./ShortcutEntry";
import BackTop from "./BackTop";
import { pullDir } from "@/mixins";
export default {
  data() {
    return {
      showFloater: true,
      // 需要显示返回顶部按钮的路由
      showBackTopRoute: [
        "index",
        "diy-page",
        "goods-list",
        "goods-detail",
        "integral-index",
        "integral-goods-list",
        "integral-goods-detail",
        "shop-list",
        "shop-home",
        "seckill-list",
        "assemble-list",
        "order-list",
        "commission-order",
        "bonus-order",
        "channel-order-list"
      ],
      // 不需要显示快捷入口的路由
      noShowShortcutEntryRoute: [
        "author",
        "nowechat",
        "login",
        "register",
        "forget",
        "unopened",
        "order-confirm",
        "pay-payment",
        "pay-result",
        "channel-order-confirm",
        "integral-order-confirm",
        "microshop-confirmorder",
        "prize-confirm"
      ],
      bottom: "75px"
    };
  },
  mixins: [pullDir],
  computed: {
    isShowShortcutEntry() {
      const routeName = this.$route.name;
      let flag = false;
      if (
        routeName &&
        this.$store.state.isWeixin &&
        !this.$store.state.tabbar.isShowTabbar
      ) {
        flag = !this.noShowShortcutEntryRoute.some(item => item == routeName);
      }
      if (flag) this.bottom = "75px";
      return flag;
    },
    isShowBackTop() {
      const routeName = this.$route.name;
      let flag = false;
      if (this.$route.name) {
        flag = this.showBackTopRoute.some(item => item == routeName);
      }
      if (flag) this.bottom = "75px";
      return flag;
    }
  },
  methods: {
    pullDir(dir, { p }) {
      // this.$Toast(dir + "," + p);
      // 滚动为0时显示
      this.showFloater = !p || dir == "down" ? true : false;
    },
    onShow(e) {
      this.bottom = e ? "130px" : "75px";
    }
  },
  components: {
    ShortcutEntry,
    BackTop
  }
};
</script>

<style scoped>
</style>
