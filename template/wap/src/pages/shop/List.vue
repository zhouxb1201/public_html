<template>
  <div class="shop-list bg-f8">
    <template v-if="$store.state.config.addons.shop">
      <HeadSearch class="shop-search-head" search-type="shop" />
      <TabSortScreen :set-params="setParams" />
      <List
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        :empty="{pageType:'shop',message:'没有相关店铺'}"
        @load="loadList"
      >
        <div class="list">
          <ShopPanelGroup v-for="(item,index) in list" :key="index" :items="item" />
        </div>
      </List>
    </template>
    <Empty v-else page-type="fail" message="未开启店铺应用" :show-foot="false" />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadSearch from "@/components/HeadSearch";
import TabSortScreen from "./component/TabSortScreen";
import ShopPanelGroup from "./component/ShopPanelGroup";
import { GET_SHOPLIST } from "@/api/shop";
import { list } from "@/mixins";
import Empty from "@/components/Empty";
export default sfc({
  name: "shop-list",
  data() {
    return {
      params: {
        page_index: 1,
        search_text: "",
        order: "",
        sort: "",
        shop_group_id: ""
      }
    };
  },
  mixins: [list],
  mounted() {
    this.$store.state.config.addons.shop && this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_SHOPLIST($this.params)
        .then(({ data }) => {
          let list = data.shop_list.map(e => {
            e.delivery_credit = Math.round(e.delivery_credit);
            e.description_credit = Math.round(e.description_credit);
            e.service_credit = Math.round(e.service_credit);
            return e;
          });
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    setParams(params) {
      this.params = params;
      this.loadList("init");
    }
  },
  components: {
    HeadSearch,
    TabSortScreen,
    ShopPanelGroup,
    Empty
  }
});
</script>

<style scoped>
.group-name {
  font-size: 12px;
  color: #909399;
  padding: 0 8px 0 4px;
}

.shop-search-head >>> .van-search {
  padding-right: 5px !important;
}
</style>
