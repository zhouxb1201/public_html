<template>
  <div class="preview">
    <component :is="'tpl_'+item.id" v-for="(item,index) in items" :key="index" :item="item" />
    <Copyright v-if="copyright && (pageType == 1 || pageType == 4)" :copyright="copyright" />
    <nav class="tabbar" v-if="pageType == 1 || pageType == 4">
      <van-tabbar v-model="$store.state.tabbar.activeTabbar" :z-index="999">
        <van-tabbar-item v-for="(item,index) in tabbar" :to="item.path" :key="index">
          <img
            slot="icon"
            slot-scope="props"
            :src="(props.active ? item.active : item.normal) | BASESRC"
          />
          <span>{{item.text}}</span>
        </van-tabbar-item>
      </van-tabbar>
    </nav>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { Tabbar, TabbarItem } from "vant";
import { preview } from "../template";
import Copyright from "@/components/Copyright";
export default sfc({
  name: "preview",
  data() {
    return {};
  },
  computed: {
    filterData() {
      let data = document.getElementById("app").getAttribute("data-custom");

      // 需要默认数据的预览组件
      const arr = [
        "goods",
        "shop",
        "seckill",
        "seckill",
        "member_fixed",
        "member_assets_fixed",
        "member_order_fixed",
        "shop_head",
        "detail_fixed",
        "commission_fixed"
      ];
      let newData = {
        page: {},
        tabbar: {
          data: {}
        },
        items: {},
        copyright: ""
      };
      if (data) {
        newData = JSON.parse(data);
      }
      for (let i in newData.items) {
        if (arr.indexOf(newData.items[i].id) != -1) {
          newData.items[i].id = newData.items[i].id + "_preview";
        }
      }
      console.log(newData);
      return newData;
    },
    pageType() {
      return this.filterData.page.type ? this.filterData.page.type : 1;
    },
    items() {
      return this.filterData.items;
    },
    copyright() {
      return this.filterData.copyright;
    },
    tabbar() {
      return this.filterData.tabbar.data;
    }
  },
  components: {
    ...preview,
    Copyright,
    [Tabbar.name]: Tabbar,
    [TabbarItem.name]: TabbarItem
  }
});
</script>

<style scoped>
.preview {
  position: relative;
}

.preview::after {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  z-index: 1000;
}

.tabbar {
  height: 50px;
}

.van-tabbar-item--active {
  color: #666;
}
</style>
