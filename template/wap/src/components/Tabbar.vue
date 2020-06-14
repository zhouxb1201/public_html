<template>
  <nav class="tabbar">
    <van-tabbar v-model="$store.state.tabbar.activeTabbar" :z-index="999">
      <van-tabbar-item v-for="(item,index) in tabbar" :to="item.path" :key="index">
        <img
          slot="icon"
          slot-scope="props"
          :src="(props.active ? item.active : item.normal) | BASESRC"
        >
        <span>{{item.text}}</span>
      </van-tabbar-item>
    </van-tabbar>
  </nav>
</template>

<script>
import { Tabbar, TabbarItem } from "vant";
export default {
  name: "tabbar",
  data() {
    return {};
  },
  computed: {
    tabbar() {
      return this.$store.state.tabbar.data;
    }
  },
  components: {
    [Tabbar.name]: Tabbar,
    [TabbarItem.name]: TabbarItem
  }
};
</script>

<style scoped>
.van-tabbar-item--active {
  color: #666;
}
</style>
